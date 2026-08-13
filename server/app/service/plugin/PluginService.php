<?php
declare(strict_types=1);

namespace app\service\plugin;

use app\model\plugin\Plugin;
use app\repository\plugin\PluginRepository;
use core\base\Service;
use core\license\LicenseGuard;
use core\license\MarketplaceEntitlement;
use core\marketplace\PackageSignatureVerifier;
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
     * Take an uploaded zip, extract it to a tmp dir, move into plugins/<code>/,
     * then run the standard install lifecycle. Returns the installed plugin code.
     */
    public function uploadAndInstall(string $zipPath): string
    {
        $tmpDir   = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pluginrt_' . uniqid();
        $manifest = PluginInstaller::extract($zipPath, $tmpDir);
        if (in_array($manifest->code, LicenseGuard::proPluginCodes(), true)
            || $manifest->category === 'value_added') {
            PackageSignatureVerifier::verifyUploadedZip($zipPath, $manifest->code, $manifest->version);
        }
        PluginInstaller::moveToPluginsDir($tmpDir, $manifest->code);
        PluginManager::install($manifest, Plugin::SOURCE_DOWNLOADED);
        $this->grantLocalEntitlement($manifest->code);
        return $manifest->code;
    }

    private function grantLocalEntitlement(string $code): void
    {
        if (!in_array($code, LicenseGuard::proPluginCodes(), true)) {
            return;
        }
        $rows = MarketplaceEntitlement::all() ?? [];
        foreach (LicenseGuard::proPluginCodes() as $pluginCode) {
            if ($pluginCode !== $code && !PluginManager::isInstalled($pluginCode)) {
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
