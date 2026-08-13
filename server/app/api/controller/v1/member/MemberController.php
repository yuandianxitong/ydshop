<?php
declare(strict_types=1);

namespace app\api\controller\v1\member;

use app\service\member\MemberLevelService;
use app\service\user\UserManageService;
use app\service\user\UserService;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '会员中心', description: '资料 / 积分明细 / 余额明细 / 等级')]
class MemberController extends Controller
{
    protected UserManageService $userManageService;
    protected MemberLevelService $memberLevelService;
    protected UserService $userService;

    /**
     * 获取当前用户资料（含会员等级信息）
     */
    #[OA\Get(
        path: '/member/profile',
        summary: '当前用户资料',
        security: [['bearerAuth' => []]],
        tags: ['会员中心'],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function profile(): Response
    {
        try {
            $userId = $this->getUserId();
            $data   = $this->userService->getProfile($userId);
            return $this->success('success', $data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 积分明细（分页）
     */
    #[OA\Get(
        path: '/member/points-log',
        summary: '积分明细',
        security: [['bearerAuth' => []]],
        tags: ['会员中心'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse')),
        ]
    )]
    public function pointsLog(): Response
    {
        try {
            $userId = $this->getUserId();
            $params = $this->request->only(['page', 'limit']);

            $result = $this->userManageService->getUserPointsLogs($userId, $params);
            return $this->success('success', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 余额明细（分页）
     */
    #[OA\Get(
        path: '/member/balance-log',
        summary: '余额明细',
        security: [['bearerAuth' => []]],
        tags: ['会员中心'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse')),
        ]
    )]
    public function balanceLog(): Response
    {
        try {
            $userId = $this->getUserId();
            $params = $this->request->only(['page', 'limit']);

            $result = $this->userManageService->getUserBalanceLogs($userId, $params);
            return $this->success('success', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 会员等级体系（C 端，仅启用，按成长值升序）
     */
    #[OA\Get(
        path: '/member/levels',
        summary: '会员等级体系',
        security: [['bearerAuth' => []]],
        tags: ['会员中心'],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function levels(): Response
    {
        try {
            $list = $this->memberLevelService->getEnabledList();
            return $this->success('success', $list);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
