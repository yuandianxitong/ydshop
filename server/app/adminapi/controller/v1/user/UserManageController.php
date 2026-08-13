<?php
declare(strict_types=1);
namespace app\adminapi\controller\v1\user;

use app\adminapi\validate\v1\user\UserManageValidate;
use app\service\user\UserManageService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '用户管理（旧版）', description: '老版用户管理接口，新接口请走 /member')]
class UserManageController extends Controller
{
    protected UserManageService $userManageService;

    #[OA\Get(
        path: '/user/manage/list',
        summary: '用户列表',
        security: [['bearerAuth' => []]],
        tags: ['用户管理（旧版）'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [new OA\Response(response: 200, description: 'ok', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    )]
    public function list(): Response { return $this->success('ok', $this->userManageService->getUserList($this->getRequestData())); }

    #[OA\Get(
        path: '/user/manage/{id}',
        summary: '用户详情',
        security: [['bearerAuth' => []]],
        tags: ['用户管理（旧版）'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'ok', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function detail(): Response
    {
        $result = $this->userManageService->getUserDetail((int) $this->request->param('id'));
        if (!$result) return $this->error('用户不存在');
        return $this->success('ok', $result);
    }

    #[OA\Post(
        path: '/user/manage/adjust-balance',
        summary: '调整余额',
        security: [['bearerAuth' => []]],
        tags: ['用户管理（旧版）'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id', 'amount'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'integer'),
                    new OA\Property(property: 'amount', type: 'number', format: 'float'),
                    new OA\Property(property: 'remark', type: 'string'),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: '调整成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function adjustBalance(): Response
    {
        $data = $this->request->post();
        $this->validate($data, UserManageValidate::class, [], false, 'adjustBalance');
        $this->userManageService->adjustBalance(
            (int) $data['user_id'], (float) $data['amount'],
            $data['remark'] ?? '',
            \app\model\user\BalanceLog::TYPE_ADMIN_ADJUST,
            'admin_adjust', $this->getUserId()
        );
        return $this->success('调整成功');
    }

    #[OA\Post(
        path: '/user/manage/adjust-points',
        summary: '调整积分',
        security: [['bearerAuth' => []]],
        tags: ['用户管理（旧版）'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id', 'points'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'integer'),
                    new OA\Property(property: 'points', type: 'integer'),
                    new OA\Property(property: 'remark', type: 'string'),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: '调整成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function adjustPoints(): Response
    {
        $data = $this->request->post();
        $this->validate($data, UserManageValidate::class, [], false, 'adjustPoints');
        $this->userManageService->adjustPoints(
            (int) $data['user_id'], (int) $data['points'],
            $data['remark'] ?? '',
            \app\model\user\PointsLog::TYPE_ADMIN_ADJUST,
            'admin_adjust', $this->getUserId()
        );
        return $this->success('调整成功');
    }

    #[OA\Put(
        path: '/user/manage/{id}/status',
        summary: '更新用户状态',
        security: [['bearerAuth' => []]],
        tags: ['用户管理（旧版）'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [new OA\Property(property: 'status', type: 'integer', enum: [0, 1])]
            )
        ),
        responses: [new OA\Response(response: 200, description: '操作成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function updateStatus(): Response
    {
        $id = (int) $this->request->param('id');
        $data = $this->request->post();
        $this->validate($data, UserManageValidate::class, [], false, 'status');
        $this->userManageService->updateStatus($id, (int) $data['status']);
        return $this->success('操作成功');
    }

    #[OA\Get(
        path: '/user/manage/balance-logs',
        summary: '余额流水',
        security: [['bearerAuth' => []]],
        tags: ['用户管理（旧版）'],
        responses: [new OA\Response(response: 200, description: 'ok', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    )]
    public function balanceLogs(): Response { return $this->success('ok', $this->userManageService->getBalanceLogs($this->getRequestData())); }

    #[OA\Get(
        path: '/user/manage/points-logs',
        summary: '积分流水',
        security: [['bearerAuth' => []]],
        tags: ['用户管理（旧版）'],
        responses: [new OA\Response(response: 200, description: 'ok', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    )]
    public function pointsLogs(): Response { return $this->success('ok', $this->userManageService->getPointsLogs($this->getRequestData())); }

    #[Permission('finance.balance.export')]
    #[OA\Get(
        path: '/user/manage/balance-logs/export',
        summary: '导出余额流水 xlsx',
        security: [['bearerAuth' => []]],
        tags: ['用户管理（旧版）'],
        responses: [new OA\Response(response: 200, description: '导出文件流')]
    )]
    public function balanceLogsExport(): \think\Response { return $this->userManageService->exportBalanceLogs($this->getRequestData()); }

    #[Permission('finance.points.export')]
    #[OA\Get(
        path: '/user/manage/points-logs/export',
        summary: '导出积分流水 xlsx',
        security: [['bearerAuth' => []]],
        tags: ['用户管理（旧版）'],
        responses: [new OA\Response(response: 200, description: '导出文件流')]
    )]
    public function pointsLogsExport(): \think\Response { return $this->userManageService->exportPointsLogs($this->getRequestData()); }
}
