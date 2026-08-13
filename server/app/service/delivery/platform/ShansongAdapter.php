<?php
declare(strict_types=1);

namespace app\service\delivery\platform;

use think\facade\Log;

/**
 * 闪送 Adapter
 *
 * 官方文档：https://open.ishansong.com/ （闪送开放平台，/openapi/oauth 体系）
 * 注意：sign 算法（按 key 升序 key=value& 拼接后 + appSecret 做 md5 大写）未完全核实，
 * 以官方最新文档为准，需沙箱联调核实。
 *
 * 平台订单标识：闪送返回的 orderNumber（issOrderNo）。
 */
class ShansongAdapter extends AbstractDeliveryPlatformAdapter
{
    private const GATEWAY = 'https://open.ishansong.com';

    public function code(): string
    {
        return 'shansong';
    }

    public function createOrder(DeliveryDispatchRequest $request): DeliveryDispatchResult
    {
        $cfg = $this->platformConfig();
        if (($cfg['app_key'] ?? '') === '' || ($cfg['app_secret'] ?? '') === '' || ($cfg['shop_id'] ?? '') === '') {
            return DeliveryDispatchResult::fail('闪送未配置');
        }

        $data = [
            'cityName'    => (string)($request->receiver['city'] ?? ''),
            'storeId'     => $cfg['shop_id'],
            'orderNo'     => $request->orderNo,
            'remarks'     => $request->remark,
            'goodType'    => 0,
            'weight'      => $request->cargoWeight,
            'insurance'   => $request->cargoPrice,
            'notifyUrl'   => $request->notifyUrl,
            'sender'      => [
                'fromAddress'       => (string)($request->sender['province'] ?? '')
                    . (string)($request->sender['city'] ?? '')
                    . (string)($request->sender['district'] ?? '')
                    . (string)($request->sender['address'] ?? ''),
                'fromSenderName'    => (string)($request->sender['name'] ?? ''),
                'fromMobile'        => (string)($request->sender['phone'] ?? ''),
                'fromLatitude'      => (string)($request->sender['lat'] ?? ''),
                'fromLongitude'     => (string)($request->sender['lng'] ?? ''),
            ],
            'receiverList' => [[
                'toAddress'        => (string)($request->receiver['province'] ?? '')
                    . (string)($request->receiver['city'] ?? '')
                    . (string)($request->receiver['district'] ?? '')
                    . (string)($request->receiver['address'] ?? ''),
                'toReceiverName'   => (string)($request->receiver['name'] ?? ''),
                'toMobile'         => (string)($request->receiver['phone'] ?? ''),
                'toLatitude'       => (string)($request->receiver['lat'] ?? ''),
                'toLongitude'      => (string)($request->receiver['lng'] ?? ''),
            ]],
        ];

        $res = $this->request('/openapi/developer/v5/orderPlace', $data, $cfg);
        if ($res === null) {
            return DeliveryDispatchResult::fail('闪送请求失败');
        }
        if ((int)($res['status'] ?? -1) !== 200) {
            return DeliveryDispatchResult::fail((string)($res['msg'] ?? '闪送发单失败'));
        }
        $result      = (array)($res['data'] ?? []);
        $orderNumber = (string)($result['orderNumber'] ?? $result['issOrderNo'] ?? '');
        if ($orderNumber === '') {
            return DeliveryDispatchResult::fail('闪送未返回订单号');
        }
        // 闪送金额单位为分，此处换算为元
        $fee = isset($result['totalFeeAfterSave'])
            ? (float)$result['totalFeeAfterSave'] / 100
            : (isset($result['totalAmount']) ? (float)$result['totalAmount'] / 100 : null);
        // 发单成功即进入 1-待接单
        return DeliveryDispatchResult::ok($orderNumber, '1', $fee);
    }

    public function cancelOrder(string $platformOrderId, string $reason): DeliveryPlatformResult
    {
        $cfg = $this->platformConfig();
        if (($cfg['app_key'] ?? '') === '' || ($cfg['app_secret'] ?? '') === '') {
            return DeliveryPlatformResult::fail('闪送未配置');
        }
        $res = $this->request('/openapi/developer/v5/abortOrder', [
            'issOrderNo'  => $platformOrderId,
            'abortReason' => $reason,
        ], $cfg);
        if ($res === null) {
            return DeliveryPlatformResult::fail('闪送请求失败');
        }
        if ((int)($res['status'] ?? -1) !== 200) {
            return DeliveryPlatformResult::fail((string)($res['msg'] ?? '闪送取消失败'));
        }
        return DeliveryPlatformResult::ok();
    }

    public function queryOrder(string $platformOrderId): DeliveryQueryResult
    {
        $cfg = $this->platformConfig();
        if (($cfg['app_key'] ?? '') === '' || ($cfg['app_secret'] ?? '') === '') {
            return DeliveryQueryResult::fail('闪送未配置');
        }
        $res = $this->request('/openapi/developer/v5/orderInfo', ['issOrderNo' => $platformOrderId], $cfg);
        if ($res === null) {
            return DeliveryQueryResult::fail('闪送请求失败');
        }
        if ((int)($res['status'] ?? -1) !== 200) {
            return DeliveryQueryResult::fail((string)($res['msg'] ?? '闪送查询失败'));
        }
        $result  = (array)($res['data'] ?? []);
        $courier = (array)($result['courier'] ?? []);
        return DeliveryQueryResult::ok(
            (string)($result['status'] ?? $result['orderStatus'] ?? ''),
            (string)($courier['name'] ?? ''),
            (string)($courier['mobile'] ?? ''),
            isset($courier['latitude']) && $courier['latitude'] !== '' ? (float)$courier['latitude'] : null,
            isset($courier['longitude']) && $courier['longitude'] !== '' ? (float)$courier['longitude'] : null
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
        if (!hash_equals(self::sign($signParams, $secret), strtoupper($sign))) {
            Log::warning('shansong 回调验签失败', ['payload' => $payload]);
            return null;
        }

        $data = $payload['data'] ?? [];
        if (is_string($data)) {
            $data = json_decode($data, true) ?: [];
        }
        $data    = array_merge($payload, (array)$data);
        $courier = (array)($data['courier'] ?? []);

        $orderNumber = (string)($data['issOrderNo'] ?? $data['orderNumber'] ?? '');
        if ($orderNumber === '') {
            return null;
        }
        return new DeliveryCallbackEvent(
            $orderNumber,
            (string)($data['status'] ?? $data['orderStatus'] ?? ''),
            (string)($courier['name'] ?? $data['courierName'] ?? ''),
            (string)($courier['mobile'] ?? $data['courierMobile'] ?? ''),
            isset($courier['latitude']) && $courier['latitude'] !== '' ? (float)$courier['latitude'] : null,
            isset($courier['longitude']) && $courier['longitude'] !== '' ? (float)$courier['longitude'] : null,
            $payload
        );
    }

    public function mapStatus(string $platformStatus): string
    {
        return match ($platformStatus) {
            '1'     => 'assigned',   // 待接单
            '2'     => 'picking',    // 已接单
            '3'     => 'delivering', // 已取货
            '4'     => 'completed',  // 已完成
            '5'     => 'cancelled',  // 已取消
            default => '',
        };
    }

    public function ackResponse(bool $ok): array
    {
        return [
            'body'        => (string)json_encode(['status' => $ok ? 200 : 400, 'msg' => $ok ? '成功' : '失败']),
            'httpCode'    => $ok ? 200 : 400,
            'contentType' => 'application/json',
        ];
    }

    /**
     * 闪送签名：按 key 升序 key=value& 拼接（去掉末尾 &）后 + appSecret 做 md5 大写
     * ⚠️ 该算法未完全核实，以官方文档为准
     */
    public static function sign(array $params, string $secret): string
    {
        unset($params['sign']);
        ksort($params);
        $pairs = [];
        foreach ($params as $key => $value) {
            $pairs[] = $key . '=' . $value;
        }
        return strtoupper(md5(implode('&', $pairs) . $secret));
    }

    private function request(string $path, array $data, array $cfg): ?array
    {
        $params = [
            'clientId'  => $cfg['app_key'],
            'timestamp' => (string)(int)(microtime(true) * 1000),
            'data'      => (string)json_encode($data, JSON_UNESCAPED_UNICODE),
        ];
        $params['sign'] = self::sign($params, $cfg['app_secret']);

        $res  = $this->httpPostForm(self::GATEWAY . $path, $params);
        $body = $this->decode($res);
        if ($body === null) {
            Log::warning('shansong 响应异常', ['path' => $path, 'body' => $res]);
        }
        return $body;
    }
}
