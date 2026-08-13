<?php
declare(strict_types=1);

namespace app\service\delivery;

use app\model\system\SystemConfig;
use core\base\Service;
use think\facade\Log;

/**
 * AMap REST API 封装：geocoding（单 + 批量）
 *
 * 文档：https://lbs.amap.com/api/webservice/guide/api/georegeo
 *
 * 配置项（system_configs）：
 * - amap.web_api_key      Web 服务 Key（仅后端使用）
 * - amap.default_city     Geocoding 默认城市（提高识别率）
 */
class AmapService extends Service
{
    private const GEOCODE_URL = 'https://restapi.amap.com/v3/geocode/geo';
    private const TIMEOUT     = 5;
    private const BATCH_SIZE  = 10;

    /**
     * 单地址 geocoding
     *
     * @return array{lat: float, lng: float}|null  失败/无结果返回 null
     */
    public function geocode(string $address, ?string $city = null): ?array
    {
        $apiKey = (string)SystemConfig::getConfigValue('amap.web_api_key', '');
        if ($apiKey === '' || trim($address) === '') {
            return null;
        }
        $params = [
            'key'     => $apiKey,
            'address' => $address,
            'city'    => $city ?? (string)SystemConfig::getConfigValue('amap.default_city', ''),
            'output'  => 'JSON',
        ];
        $url  = self::GEOCODE_URL . '?' . http_build_query($params);
        $body = $this->httpGet($url);
        $data = json_decode($body, true);

        if (!is_array($data) || ($data['status'] ?? '0') !== '1' || empty($data['geocodes'])) {
            Log::warning('AMap geocode miss', ['address' => $address, 'response' => $body]);
            return null;
        }
        $loc = $data['geocodes'][0]['location'] ?? '';
        if (!str_contains($loc, ',')) {
            return null;
        }
        [$lng, $lat] = array_map('floatval', explode(',', $loc));
        return ['lat' => $lat, 'lng' => $lng];
    }

    /**
     * 批量 geocoding（AMap 单次最多 10 个；超出自动分片）
     *
     * @param string[] $addresses
     * @return array<int, array{lat: float, lng: float}|null>  与输入数组一一对应
     */
    public function batchGeocode(array $addresses, ?string $city = null): array
    {
        if (empty($addresses)) {
            return [];
        }
        $results = [];
        foreach (array_chunk($addresses, self::BATCH_SIZE) as $chunk) {
            $results = array_merge($results, $this->batchGeocodeChunk($chunk, $city));
        }
        return $results;
    }

    private function batchGeocodeChunk(array $chunk, ?string $city): array
    {
        $apiKey = (string)SystemConfig::getConfigValue('amap.web_api_key', '');
        if ($apiKey === '') {
            return array_fill(0, count($chunk), null);
        }
        $params = [
            'key'     => $apiKey,
            'address' => implode('|', $chunk),
            'batch'   => 'true',
            'city'    => $city ?? (string)SystemConfig::getConfigValue('amap.default_city', ''),
            'output'  => 'JSON',
        ];
        $url  = self::GEOCODE_URL . '?' . http_build_query($params);
        $body = $this->httpGet($url);
        $data = json_decode($body, true);

        if (!is_array($data) || ($data['status'] ?? '0') !== '1') {
            Log::warning('AMap batchGeocode miss', ['response' => $body]);
            return array_fill(0, count($chunk), null);
        }
        $geocodes = $data['geocodes'] ?? [];
        $out      = [];
        $count    = count($chunk);
        for ($i = 0; $i < $count; $i++) {
            $loc = $geocodes[$i]['location'] ?? '';
            if (str_contains($loc, ',')) {
                [$lng, $lat] = array_map('floatval', explode(',', $loc));
                $out[] = ['lat' => $lat, 'lng' => $lng];
            } else {
                $out[] = null;
            }
        }
        return $out;
    }

    private function httpGet(string $url): string
    {
        $ctx = stream_context_create(['http' => ['timeout' => self::TIMEOUT]]);
        $res = @file_get_contents($url, false, $ctx);
        return $res === false ? '' : $res;
    }
}
