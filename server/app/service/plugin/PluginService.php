<?php
declare(strict_types=1);

namespace app\service\plugin;

use app\model\plugin\Plugin;
use app\repository\plugin\PluginRepository;
use core\base\Service;
use core\exception\BusinessException;
use core\license\LicenseGuard;
use core\license\MarketplaceEntitlement;
use core\marketplace\OfficialAccountSession;
use core\marketplace\OfficialCatalogClient;
use core\marketplace\PackageSignatureVerifier;
use core\plugin\PluginFrontendDeployer;
use core\plugin\PluginInstaller;
use core\plugin\PluginManager;
use core\plugin\PluginManifest;

class PluginService extends Service
{
    protected PluginRepository $pluginRepository;

    /**
     * List installed plugins, augmenting each row with whether an upgrade
     * is available based on the on-disk plugin.json version.
     *
     * @return array<int, array<string, mixed>>
     */
    public function list(): array
    {
        $rows = $this->pluginRepository->listAll();
        foreach ($rows as &$r) {
            $r['has_upgrade']  = false;
            $r['disk_version'] = null;
            $r['entry_path']   = $this->entryPathFor($r);
            $r['icon_url']     = $this->iconUrlFor($r);

            $diskPath = PluginManager::pluginsPath() . $r['code'] . DIRECTORY_SEPARATOR . 'plugin.json';
            if (!is_file($diskPath)) {
                continue;
            }
            try {
                $disk = PluginManifest::fromFile($diskPath);
                $r['disk_version'] = $disk->version;
                $r['has_upgrade']  = version_compare($disk->version, (string) $r['version'], '>');
            } catch (\Throwable) {
                // Bad on-disk manifest — leave defaults, don't break the listing.
            }
        }
        return $rows;
    }

    /**
     * Where the AppCard click should navigate to. Workspace plugins
     * (parent_menu=Plugin) get the dedicated workspace URL; everything
     * else uses the first menu path declared in the manifest.
     */
    private function entryPathFor(array $row): ?string
    {
        if (($row['status'] ?? null) !== Plugin::STATUS_INSTALLED) {
            return null;
        }
        if (($row['parent_menu'] ?? null) === 'Plugin') {
            return '/plugin/' . $row['code'];
        }
        $manifest = is_string($row['manifest']) ? json_decode($row['manifest'], true) : $row['manifest'];
        $first    = $manifest['menus'][0] ?? null;
        return is_array($first) && !empty($first['path']) ? (string) $first['path'] : null;
    }

    private function iconUrlFor(array $row): ?string
    {
        $code = (string) ($row['code'] ?? '');
        if ($code === '' || !preg_match('/^[a-z][a-z0-9_]*$/', $code)) {
            return null;
        }

        $pluginDir = PluginManager::pluginsPath() . $code . DIRECTORY_SEPARATOR;
        $candidates = [];
        if (!empty($row['icon'])) {
            $candidates[] = (string) $row['icon'];
        }
        $candidates[] = 'icon.png';

        foreach ($candidates as $relative) {
            $relative = ltrim(str_replace(['\\', "\0"], ['/', ''], $relative), '/');
            if ($relative === '' || str_contains($relative, '..')) {
                continue;
            }

            $path = $pluginDir . $relative;
            if (!is_file($path) || !is_readable($path)) {
                continue;
            }

            $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
                'jpg', 'jpeg' => 'image/jpeg',
                'svg' => 'image/svg+xml',
                'webp' => 'image/webp',
                default => 'image/png',
            };

            $contents = file_get_contents($path);
            if ($contents === false) {
                continue;
            }

            return 'data:' . $mime . ';base64,' . base64_encode($contents);
        }

        return null;
    }

    /**
     * Official shop catalog plus local install / official entitlement flags.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function marketCatalog(array $params): array
    {
        $client = new OfficialCatalogClient();
        $data = $client->listShopComponents($params);
        $list = $data['list'] ?? [];

        $installed = [];
        foreach ($this->pluginRepository->listAll() as $row) {
            $installed[(string) $row['code']] = $row;
        }

        $owned = [];
        $connected = OfficialAccountSession::publicInfo();
        if ($connected !== null) {
            try {
                foreach ($this->syncOfficialEntitlements() as $appCode => $ent) {
                    if (($ent['source'] ?? '') === 'official') {
                        $owned[$appCode] = $ent;
                    }
                }
            } catch (\Throwable) {
                OfficialAccountSession::clear();
                $connected = null;
            }
        }

        foreach ($list as &$row) {
            $code = (string) ($row['code'] ?? '');
            $row['buy_url'] = $code !== '' ? $client->buyUrl($code) : '';
            $row['owned'] = $code !== '' && (isset($owned[$code]) || !empty($row['is_free']));
            $row['installed'] = $code !== '' && isset($installed[$code]);
            $row['installed_version'] = $code !== '' ? ($installed[$code]['version'] ?? null) : null;
        }
        unset($row);

        $data['list'] = $list;
        $data['site_base'] = $client->siteBase();
        $data['connected'] = $connected !== null;
        $data['account'] = $connected;
        return $data;
    }

    /**
     * Take an uploaded zip, extract it to a tmp dir, move into plugins/<code>/,
     * then run the standard install lifecycle.
     *
     * @return array{code: string, backend: string, mode?: string, builds: array<string, mixed>}
     */
    public function uploadAndInstall(string $zipPath): array
    {
        return $this->installFromZip($zipPath, 'local_zip');
    }

    /**
     * Download a purchased Shop component from the official site and install it.
     *
     * @return array{code: string, backend: string, builds: array<string, mixed>}
     */
    public function installFromOfficial(string $code, ?string $version = null): array
    {
        $code = trim($code);
        if ($code === '' || !preg_match('/^[a-z][a-z0-9_]*$/', $code)) {
            throw new BusinessException('无效的插件 code', 422);
        }
        $token = OfficialAccountSession::token();
        if ($token === null || $token === '') {
            throw new BusinessException('请先连接官网账号', 422);
        }

        $client = new OfficialCatalogClient();
        $this->assertOfficialEntitlement($client, $token, $code);

        $tmpZip = rtrim((string) runtime_path(), '/\\') . DIRECTORY_SEPARATOR . 'plugin_dl_' . $code . '_' . uniqid('', true) . '.zip';
        try {
            $client->downloadApp($token, $code, $version, $tmpZip);
            return $this->installFromZip($tmpZip, 'official');
        } finally {
            @unlink($tmpZip);
        }
    }

    /**
     * Pull active shop entitlements from the official site into the local cache.
     *
     * @return array<string, array<string, mixed>>
     */
    public function syncOfficialEntitlements(): array
    {
        $token = OfficialAccountSession::token();
        if ($token === null || $token === '') {
            throw new BusinessException('请先连接官网账号', 422);
        }
        $ents = (new OfficialCatalogClient())->listEntitlements($token);
        $rows = MarketplaceEntitlement::all() ?? [];
        foreach ($ents as $ent) {
            $appCode = (string) ($ent['app_code'] ?? '');
            if ($appCode === '') {
                continue;
            }
            if (($ent['runtime'] ?? 'shop') === 'saas') {
                continue;
            }
            $rows[$appCode] = [
                'status'     => 'active',
                'source'     => 'official',
                'period_end' => $ent['period_end'] ?? null,
                'updated_at' => date('c'),
            ];
        }
        MarketplaceEntitlement::save($rows);
        return $rows;
    }

    /**
     * @return array{code: string, backend: string, builds: array<string, mixed>}
     */
    public function installFromZip(string $zipPath, string $entitlementSource = 'local_zip'): array
    {
        $tmpDir   = rtrim((string) runtime_path(), '/\\') . DIRECTORY_SEPARATOR . 'pluginrt_' . uniqid('', true);
        $manifest = PluginInstaller::extract($zipPath, $tmpDir);
        if (in_array($manifest->code, LicenseGuard::proPluginCodes(), true)
            || $manifest->category === 'value_added') {
            PackageSignatureVerifier::verifyUploadedZip($zipPath, $manifest->code, $manifest->version);
        }

        $this->grantLocalEntitlement($manifest->code, $entitlementSource);
        try {
            PluginFrontendDeployer::deployFromExtracted($tmpDir);
        } catch (\Throwable $e) {
            $this->log('[plugin] _frontend deploy ' . $manifest->code . ': ' . $e->getMessage(), [], 'warning');
        }

        $existing = $this->pluginRepository->findByCode($manifest->code);
        $pluginDir = rtrim(PluginManager::pluginsPath(), '/\\') . DIRECTORY_SEPARATOR . $manifest->code;
        if (is_dir($pluginDir)) {
            PluginInstaller::replacePluginsDir($tmpDir, $manifest->code);
        } else {
            PluginInstaller::moveToPluginsDir($tmpDir, $manifest->code);
        }

        if ($existing) {
            if (version_compare($manifest->version, (string) ($existing['version'] ?? '0.0.0'), '>')) {
                PluginManager::upgrade($manifest->code);
            }
        } else {
            PluginManager::install($manifest, Plugin::SOURCE_DOWNLOADED);
        }
        return $this->installPayload($manifest->code);
    }

    /**
     * @return array{code: string, backend: string, builds: array<string, mixed>}
     */
    private function installPayload(string $code): array
    {
        $builds = PluginManager::$lastFrontend;
        return [
            'code'    => $code,
            'backend' => 'installed',
            'mode'    => (string) ($builds['mode'] ?? 'sync'),
            'builds'  => $builds,
        ];
    }

    private function assertOfficialEntitlement(OfficialCatalogClient $client, string $token, string $code): void
    {
        foreach ($client->listEntitlements($token) as $ent) {
            if ((string) ($ent['app_code'] ?? '') === $code && ($ent['runtime'] ?? 'shop') !== 'saas') {
                return;
            }
        }
        $detail = $client->getApp($code);
        if (is_array($detail) && !empty($detail['is_free'])) {
            return;
        }
        throw new BusinessException('未持有该组件的官网权益，请先购买', 403);
    }

    private function grantLocalEntitlement(string $code, string $source = 'local_zip'): void
    {
        if (!in_array($code, LicenseGuard::proPluginCodes(), true)) {
            return;
        }
        $rows = MarketplaceEntitlement::all() ?? [];
        foreach (LicenseGuard::proPluginCodes() as $pluginCode) {
            if ($pluginCode !== $code && !PluginManager::isInstalled($pluginCode) && !$this->pluginRepository->findByCode($pluginCode)) {
                continue;
            }
            if ($pluginCode === $code) {
                $rows[$pluginCode] = [
                    'status'     => 'active',
                    'source'     => $source,
                    'updated_at' => date('c'),
                ];
                continue;
            }
            if (!isset($rows[$pluginCode])) {
                $rows[$pluginCode] = [
                    'status'     => 'active',
                    'source'     => 'local_zip',
                    'updated_at' => date('c'),
                ];
            }
        }
        MarketplaceEntitlement::save($rows);
    }

    public function uninstall(string $code, bool $purge = false): void
    {
        PluginManager::uninstall($code, $purge);
        PluginFrontendDeployer::remove($code);
    }

    public function upgrade(string $code): void
    {
        PluginManager::upgrade($code);
    }

    public function enable(string $code): void
    {
        PluginManager::enable($code);
    }

    public function disable(string $code): void
    {
        PluginManager::disable($code);
    }

    /**
     * @return array<string, mixed>
     */
    public function logs(?string $code, int $page, int $size): array
    {
        return $this->pluginRepository->logs($code, $page, $size);
    }
}
