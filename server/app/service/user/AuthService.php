<?php
declare(strict_types=1);

namespace app\service\user;

use app\repository\system\SystemConfigRepository;
use app\repository\user\UserRepository;
use core\auth\TokenManager;
use core\base\Service;
use core\exception\BusinessException;
use GuzzleHttp\Client as HttpClient;

/**
 * 用户认证 Service —— 登录 / 注册 / 微信各通道授权
 *
 * 从原 UserService 中拆出，专门承载会话建立路径。UserService 保留资料
 * 维护（profile / password / openid 绑定）。
 */
class AuthService extends Service
{
    protected UserRepository $userRepository;
    protected SystemConfigRepository $systemConfigRepository;
    protected TokenManager $tokenManager;
    protected \app\service\wechat\MiniAppService $miniAppService;
    protected \app\service\wechat\OfficialAccountService $officialAccountService;

    /**
     * 账号+密码登录（账号可以是手机号或用户名）
     */
    public function loginByPassword(string $account, string $password, string $ip = ''): array
    {
        $user = $this->userRepository->findByAccount($account);
        if (!$user) {
            throw new BusinessException(lang('business.user_not_found'));
        }

        if ($user->status !== 1) {
            throw new BusinessException(lang('business.user_account_disabled'));
        }

        $hash = (string) ($user->password ?? '');
        if ($hash === '') {
            throw new BusinessException(lang('business.user_password_not_set'));
        }

        if (!password_verify($password, $hash)) {
            throw new BusinessException(lang('business.user_password_error'));
        }

        return $this->loginSuccess($user, $ip);
    }

    /**
     * 手机号+验证码登录
     */
    public function loginBySmsCode(string $mobile, string $ip = ''): array
    {
        $user = $this->userRepository->findByMobile($mobile);

        if (!$user) {
            throw new BusinessException(lang('business.mobile_not_registered'));
        }

        if ($user->status !== 1) {
            throw new BusinessException(lang('business.user_account_disabled'));
        }

        return $this->loginSuccess($user, $ip);
    }

    /**
     * 微信小程序登录
     */
    public function loginByMiniApp(string $openid, string $unionid = '', array $userInfo = [], string $ip = ''): array
    {
        $user = $this->userRepository->findByMiniOpenid($openid);

        if (!$user && $unionid) {
            $user = $this->userRepository->findByUnionid($unionid);
            if ($user) {
                $this->userRepository->bindMiniOpenid((int) $user->id, $openid, $unionid);
                $user = $this->userRepository->findByMiniOpenid($openid);
            }
        }

        if (!$user) {
            // 自动注册
            $this->userRepository->create([
                'mini_openid' => $openid,
                'unionid'     => $unionid ?: null,
                'nickname'    => $userInfo['nickname'] ?? '微信用户',
                'avatar'      => $userInfo['avatar'] ?? '',
                'status'      => 1,
            ]);
            $user = $this->userRepository->findByMiniOpenid($openid);

            $this->trigger('user.register', ['user_id' => $user->id, 'channel' => 'miniapp']);
        }

        $this->ensureUnionid($user, $unionid);

        if ($user->status !== 1) {
            throw new BusinessException(lang('business.user_account_disabled'));
        }

        return $this->loginSuccess($user, $ip);
    }

    /**
     * 注册（账号可以是手机号或用户名）
     */
    public function register(string $account, string $password, string $ip = ''): array
    {
        $isMobile = preg_match('/^1[3-9]\d{9}$/', $account);

        $existing = $this->userRepository->findByAccount($account);
        if ($existing) {
            throw new BusinessException($isMobile ? lang('business.mobile_already_registered') : '该账号已被注册');
        }

        $this->userRepository->create([
            'mobile'   => $account,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'nickname' => $isMobile ? '用户' . substr($account, -4) : $account,
            'status'   => 1,
        ]);

        $user = $this->userRepository->findByAccount($account);

        $this->trigger('user.register', ['user_id' => $user->id, 'account' => $account]);

        return $this->loginSuccess($user, $ip);
    }

    /**
     * 微信开放平台 code 换登录（PC 端扫码）
     */
    public function loginByWechatOpenPlatformCode(string $code, string $ip = ''): array
    {
        $appId     = (string) $this->systemConfigRepository->getConfigValue('wechat_open_app_id', '');
        $appSecret = (string) $this->systemConfigRepository->getConfigValue('wechat_open_app_secret', '');
        if ($appId === '' || $appSecret === '') {
            throw new BusinessException(lang('business.wechat_open_platform_not_configured'));
        }

        $http = new HttpClient(['timeout' => 5.0]);

        $tokenRes = $http->get('https://api.weixin.qq.com/sns/oauth2/access_token', [
            'query' => [
                'appid'      => $appId,
                'secret'     => $appSecret,
                'code'       => $code,
                'grant_type' => 'authorization_code',
            ],
        ]);
        $tokenData = json_decode((string) $tokenRes->getBody(), true) ?: [];

        if (empty($tokenData['openid'])) {
            throw new BusinessException($tokenData['errmsg'] ?? lang('business.wechat_auth_failed'));
        }

        $openid      = (string) $tokenData['openid'];
        $unionid     = (string) ($tokenData['unionid'] ?? '');
        $accessToken = (string) ($tokenData['access_token'] ?? '');

        $wxUser = [];
        if ($accessToken !== '') {
            try {
                $userRes = $http->get('https://api.weixin.qq.com/sns/userinfo', [
                    'query' => [
                        'access_token' => $accessToken,
                        'openid'       => $openid,
                    ],
                ]);
                $wxUser = json_decode((string) $userRes->getBody(), true) ?: [];
            } catch (\Throwable $e) {
                $this->log('获取微信用户信息失败: ' . $e->getMessage(), [], 'warning');
            }
        }

        return $this->loginByWechatWeb($openid, $unionid, [
            'nickname' => (string) ($wxUser['nickname'] ?? ''),
            'avatar'   => (string) ($wxUser['headimgurl'] ?? ''),
        ], $ip);
    }

    /**
     * 微信开放平台网页登录（PC端扫码）
     */
    public function loginByWechatWeb(string $openid, string $unionid = '', array $userInfo = [], string $ip = ''): array
    {
        $user = $this->userRepository->findByOpenid($openid);

        if (!$user && $unionid) {
            $user = $this->userRepository->findByUnionid($unionid);
            if ($user && empty($user->openid)) {
                $this->userRepository->bindOpenid((int) $user->id, $openid);
                $user = $this->userRepository->findByOpenid($openid);
            }
        }

        if (!$user) {
            $this->userRepository->create([
                'openid'   => $openid,
                'unionid'  => $unionid ?: null,
                'nickname' => $userInfo['nickname'] ?: '微信用户',
                'avatar'   => $userInfo['avatar'] ?? '',
                'status'   => 1,
            ]);
            $user = $this->userRepository->findByOpenid($openid);

            $this->trigger('user.register', ['user_id' => $user->id, 'channel' => 'wechat_web']);
        }

        $this->ensureUnionid($user, $unionid);

        if ($user->status !== 1) {
            throw new BusinessException(lang('business.user_account_disabled'));
        }

        return $this->loginSuccess($user, $ip);
    }

    /**
     * 微信快捷登录（小程序）— 通过 code 获取 openid，已注册直接登录
     */
    public function wechatQuickLogin(string $code, string $ip = ''): array
    {
        $session = $this->miniAppService->login($code);
        $openid  = $session['openid'] ?? '';
        $unionid = $session['unionid'] ?? '';

        $user = $this->userRepository->findByMiniOpenid($openid);

        if (!$user && $unionid) {
            $user = $this->userRepository->findByUnionid($unionid);
            if ($user && empty($user->mini_openid)) {
                $this->userRepository->bindMiniOpenid((int) $user->id, $openid, $unionid);
                $user = $this->userRepository->findByMiniOpenid($openid);
            }
        }

        if ($user) {
            $this->ensureUnionid($user, $unionid);

            if ($user->status !== 1) {
                throw new BusinessException(lang('business.user_account_disabled'));
            }
            return array_merge([
                'status'       => 'logged_in',
                'need_profile' => $this->needsProfile($user),
            ], $this->loginSuccess($user, $ip));
        }

        $tempToken = md5(uniqid((string)mt_rand(), true));
        cache('wechat_quick_' . $tempToken, [
            'openid'  => $openid,
            'unionid' => $unionid,
        ], 300);

        return [
            'status'     => 'need_bindphone',
            'temp_token' => $tempToken,
        ];
    }

    /**
     * 微信快捷登录 — 绑定手机号完成注册
     *
     * @param array{nickname?: string, avatar?: string} $profile 用户自选的头像/昵称（可选）
     */
    public function wechatBindPhone(string $tempToken, string $phoneCode, string $ip = '', array $profile = []): array
    {
        $wechatData = cache('wechat_quick_' . $tempToken);
        if (empty($wechatData)) {
            throw new BusinessException('登录已过期，请重新操作');
        }
        cache('wechat_quick_' . $tempToken, null);

        $openid  = $wechatData['openid'];
        $unionid = $wechatData['unionid'] ?? '';

        $phoneInfo = $this->miniAppService->decryptPhoneNumber($phoneCode);
        $mobile    = $phoneInfo['pure_phone_number'] ?: ($phoneInfo['phone_number'] ?? '');
        if (empty($mobile)) {
            throw new BusinessException('获取手机号失败');
        }

        $user = $this->userRepository->findByMobile($mobile);
        if ($user) {
            $bindUnionid = ($unionid && empty($user->unionid)) ? $unionid : null;
            $this->userRepository->bindMiniOpenid((int) $user->id, $openid, $bindUnionid);
            $user = $this->userRepository->findByMobile($mobile);
        } else {
            // 新注册：优先使用前端弹窗里用户选择的资料；都没有则回落到"微信用户" + 空头像
            $nickname = isset($profile['nickname']) ? trim((string)$profile['nickname']) : '';
            $avatar   = isset($profile['avatar']) ? trim((string)$profile['avatar']) : '';
            $this->userRepository->create([
                'mobile'      => $mobile,
                'mini_openid' => $openid,
                'unionid'     => $unionid ?: null,
                'nickname'    => $nickname !== '' ? $nickname : '微信用户',
                'avatar'      => $avatar,
                'status'      => 1,
            ]);
            $user = $this->userRepository->findByMobile($mobile);
            $this->trigger('user.register', ['user_id' => $user->id, 'channel' => 'wechat_mini_quick']);
        }

        if ($user->status !== 1) {
            throw new BusinessException(lang('business.user_account_disabled'));
        }

        return array_merge([
            'status'       => 'logged_in',
            'need_profile' => $this->needsProfile($user),
            'mobile'       => $mobile,
        ], $this->loginSuccess($user, $ip));
    }

    /**
     * 判定用户资料是否不全，需要弹出授权补全弹窗
     *
     * 触发条件：昵称为空、昵称仍为微信默认注册串「微信用户」、或头像为空。
     */
    private function needsProfile($user): bool
    {
        return $user->nickname === '' || $user->nickname === '微信用户' || (string) $user->avatar === '';
    }

    /**
     * 微信 H5 登录（公众号 OAuth）
     *
     * 微信内置浏览器里的主登录通道：拿 code 换 openid 后直接建立会话，
     * 首次访问的用户按微信资料自动注册，不再要求手机号登录。
     *
     * @param string $scope 需与授权时一致；snsapi_userinfo 才有昵称头像
     */
    public function wechatH5Login(string $code, string $ip = '', string $scope = 'snsapi_base'): array
    {
        $wechatUser = $this->officialAccountService->getUserByCode($code, $scope);
        $openid     = $wechatUser['openid'] ?? '';
        $unionid    = $wechatUser['unionid'] ?? '';
        $nickname   = trim((string)($wechatUser['nickname'] ?? ''));
        $avatar     = trim((string)($wechatUser['avatar'] ?? ''));

        if (empty($openid)) {
            throw new BusinessException('微信授权失败，未获取到 openid');
        }

        $user = $this->userRepository->findByOaOpenid($openid);

        if (!$user && $unionid) {
            $user = $this->userRepository->findByUnionid($unionid);
            if ($user && empty($user->oa_openid)) {
                $this->userRepository->updateOaOpenid((int) $user->id, $openid);
                $user = $this->userRepository->findByOaOpenid($openid);
            }
        }

        if (!$user) {
            $this->userRepository->create([
                'oa_openid' => $openid,
                'unionid'   => $unionid ?: null,
                'nickname'  => $nickname !== '' ? $nickname : '微信用户',
                'avatar'    => $avatar,
                'status'    => 1,
            ]);
            $user = $this->userRepository->findByOaOpenid($openid);

            $this->trigger('user.register', ['user_id' => $user->id, 'channel' => 'wechat_h5']);
        } else {
            $this->syncWechatProfile($user, $nickname, $avatar);
        }

        $this->ensureUnionid($user, $unionid);

        if ($user->status !== 1) {
            throw new BusinessException(lang('business.user_account_disabled'));
        }

        return array_merge([
            'status'  => 'logged_in',
            'openid'  => $openid,
            'unionid' => $unionid,
        ], $this->loginSuccess($user, $ip));
    }

    /**
     * 回填 unionid：任一端登录拿到 unionid 就写回，保证换端登录能命中同一账号
     *
     * 老账号（开放平台绑定前注册、或手机号注册后才绑微信）库里 unionid 为空，
     * 只靠单端 openid 命中会导致同一个微信号在小程序/公众号/PC 各注册一个账号。
     */
    protected function ensureUnionid($user, string $unionid): void
    {
        if ($unionid === '' || !empty($user->unionid)) {
            return;
        }

        if ($this->userRepository->backfillUnionid((int) $user->id, $unionid)) {
            $user->unionid = $unionid;
        }
    }

    /**
     * 老用户资料回填：仅在本地为空或还是默认昵称时，用微信资料补齐
     */
    protected function syncWechatProfile($user, string $nickname, string $avatar): void
    {
        $data = [];

        if ($nickname !== '' && (empty($user->nickname) || $user->nickname === '微信用户')) {
            $data['nickname'] = $nickname;
        }
        if ($avatar !== '' && empty($user->avatar)) {
            $data['avatar'] = $avatar;
        }

        if ($data) {
            $this->userRepository->update((int) $user->id, $data);
            foreach ($data as $field => $value) {
                $user->$field = $value;
            }
        }
    }

    /**
     * 登录成功处理：生成 token + 触发 user.login 事件
     */
    protected function loginSuccess($user, string $ip = ''): array
    {
        $token = $this->tokenManager->generate([
            'type'    => 'user',
            'user_id' => $user->id,
            'mobile'  => $user->mobile ?? '',
        ]);

        // 触发登录事件（Listener 负责写 user_login_logs + 更新 users.last_login_*）
        $this->trigger('user.login', [
            'user_id' => $user->id,
            'ip'      => $ip,
        ]);

        return [
            'token'     => $token,
            'user_info' => [
                'id'       => $user->id,
                'nickname' => $user->nickname,
                'avatar'   => $user->avatar,
                'mobile'   => $user->mobile,
            ],
        ];
    }
}
