<?php
declare(strict_types=1);

namespace app\api\controller\v1\order;

use app\service\order\OrderRefundReasonService;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '订单退款原因', description: '退款原因字典（公开）')]
class OrderRefundReasonController extends Controller
{
    protected OrderRefundReasonService $orderRefundReasonService;

    /**
     * 获取可用退款原因列表（公开，无需登录）
     */
    #[OA\Get(
        path: '/order-refund/reasons',
        summary: '可用退款原因列表',
        tags: ['订单退款原因'],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function list(): Response
    {
        $list = $this->orderRefundReasonService->getActiveList();
        return $this->success('获取成功', $list);
    }
}
