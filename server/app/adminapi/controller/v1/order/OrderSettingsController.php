<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\order;

use app\service\system\SystemConfigService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '订单设置', description: '订单自动取消 / 自动确认 / 自动评价 / 退款期限')]
class OrderSettingsController extends Controller
{
    protected SystemConfigService $systemConfigService;

    private const KEYS = [
        'order.auto_cancel_minutes',
        'order.auto_confirm_days',
        'order.auto_review_days',
        'order.refund_deadline_days',
    ];

    private const DEFAULTS = [
        'order.auto_cancel_minutes'  => 30,
        'order.auto_confirm_days'    => 7,
        'order.auto_review_days'     => 15,
        'order.refund_deadline_days' => 15,
    ];

    #[Permission('order.settings.view')]
    #[OA\Get(
        path: '/order/settings',
        summary: '获取订单自动化设置',
        security: [['bearerAuth' => []]],
        tags: ['订单设置'],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function getSettings(): Response
    {
        $result = [];
        foreach (self::KEYS as $key) {
            $result[$key] = (int) $this->systemConfigService->getConfigValue($key, self::DEFAULTS[$key]);
        }
        return $this->success('获取成功', $result);
    }

    #[Permission('order.settings.update')]
    #[OA\Put(
        path: '/order/settings',
        summary: '更新订单自动化设置',
        security: [['bearerAuth' => []]],
        tags: ['订单设置'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'order.auto_cancel_minutes', type: 'integer', description: '订单自动取消（未支付）分钟数'),
                new OA\Property(property: 'order.auto_confirm_days', type: 'integer', description: '自动确认收货天数'),
                new OA\Property(property: 'order.auto_review_days', type: 'integer', description: '完成后多少天内可评价'),
                new OA\Property(property: 'order.refund_deadline_days', type: 'integer', description: '退款期限天数'),
            ])
        ),
        responses: [new OA\Response(response: 200, description: '保存成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function updateSettings(): Response
    {
        $data = $this->getRequestData();

        foreach (self::KEYS as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            $value = max(0, (int) $data[$key]);
            $this->systemConfigService->upsertConfigValue($key, $value, [
                'config_group' => 'order',
                'config_type'  => 'number',
                'config_name'  => $key,
            ]);
        }

        return $this->success('保存成功');
    }
}
