<?php
declare(strict_types=1);

namespace app\api\controller\v1\auth;

use core\base\Controller;
use core\auth\TokenManager;
use app\api\validate\v1\auth\AuthValidate;
use app\service\user\AuthService;
use app\service\user\UserService;
use app\service\wechat\MiniAppService;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '认证', description: '用户登录、注册、Token管理')]
class AuthController extends Controller
{
    protected UserService $userService;
    protected AuthService $authService;
    protected MiniAppService $miniAppService;
    protected TokenManager $tokenManager;

    #[OA\Post(
        path: '/auth/login',
        summary: '账号密码登录',
        tags: ['认证'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['account', 'password'],
                properties: [
                    new OA\Property(property: 'account', type: 'string', description: '账号（手机号或用户名）'),
                    new OA\Property(property: 'password', type: 'string', description: '密码'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '登录成功，返回token', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '账号或密码错误', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function login(): Response
    {
        try {
            $data = $this->request->param();
            // 兼容旧版 mobile 参数
            if (empty($data['account'] ?? '')) {
                $data['account'] = (string)($data['mobile'] ?? '');
            }
            $this->validate($data, AuthValidate::class, [], false, 'login');

            $result = $this->authService->loginByPassword(
                (string)$data['account'],
                (string)$data['password'],
                $this->request->ip()
            );
            return $this->success(lang('messages.login_success'), $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    #[OA\Post(
        path: '/auth/sms-login',
        summary: '手机号验证码登录',
        tags: ['认证'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['mobile', 'code'],
                properties: [
                    new OA\Property(property: 'mobile', type: 'string', description: '手机号'),
                    new OA\Property(property: 'code', type: 'string', description: '短信验证码'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '登录成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '验证码错误', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function smsLogin(): Response
    {
        try {
            $data = $this->validate($this->request->param(), AuthValidate::class, [], false, 'sms_login');
            $mobile = (string)$data['mobile'];
            $code   = (string)$data['code'];

            $cacheKey   = 'sms_code:login:' . $mobile;
            $cachedCode = cache($cacheKey);
            if (!$cachedCode || $cachedCode !== $code) {
                return $this->error(lang('auth.captcha_invalid'));
            }

            $result = $this->authService->loginBySmsCode($mobile, $this->request->ip());
            cache($cacheKey, null);

            return $this->success(lang('messages.login_success'), $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    #[OA\Post(
        path: '/auth/wechat-login',
        summary: '微信小程序登录',
        tags: ['认证'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['code'],
                properties: [
                    new OA\Property(property: 'code', type: 'string', description: '微信小程序 code'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '登录成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function wechatLogin(): Response
    {
        try {
            $code = (string)$this->request->param('code', '');
            if (empty($code)) {
                return $this->error(lang('business.missing_code_param'));
            }

            $session = $this->miniAppService->login($code);

            $result = $this->authService->loginByMiniApp(
                $session['openid'],
                $session['unionid'] ?? '',
                [],
                $this->request->ip()
            );

            return $this->success(lang('messages.login_success'), $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    #[OA\Post(
        path: '/auth/wechat-web-login',
        summary: '微信开放平台网页扫码登录（PC端）',
        tags: ['认证'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['code'],
                properties: [
                    new OA\Property(property: 'code', type: 'string', description: '微信授权 code'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '登录成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function wechatWebLogin(): Response
    {
        try {
            $code = (string)$this->request->param('code', '');
            if (empty($code)) {
                return $this->error(lang('business.missing_code_param'));
            }

            $result = $this->authService->loginByWechatOpenPlatformCode($code, $this->request->ip());
            return $this->success(lang('messages.login_success'), $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    #[OA\Get(
        path: '/auth/info',
        summary: '获取当前用户信息',
        security: [['bearerAuth' => []]],
        tags: ['认证'],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function info(): Response
    {
        try {
            $userId = $this->getUserId();
            $userInfo = $this->userService->getUserInfo($userId);
            return $this->success(lang('messages.get_success'), $userInfo);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    #[OA\Post(
        path: '/auth/logout',
        summary: '退出登录',
        security: [['bearerAuth' => []]],
        tags: ['认证'],
        responses: [
            new OA\Response(response: 200, description: '退出成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function logout(): Response
    {
        try {
            $token = $this->tokenManager->getTokenFromHeader();
            if ($token) {
                $this->tokenManager->blacklist($token);
            }
            return $this->success(lang('messages.logout_success'));
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    #[OA\Post(
        path: '/auth/register',
        summary: '注册（手机号+验证码+密码）',
        tags: ['认证'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['mobile', 'code', 'password'],
                properties: [
                    new OA\Property(property: 'mobile', type: 'string', description: '手机号'),
                    new OA\Property(property: 'code', type: 'string', description: '短信验证码'),
                    new OA\Property(property: 'password', type: 'string', description: '密码'),
                    new OA\Property(property: 'password_confirmation', type: 'string', description: '确认密码'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '注册成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '注册失败', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function register(): Response
    {
        try {
            $data = $this->request->param();
            // 兼容旧版 account 参数
            if (empty($data['mobile'] ?? '')) {
                $data['mobile'] = (string)($data['account'] ?? '');
            }
            $data = $this->validate($data, AuthValidate::class, [], false, 'register');

            $mobile   = (string)$data['mobile'];
            $code     = (string)$data['code'];
            $password = (string)$data['password'];

            $cacheKey   = 'sms_code:register:' . $mobile;
            $cachedCode = cache($cacheKey);
            if (!$cachedCode || $cachedCode !== $code) {
                return $this->error(lang('auth.captcha_invalid'));
            }

            $result = $this->authService->register($mobile, $password, $this->request->ip());
            cache($cacheKey, null);

            return $this->success(lang('messages.register_success'), $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    #[OA\Post(
        path: '/auth/refresh-token',
        summary: '刷新 Token',
        security: [['bearerAuth' => []]],
        tags: ['认证'],
        responses: [
            new OA\Response(response: 200, description: '刷新成功，返回新 token', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function refreshToken(): Response
    {
        try {
            $token = $this->tokenManager->getTokenFromHeader();
            if (!$token) {
                return $this->error(lang('messages.token_not_provided'));
            }
            $newToken = $this->tokenManager->refresh($token);
            return $this->success(lang('messages.refresh_success'), ['token' => $newToken]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    #[OA\Post(
        path: '/auth/wechat-quick-login',
        summary: '微信快捷登录（小程序）',
        tags: ['认证'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['code'],
                properties: [
                    new OA\Property(property: 'code', type: 'string', description: '微信小程序 code'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '登录成功或返回临时 token 待绑定手机号', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function wechatQuickLogin(): Response
    {
        try {
            $code = (string)$this->request->post('code', '');
            if (empty($code)) {
                return $this->error('缺少 code');
            }
            $result = $this->authService->wechatQuickLogin($code, $this->request->ip());
            return $this->success('ok', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    #[OA\Post(
        path: '/auth/wechat-bindphone',
        summary: '微信快捷登录 — 绑定手机号',
        tags: ['认证'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['temp_token', 'phone_code'],
                properties: [
                    new OA\Property(property: 'temp_token', type: 'string', description: '快捷登录返回的临时 token'),
                    new OA\Property(property: 'phone_code', type: 'string', description: '微信 getPhoneNumber code'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '绑定并登录成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function wechatBindPhone(): Response
    {
        try {
            $data = $this->validate($this->request->param(), AuthValidate::class, [], false, 'wechat_bindphone');
            $profile = [
                'nickname' => (string)($data['nickname'] ?? ''),
                'avatar'   => (string)($data['avatar'] ?? ''),
            ];
            $result = $this->authService->wechatBindPhone(
                (string)$data['temp_token'],
                (string)$data['phone_code'],
                $this->request->ip(),
                $profile
            );
            return $this->success('ok', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    #[OA\Post(
        path: '/auth/wechat-h5-login',
        summary: '微信 H5 登录（公众号 OAuth code 换登录）',
        tags: ['认证'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['code'],
                properties: [
                    new OA\Property(property: 'code', type: 'string', description: '公众号 OAuth code'),
                    new OA\Property(property: 'scope', type: 'string', description: '授权 scope，需与获取 code 时一致（snsapi_base/snsapi_userinfo）', default: 'snsapi_base'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '登录成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function wechatH5Login(): Response
    {
        try {
            $code = (string)$this->request->post('code', '');
            if (empty($code)) {
                return $this->error('缺少 code');
            }
            $scope = (string)$this->request->post('scope', 'snsapi_base');
            $result = $this->authService->wechatH5Login($code, $this->request->ip(), $scope);
            return $this->success('ok', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
