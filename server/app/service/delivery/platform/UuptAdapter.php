<?php
declare(strict_types=1);

namespace app\service\delivery\platform;

use think\facade\Log;

/**
 * UU跑腿 Adapter
 *
 * 官方文档：https://open.uupt.com/ （开放平台）
 * 签名细节以官方最新文档为准，需沙箱联调核实（部分接口还需 open_id/user_token，
 * 接入时按官方授权流程补充）。
 *
 * 签名：参数按 key 升序、排除空值与 sign 后 key.value 拼接，md5(拼接串 + app_secret) 小写。
 * 平台订单标识：UU 返回的 order_code。
 */
class UuptAdapter extends AbstractDeliveryPlatformAdapter
{
    private const GATEWAY = 'https://openapi.uupt.com';

    public function code(): string
    {
        return 'uupt';
    }

    public function createOrder(DeliveryDispatchRequest $request): DeliveryDispatchResult
    {
        $cfg = $this->platformConfig();
        if (($cfg['app_key'] ?? '') === '' || ($cfg['app_secret'] ?? '') === '' || ($cfg['shop_id'] ?? '') === '') {
            return DeliveryDispatchResult::fail('UU跑腿未配置');
        }

        $params = [
            'appid'           => $cfg['app_key'],
            'timestamp'       => (string)time(),
            'shop_id'         => $cfg['shop_id'],
            'origin_id'       => $request->orderNo,
            'send_type'       => '0', // 帮我送
            'goods_type'      => $request->cargoName,
            'order_price'     => (string)$request->cargoPrice,
            'city_name'       => (string)($request->receiver['city'] ?? ''),
            'county_name'     => (string)($request->receiver['district'] ?? ''),
            'from_address'    => (string)($request->sender['province'] ?? '')
                . (string)($request->sender['city'] ?? '')
                . (string)($request->sender['district'] ?? '')
                . (string)($request->sender['address'] ?? ''),
            'from_lat'        => (string)($request->sender['lat'] ?? ''),
            'from_lng'        => (string)($request->sender['lng'] ?? ''),
            'to_address'      => (string)($request->receiver['province'] ?? '')
                . (string)($request->receiver['city'] ?? '')
                . (string)($request->receiver['district'] ?? '')
                . (string)($request->receiver['address'] ?? ''),
            'to_lat'          => (string)($request->receiver['lat'] ?? ''),
            'to_lng'          => (string)($request->receiver['lng'] ?? ''),
            'receiver'        => (string)($request->receiver['name'] ?? ''),
            'receiver_phone'  => (string)($request->receiver['phone'] ?? ''),
            'note'            => $request->remark,
            'callback_url'    => $request->notifyUrl,
        ];
        $params['sign'] = self::sign($params, $cfg['app_secret']);

        $res  = $this->httpPostForm(self::GATEWAY . '/v2_0/addorder.ashx', $params);
        $data = $this->decode($res);
        if ($data === null) {
            Log::warning('uupt 响应异常', ['body' => $res]);
            return DeliveryDispatchResult::fail('UU跑腿请求失败');
        }
        if (strtolower((string)($data['return_code'] ?? '')) !== 'ok') {
            return DeliveryDispatchResult::fail((string)($data['return_msg'] ?? 'UU跑腿发单失败'));
        }
        $orderCode = (string)($data['order_code'] ?? $data['ordercode'] ?? '');
        if ($orderCode === '') {
            return DeliveryDispatchResult::fail('UU跑腿未返回订单号');
        }
        $fee = isset($data['need_paymoney']) ? (float)$data['need_paymoney'] : null;
        // 发单成功即进入 0-待接单
        return DeliveryDispatchResult::ok($orderCode, '0', $fee);
    }

    public function cancelOrder(string $platformOrderId, string $reason): DeliveryPlatformResult
    {
        $cfg = $this->platformConfig();
        if (($cfg['app_key'] ?? '') === '' || ($cfg['app_secret'] ?? '') === '') {
            return DeliveryPlatformResult::fail('UU跑腿未配置');
        }
        $params = [
            'appid'      => $cfg['app_key'],
            'timestamp'  => (string)time(),
            'order_code' => $platformOrderId,
            'reason'     => $reason,
        ];
        $params['sign'] = self::sign($params, $cfg['app_secret']);

        $res  = $this->httpPostForm(self::GATEWAY . '/v2_0/cancelorder.ashx', $params);
        $data = $this->decode($res);
        if ($data === null) {
            Log::warning('uupt 响应异常', ['body' => $res]);
            return DeliveryPlatformResult::fail('UU跑腿请求失败');
        }
        if (strtolower((string)($data['return_code'] ?? '')) !== 'ok') {
            return DeliveryPlatformResult::fail((string)($data['return_msg'] ?? 'UU跑腿取消失败'));
        }
        return DeliveryPlatformResult::ok();
    }

    public function queryOrder(string $platformOrderId): DeliveryQueryResult
    {
        $cfg = $this->platformConfig();
        if (($cfg['app_key'] ?? '') === '' || ($cfg['app_secret'] ?? '') === '') {
            return DeliveryQueryResult::fail('UU跑腿未配置');
        }
        $params = [
            'appid'      => $cfg['app_key'],
            'timestamp'  => (string)time(),
            'order_code' => $platformOrderId,
        ];
        $params['sign'] = self::sign($params, $cfg['app_secret']);

        $res  = $this->httpPostForm(self::GATEWAY . '/v2_0/getorderstate.ashx', $params);
        $data = $this->decode($res);
        if ($data === null) {
            Log::warning('uupt 响应异常', ['body' => $res]);
            return DeliveryQueryResult::fail('UU跑腿请求失败');
        }
        if (strtolower((string)($data['return_code'] ?? '')) !== 'ok') {
            return DeliveryQueryResult::fail((string)($data['return_msg'] ?? 'UU跑腿查询失败'));
        }
        return DeliveryQueryResult::ok(
            (string)($data['state'] ?? ''),
            (string)($data['driver_name'] ?? ''),
            (string)($data['driver_mobile'] ?? ''),
            isset($data['driver_lat']) && $data['driver_lat'] !== '' ? (float)$data['driver_lat'] : null,
            isset($data['driver_lng']) && $data['driver_lng'] !== '' ? (float)$data['driver_lng'] : null
        );
    }

    public function parseCallback(array $payload, array $headers): ?DeliveryCallbackEvent
    {
        $cfg    = $this->platformConfig();
        $secret = (string)($cfg['app_secret'] ?? '');
        $sign   = (string)($payload['sign'] ?? '');
        if ($secret === '' || $sign === '') {
            return null;
        }
        $signParams = $payload;
        unset($signParams['sign']);
        foreach ($signParams as $k => $v) {
            if (is_array($v)) {
                $signParams[$k] = (string)json_encode($v, JSON_UNESCAPED_UNICODE);
            }
        }
        if (!hash_equals(self::sign($signParams, $secret), strtolower($sign))) {
            Log::warning('uupt 回调验签失败', ['payload' => $payload]);
            return null;
        }

        $orderCode = (string)($payload['order_code'] ?? '');
        if ($orderCode === '') {
            return null;
        }
        return new DeliveryCallbackEvent(
            $orderCode,
            (string)($payload['state'] ?? ''),
            (string)($payload['driver_name'] ?? ''),
            (string)($payload['driver_mobile'] ?? ''),
            isset($payload['driver_lat']) && $payload['driver_lat'] !== '' ? (float)$payload['driver_lat'] : null,
            isset($payload['driver_lng']) && $payload['driver_lng'] !== '' ? (float)$payload['driver_lng'] : null,
            $payload
        );
    }

    public function mapStatus(string $platformStatus): string
    {
        return match ($platformStatus) {
            '0'     => 'assigned',   // 待接单
            '1'     => 'picking',    // 待取货
            '2'     => 'delivering', // 配送中
            '3'     => 'completed',  // 已完成
            '4'     => 'cancelled',  // 已取消
            default => '',
        };
    }

    public function ackResponse(bool $ok): array
    {
        return [
            'body'        => $ok ? 'ok' : 'fail',
            'httpCode'    => $ok ? 200 : 400,
            'contentType' => 'text/plain',
        ];
    }

    /**
     * UU 签名：按 key 升序、排除空值与 sign，key.value 拼接后 md5(拼接串 + secret) 小写
     */
    public static function sign(array $params, string $secret): string
    {
        unset($params['sign']);
        ksort($params);
        $str = '';
        foreach ($params as $key => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            $str .= $key . $value;
        }
        return md5($str . $secret);
    }
}
