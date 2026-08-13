<?php
declare(strict_types=1);

namespace app\service\delivery\platform;

use think\facade\Log;

/**
 * 达达配送 Adapter
 *
 * 官方文档：https://newopen.imdada.cn/#/development/file/index
 * 签名细节以官方最新文档为准，需沙箱联调核实。
 *
 * 请求信封：{ source_id, app_key, timestamp, format:'json', v:'1.0', body: json字符串, signature }
 * signature = strtoupper(md5(app_secret + 按参数名ASCII升序的 key.value 拼接（不含 signature）+ app_secret))
 *
 * 平台订单标识：达达以商户传入的 origin_id 作为订单唯一标识（回调中为 order_id），
 * 因此 platform_order_id 存 origin_id（即本地 orderNo）。
 */
class DadaAdapter extends AbstractDeliveryPlatformAdapter
{
    private const GATEWAY = 'https://newopen.imdada.cn';

    public function code(): string
    {
        return 'dada';
    }

    public function createOrder(DeliveryDispatchRequest $request): DeliveryDispatchResult
    {
        $cfg = $this->platformConfig();
        if (($cfg['app_key'] ?? '') === '' || ($cfg['app_secret'] ?? '') === ''
            || ($cfg['source_id'] ?? '') === '' || ($cfg['shop_no'] ?? '') === '') {
            return DeliveryDispatchResult::fail('达达配送未配置');
        }

        $body = [
            'shop_no'          => $cfg['shop_no'],
            'origin_id'        => $request->orderNo,
            // city_code 官方要求城市区号（如 021），此处透传城市名，接入沙箱联调时按官方城市编码表转换
            'city_code'        => (string)($request->receiver['city'] ?? ''),
            'cargo_price'      => $request->cargoPrice,
            'is_prepay'        => 0,
            'cargo_weight'     => $request->cargoWeight,
            'cargo_num'        => 1,
            'tips'             => $request->tips,
            'info'             => $request->remark,
            'receiver_name'    => (string)($request->receiver['name'] ?? ''),
            'receiver_phone'   => (string)($request->receiver['phone'] ?? ''),
            'receiver_address' => (string)($request->receiver['province'] ?? '')
                . (string)($request->receiver['city'] ?? '')
                . (string)($request->receiver['district'] ?? '')
                . (string)($request->receiver['address'] ?? ''),
            'receiver_lat'     => (float)($request->receiver['lat'] ?? 0),
            'receiver_lng'     => (float)($request->receiver['lng'] ?? 0),
            'callback'         => $request->notifyUrl,
        ];

        $data = $this->request('/api/order/addOrder', $body, $cfg);
        if ($data === null) {
            return DeliveryDispatchResult::fail('达达配送请求失败');
        }
        if ((int)($data['code'] ?? -1) !== 0) {
            return DeliveryDispatchResult::fail((string)($data['msg'] ?? '达达发单失败'));
        }
        $fee = null;
        if (isset($data['result']['fee'])) {
            $fee = (float)$data['result']['fee'];
        } elseif (isset($data['result']['deliverFee'])) {
            $fee = (float)$data['result']['deliverFee'];
        }
        // 发单成功即进入 1-待接单
        return DeliveryDispatchResult::ok($request->orderNo, '1', $fee);
    }

    public function cancelOrder(string $platformOrderId, string $reason): DeliveryPlatformResult
    {
        $cfg = $this->platformConfig();
        if (($cfg['app_key'] ?? '') === '' || ($cfg['app_secret'] ?? '') === '') {
            return DeliveryPlatformResult::fail('达达配送未配置');
        }
        $data = $this->request('/api/order/formalCancel', [
            'order_id'         => $platformOrderId,
            'cancel_reason_id' => 10000,
            'cancel_reason'    => $reason,
        ], $cfg);
        if ($data === null) {
            return DeliveryPlatformResult::fail('达达配送请求失败');
        }
        if ((int)($data['code'] ?? -1) !== 0) {
            return DeliveryPlatformResult::fail((string)($data['msg'] ?? '达达取消失败'));
        }
        return DeliveryPlatformResult::ok();
    }

    public function queryOrder(string $platformOrderId): DeliveryQueryResult
    {
        $cfg = $this->platformConfig();
        if (($cfg['app_key'] ?? '') === '' || ($cfg['app_secret'] ?? '') === '') {
            return DeliveryQueryResult::fail('达达配送未配置');
        }
        $data = $this->request('/api/order/status/query', ['order_id' => $platformOrderId], $cfg);
        if ($data === null) {
            return DeliveryQueryResult::fail('达达配送请求失败');
        }
        if ((int)($data['code'] ?? -1) !== 0) {
            return DeliveryQueryResult::fail((string)($data['msg'] ?? '达达查询失败'));
        }
        $result = (array)($data['result'] ?? []);
        return DeliveryQueryResult::ok(
            (string)($result['statusCode'] ?? ''),
            (string)($result['transporterName'] ?? ''),
            (string)($result['transporterPhone'] ?? ''),
            isset($result['transporterLat']) && $result['transporterLat'] !== '' ? (float)$result['transporterLat'] : null,
            isset($result['transporterLng']) && $result['transporterLng'] !== '' ? (float)$result['transporterLng'] : null
        );
    }

    /**
     * 回调验签（官方文档：将 client_id、order_id、update_time 三个值按字典序排序后
     * 拼接 md5，与 signature 比对）
     */
    public function parseCallback(array $payload, array $headers): ?DeliveryCallbackEvent
    {
        $signature = (string)($payload['signature'] ?? '');
        $clientId  = (string)($payload['client_id'] ?? '');
        $orderId   = (string)($payload['order_id'] ?? '');
        $updateAt  = (string)($payload['update_time'] ?? '');
        if ($signature === '' || $orderId === '') {
            return null;
        }
        $values = [$clientId, $orderId, $updateAt];
        sort($values, SORT_STRING);
        if (!hash_equals(md5(implode('', $values)), strtolower($signature))) {
            Log::warning('dada 回调验签失败', ['payload' => $payload]);
            return null;
        }
        return new DeliveryCallbackEvent(
            $orderId,
            (string)($payload['order_status'] ?? ''),
            (string)($payload['dm_name'] ?? ''),
            (string)($payload['dm_mobile'] ?? ''),
            null,
            null,
            $payload
        );
    }

    public function mapStatus(string $platformStatus): string
    {
        return match ($platformStatus) {
            '1'      => 'assigned',   // 待接单
            '2', '8' => 'picking',    // 待取货 / 骑士到店
            '3', '9' => 'delivering', // 配送中 / 妥投异常
            '4'      => 'completed',  // 已完成
            '5'      => 'cancelled',  // 已取消（Service 回落 pending）
            default  => '',
        };
    }

    public function ackResponse(bool $ok): array
    {
        return [
            'body'        => (string)json_encode(['status' => $ok ? 'success' : 'fail']),
            'httpCode'    => $ok ? 200 : 400,
            'contentType' => 'application/json',
        ];
    }

    /**
     * 达达签名：strtoupper(md5(secret + 按 key ASCII 升序 key.value 拼接 + secret))，不含 signature
     */
    public static function sign(array $params, string $secret): string
    {
        unset($params['signature']);
        ksort($params);
        $str = '';
        foreach ($params as $key => $value) {
            $str .= $key . $value;
        }
        return strtoupper(md5($secret . $str . $secret));
    }

    private function request(string $path, array $body, array $cfg): ?array
    {
        $params = [
            'source_id' => $cfg['source_id'] ?? '',
            'app_key'   => $cfg['app_key'],
            'timestamp' => (string)time(),
            'format'    => 'json',
            'v'         => '1.0',
            'body'      => (string)json_encode($body, JSON_UNESCAPED_UNICODE),
        ];
        $params['signature'] = self::sign($params, $cfg['app_secret']);

        $res  = $this->httpPostJson(self::GATEWAY . $path, $params);
        $data = $this->decode($res);
        if ($data === null) {
            Log::warning('dada 响应异常', ['path' => $path, 'body' => $res]);
        }
        return $data;
    }
}
