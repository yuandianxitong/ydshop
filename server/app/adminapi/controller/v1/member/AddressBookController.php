<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\member;

use app\adminapi\validate\v1\member\AddressBookValidate;
use app\service\member\AddressBookService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '会员收货地址', description: '会员收货地址管理（admin）')]
class AddressBookController extends Controller
{
    protected AddressBookService $addressBookService;

    #[Permission('member.address.list')]
    #[OA\Get(
        path: '/member/address',
        summary: '收货地址列表（分页）',
        security: [['bearerAuth' => []]],
        tags: ['会员收货地址'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'user_id', in: 'query', description: '按会员过滤', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'keyword', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    )]
    public function index(): Response
    {
        return $this->paginate($this->addressBookService->getList($this->getRequestData()));
    }

    #[Permission('member.address.list')]
    #[OA\Get(
        path: '/member/address/stats',
        summary: '收货地址统计',
        security: [['bearerAuth' => []]],
        tags: ['会员收货地址'],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function stats(): Response { return $this->success('获取成功', $this->addressBookService->getStats()); }

    #[Permission('member.address.delete')]
    #[OA\Delete(
        path: '/member/address/{id}',
        summary: '删除会员收货地址',
        security: [['bearerAuth' => []]],
        tags: ['会员收货地址'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '删除成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function delete(): Response
    {
        $this->addressBookService->delete((int)$this->request->param('id'));
        return $this->success('删除成功');
    }

    #[Permission('member.address.update')]
    #[OA\Post(
        path: '/member/address',
        summary: '新增会员收货地址',
        security: [['bearerAuth' => []]],
        tags: ['会员收货地址'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id', 'name', 'phone', 'province', 'city', 'detail'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'integer'),
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'phone', type: 'string'),
                    new OA\Property(property: 'province', type: 'string'),
                    new OA\Property(property: 'city', type: 'string'),
                    new OA\Property(property: 'district', type: 'string'),
                    new OA\Property(property: 'detail', type: 'string'),
                    new OA\Property(property: 'is_default', type: 'integer', enum: [0, 1]),
                    new OA\Property(property: 'lng', type: 'number', format: 'float'),
                    new OA\Property(property: 'lat', type: 'number', format: 'float'),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: '创建成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function store(): Response
    {
        $data = $this->validate($this->request->post(), AddressBookValidate::class, [], false, 'store');
        return $this->success('创建成功', $this->addressBookService->create($data));
    }

    #[Permission('member.address.update')]
    #[OA\Put(
        path: '/member/address/{id}',
        summary: '更新会员收货地址',
        security: [['bearerAuth' => []]],
        tags: ['会员收货地址'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'phone', type: 'string'),
                new OA\Property(property: 'province', type: 'string'),
                new OA\Property(property: 'city', type: 'string'),
                new OA\Property(property: 'district', type: 'string'),
                new OA\Property(property: 'detail', type: 'string'),
                new OA\Property(property: 'is_default', type: 'integer', enum: [0, 1]),
            ])
        ),
        responses: [new OA\Response(response: 200, description: '保存成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function update(): Response
    {
        $id   = (int)$this->request->param('id');
        $data = $this->validate((array)$this->request->put(), AddressBookValidate::class, [], false, 'update');
        $this->addressBookService->update($id, $data);
        return $this->success('保存成功');
    }

    #[Permission('member.address.update')]
    #[OA\Post(
        path: '/member/address/{id}/default',
        summary: '设为默认地址',
        security: [['bearerAuth' => []]],
        tags: ['会员收货地址'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '已设为默认', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function setDefault(): Response
    {
        $this->addressBookService->setDefault((int)$this->request->param('id'));
        return $this->success('已设为默认');
    }

    #[Permission('member.address.export')]
    #[OA\Get(
        path: '/member/address/export',
        summary: '导出地址 xlsx',
        security: [['bearerAuth' => []]],
        tags: ['会员收货地址'],
        responses: [new OA\Response(response: 200, description: '导出文件流')]
    )]
    public function export(): Response { return $this->addressBookService->exportXlsx($this->getRequestData()); }
}
