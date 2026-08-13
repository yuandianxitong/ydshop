<?php
declare(strict_types=1);

namespace core\marketplace;

use core\exception\BusinessException;

/**
 * 浏览官网 Shop 组件目录（公开接口，无需实例 token）。
 */
class OfficialCatalogClient
{
    public function siteBase(): string
    {
        return rtrim((string) config('license.site_base_url', 'https://www.dev007.cn'), '/');
    }

    /**
     * @return array{list: array<int, mixed>, pagination?: array<string, mixed>}
     */
    public function listShopComponents(array $params = []): array
    {
        $qs = http_build_query(array_filter([
            'page' => $params['page'] ?? 1,
            'limit' => $params['limit'] ?? 24,
            'keyword' => $params['keyword'] ?? null,
            'runtime' => 'shop',
        ], static fn ($v) => $v !== null && $v !== ''));
        $r = $this->get($this->siteBase() . '/api/commerce/market/apps?' . $qs);
        $data = $r['data'] ?? [];
        return is_array($data) ? $data : ['list' => []];
    }

    public function buyUrl(string $code): string
    {
        return $this->siteBase() . '/market/' . rawurlencode($code);
    }

    /**
     * 公开应用详情。官网不可达或未上架时返回 null，由调用方决定是否跳过验签。
     */
    public function getApp(string $code): ?array
    {
        $r = $this->getOptional($this->siteBase() . '/api/commerce/market/apps/' . rawurlencode($code));
        if ($r === null) {
            return null;
        }
        $data = $r['data'] ?? null;
        return is_array($data) ? $data : null;
    }

    public function fetchPublicKey(string $keyId): string
    {
        $r = $this->getOptional($this->siteBase() . '/api/open/market/public-key/' . rawurlencode($keyId));
        if ($r === null) {
            return '';
        }
        return (string) ($r['data']['public_key'] ?? $r['data']['pem'] ?? '');
    }

    /** @return array<string, mixed>|null */
    private function getOptional(string $url): ?array
    {
        try {
            return $this->get($url);
        } catch (BusinessException) {
            return null;
        }
    }

    private function get(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($body === false || $status < 200 || $status >= 300) {
            throw new BusinessException('官方市场不可达' . ($err !== '' ? "：{$err}" : ''), 502);
        }
        $json = json_decode((string) $body, true);
        return is_array($json) ? $json : [];
    }
}
