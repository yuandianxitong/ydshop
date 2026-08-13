<?php
declare(strict_types=1);

namespace app\api\controller\v1\member;

use app\api\validate\v1\member\CartValidate;
use app\service\member\MemberCartService;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '购物车', description: '购物车增删改查 + 选中状态')]
class CartController extends Controller
{
    protected MemberCartService $cartService;

    /**
     * 购物车列表
     */
    #[OA\Get(
        path: '/cart',
        summary: '购物车列表',
        security: [['bearerAuth' => []]],
        tags: ['购物车'],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function index(): Response
    {
        try {
            $userId = $this->getUserId();
            $list   = $this->cartService->getList($userId);
            return $this->success('success', $list);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 加入购物车
     */
    #[OA\Post(
        path: '/cart/add',
        summary: '加入购物车',
        security: [['bearerAuth' => []]],
        tags: ['购物车'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['sku_id', 'quantity'],
                properties: [
                    new OA\Property(property: 'sku_id', type: 'integer', description: 'SKU ID'),
                    new OA\Property(property: 'quantity', type: 'integer', minimum: 1, maximum: 999, description: '数量'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '添加成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: 'SKU 不存在 / 库存不足', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function add(): Response
    {
        try {
            $data = $this->validate($this->request->post(), CartValidate::class, [], false, 'add');
            $item = $this->cartService->add($this->getUserId(), (int)$data['sku_id'], (int)$data['quantity']);
            return $this->success('添加成功', $item);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 更新购物车商品数量
     */
    #[OA\Put(
        path: '/cart/{id}',
        summary: '更新购物车数量',
        security: [['bearerAuth' => []]],
        tags: ['购物车'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '购物车条目 ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'quantity', type: 'integer', minimum: 1, maximum: 999),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: '更新成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function update(): Response
    {
        try {
            $data    = $this->validate($this->request->post(), CartValidate::class, [], false, 'update');
            $cartId  = (int) $this->request->param('id');
            $item    = $this->cartService->update($cartId, $this->getUserId(), (int)$data['quantity']);
            return $this->success('更新成功', $item);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 移除购物车商品
     */
    #[OA\Delete(
        path: '/cart/{id}',
        summary: '移除购物车',
        security: [['bearerAuth' => []]],
        tags: ['购物车'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '移除成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function remove(): Response
    {
        try {
            $cartId = (int) $this->request->param('id');
            $userId = $this->getUserId();

            $this->cartService->remove($cartId, $userId);
            return $this->success('移除成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 切换单条购物车条目选中状态
     */
    #[OA\Post(
        path: '/cart/{id}/toggle-select',
        summary: '切换选中状态',
        security: [['bearerAuth' => []]],
        tags: ['购物车'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '切换成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function toggleSelect(): Response
    {
        try {
            $cartId = (int) $this->request->param('id');
            $userId = $this->getUserId();

            $item = $this->cartService->toggleSelect($cartId, $userId);
            return $this->success('success', $item);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 全选/反选
     */
    #[OA\Post(
        path: '/cart/select-all',
        summary: '全选 / 取消全选',
        security: [['bearerAuth' => []]],
        tags: ['购物车'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'selected', type: 'boolean', description: 'true=全选 / false=取消全选（默认 true）'),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: '操作成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function selectAll(): Response
    {
        try {
            $data     = $this->validate($this->request->post(), CartValidate::class, [], false, 'select_all');
            $selected = (bool)($data['selected'] ?? true);
            $this->cartService->selectAll($this->getUserId(), $selected);
            return $this->success('success');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取已选中的购物车条目（用于结算）
     */
    #[OA\Get(
        path: '/cart/selected',
        summary: '已选中条目（结算页用）',
        security: [['bearerAuth' => []]],
        tags: ['购物车'],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function selectedItems(): Response
    {
        try {
            $userId = $this->getUserId();
            $items  = $this->cartService->getSelectedItems($userId);
            return $this->success('success', $items);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
