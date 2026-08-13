<?php
declare(strict_types=1);

namespace app\api\controller\v1\member;

use app\api\validate\v1\member\AddressValidate;
use app\service\member\MemberAddressService;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '收货地址', description: '用户收货地址 CRUD')]
class AddressController extends Controller
{
    protected MemberAddressService $addressService;

    /**
     * 收货地址列表
     */
    #[OA\Get(
        path: '/address',
        summary: '收货地址列表',
        security: [['bearerAuth' => []]],
        tags: ['收货地址'],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function index(): Response
    {
        try {
            $userId = $this->getUserId();
            $list   = $this->addressService->getList($userId);
            return $this->success('success', $list);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 新增收货地址
     */
    #[OA\Post(
        path: '/address',
        summary: '新增收货地址',
        security: [['bearerAuth' => []]],
        tags: ['收货地址'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'phone', 'province', 'city', 'detail'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', description: '收货人'),
                    new OA\Property(property: 'phone', type: 'string', description: '手机号（兼容旧字段 mobile）'),
                    new OA\Property(property: 'province', type: 'string'),
                    new OA\Property(property: 'city', type: 'string'),
                    new OA\Property(property: 'district', type: 'string'),
                    new OA\Property(property: 'detail', type: 'string', description: '详细地址'),
                    new OA\Property(property: 'lng', type: 'number', format: 'float', description: '经度（同城配送用）'),
                    new OA\Property(property: 'lat', type: 'number', format: 'float', description: '纬度'),
                    new OA\Property(property: 'is_default', type: 'integer', enum: [0, 1]),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '添加成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function store(): Response
    {
        try {
            $userId = $this->getUserId();
            $data   = $this->normalizePhone($this->request->only([
                'name', 'phone', 'mobile', 'province', 'city', 'district', 'detail',
                'lng', 'lat', 'is_default',
            ]));
            $data = $this->validate($data, AddressValidate::class, [], false, 'store');

            $address = $this->addressService->create($userId, $data);
            return $this->success('添加成功', $address);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 更新收货地址
     */
    #[OA\Put(
        path: '/address/{id}',
        summary: '更新收货地址',
        security: [['bearerAuth' => []]],
        tags: ['收货地址'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'phone', type: 'string'),
                new OA\Property(property: 'province', type: 'string'),
                new OA\Property(property: 'city', type: 'string'),
                new OA\Property(property: 'district', type: 'string'),
                new OA\Property(property: 'detail', type: 'string'),
                new OA\Property(property: 'lng', type: 'number', format: 'float'),
                new OA\Property(property: 'lat', type: 'number', format: 'float'),
                new OA\Property(property: 'is_default', type: 'integer', enum: [0, 1]),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: '更新成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function update(): Response
    {
        try {
            $id     = (int) $this->request->param('id');
            $userId = $this->getUserId();
            $data   = $this->normalizePhone($this->request->only([
                'name', 'phone', 'mobile', 'province', 'city', 'district', 'detail',
                'lng', 'lat', 'is_default',
            ]));
            $data = $this->validate($data, AddressValidate::class, [], false, 'update');

            $address = $this->addressService->update($id, $userId, $data);
            return $this->success('更新成功', $address);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 删除收货地址
     */
    #[OA\Delete(
        path: '/address/{id}',
        summary: '删除收货地址',
        security: [['bearerAuth' => []]],
        tags: ['收货地址'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '删除成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function delete(): Response
    {
        try {
            $id     = (int) $this->request->param('id');
            $userId = $this->getUserId();

            $this->addressService->delete($id, $userId);
            return $this->success('删除成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 字段名兼容：旧版 mobile → 新版 phone（v2.4.0+）
     */
    private function normalizePhone(array $data): array
    {
        if (empty($data['phone'] ?? '') && !empty($data['mobile'] ?? '')) {
            $data['phone'] = $data['mobile'];
        }
        unset($data['mobile']);
        return $data;
    }

    /**
     * 获取默认收货地址
     */
    #[OA\Get(
        path: '/address/default',
        summary: '默认收货地址',
        security: [['bearerAuth' => []]],
        tags: ['收货地址'],
        responses: [
            new OA\Response(response: 200, description: '获取成功（无默认地址时返回 null）', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function getDefault(): Response
    {
        try {
            $userId  = $this->getUserId();
            $address = $this->addressService->getDefault($userId);
            return $this->success('success', $address);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
