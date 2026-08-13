<?php
/* ============================================================
 * 项目：元点Shop
 * 官网：https://www.dev007.cn
 * Slogan：提供高质量行业系统源码，帮助中小企业快速搭建专属应用
 * Author：mashanglai Team
 * ============================================================ */
declare(strict_types=1);

namespace app\api\controller\v1\order;

use app\api\validate\v1\order\OrderValidate;
use app\service\delivery\TrackingService;
use app\service\order\OrderQueryService;
use app\service\order\OrderService;
use app\service\order\OrderShipService;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '订单', description: '订单创建、试算、取消、收货、查询')]
class OrderController extends Controller
{
    protected OrderService $orderService;
    protected OrderShipService $orderShipService;
    protected OrderQueryService $orderQueryService;
    protected TrackingService $trackingService;

    /**
     * 创建订单
     */
    #[OA\Post(
        path: '/order',
        summary: '创建订单',
        security: [['bearerAuth' => []]],
        tags: ['订单'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['items'],
                properties: [
                    new OA\Property(property: 'items', type: 'array', description: '商品行 [{sku_id, quantity, flash_item_id?, group_activity_id?, group_id?}]', items: new OA\Items(type: 'object')),
                    new OA\Property(property: 'address', type: 'object', description: '收货地址快照（自提单可省）'),
                    new OA\Property(property: 'buyer_remark', type: 'string', description: '买家备注（兼容旧字段 remark）'),
                    new OA\Property(property: 'coupon_user_id', type: 'integer', description: '使用的优惠券（用户已领的 coupon_user.id），0=不使用'),
                    new OA\Property(property: 'delivery_type', type: 'string', enum: ['express', 'merchant'], description: '配送方式：express=快递 / merchant=同城自配送'),
                    new OA\Property(property: 'pickup_store_id', type: 'integer', description: '自提门店 ID（自提单时必填）'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '下单成功，返回订单详情', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '校验失败 / 业务异常', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function create(): Response
    {
        try {
            $data = $this->validate($this->request->post(), OrderValidate::class, [], false, 'create');
            $userId  = $this->getUserId();
            $items   = (array)($data['items'] ?? []);
            $address = (array)($data['address'] ?? []);
            $remark  = (string)($data['buyer_remark'] ?? $data['remark'] ?? '');
            $extra   = [
                'coupon_user_id'  => (int)($data['coupon_user_id'] ?? 0),
                'delivery_type'   => (string)($data['delivery_type'] ?? 'express'),
                'pickup_store_id' => (int)($data['pickup_store_id'] ?? 0),
            ];

            $order = $this->orderService->create($userId, $items, $address, $remark, $extra);
            return $this->success('下单成功', $order);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 订单试算
     */
    #[OA\Post(
        path: '/order/calc',
        summary: '订单试算（不创建订单）',
        security: [['bearerAuth' => []]],
        tags: ['订单'],
        description: 'cart 和 checkout 页面预下单使用，传 skus 数组算商品金额 / 运费 / 优惠 / 应付。skus 为空时直接返回 0',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'skus', type: 'array', description: '[{sku_id, quantity, flash_item_id?, group_activity_id?, group_id?}]', items: new OA\Items(type: 'object')),
                    new OA\Property(property: 'coupon_user_id', type: 'integer', description: '优惠券领取记录 id'),
                    new OA\Property(property: 'delivery_type', type: 'string', enum: ['express', 'merchant'], description: '配送方式（merchant 需要 lng/lat 算同城运费）'),
                    new OA\Property(property: 'lng', type: 'number', format: 'float', description: '收货点经度（merchant 时用）'),
                    new OA\Property(property: 'lat', type: 'number', format: 'float', description: '收货点纬度（merchant 时用）'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '返回 goods_amount / freight_amount / discount_amount / pay_amount', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function calc(): Response
    {
        try {
            $raw = $this->request->post();
            if (empty($raw['skus'] ?? [])) {
                return $this->success('获取成功', [
                    'goods_amount'    => '0.00',
                    'freight_amount'  => '0.00',
                    'discount_amount' => '0.00',
                    'pay_amount'      => '0.00',
                ]);
            }

            $data         = $this->validate($raw, OrderValidate::class, [], false, 'calc');
            $userId       = $this->getUserId();
            $skus         = (array)$data['skus'];
            $couponUserId = (int)($data['coupon_user_id'] ?? 0);
            $deliveryType = (string)($data['delivery_type'] ?? 'express');
            $lng          = isset($data['lng']) ? (float)$data['lng'] : null;
            $lat          = isset($data['lat']) ? (float)$data['lat'] : null;
            $regionCode   = isset($data['region_code']) ? (string)$data['region_code'] : null;
            $province     = isset($data['province']) ? (string)$data['province'] : null;

            $result = $this->orderService->calculate(
                $userId,
                $skus,
                $couponUserId,
                $deliveryType,
                $lng,
                $lat,
                $regionCode,
                $province
            );
            return $this->success('获取成功', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 当前用户各状态订单数（我的页 quick entry 上标用）
     */
    #[OA\Get(
        path: '/order/counts',
        summary: '我的订单各状态计数',
        security: [['bearerAuth' => []]],
        tags: ['订单'],
        description: '返回 { pending, paid, shipped, refunding }；已完成不打标',
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function counts(): Response
    {
        try {
            $userId = $this->getUserId();
            $counts = $this->orderQueryService->getCountsForUser($userId);
            return $this->success('success', $counts);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    #[OA\Get(
        path: '/order',
        summary: '我的订单列表',
        security: [['bearerAuth' => []]],
        tags: ['订单'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', description: '订单状态（pending/paid/shipped/completed/cancelled/closed）', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse')),
        ]
    )]
    public function index(): Response
    {
        try {
            $userId = $this->getUserId();
            $params = $this->request->only(['status', 'page', 'limit', 'page_no', 'page_size']);
            $params['user_id'] = $userId;

            $result = $this->orderQueryService->getList($params);
            return $this->success('success', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 订单详情
     */
    #[OA\Get(
        path: '/order/{id}',
        summary: '订单详情',
        security: [['bearerAuth' => []]],
        tags: ['订单'],
        description: 'id 可以是数字订单 ID，也可以是订单号（长度 ≥ 14 自动按 order_no 解析）',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '订单 ID 或订单号', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '订单不存在', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(): Response
    {
        try {
            $idOrNo = (string)$this->request->param('id', '');
            if ($idOrNo === '') {
                return $this->error('参数缺失');
            }
            $userId = $this->getUserId();
            $detail = $this->orderQueryService->getUserDetailByIdOrNo($userId, $idOrNo);
            return $this->success('success', $detail);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 物流轨迹
     */
    #[OA\Get(
        path: '/order/{id}/tracking',
        summary: '订单物流轨迹',
        security: [['bearerAuth' => []]],
        tags: ['订单'],
        description: '返回 { logistics: {...}|null, traces: [{time, desc, location, action}] }；未发货订单 logistics 为 null',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '订单 ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '订单不存在', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function tracking(): Response
    {
        try {
            $id     = (int) $this->request->param('id');
            $userId = $this->getUserId();

            $this->orderQueryService->assertOwnedByUser($userId, $id);
            $result = $this->trackingService->getByOrderId($id);
            return $this->success('success', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 取消订单
     */
    #[OA\Post(
        path: '/order/{id}/cancel',
        summary: '取消订单',
        security: [['bearerAuth' => []]],
        tags: ['订单'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'reason', type: 'string', description: '取消原因（可选，最多 255 字）'),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: '取消成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '订单状态不允许取消', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function cancel(): Response
    {
        try {
            $data   = $this->validate($this->request->post(), OrderValidate::class, [], false, 'cancel');
            $id     = (int) $this->request->param('id');
            $userId = $this->getUserId();
            $reason = (string)($data['reason'] ?? '');

            $this->orderService->cancel($id, $userId, $reason);
            return $this->success('取消成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 确认收货
     */
    #[OA\Post(
        path: '/order/{id}/confirm',
        summary: '确认收货',
        security: [['bearerAuth' => []]],
        tags: ['订单'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '确认收货成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '订单状态不允许确认收货', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function confirmReceive(): Response
    {
        try {
            $id     = (int) $this->request->param('id');
            $userId = $this->getUserId();

            $this->orderShipService->confirmReceive($id, $userId);
            return $this->success('确认收货成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
