<?php
declare(strict_types=1);

namespace app\service\delivery\platform;

use think\facade\Log;

/**
 * 顺丰同城急送 Adapter
 *
 * 官方文档：https://commit-openic.sf-express.com/#/apidoc （顺丰同城开放平台）
 * 签名细节以官方最新文档为准，需沙箱联调核实（接口路径/字段名可能随版本调整）。
 *
 * 公共参数（URL query）：partnerID / requestID / timestamp / msgDigest
 * msgDigest = base64_encode(md5(urlencode(msgData . timestamp . checkWord), true))
 * 配置映射：app_key 存 partnerID(dev_id)，app_secret 存 checkWord。
 *
 * 平台订单标识：顺丰返回的 sf_order_id。
 */
class SfscAdapter extends AbstractDeliveryPlatformAdapter
{
    private const GATEWAY = 'https://commit-openic.sf-express.com';

    public function code(): string
    {
        return 'sfsc';
    }

    public function createOrder(DeliveryDispatchRequest $request): DeliveryDispatchResult
    {
        $cfg = $this->platformConfig();
        if (($cfg['app_key'] ?? '') === '' || ($cfg['app_secret'] ?? '') === '' || ($cfg['shop_id'] ?? '') === '') {
            return DeliveryDispatchResult::fail('顺丰同城未配置');
        }

        $msgData = [
            'dev_id'         => (int)$cfg['app_key'],
            'shop_id'        => $cfg['shop_id'],
            'shop_type'      => 1,
            'shop_order_id'  => $request->orderNo,
            'order_source'   => 'shop',
            'push_time'      => (int)(microtime(true) * 1000),
            'order_detail'   => [
                'total_price'   => (int)round($request->cargoPrice * 100), // 单位：分
                'product_type'  => 1,
                'weight_gram'   => (int)round($request->cargoWeight * 1000),
                'product_name'  => $request->cargoName,
                'remark'        => $request->remark,
            ],
            'receive'        => [
                'user_name'    => (string)($request->receiver['name'] ?? ''),
                'user_phone'   => (string)($request->receiver['phone'] ?? ''),
                'user_address' => (string)($request->receiver['province'] ?? '')
                    . (string)($request->receiver['city'] ?? '')
                    . (string)($request->receiver['district'] ?? '')
                    . (string)($request->receiver['address'] ?? ''),
                'user_lat'     => (string)($request->receiver['lat'] ?? ''),
                'user_lng'     => (string)($request->receiver['lng'] ?? ''),
            ],
            'callback_url'   => $request->notifyUrl,
        ];

        $res = $this->request('/open/api/external/createorder', $msgData, $cfg);
        if ($res === null) {
            return DeliveryDispatchResult::fail('顺丰同城请求失败');
        }
        if ((int)($res['error_code'] ?? -1) !== 0) {
            return DeliveryDispatchResult::fail((string)($res['error_msg'] ?? '顺丰同城发单失败'));
        }
        $result    = (array)($res['result'] ?? []);
        $sfOrderId = (string)($result['sf_order_id'] ?? '');
        if ($sfOrderId === '') {
            return DeliveryDispatchResult::fail('顺丰同城未返回订单号');
        }
        $fee = isset($result['total_price']) ? (float)$result['total_price'] / 100 : null;
        // 发单成功即进入 10-待接单
        return DeliveryDispatchResult::ok($sfOrderId, '10', $fee);
    }

    public function cancelOrder(string $platformOrderId, string $reason): DeliveryPlatformResult
    {
        $cfg = $this->platformConfig();
        if (($cfg['app_key'] ?? '') === '' || ($cfg['app_secret'] ?? '') === '') {
            return DeliveryPlatformResult::fail('顺丰同城未配置');
        }
        $res = $this->request('/open/api/external/cancelorder', [
            'dev_id'        => (int)$cfg['app_key'],
            'sf_order_id'   => $platformOrderId,
            'cancel_reason' => $reason,
            'push_time'     => (int)(microtime(true) * 1000),
        ], $cfg);
        if ($res === null) {
            return DeliveryPlatformResult::fail('顺丰同城请求失败');
        }
        if ((int)($res['error_code'] ?? -1) !== 0) {
            return DeliveryPlatformResult::fail((string)($res['error_msg'] ?? '顺丰同城取消失败'));
        }
        return DeliveryPlatformResult::ok();
    }

    public function queryOrder(string $platformOrderId): DeliveryQueryResult
    {
        $cfg = $this->platformConfig();
        if (($cfg['app_key'] ?? '') === '' || ($cfg['app_secret'] ?? '') === '') {
            return DeliveryQueryResult::fail('顺丰同城未配置');
        }
        $res = $this->request('/open/api/external/getorderstatus', [
            'dev_id'      => (int)$cfg['app_key'],
            'sf_order_id' => $platformOrderId,
        ], $cfg);
        if ($res === null) {
            return DeliveryQueryResult::fail('顺丰同城请求失败');
        }
        if ((int)($res['error_code'] ?? -1) !== 0) {
            return DeliveryQueryResult::fail((string)($res['error_msg'] ?? '顺丰同城查询失败'));
        }
        $result = (array)($res['result'] ?? []);
        return DeliveryQueryResult::ok(
            (string)($result['order_status'] ?? $result['status'] ?? ''),
            (string)($result['operator_name'] ?? $result['rider_name'] ?? ''),
            (string)($result['operator_phone'] ?? $result['rider_phone'] ?? ''),
            isset($result['rider_lat']) && $result['rider_lat'] !== '' ? (float)$result['rider_lat'] : null,
            isset($result['rider_lng']) && $result['rider_lng'] !== '' ? (float)$result['rider_lng'] : null
        );
    }

    /**
     * 回调验签：回调报文携带 msgData（JSON 字符串）、timestamp、msgDigest，
     * 按请求同款 digest 算法比对。字段名以官方文档为准。
     */
    public function parseCallback(array $payload, array $headers): ?DeliveryCallbackEvent
    {
        $cfg       = $this->platformConfig();
        $checkWord = (string)($cfg['app_secret'] ?? '');
        if ($checkWord === '') {
            return null;
        }
        $timestamp = (string)($payload['timestamp'] ?? ($headers['timestamp'] ?? ''));
        $digest    = (string)($payload['msgDigest'] ?? ($headers['msgdigest'] ?? ''));
        $msgData   = $payload['msgData'] ?? null;
        if (!is_string($msgData) || $msgData === '' || $digest === '') {
            return null;
        }
        if (!hash_equals(self::digest($msgData, $timestamp, $checkWord), $digest)) {
            Log::warning('sfsc 回调验签失败', ['payload' => $payload]);
            return null;
        }

        $data = json_decode($msgData, true) ?: [];
        $platformOrderId = (string)($data['sf_order_id'] ?? $data['sfOrderId'] ?? '');
        if ($platformOrderId === '') {
            return null;
        }
        return new DeliveryCallbackEvent(
            $platformOrderId,
            (string)($data['order_status'] ?? $data['orderStatusCode'] ?? ''),
            (string)($data['operator_name'] ?? $data['rider_name'] ?? ''),
            (string)($data['operator_phone'] ?? $data['rider_phone'] ?? ''),
            isset($data['rider_lat']) && $data['rider_lat'] !== '' ? (float)$data['rider_lat'] : null,
            isset($data['rider_lng']) && $data['rider_lng'] !== '' ? (float)$data['rider_lng'] : null,
            $payload
        );
    }

    public function mapStatus(string $platformStatus): string
    {
        return match ($platformStatus) {
            '10'    => 'assigned',   // 待接单
            '20'    => 'picking',    // 已接单
            '30'    => 'picked',     // 已取件
            '40'    => 'delivering', // 派送中
            '50'    => 'completed',  // 已签收
            '60'    => 'cancelled',  // 已取消
            default => '',
        };
    }

    public function ackResponse(bool $ok): array
    {
        return [
            'body'        => (string)json_encode(['error_code' => $ok ? 0 : 1, 'error_msg' => $ok ? '' : 'fail']),
            'httpCode'    => $ok ? 200 : 400,
            'contentType' => 'application/json',
        ];
    }

    /**
     * msgDigest = base64_encode(md5(urlencode(msgData . timestamp . checkWord), true))
     */
    public static function digest(string $msgData, string $timestamp, string $checkWord): string
    {
        return base64_encode(md5(urlencode($msgData . $timestamp . $checkWord), true));
    }

    private function request(string $path, array $msgData, array $cfg): ?array
    {
        $json      = (string)json_encode($msgData, JSON_UNESCAPED_UNICODE);
        $timestamp = (string)time();
        $query     = http_build_query([
            'partnerID' => $cfg['app_key'],
            'requestID' => uniqid('sfsc', true),
            'timestamp' => $timestamp,
            'msgDigest' => self::digest($json, $timestamp, $cfg['app_secret']),
        ]);

        $res  = $this->httpPostJson(self::GATEWAY . $path . '?' . $query, $msgData);
        $body = $this->decode($res);
        if ($body === null) {
            Log::warning('sfsc 响应异常', ['path' => $path, 'body' => $res]);
        }
        return $body;
    }
}
