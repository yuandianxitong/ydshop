<?php
declare(strict_types=1);

namespace app\service\delivery\waybill;

use app\repository\system\SystemConfigRepository;
use think\facade\Log;

class KdniaoProvider implements WaybillProviderInterface
{
    private const API_URL     = 'https://api.kdniao.com/api/EOrderService';
    private const BUSINESS_ID = '1007';
    private const TIMEOUT     = 10;

    public function __construct(private readonly SystemConfigRepository $systemConfigRepository)
    {
    }

    public function generate(array $orderData): WaybillResult
    {
        $appKey    = (string)$this->systemConfigRepository->getConfigValue('waybill_app_key', '');
        $appSecret = (string)$this->systemConfigRepository->getConfigValue('waybill_app_secret', '');
        if ($appKey === '' || $appSecret === '') {
            return WaybillResult::fail('快递鸟 EBusinessID / AppKey 未配置');
        }

        foreach (['name', 'phone', 'province', 'city', 'district', 'detail'] as $f) {
            if (empty($orderData['receiver'][$f])) {
                return WaybillResult::fail("收件人 {$f} 为空");
            }
            if (empty($orderData['sender'][$f])) {
                return WaybillResult::fail("寄件人 {$f} 为空");
            }
        }
        if (empty($orderData['express_code'])) {
            return WaybillResult::fail('快递公司编码为空');
        }

        $expType = trim((string)($orderData['exp_type'] ?? '1'));
        if ($expType === '') {
            $expType = '1';
        }
        $payType = (int)($orderData['pay_type'] ?? 1);
        if ($payType < 1 || $payType > 3) {
            $payType = 1;
        }
        $templateSize = trim((string)($orderData['template_size'] ?? ''));
        // need_pickup=1 → IsNotice=0（通知上门）；need_pickup=0 → IsNotice=1（不通知，默认）
        $isNotice = !empty($orderData['need_pickup']) ? 0 : 1;

        $requestData = [
            'ShipperCode'           => $orderData['express_code'],
            'OrderCode'             => $orderData['order_no'],
            'PayType'               => $payType,
            'ExpType'               => $expType,
            'IsNotice'              => $isNotice,
            'IsReturnPrintTemplate' => 1,
            'Sender'                => $this->mapAddress($orderData['sender']),
            'Receiver'              => $this->mapAddress($orderData['receiver']),
            'Commodity'             => [['GoodsName' => (string)($orderData['goods_name'] ?? '商品')]],
            'Quantity'              => 1,
            'Weight'                => (float)($orderData['weight'] ?? 0),
            'CustomerName'          => $appKey,
        ];
        if ($templateSize !== '') {
            $requestData['TemplateSize'] = $templateSize;
        }
        $requestJson = json_encode($requestData, JSON_UNESCAPED_UNICODE);

        // 注：传原始 JSON；http_build_query 会做一次 urlencode，避免双重编码
        $params = [
            'RequestData' => $requestJson,
            'EBusinessID' => $appKey,
            'RequestType' => self::BUSINESS_ID,
            'DataSign'    => $this->sign($requestJson, $appSecret),
            'DataType'    => 2,
        ];

        $body     = $this->httpPost(self::API_URL, $params);
        $response = json_decode($body, true);
        if (!is_array($response) || empty($response['Success'])) {
            $reason = $response['Reason'] ?? 'kdniao API 调用失败';
            Log::warning('Kdniao generate failed', ['response' => $body]);
            return WaybillResult::fail($reason);
        }

        $waybillNo = (string)($response['Order']['LogisticCode'] ?? '');
        $template  = (string)($response['PrintTemplate'] ?? '');
        if ($waybillNo === '') {
            return WaybillResult::fail('kdniao 返回未含面单号');
        }
        return WaybillResult::ok($waybillNo, $template);
    }

    private function mapAddress(array $a): array
    {
        return [
            'Name'         => (string)($a['name']     ?? ''),
            'Mobile'       => (string)($a['phone']    ?? ''),
            'ProvinceName' => (string)($a['province'] ?? ''),
            'CityName'     => (string)($a['city']     ?? ''),
            'ExpAreaName'  => (string)($a['district'] ?? ''),
            'Address'      => (string)($a['detail']   ?? ''),
        ];
    }

    /**
     * kdniao 签名算法（官方文档标准）：
     * base64_encode(md5(RequestData + AppSecret, false))；最终 URL 编码由
     * http_build_query 统一完成，避免签名被二次编码。
     */
    private function sign(string $requestJson, string $appSecret): string
    {
        return base64_encode(md5($requestJson . $appSecret, false));
    }

    private function httpPost(string $url, array $params): string
    {
        $body = http_build_query($params);
        $ctx  = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $body,
                'timeout' => self::TIMEOUT,
            ],
        ]);
        $res = @file_get_contents($url, false, $ctx);
        return $res === false ? '' : $res;
    }
}
