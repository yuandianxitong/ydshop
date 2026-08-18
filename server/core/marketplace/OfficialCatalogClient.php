<?php
declare(strict_types=1);

namespace core\marketplace;

use core\exception\BusinessException;

/**
 * 官网 Shop 组件目录（公开）与实例授权后的权益/下载。
 */
class OfficialCatalogClient
{
    public function siteBase(): string
    {
        return rtrim((string) config('license.site_base_url', 'https://www.dev007.cn'), '/');
    }

    public function webBase(): string
    {
        $web = rtrim((string) config('license.site_web_base_url', ''), '/');
        return $web !== '' ? $web : $this->siteBase();
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

    /**
     * @return array<string, mixed>
     */
    public function exchangeCode(string $authCode, string $verifier, string $instanceUuid): array
    {
        $r = $this->call($this->siteBase() . '/api/open/instances/exchange-code', 'POST', null, [
            'auth_code'     => $authCode,
            'code_verifier' => $verifier,
            'instance_uuid' => $instanceUuid,
        ]);
        $data = $r['data'] ?? [];
        if (!is_array($data) || (string) ($data['access_token'] ?? '') === '') {
            throw new BusinessException((string) ($r['message'] ?? '官网未返回实例凭证'), 502);
        }
        return $data;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listEntitlements(string $token): array
    {
        $r = $this->call($this->siteBase() . '/api/open/instances/entitlements', 'GET', $token);
        $payload = $r['data'] ?? [];
        if (isset($payload['list']) && is_array($payload['list'])) {
            return array_values(array_filter($payload['list'], 'is_array'));
        }
        if (is_array($payload) && array_is_list($payload)) {
            return array_values(array_filter($payload, 'is_array'));
        }
        return [];
    }

    /**
     * Stream a purchased (or free entitled) zip to $destPath via instance download-token.
     *
     * @return array<string, string>
     */
    public function downloadApp(string $token, string $code, ?string $version, string $destPath): array
    {
        $ver = $version !== null ? trim($version) : '';
        if ($ver === '') {
            foreach ($this->listEntitlements($token) as $ent) {
                if ((string) ($ent['app_code'] ?? '') === $code) {
                    $ver = (string) ($ent['latest_version'] ?? '');
                    break;
                }
            }
        }
        if ($ver === '') {
            $detail = $this->getApp($code);
            $latest = $detail['latest_version'] ?? null;
            if (is_array($latest)) {
                $ver = (string) ($latest['version'] ?? '');
            } elseif (is_string($latest)) {
                $ver = $latest;
            }
        }
        if ($ver === '') {
            throw new BusinessException('官网未返回可安装版本', 404);
        }

        $issued = $this->call(
            $this->siteBase() . '/api/open/market/apps/' . rawurlencode($code)
            . '/versions/' . rawurlencode($ver) . '/download-token',
            'POST',
            $token
        );
        $url = (string) ($issued['data']['download_url'] ?? '');
        if ($url === '') {
            throw new BusinessException('官网未返回下载地址', 502);
        }
        if (!str_starts_with($url, 'http')) {
            $url = $this->siteBase() . $url;
        }
        return $this->stream($url, $token, $destPath);
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
        return $this->call($url, 'GET', null);
    }

    /**
     * @param array<string, mixed>|null $json
     * @return array<string, mixed>
     */
    private function call(string $url, string $method, ?string $token, ?array $json = null): array
    {
        $headers = ['Accept: application/json'];
        if ($token !== null && $token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
        ];
        if ($json !== null) {
            $headers[] = 'Content-Type: application/json';
            $opts[CURLOPT_HTTPHEADER] = $headers;
            $opts[CURLOPT_POSTFIELDS] = json_encode($json, JSON_UNESCAPED_UNICODE);
        }
        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($body === false || $status < 200 || $status >= 300) {
            $decoded = is_string($body) ? json_decode($body, true) : null;
            $msg = is_array($decoded) ? (string) ($decoded['message'] ?? '') : '';
            throw new BusinessException(
                $msg !== '' ? $msg : ('官方市场不可达' . ($err !== '' ? "：{$err}" : ($status > 0 ? " HTTP {$status}" : ''))),
                $status >= 400 && $status < 500 ? $status : 502
            );
        }
        $parsed = json_decode((string) $body, true);
        return is_array($parsed) ? $parsed : [];
    }

    /**
     * @return array<string, string>
     */
    private function stream(string $url, string $token, string $destPath): array
    {
        $fp = fopen($destPath, 'wb');
        if ($fp === false) {
            throw new BusinessException('无法写入下载文件', 500);
        }
        $headerBag = [];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_HTTPHEADER => [
                'Accept: application/zip, application/octet-stream, */*',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_HEADERFUNCTION => static function ($ch, string $header) use (&$headerBag): int {
                $len = strlen($header);
                $parts = explode(':', $header, 2);
                if (count($parts) === 2) {
                    $headerBag[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return $len;
            },
        ]);
        $ok = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        fclose($fp);
        if ($ok === false || $status < 200 || $status >= 300) {
            @unlink($destPath);
            throw new BusinessException(
                '官网组件下载失败' . ($err !== '' ? "：{$err}" : ($status > 0 ? " HTTP {$status}" : '')),
                $status >= 400 && $status < 500 ? $status : 502
            );
        }
        if (!is_file($destPath) || filesize($destPath) < 32) {
            @unlink($destPath);
            throw new BusinessException('官网未返回有效安装包（该组件可能尚未发版）', 404);
        }
        return $headerBag;
    }
}
