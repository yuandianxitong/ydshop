<?php
declare(strict_types=1);

namespace app\api\controller\v1\user;

use core\base\Controller;
use app\service\user\UserService;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '用户中心', description: '用户个人信息、余额、积分、充值')]
class UserController extends Controller
{
    protected UserService $userService;
    protected \app\service\user\UserManageService $userManageService;
    protected \app\service\member\MemberRechargeService $memberRechargeService;

    #[OA\Get(
        path: '/user/profile',
        summary: '获取个人信息',
        security: [['bearerAuth' => []]],
        tags: ['用户中心'],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function profile(): Response
    {
        try {
            $userInfo = $this->userService->getUserInfo($this->getUserId());
            return $this->success(lang('messages.get_success'), $userInfo);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    #[OA\Put(
        path: '/user/profile',
        summary: '更新个人信息',
        security: [['bearerAuth' => []]],
        tags: ['用户中心'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'nickname', type: 'string', description: '昵称'),
                new OA\Property(property: 'avatar', type: 'string', description: '头像URL'),
                new OA\Property(property: 'gender', type: 'integer', description: '性别(0未知 1男 2女)', enum: [0, 1, 2]),
                new OA\Property(property: 'birthday', type: 'string', description: '生日(Y-m-d)'),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: '更新成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function updateProfile(): Response
    {
        try {
            $data = $this->request->only(['nickname', 'avatar', 'gender', 'birthday']);
            $this->userService->updateProfile($this->getUserId(), $data);
            return $this->success(lang('messages.update_success'));
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    #[OA\Put(
        path: '/user/change-password',
        summary: '修改密码',
        security: [['bearerAuth' => []]],
        tags: ['用户中心'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['new_password'],
                properties: [
                    new OA\Property(property: 'old_password', type: 'string', description: '旧密码（已设置密码时必填）'),
                    new OA\Property(property: 'new_password', type: 'string', description: '新密码（至少6位）'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '修改成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function changePassword(): Response
    {
        try {
            $oldPassword = (string)$this->request->param('old_password', '');
            $newPassword = (string)$this->request->param('new_password', '');

            if (empty($newPassword) || strlen($newPassword) < 6) {
                return $this->error(lang('business.password_min_length'));
            }

            $this->userService->changePassword($this->getUserId(), $oldPassword, $newPassword);
            return $this->success(lang('messages.password_change_success'));
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    #[OA\Get(
        path: '/user/balance',
        summary: '获取余额',
        security: [['bearerAuth' => []]],
        tags: ['用户中心'],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function balance(): Response
    {
        $userId = $this->getUserId();
        $result = $this->userManageService->getUserBalance($userId);
        return $this->success('ok', $result);
    }

    #[OA\Get(
        path: '/user/balance-logs',
        summary: '获取余额记录',
        security: [['bearerAuth' => []]],
        tags: ['用户中心'],
        parameters: [
            new OA\Parameter(name: 'page_no', in: 'query', description: '页码', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'page_size', in: 'query', description: '每页数量', schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse')),
        ]
    )]
    public function balanceLogs(): Response
    {
        $userId = $this->getUserId();
        $params = $this->getRequestData();
        $result = $this->userManageService->getUserBalanceLogs($userId, $params);
        return $this->success('ok', $result);
    }

    #[OA\Get(
        path: '/user/points',
        summary: '获取积分',
        security: [['bearerAuth' => []]],
        tags: ['用户中心'],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function points(): Response
    {
        $userId = $this->getUserId();
        $result = $this->userManageService->getUserPoints($userId);
        return $this->success('ok', $result);
    }

    #[OA\Get(
        path: '/user/points-logs',
        summary: '获取积分记录',
        security: [['bearerAuth' => []]],
        tags: ['用户中心'],
        parameters: [
            new OA\Parameter(name: 'page_no', in: 'query', description: '页码', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'page_size', in: 'query', description: '每页数量', schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse')),
        ]
    )]
    public function pointsLogs(): Response
    {
        $userId = $this->getUserId();
        $params = $this->getRequestData();
        $result = $this->userManageService->getUserPointsLogs($userId, $params);
        return $this->success('ok', $result);
    }

    #[OA\Post(
        path: '/user/recharge',
        summary: '余额充值',
        security: [['bearerAuth' => []]],
        tags: ['用户中心'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['amount'],
                properties: [
                    new OA\Property(property: 'amount', type: 'number', description: '充值金额（1.00~10000.00）'),
                    new OA\Property(property: 'channel', type: 'string', description: '支付渠道（wechat/alipay）', default: 'wechat'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '创建充值订单成功，返回支付参数', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function recharge(): Response
    {
        try {
            // Controller 只传递请求上下文；金额、渠道、trade_type、appid/openid
            // 均由充值 Service 和支付 Service 使用服务端配置解析。
            $result = $this->memberRechargeService->createRechargeForClient(
                $this->getUserId(),
                $this->request->post('amount', ''),
                (string)$this->request->post('channel', 'wechat'),
                $this->getClientType(),
                $this->request->ip()
            );

            return $this->success('ok', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    #[OA\Post(
        path: '/user/bind-mobile',
        summary: '绑定手机号（短信验证码）',
        security: [['bearerAuth' => []]],
        tags: ['用户中心'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['mobile', 'code'],
                properties: [
                    new OA\Property(property: 'mobile', type: 'string', description: '手机号'),
                    new OA\Property(property: 'code', type: 'string', description: '短信验证码（scene=bind_mobile）'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '绑定成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function bindMobile(): Response
    {
        try {
            $mobile = (string)$this->request->post('mobile', '');
            $code   = (string)$this->request->post('code', '');

            if (!preg_match('/^1[3-9]\d{9}$/', $mobile)) {
                return $this->error(lang('business.invalid_mobile_format'));
            }
            if ($code === '') {
                return $this->error(lang('auth.captcha_invalid'));
            }

            $cacheKey   = 'sms_code:bind_mobile:' . $mobile;
            $cachedCode = cache($cacheKey);
            if (!$cachedCode || $cachedCode !== $code) {
                return $this->error(lang('auth.captcha_invalid'));
            }

            $this->userService->bindMobile($this->getUserId(), $mobile);
            cache($cacheKey, null);

            return $this->success('ok', ['mobile' => $mobile]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    #[OA\Post(
        path: '/user/bind-oa-openid',
        summary: '绑定公众号 openid',
        security: [['bearerAuth' => []]],
        tags: ['用户中心'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['oa_openid'],
                properties: [
                    new OA\Property(property: 'oa_openid', type: 'string', description: '公众号 openid'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '绑定成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function bindOaOpenid(): Response
    {
        try {
            $oaOpenid = (string)$this->request->post('oa_openid', '');
            if (empty($oaOpenid)) {
                return $this->error('缺少 oa_openid');
            }

            $userId = $this->getUserId();
            $this->userService->bindOaOpenid($userId, $oaOpenid);

            return $this->success('ok');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
