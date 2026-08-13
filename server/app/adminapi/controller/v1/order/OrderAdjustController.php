<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\order;

use app\adminapi\validate\v1\order\OrderAdjustValidate;
use app\service\order\OrderMergeService;
use app\service\order\OrderPriceAdjustService;
use app\service\order\OrderQueryService;
use app\service\order\OrderSplitService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '订单拆合改价', description: '订单拆分 / 合并 / 改价 / 调整日志')]
class OrderAdjustController extends Controller
{
    protected OrderSplitService $orderSplitService;
    protected OrderMergeService $orderMergeService;
    protected OrderPriceAdjustService $orderPriceAdjustService;
    protected OrderQueryService $orderQueryService;

    #[Permission('order.split')]
    #[OA\Post(
        path: '/order/order/{id}/split',
        summary: '拆分订单（仅已付款订单）',
        security: [['bearerAuth' => []]],
        tags: ['订单拆合改价'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['items'],
                properties: [
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        description: '要拆出的商品行 [{item_id, quantity}]',
                        items: new OA\Items(
                            required: ['item_id', 'quantity'],
                            properties: [
                                new OA\Property(property: 'item_id', type: 'integer'),
                                new OA\Property(property: 'quantity', type: 'integer', description: '拆出数量，1 ~ 行数量'),
                            ]
                        )
                    ),
                    new OA\Property(property: 'remark', type: 'string', description: '调整备注（最多 255 字）'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '拆分成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '状态不允许 / 参数错误', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function split(): Response
    {
        $id   = (int)$this->request->param('id');
        $data = $this->validate($this->request->post(), OrderAdjustValidate::class, [], false, 'split');
        $result = $this->orderSplitService->split(
            $id,
            (array)$data['items'],
            $this->getUserId(),
            (string)($data['remark'] ?? '')
        );
        return $this->success('拆分成功', $result);
    }

    #[Permission('order.merge')]
    #[OA\Post(
        path: '/order/order/merge',
        summary: '合并订单（仅同用户待付款订单）',
        security: [['bearerAuth' => []]],
        tags: ['订单拆合改价'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['order_ids'],
                properties: [
                    new OA\Property(
                        property: 'order_ids',
                        type: 'array',
                        description: '要合并的订单 id（至少两个）',
                        items: new OA\Items(type: 'integer')
                    ),
                    new OA\Property(property: 'remark', type: 'string', description: '调整备注（最多 255 字）'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '合并成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '状态不允许 / 参数错误', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function merge(): Response
    {
        $data = $this->validate($this->request->post(), OrderAdjustValidate::class, [], false, 'merge');
        $result = $this->orderMergeService->merge(
            (array)$data['order_ids'],
            $this->getUserId(),
            (string)($data['remark'] ?? '')
        );
        return $this->success('合并成功', $result);
    }

    #[Permission('order.price-adjust')]
    #[OA\Post(
        path: '/order/order/{id}/price-adjust',
        summary: '订单改价（仅待付款订单）',
        security: [['bearerAuth' => []]],
        tags: ['订单拆合改价'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['items'],
                properties: [
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        description: '要改价的商品行 [{item_id, price}]，可只传部分行',
                        items: new OA\Items(
                            required: ['item_id', 'price'],
                            properties: [
                                new OA\Property(property: 'item_id', type: 'integer'),
                                new OA\Property(property: 'price', type: 'string', description: '新单价（元，最多两位小数）'),
                            ]
                        )
                    ),
                    new OA\Property(property: 'freight_amount', type: 'string', description: '新运费（不传沿用原值）'),
                    new OA\Property(property: 'discount_amount', type: 'string', description: '新优惠（不传沿用原值）'),
                    new OA\Property(property: 'remark', type: 'string', description: '调整备注（最多 255 字）'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '改价成功，返回最新订单详情', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '状态不允许 / 参数错误', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function priceAdjust(): Response
    {
        $id   = (int)$this->request->param('id');
        $data = $this->validate($this->request->post(), OrderAdjustValidate::class, [], false, 'price_adjust');
        $detail = $this->orderPriceAdjustService->adjust(
            $id,
            (array)$data['items'],
            isset($data['freight_amount']) ? (string)$data['freight_amount'] : null,
            isset($data['discount_amount']) ? (string)$data['discount_amount'] : null,
            $this->getUserId(),
            (string)($data['remark'] ?? '')
        );
        return $this->success('改价成功', $detail);
    }

    #[Permission('order.list')]
    #[OA\Get(
        path: '/order/order/{id}/adjust-logs',
        summary: '订单调整日志（拆分/合并/改价）',
        security: [['bearerAuth' => []]],
        tags: ['订单拆合改价'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function adjustLogs(): Response
    {
        $id = (int)$this->request->param('id');
        return $this->success('获取成功', $this->orderQueryService->getAdjustLogs($id));
    }
}
