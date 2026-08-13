<?php
declare(strict_types=1);

namespace app\service\delivery;

use app\repository\delivery\ExpressCompanyRepository;
use app\repository\order\OrderLogisticsRepository;
use app\repository\system\SystemConfigRepository;
use core\base\Service;
use think\facade\Log;

class TrackingService extends Service
{
    private const KDNIAO_TRACK_URL = 'https://api.kdniao.com/Ebusiness/EbusinessOrderHandle.aspx';
    private const HTTP_TIMEOUT = 10;

    protected OrderLogisticsRepository $orderLogisticsRepository;
    protected SystemConfigRepository $systemConfigRepository;
    protected ExpressCompanyRepository $expressCompanyRepository;

    /**
     * 查询物流轨迹
     *
     * @param string $expressCode 快递公司编码
     * @param string $expressNo   快递单号
     * @return array 轨迹列表 [['time' => '...', 'desc' => '...'], ...]
     */
    public function query(string $expressCode, string $expressNo): array
    {
        $config   = $this->getWaybillConfig();
        $provider = $config['waybill_provider'] ?? '';

        if (empty($provider)) {
            Log::info('物流追踪未配置服务商，跳过查询');
            return [];
        }

        if ($expressCode === '' || $expressNo === '') {
            return [];
        }

        try {
            return match ($provider) {
                'kdniao' => $this->queryKdniao($expressCode, $expressNo, $config),
                default => [],
            };
        } catch (\Throwable $e) {
            Log::warning('物流追踪查询失败', [
                'provider' => $provider,
                'express_code' => $expressCode,
                'express_no' => $expressNo,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * 快递鸟即时查询（RequestType=1002）。
     */
    private function queryKdniao(string $expressCode, string $expressNo, array $config): array
    {
        $businessId = trim((string)($config['waybill_app_key'] ?? ''));
        $appSecret = trim((string)($config['waybill_app_secret'] ?? ''));
        if ($businessId === '' || $appSecret === '') {
            Log::info('物流追踪未配置快递鸟 EBusinessID/AppKey');
            return [];
        }

        $requestJson = json_encode([
            'OrderCode' => '',
            'ShipperCode' => $expressCode,
            'LogisticCode' => $expressNo,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($requestJson === false) {
            return [];
        }

        $body = http_build_query([
            'RequestData' => $requestJson,
            'EBusinessID' => $businessId,
            'RequestType' => '1002',
            // http_build_query 统一负责 URL 编码，避免 DataSign 二次编码。
            'DataSign' => base64_encode(md5($requestJson . $appSecret, false)),
            'DataType' => '2',
        ]);
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded;charset=utf-8\r\n",
                'content' => $body,
                'timeout' => self::HTTP_TIMEOUT,
                'ignore_errors' => true,
            ],
        ]);
        $raw = @file_get_contents(self::KDNIAO_TRACK_URL, false, $context);
        if ($raw === false || $raw === '') {
            throw new \RuntimeException('快递鸟接口无响应');
        }

        $response = json_decode($raw, true);
        if (!is_array($response) || empty($response['Success'])) {
            throw new \RuntimeException((string)($response['Reason'] ?? '快递鸟返回失败'));
        }

        $traces = [];
        foreach (($response['Traces'] ?? []) as $trace) {
            $description = trim((string)($trace['AcceptStation'] ?? ''));
            if ($description === '') {
                continue;
            }
            $traces[] = [
                'time' => (string)($trace['AcceptTime'] ?? ''),
                'desc' => $description,
                'location' => (string)($trace['Location'] ?? ''),
                'action' => (string)($trace['Action'] ?? ''),
            ];
        }

        return $traces;
    }

    /**
     * 同步物流轨迹到数据库
     */
    public function syncTraces(int $logisticsId): bool
    {
        $logistics = $this->orderLogisticsRepository->findModel($logisticsId);
        if (!$logistics) {
            return false;
        }

        $expressCode = $this->expressCompanyRepository->resolveCode((string)$logistics->express_company);
        $traces      = $this->query($expressCode, (string)$logistics->express_no);

        if (!empty($traces)) {
            $logistics->traces = $traces;
            $logistics->status = $this->detectStatus($traces);
            $logistics->save();
            return true;
        }

        return false;
    }

    /**
     * 获取订单的物流轨迹
     *
     * @return array ['logistics' => [...], 'traces' => [...]]
     */
    public function getByOrderId(int $orderId): array
    {
        $logistics = $this->orderLogisticsRepository->findModelByOrderId($orderId);
        if (!$logistics) {
            return ['logistics' => null, 'traces' => []];
        }

        $logisticsData = $logistics->toArray();
        $traces        = $logisticsData['traces'] ?? [];

        // 如果 traces 为空，尝试实时查询
        if (empty($traces)) {
            $expressCode = $this->expressCompanyRepository->resolveCode((string)$logistics->express_company);
            $traces      = $this->query($expressCode, (string)$logistics->express_no);
            if (!empty($traces)) {
                $logistics->traces = $traces;
                $logistics->status = $this->detectStatus($traces);
                $logistics->save();
                $logisticsData = $logistics->toArray();
            }
        }

        return [
            'logistics' => $logisticsData,
            'traces'    => $traces,
        ];
    }

    /**
     * 获取面单配置（复用 waybill 配置组）
     */
    private function getWaybillConfig(): array
    {
        return $this->systemConfigRepository->getRawValuesByGroup('waybill');
    }

    /**
     * 根据轨迹检测物流状态
     * 0=待揽收, 1=运输中, 2=已签收
     */
    private function detectStatus(array $traces): int
    {
        if (empty($traces)) {
            return 0;
        }
        $latest = end($traces);
        $desc   = $latest['desc'] ?? '';
        if (str_contains($desc, '签收') || str_contains($desc, '已签收')) {
            return 2;
        }
        return 1;
    }
}
