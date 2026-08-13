<?php
declare(strict_types=1);

namespace core\license;

use core\exception\BusinessException;

/**
 * 调用 Site 开放授权接口：activate / heartbeat
 */
class LicenseClient
{
    public function activate(string $licenseKey, ?string $domain = null): array
    {
        return $this->request('activate', $licenseKey, $domain);
    }

    public function heartbeat(string $licenseKey, ?string $domain = null): array
    {
        return $this->request('heartbeat', $licenseKey, $domain);
    }

    /**
     * 综合本地缓存与远程结果，返回运行时状态：active|grace|expired|inactive
     */
    public function evaluate(): array
    {
        $state = LicenseState::load();
        $licenseKey = (string) ($state['license_key'] ?? '');
        if ($licenseKey === '') {
            return [
                'status'      => 'inactive',
                'pro_enabled' => false,
                'message'     => '未配置授权码',
                'state'       => $state,
            ];
        }

        $remoteStatus = (string) ($state['remote_status'] ?? '');
        $checkedAt = (int) ($state['checked_at'] ?? 0);
        $graceDays = (int) config('license.grace_days', 14);
        $stale = $checkedAt > 0 && (time() - $checkedAt) > ($graceDays * 86400);

        if ($remoteStatus === 'active' && !$stale) {
            return [
                'status'      => 'active',
                'pro_enabled' => true,
                'message'     => '授权有效',
                'state'       => $state,
            ];
        }

        if ($remoteStatus === 'active' && $stale) {
            return [
                'status'      => 'grace',
                'pro_enabled' => true,
                'message'     => '官网长时间未校验，处于宽限期',
                'state'       => $state,
            ];
        }

        if (in_array($remoteStatus, ['revoked', 'expired'], true)) {
            return [
                'status'      => $remoteStatus,
                'pro_enabled' => false,
                'message'     => $remoteStatus === 'revoked' ? '授权已撤销' : '授权已过期',
                'state'       => $state,
            ];
        }

        // 有授权码但尚无成功校验：宽限内仍允许 Pro（离线安装友好）
        if ($checkedAt === 0 || (time() - $checkedAt) <= ($graceDays * 86400)) {
            return [
                'status'      => 'grace',
                'pro_enabled' => true,
                'message'     => '授权待校验（宽限期）',
                'state'       => $state,
            ];
        }

        return [
            'status'      => 'expired',
            'pro_enabled' => false,
            'message'     => '授权校验失败且已超过宽限期',
            'state'       => $state,
        ];
    }

    public function isProEnabled(): bool
    {
        return (bool) ($this->evaluate()['pro_enabled'] ?? false);
    }

    protected function request(string $action, string $licenseKey, ?string $domain = null): array
    {
        $base = rtrim((string) config('license.site_base_url'), '/');
        $slug = (string) config('license.product_slug', 'shop');
        $domain = $this->resolveDomain($domain);
        $version = (string) (config('version.version') ?? '');

        $url = $base . '/api/open/license/' . $action;
        $payload = [
            'license_key'     => trim($licenseKey),
            'product_slug'    => $slug,
            'domain'          => $domain,
            'product_version' => $version,
        ];

        $response = $this->httpPostJson($url, $payload);
        $code = (int) ($response['code'] ?? 0);
        if ($code !== 200) {
            throw new BusinessException((string) ($response['message'] ?? '授权接口调用失败'), $code ?: 400);
        }

        $data = $response['data'] ?? [];
        if (!is_array($data)) {
            throw new BusinessException('授权接口返回异常', 500);
        }

        $license = $data['license'] ?? [];
        $now = time();
        LicenseState::save([
            'license_key'     => trim($licenseKey),
            'domain'          => (string) ($license['domain'] ?? $domain),
            'product_slug'    => $slug,
            'remote_status'   => (string) ($license['status'] ?? 'active'),
            'features'        => $license['features'] ?? [],
            'signature'       => $data['signature'] ?? '',
            'key_id'          => $data['key_id'] ?? '',
            'alg'             => $data['alg'] ?? '',
            'digest'          => $data['digest'] ?? '',
            'checked_at'      => $now,
            'activated_at'    => $license['activated_at'] ?? ($action === 'activate' ? date('c') : null),
            'last_action'     => $action,
            'updated_at'      => date('c'),
        ]);

        return $data;
    }

    protected function resolveDomain(?string $domain): string
    {
        $domain = trim((string) ($domain ?: config('license.domain') ?: ''));
        if ($domain !== '') {
            return $this->normalizeDomain($domain);
        }
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        return $this->normalizeDomain($host);
    }

    protected function normalizeDomain(string $domain): string
    {
        $domain = trim(strtolower($domain));
        $domain = (string) preg_replace('#^https?://#i', '', $domain);
        $domain = explode('/', $domain)[0] ?? '';
        $domain = explode('?', $domain)[0] ?? '';
        $domain = (string) preg_replace('#:(80|443)$#', '', $domain);
        return rtrim($domain, '.');
    }

    protected function httpPostJson(string $url, array $payload): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new BusinessException('无法初始化 HTTP 客户端', 500);
        }

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            throw new BusinessException('连接授权中心失败: ' . $error, 503);
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            throw new BusinessException('授权中心响应无效 (HTTP ' . $httpCode . ')', 502);
        }

        return $decoded;
    }
}
