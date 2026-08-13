<?php
declare(strict_types=1);

namespace app\service\delivery;

use app\repository\order\OrderLogisticsRepository;
use app\repository\order\OrderOrderRepository;
use app\repository\delivery\ExpressCompanyRepository;
use app\repository\delivery\WaybillTemplateRepository;
use app\repository\goods\GoodsSkuRepository;
use app\repository\system\SystemConfigRepository;
use app\service\delivery\waybill\KdniaoProvider;
use app\service\delivery\waybill\WaybillProviderInterface;
use app\service\delivery\waybill\WaybillResult;
use core\base\Service;

class WaybillService extends Service
{
    protected SystemConfigRepository $systemConfigRepository;
    protected OrderOrderRepository $orderOrderRepository;
    protected OrderLogisticsRepository $orderLogisticsRepository;
    protected ExpressCompanyRepository $expressCompanyRepository;
    protected WaybillTemplateRepository $waybillTemplateRepository;
    protected GoodsSkuRepository $goodsSkuRepository;

    public function getConfig(): array
    {
        return $this->systemConfigRepository->getRawValuesByGroup('waybill');
    }

    public function isEnabled(): bool
    {
        $cfg = $this->getConfig();
        return !empty($cfg['waybill_enabled']) && $cfg['waybill_enabled'] !== '0';
    }

    /**
     * 生成单张面单。
     *
     * @param int         $orderId    订单 ID
     * @param string      $expressCode 公司名称或编码（无模版时使用）
     * @param int|null    $templateId 面单模版 ID（优先）
     */
    public function generate(int $orderId, string $expressCode = '', ?int $templateId = null): WaybillResult
    {
        if (!$this->isEnabled()) {
            return WaybillResult::fail('电子面单未启用');
        }

        $order = $this->orderOrderRepository->findWithItems($orderId);
        if (!$order) {
            return WaybillResult::fail('订单不存在');
        }

        $template = null;
        if ($templateId !== null && $templateId > 0) {
            $template = $this->waybillTemplateRepository->find($templateId);
            if (!$template || (int)($template['status'] ?? 0) !== 1) {
                return WaybillResult::fail('面单模版不存在或已禁用');
            }
            $expressCode = (string)$template['express_code'];
        }

        // 管理端保存的是快递公司名称，provider 需要标准编码。
        $expressCode = $this->expressCompanyRepository->resolveCode($expressCode);
        $snap = is_array($order['address_snapshot'] ?? null) ? $order['address_snapshot'] : [];
        if (empty($snap)) {
            return WaybillResult::fail('订单缺少收货地址');
        }

        $cfg     = $this->getConfig();
        $sender  = [
            'name'     => (string)($cfg['waybill_sender_name']     ?? ''),
            'phone'    => (string)($cfg['waybill_sender_phone']    ?? ''),
            'province' => (string)($cfg['waybill_sender_province'] ?? ''),
            'city'     => (string)($cfg['waybill_sender_city']     ?? ''),
            'district' => (string)($cfg['waybill_sender_district'] ?? ''),
            'detail'   => (string)($cfg['waybill_sender_detail'] ?? $cfg['waybill_sender_address'] ?? ''),
        ];
        $receiver = [
            'name'     => (string)($snap['name']     ?? ''),
            'phone'    => (string)($snap['phone']    ?? ''),
            'province' => (string)($snap['province'] ?? ''),
            'city'     => (string)($snap['city']     ?? ''),
            'district' => (string)($snap['district'] ?? ''),
            'detail'   => (string)($snap['detail']   ?? ''),
        ];

        $items = array_values(array_filter(
            (array)($order['items'] ?? []),
            static fn (array $item): bool => (int)($item['refund_status'] ?? 0) === 0
        ));
        if ($items === []) {
            return WaybillResult::fail('订单没有可发货商品');
        }
        $skuMap = [];
        foreach ($this->goodsSkuRepository->findByIds(array_column($items, 'sku_id')) as $sku) {
            $skuMap[(int)$sku['id']] = $sku;
        }
        $goodsNames = [];
        $weight = 0.0;
        foreach ($items as $item) {
            $quantity = max(1, (int)($item['quantity'] ?? 1));
            $goodsNames[] = (string)($item['goods_name'] ?? '商品') . '×' . $quantity;
            $weight += (float)($skuMap[(int)($item['sku_id'] ?? 0)]['weight'] ?? 0) * $quantity;
        }

        $orderData = [
            'order_no'       => (string)$order['order_no'],
            'sender'         => $sender,
            'receiver'       => $receiver,
            'goods_name'     => mb_substr(implode('；', array_unique($goodsNames)) ?: '商品', 0, 100),
            'weight'         => round($weight, 3),
            'express_code'   => $expressCode,
            'exp_type'       => (string)($template['exp_type'] ?? '1'),
            'template_size'  => (string)($template['template_size'] ?? ''),
            'pay_type'       => (int)($template['pay_type'] ?? 1),
            'need_pickup'    => (int)($template['need_pickup'] ?? 0),
            'express_name'   => (string)($template['express_name'] ?? ''),
        ];

        $provider = $this->getProvider();
        if (!$provider) {
            return WaybillResult::fail('未配置或未实现的 waybill_provider');
        }

        $result = $provider->generate($orderData);
        if ($result->success) {
            // 有模版时回写展示名；无模版时不覆盖已有 express_company（管理端多为中文名）
            $companyName = is_array($template) ? (string)($template['express_name'] ?? '') : '';
            $this->writeWaybillNo($orderId, $result->waybillNo, $companyName);
        }
        return $result;
    }

    /**
     * 批量生成（同步串行）
     *
     * @return array {
     *   success: array<{ order_id, waybill_no, print_template_html }>,
     *   failed:  array<{ order_id, reason }>,
     * }
     */
    public function batchGenerate(array $orderIds): array
    {
        $success = [];
        $failed  = [];
        foreach ($orderIds as $orderId) {
            $orderId     = (int)$orderId;
            $expressCode = $this->resolveExpressCode($orderId);
            $result      = $this->generate($orderId, $expressCode);
            if ($result->success) {
                $success[] = [
                    'order_id'            => $orderId,
                    'waybill_no'          => $result->waybillNo,
                    'print_template_html' => $result->printTemplateHtml,
                ];
            } else {
                $failed[] = ['order_id' => $orderId, 'reason' => $result->errorMessage];
            }
        }
        return ['success' => $success, 'failed' => $failed];
    }

    /** 测试和后续 provider 扩展可替换的解析点。 */
    protected function getProvider(): ?WaybillProviderInterface
    {
        $providerKey = (string)$this->systemConfigRepository->getConfigValue('waybill_provider', '');
        return match ($providerKey) {
            'kdniao' => app(KdniaoProvider::class),
            default  => null,
        };
    }

    private function resolveExpressCode(int $orderId): string
    {
        $logistics = $this->orderLogisticsRepository->findByOrderId($orderId);
        if (!$logistics) {
            return '';
        }
        $company = (string)($logistics['express_company'] ?? '');
        return $this->expressCompanyRepository->resolveCode($company);
    }

    private function writeWaybillNo(int $orderId, string $waybillNo, string $expressCompany = ''): void
    {
        $payload = [
            'waybill_no' => $waybillNo,
            'express_no' => $waybillNo,
        ];
        if ($expressCompany !== '') {
            $payload['express_company'] = $expressCompany;
        }
        $this->orderLogisticsRepository->upsertWaybillByOrderId($orderId, $payload);
    }
}
