<?php
declare(strict_types=1);

namespace app\service\delivery\platform;

use app\repository\system\SystemConfigRepository;

/**
 * 三方配送平台 Adapter 共享基类
 *
 * 提供配置读取（system_configs → local_delivery 分组）与
 * stream_context HTTP 请求范式（同 KdniaoProvider）。
 */
abstract class AbstractDeliveryPlatformAdapter implements DeliveryPlatformInterface
{
    protected const TIMEOUT = 10;

    public function __construct(protected readonly SystemConfigRepository $systemConfigRepository)
    {
    }

    /**
     * 当前平台配置（去掉 local_delivery_{code}_ 前缀）
     *
     * 例：dada 平台返回 { enabled, app_key, app_secret, source_id, shop_no }
     */
    protected function platformConfig(): array
    {
        $prefix = 'local_delivery_' . $this->code() . '_';
        $all    = $this->systemConfigRepository->getRawValuesByGroup('local_delivery');
        $out    = [];
        foreach ($all as $key => $value) {
            if (str_starts_with((string)$key, $prefix)) {
                $out[substr((string)$key, strlen($prefix))] = (string)$value;
            }
        }
        return $out;
    }

    protected function httpPostJson(string $url, array $payload): string
    {
        return $this->httpPost(
            $url,
            (string)json_encode($payload, JSON_UNESCAPED_UNICODE),
            'application/json'
        );
    }

    protected function httpPostForm(string $url, array $params): string
    {
        return $this->httpPost($url, http_build_query($params), 'application/x-www-form-urlencoded');
    }

    private function httpPost(string $url, string $body, string $contentType): string
    {
        $ctx = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => "Content-Type: {$contentType}\r\n",
                'content'       => $body,
                'timeout'       => self::TIMEOUT,
                'ignore_errors' => true,
            ],
        ]);
        $res = @file_get_contents($url, false, $ctx);
        return $res === false ? '' : $res;
    }

    /** @return array|null 非合法 JSON 返回 null */
    protected function decode(string $body): ?array
    {
        $data = json_decode($body, true);
        return is_array($data) ? $data : null;
    }
}
