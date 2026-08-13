<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\order;

use app\adminapi\validate\v1\order\OrderShipValidate;
use app\service\delivery\TrackingService;
use app\service\delivery\WaybillService;
use app\service\order\OrderShipService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '订单发货', description: '发货 / 修改物流 / 物流追踪 / 批量电子面单')]
class OrderShipController extends Controller
{
    protected OrderShipService $orderShipService;
    protected TrackingService $trackingService;
    protected WaybillService $waybillService;

    #[Permission('order.ship')]
    #[OA\Post(
        path: '/order/ship',
        summary: '订单发货',
        security: [['bearerAuth' => []]],
        tags: ['订单发货'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['order_id', 'delivery_mode'],
                properties: [
                    new OA\Property(property: 'order_id', type: 'integer'),
                    new OA\Property(property: 'delivery_mode', type: 'string', description: 'express|none'),
                    new OA\Property(property: 'ship_mode', type: 'string', description: 'manual|waybill'),
                    new OA\Property(property: 'express_company', type: 'string'),
                    new OA\Property(property: 'express_no', type: 'string'),
                    new OA\Property(property: 'waybill_template_id', type: 'integer'),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: '发货成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function ship(): Response
    {
        $data = $this->request->post();
        if (empty($data['delivery_mode']) && !empty($data['express_company']) && !empty($data['express_no'])) {
            $data['delivery_mode'] = 'express';
            $data['ship_mode'] = $data['ship_mode'] ?? 'manual';
        }
        $this->validate($data, OrderShipValidate::class);
        $result = $this->orderShipService->ship((int)($data['order_id'] ?? 0), [
            'delivery_mode'       => (string)($data['delivery_mode'] ?? 'express'),
            'ship_mode'           => (string)($data['ship_mode'] ?? 'manual'),
            'express_company'     => (string)($data['express_company'] ?? ''),
            'express_no'          => (string)($data['express_no'] ?? ''),
            'waybill_template_id' => (int)($data['waybill_template_id'] ?? 0),
        ]);
        return $this->success('发货成功', $result);
    }

    #[Permission('order.ship')]
    #[OA\Put(
        path: '/order/ship/{id}/logistics',
        summary: '修改物流信息',
        security: [['bearerAuth' => []]],
        tags: ['订单发货'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, description: '订单 ID', schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'express_company', type: 'string'),
                new OA\Property(property: 'express_no', type: 'string'),
            ])
        ),
        responses: [new OA\Response(response: 200, description: '更新成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function updateLogistics(): Response
    {
        $id             = (int) $this->request->param('id');
        $expressCompany = (string) $this->request->put('express_company', '');
        $expressNo      = (string) $this->request->put('express_no', '');
        $this->orderShipService->updateLogistics($id, $expressCompany, $expressNo);
        return $this->success('更新成功');
    }

    #[Permission('order.list')]
    #[OA\Get(
        path: '/order/ship/{id}/tracking',
        summary: '物流轨迹查询',
        security: [['bearerAuth' => []]],
        tags: ['订单发货'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, description: '订单 ID', schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '获取成功，含 logistics + traces', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function tracking(): Response
    {
        $orderId = (int) $this->request->param('id');
        return $this->success('获取成功', $this->trackingService->getByOrderId($orderId));
    }

    #[Permission('order.waybill.print')]
    #[OA\Post(
        path: '/order/waybill/batch-generate',
        summary: '批量生成电子面单',
        security: [['bearerAuth' => []]],
        tags: ['订单发货'],
        description: '单次最多 50 单',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['order_ids'],
                properties: [new OA\Property(property: 'order_ids', type: 'array', items: new OA\Items(type: 'integer'))]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '生成完成，返回 success/failed 数组', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '参数缺失或超过 50 单', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function batchGenerateWaybill(): Response
    {
        $orderIds = (array)$this->request->post('order_ids', []);
        if (empty($orderIds)) {
            return $this->error('请选择订单');
        }
        if (count($orderIds) > 50) {
            return $this->error('单次最多 50 单');
        }
        $result = $this->waybillService->batchGenerate($orderIds);
        return $this->success('生成完成', $result);
    }
}
