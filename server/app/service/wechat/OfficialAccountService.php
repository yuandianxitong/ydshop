<?php
declare(strict_types=1);

namespace app\service\wechat;

use core\base\Service;
use core\wechat\WechatManager;
use core\exception\BusinessException;
use Overtrue\Socialite\Contracts\ProviderInterface;
use Overtrue\Socialite\Providers\WeChat;

class OfficialAccountService extends Service
{
    protected AutoReplyService $autoReplyService;

    /**
     * 获取网页授权 URL
     * @param string $redirectUrl 回调地址
     * @param string $scope snsapi_base / snsapi_userinfo
     * @return string
     */
    public function getOAuthUrl(string $redirectUrl, string $scope = 'snsapi_userinfo'): string
    {
        $scope = $this->normalizeOAuthScope($scope);
        $app = WechatManager::officialAccount();
        // EasyWeChat/Socialite: redirect() 只接收回调地址，scope 必须通过 scopes() 设置
        // 默认 oauth.scopes 为 snsapi_userinfo，直接 redirect($url, $scope) 会导致第二参数被忽略
        return $app->getOAuth()
            ->scopes([$scope])
            ->redirect($redirectUrl);
    }

    /**
     * 通过授权 code 获取用户信息
     *
     * code→access_token 的接口两种 scope 完全相同，因此统一走 snsapi_base 分支换取
     * openid；只有 scope=snsapi_userinfo 时再补拉一次 sns/userinfo 取昵称头像。
     * 补拉失败（未认证服务号无 snsapi_userinfo 权限、access_token 作用域不足等）
     * 不影响 openid，调用方仍可正常登录。
     *
     * @param string $scope 需与授权时一致；snsapi_base 仅得 openid，snsapi_userinfo 额外得昵称头像
     */
    public function getUserByCode(string $code, string $scope = 'snsapi_base'): array
    {
        $scope = $this->normalizeOAuthScope($scope);
        $app = WechatManager::officialAccount();
        $oauth = $app->getOAuth()->scopes(['snsapi_base']);
        $user = $oauth->userFromCode($code);

        $raw = $user->getRaw();
        $openid = (string)$user->getId();

        $result = [
            'openid'   => $openid,
            'unionid'  => (string)($raw['unionid'] ?? ''),
            'nickname' => '',
            'avatar'   => '',
            'raw'      => $raw,
        ];

        if ($scope === 'snsapi_userinfo' && $openid !== '') {
            $result = array_merge($result, $this->fetchUserProfile($oauth, $openid, (string)$user->getAccessToken()));
        }

        return $result;
    }

    /**
     * 补拉 sns/userinfo，失败时返回空数组（记 warning 日志，不中断登录）
     */
    protected function fetchUserProfile(ProviderInterface $oauth, string $openid, string $accessToken): array
    {
        if ($accessToken === '' || !$oauth instanceof WeChat) {
            return [];
        }

        try {
            $detail = $oauth->withOpenid($openid)->userFromToken($accessToken);
            $detailRaw = $detail->getRaw();

            return array_filter([
                'nickname' => (string)$detail->getNickname(),
                'avatar'   => (string)$detail->getAvatar(),
                'unionid'  => (string)($detailRaw['unionid'] ?? ''),
                'raw'      => $detailRaw,
            ], fn ($value) => $value !== '' && $value !== []);
        } catch (\Throwable $e) {
            $this->log('获取微信用户资料失败，降级为仅 openid: ' . $e->getMessage(), ['openid' => $openid], 'warning');
            return [];
        }
    }

    protected function normalizeOAuthScope(string $scope): string
    {
        return in_array($scope, ['snsapi_base', 'snsapi_userinfo'], true)
            ? $scope
            : 'snsapi_userinfo';
    }

    /**
     * 获取自定义菜单
     */
    public function getMenu(): array
    {
        $app = WechatManager::officialAccount();
        $api = $app->getClient();
        $response = $api->get('/cgi-bin/get_current_selfmenu_info');
        $result = $response->toArray();
        $this->checkResponse($result);

        return $result;
    }

    /**
     * 创建自定义菜单
     */
    public function createMenu(array $buttons): array
    {
        $app = WechatManager::officialAccount();
        $api = $app->getClient();
        $response = $api->postJson('/cgi-bin/menu/create', ['button' => $buttons]);
        $result = $response->toArray();
        $this->checkResponse($result);

        return $result;
    }

    /**
     * 删除自定义菜单
     */
    public function deleteMenu(): array
    {
        $app = WechatManager::officialAccount();
        $api = $app->getClient();
        $response = $api->get('/cgi-bin/menu/delete');
        $result = $response->toArray();
        $this->checkResponse($result);

        return $result;
    }

    /**
     * 获取粉丝列表
     */
    public function getUserList(string $nextOpenid = ''): array
    {
        $app = WechatManager::officialAccount();
        $api = $app->getClient();

        $query = $nextOpenid ? ['next_openid' => $nextOpenid] : [];
        $response = $api->get('/cgi-bin/user/get', $query);
        $result = $response->toArray();
        $this->checkResponse($result);

        return $result;
    }

    /**
     * 获取用户基本信息
     */
    public function getUserInfo(string $openid): array
    {
        $app = WechatManager::officialAccount();
        $api = $app->getClient();
        $response = $api->get('/cgi-bin/user/info', ['openid' => $openid, 'lang' => 'zh_CN']);
        $result = $response->toArray();
        $this->checkResponse($result);

        return $result;
    }

    /**
     * 处理服务器消息/事件推送
     */
    public function handleServerRequest(): \Psr\Http\Message\ResponseInterface
    {
        $app = WechatManager::officialAccount();
        $server = $app->getServer();

        // 关注事件 — 从自动回复表查询，无配置则使用默认
        $server->addEventListener('subscribe', function ($message) {
            return $this->autoReplyService->getSubscribeReply() ?? '感谢关注！';
        });

        // 文本消息 — 先匹配关键词，未命中则使用默认回复
        $server->addMessageListener('text', function ($message) {
            $content = $message['Content'] ?? '';

            // 关键词匹配
            $reply = $this->autoReplyService->matchKeyword($content);
            if ($reply !== null) {
                return $reply;
            }

            // 默认回复
            return $this->autoReplyService->getDefaultReply() ?? '';
        });

        return $server->serve();
    }

    /**
     * 校验微信 API 响应
     */
    protected function checkResponse(array $result): void
    {
        $errcode = $result['errcode'] ?? 0;
        if ($errcode !== 0) {
            throw new BusinessException(lang('business.wechat_api_error') . ': ' . ($result['errmsg'] ?? '') . " (errcode: {$errcode})");
        }
    }
}
