<?php
declare(strict_types=1);

namespace app\service\plugin;

use app\repository\system\SystemConfigRepository;
use core\base\Service;
use core\exception\BusinessException;
use core\marketplace\MarketplaceInstallation;
use core\marketplace\OfficialAccountSession;
use core\marketplace\OfficialCatalogClient;
use think\facade\Cache;

/**
 * 官网市场实例 PKCE：发起授权、兑换实例 token。
 */
class OfficialConnectService extends Service
{
    protected SystemConfigRepository $configRepository;
    protected PluginService $pluginService;

    /**
     * @return array{authorize_url: string, state: string}
     */
    public function initiate(string $callbackUrl): array
    {
        $this->assertCallbackUrl($callbackUrl);

        $client = new OfficialCatalogClient();
        $apiBase = $client->siteBase();
        $webBase = $client->webBase();
        $this->assertHttpsUrl($apiBase);
        $this->assertHttpsUrl($webBase);

        $verifier = $this->base64url(random_bytes(64));
        $challenge = $this->base64url(hash('sha256', $verifier, true));
        $state = $this->base64url(random_bytes(32));
        $instanceUuid = MarketplaceInstallation::uuid();
        $instanceName = $this->deriveInstanceName();

        Cache::set('oauth_pkce:' . $state, json_encode([
            'verifier'      => $verifier,
            'instance_uuid' => $instanceUuid,
            'instance_name' => $instanceName,
            'site_base_url' => $apiBase,
            'runtime'       => 'shop',
        ], JSON_UNESCAPED_UNICODE), 600);

        $url = $webBase . '/connect/saas?' . http_build_query([
            'instance_uuid'         => $instanceUuid,
            'instance_name'         => $instanceName,
            'callback_url'          => $callbackUrl,
            'state'                 => $state,
            'code_challenge'        => $challenge,
            'code_challenge_method' => 'S256',
            'runtime'               => 'shop',
        ]);

        return ['authorize_url' => $url, 'state' => $state];
    }

    /** @return array{connected: bool, account: string, nickname: string, connected_at: string}|null */
    public function exchange(string $state, string $code): array
    {
        $state = trim($state);
        $code = trim($code);
        if ($state === '' || $code === '') {
            throw new BusinessException('缺少授权参数', 422);
        }

        $raw = Cache::get('oauth_pkce:' . $state);
        if (!$raw) {
            throw new BusinessException('授权请求已过期，请重新发起', 400);
        }
        $ctx = is_string($raw) ? json_decode($raw, true) : $raw;
        if (!is_array($ctx)) {
            throw new BusinessException('授权请求已过期，请重新发起', 400);
        }

        $client = new OfficialCatalogClient();
        $ex = $client->exchangeCode($code, (string) ($ctx['verifier'] ?? ''), (string) ($ctx['instance_uuid'] ?? ''));
        $token = (string) ($ex['access_token'] ?? '');
        $bound = is_array($ex['bound_user'] ?? null) ? $ex['bound_user'] : [];
        OfficialAccountSession::save($token, $bound);
        Cache::delete('oauth_pkce:' . $state);

        try {
            $this->pluginService->syncOfficialEntitlements();
        } catch (\Throwable) {
            // 同步失败不阻断已连接；下次拉目录会重试
        }

        return OfficialAccountSession::publicInfo();
    }

    private function deriveInstanceName(): string
    {
        $siteName = (string) $this->configRepository->getConfigValue('site_name', '元点Shop');
        if ($siteName === '') {
            $siteName = '元点Shop';
        }
        $host = '';
        try {
            $host = (string) request()->host();
        } catch (\Throwable) {
            $host = '';
        }
        $name = $host !== '' ? sprintf('%s (%s)', $siteName, $host) : $siteName;
        return mb_substr($name, 0, 120);
    }

    private function assertHttpsUrl(string $url): void
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new BusinessException('官网地址非法', 422);
        }
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = (string) parse_url($url, PHP_URL_HOST);
        if ($scheme === 'https') {
            return;
        }
        if ($scheme === 'http' && in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '::1'], true)) {
            return;
        }
        throw new BusinessException('官网地址必须 https（本地开发可用 http://localhost）', 422);
    }

    private function assertCallbackUrl(string $url): void
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new BusinessException('callback_url 非法', 422);
        }
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = (string) parse_url($url, PHP_URL_HOST);
        if ($scheme === 'https') {
            return;
        }
        if ($scheme === 'http' && in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '::1'], true)) {
            return;
        }
        throw new BusinessException('callback_url 必须 https（本地开发可用 http://localhost）', 422);
    }

    private function base64url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
