<?php
declare(strict_types=1);

namespace app\service\delivery\platform;

use think\facade\Log;

/**
 * 蜂鸟配送（蜂鸟即配开放平台）Adapter
 *
 * 官方文档：https://open.ele.me/openapi （蜂鸟即配）
 * 签名细节以官方最新文档为准，需沙箱联调核实（网关路径/字段名可能随版本调整）。
 *
 * TOP 风格签名：strtoupper(md5(app_secret + 按 key 升序 key.value 拼接 + app_secret))
 * 平台订单标识：以商户订单号 partner_order_code 为准（本地 orderNo）。
 */
class FengniaoAdapter extends AbstractDeliveryPlatformAdapter
{
    private const GATEWAY = 'https://open-anubis.ele.me/anubis-webapi';

    public function code(): string
    {
        return 'fengniao';
    }

    public function createOrder(DeliveryDispatchRequest $request): DeliveryDispatchResult
    {
        $cfg = $this->platformConfig();
        if (($cfg['app_key'] ?? '') === '' || ($cfg['app_secret'] ?? '') === '' || ($cfg['shop_id'] ?? '') === '') {
            return DeliveryDispatchResult::fail('蜂鸟配送未配置');
        }

        $data = [
            'partner_order_code'        => $request->orderNo,
            'chain_store_code'          => $cfg['shop_id'],
            'order_type'                => 1,
            'goods_count'               => 1,
            'order_total_amount'        => $request->cargoPrice,
            'order_actual_amount'       => $request->cargoPrice,
            'order_weight'              => $request->cargoWeight,
            'order_tips'                => $request->tips,
            'order_remark'              => $request->remark,
            'goods_name'                => $request->cargoName,
            'receiver_name'             => (string)($request->receiver['name'] ?? ''),
            'receiver_primary_phone'    => (string)($request->receiver['phone'] ?? ''),
            'receiver_address'          => (string)($request->receiver['province'] ?? '')
                . (string)($request->receiver['city'] ?? '')
                . (string)($request->receiver['district'] ?? '')
                . (string)($request->receiver['address'] ?? ''),
            'receiver_latitude'         => (float)($request->receiver['lat'] ?? 0),
            'receiver_longitude'        => (float)($request->receiver['lng'] ?? 0),
            'callback_url'              => $request->notifyUrl,
        ];

        $res = $this->request('/v2/order', $data, $cfg);
        if ($res === null) {
            return DeliveryDispatchResult::fail('蜂鸟配送请求失败');
        }
        if ((int)($res['code'] ?? -1) !== 200) {
            return DeliveryDispatchResult::fail((string)($res['msg'] ?? '蜂鸟发单失败'));
        }
        // 发单成功即进入 10-已下单
        return DeliveryDispatchResult::ok($request->orderNo, '10', null);
    }

    public function cancelOrder(string $platformOrderId, string $reason): DeliveryPlatformResult
    {
        $cfg = $this->platformConfig();
        if (($cfg['app_key'] ?? '') === '' || ($cfg['app_secret'] ?? '') === '') {
            return DeliveryPlatformResult::fail('蜂鸟配送未配置');
        }
        $res = $this->request('/v2/order/cancel', [
            'partner_order_code'   => $platformOrderId,
            'order_cancel_reason'  => $reason,
            'order_cancel_code'    => 2,
            'order_cancel_time'    => (int)(microtime(true) * 1000),
        ], $cfg);
        if ($res === null) {
            return DeliveryPlatformResult::fail('蜂鸟配送请求失败');
        }
        if ((int)($res['code'] ?? -1) !== 200) {
            return DeliveryPlatformResult::fail((string)($res['msg'] ?? '蜂鸟取消失败'));
        }
        return DeliveryPlatformResult::ok();
    }

    public function queryOrder(string $platformOrderId): DeliveryQueryResult
    {
        $cfg = $this->platformConfig();
        if (($cfg['app_key'] ?? '') === '' || ($cfg['app_secret'] ?? '') === '') {
            return DeliveryQueryResult::fail('蜂鸟配送未配置');
        }
        $res = $this->request('/v2/order/query', ['partner_order_code' => $platformOrderId], $cfg);
        if ($res === null) {
            return DeliveryQueryResult::fail('蜂鸟配送请求失败');
        }
        if ((int)($res['code'] ?? -1) !== 200) {
            return DeliveryQueryResult::fail((string)($res['msg'] ?? '蜂鸟查询失败'));
        }
        $bd = (array)($res['business_data'] ?? $res['data'] ?? []);
        return DeliveryQueryResult::ok(
            (string)($bd['order_status'] ?? ''),
            (string)($bd['carrier_driver_name'] ?? ''),
            (string)($bd['carrier_driver_phone'] ?? ''),
            isset($bd['latitude']) && $bd['latitude'] !== '' ? (float)$bd['latitude'] : null,
            isset($bd['longitude']) && $bd['longitude'] !== '' ? (float)$bd['longitude'] : null
        );
    }

    /**
     * 回调验签：对 payload（不含 signature）做同款 TOP 签名比对；
     * data 字段为 JSON 字符串时先解出业务数据。
     */
    public function parseCallback(array $payload, array $headers): ?DeliveryCallbackEvent
    {
        $cfg       = $this->platformConfig();
        $secret    = (string)($cfg['app_secret'] ?? '');
        $signature = (string)($payload['signature'] ?? '');
        if ($secret === '' || $signature === '') {
            return null;
        }
        $signParams = $payload;
        unset($signParams['signature']);
        foreach ($signParams as $k => $v) {
            if (is_array($v)) {
                $signParams[$k] = (string)json_encode($v, JSON_UNESCAPED_UNICODE);
            }
        }
        if (!hash_equals(self::sign($signParams, $secret), strtoupper($signature))) {
            Log::warning('fengniao 回调验签失败', ['payload' => $payload]);
            return null;
        }

        $data = $payload['data'] ?? [];
        if (is_string($data)) {
            $data = json_decode($data, true) ?: [];
        }
        $data = array_merge($payload, (array)$data);

        $orderCode = (string)($data['partner_order_code'] ?? '');
        if ($orderCode === '') {
            return null;
        }
        return new DeliveryCallbackEvent(
            $orderCode,
            (string)($data['order_status'] ?? ''),
            (string)($data['carrier_driver_name'] ?? ''),
            (string)($data['carrier_driver_phone'] ?? ''),
            isset($data['latitude']) && $data['latitude'] !== '' ? (float)$data['latitude'] : null,
            isset($data['longitude']) && $data['longitude'] !== '' ? (float)$data['longitude'] : null,
            $payload
        );
    }

    public function mapStatus(string $platformStatus): string
    {
        return match ($platformStatus) {
            '10'    => 'assigned',   // 已下单
            '20'    => 'picking',    // 已接单
            '30'    => 'delivering', // 已取货
            '40'    => 'completed',  // 已完成
            '99'    => 'cancelled',  // 已取消
            default => '',
        };
    }

    public function ackResponse(bool $ok): array
    {
        return [
            'body'        => (string)json_encode(['code' => $ok ? 200 : 400, 'msg' => $ok ? 'ok' : 'fail']),
            'httpCode'    => $ok ? 200 : 400,
            'contentType' => 'application/json',
        ];
    }

    /**
     * TOP 风格签名：strtoupper(md5(secret + 按 key 升序 key.value 拼接 + secret))
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

    private function request(string $path, array $data, array $cfg): ?array
    {
        $params = [
            'app_id'    => $cfg['app_key'],
            'timestamp' => (string)time(),
            'data'      => (string)json_encode($data, JSON_UNESCAPED_UNICODE),
        ];
        $params['signature'] = self::sign($params, $cfg['app_secret']);

        $res  = $this->httpPostJson(self::GATEWAY . $path, $params);
        $body = $this->decode($res);
        if ($body === null) {
            Log::warning('fengniao 响应异常', ['path' => $path, 'body' => $res]);
        }
        return $body;
    }
}
