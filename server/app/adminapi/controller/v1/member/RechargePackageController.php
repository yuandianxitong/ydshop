<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\member;

use app\adminapi\validate\v1\member\RechargePackageValidate;
use app\service\member\RechargePackageService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '充值套餐', description: '余额充值套餐配置')]
class RechargePackageController extends Controller
{
    protected RechargePackageService $rechargePackageService;

    #[Permission('member.recharge_package.list')]
    #[OA\Get(
        path: '/member/recharge-package',
        summary: '充值套餐列表（分页）',
        security: [['bearerAuth' => []]],
        tags: ['充值套餐'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'integer', enum: [0, 1])),
        ],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    )]
    public function index(): Response { return $this->paginate($this->rechargePackageService->getList($this->getRequestData())); }

    #[Permission('member.recharge_package.create')]
    #[OA\Post(
        path: '/member/recharge-package',
        summary: '创建充值套餐',
        security: [['bearerAuth' => []]],
        tags: ['充值套餐'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['amount'],
                properties: [
                    new OA\Property(property: 'amount', type: 'number', format: 'float', description: '充值金额 > 0 且 ≤ 100000'),
                    new OA\Property(property: 'gift_amount', type: 'number', format: 'float', description: '赠送金额'),
                    new OA\Property(property: 'gift_points', type: 'integer', description: '赠送积分'),
                    new OA\Property(property: 'sort', type: 'integer'),
                    new OA\Property(property: 'status', type: 'integer', enum: [0, 1]),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: '创建成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function store(): Response
    {
        $data = $this->validate($this->request->post(), RechargePackageValidate::class, [], false, 'store');
        return $this->success('创建成功', $this->rechargePackageService->create($data));
    }

    #[Permission('member.recharge_package.update')]
    #[OA\Put(
        path: '/member/recharge-package/{id}',
        summary: '更新充值套餐',
        security: [['bearerAuth' => []]],
        tags: ['充值套餐'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '更新成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function update(): Response
    {
        $id   = (int) $this->request->param('id');
        $data = $this->validate($this->request->post(), RechargePackageValidate::class, [], false, 'update');
        return $this->success('更新成功', $this->rechargePackageService->update($id, $data));
    }

    #[Permission('member.recharge_package.delete')]
    #[OA\Delete(
        path: '/member/recharge-package/{id}',
        summary: '删除充值套餐',
        security: [['bearerAuth' => []]],
        tags: ['充值套餐'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '删除成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function delete(): Response
    {
        $this->rechargePackageService->delete((int) $this->request->param('id'));
        return $this->success('删除成功');
    }
}
