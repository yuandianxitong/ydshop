<?php
declare(strict_types=1);

namespace app\listener;

use app\repository\user\UserRepository;
use app\service\message\MessageService;
use think\facade\Log;

/**
 * 消息推送监听器
 *
 * 当前支持数据库站内消息和微信小程序订阅消息。App/H5 均可通过
 * 站内消息接口拉取；没有接收标识的通道不会被虚假标记为已推送。
 */
class MessagePushListener
{
    public function __construct(
        protected UserRepository $userRepository,
        protected MessageService $messageService,
    ) {
    }

    /**
     * 处理消息推送事件
     *
     * @param array $data ['user_id' => int, 'title' => string, 'content' => string, 'type' => string, 'extra' => array]
     */
    public function handle(array $data): void
    {
        $userId = $data['user_id'] ?? 0;
        $title = $data['title'] ?? '';
        $content = $data['content'] ?? '';
        $type = $data['type'] ?? 'system';
        $extra = $data['extra'] ?? [];

        if (empty($userId) || empty($title)) {
            Log::warning('MessagePush: missing user_id or title', $data);
            return;
        }

        try {
            $channels = $this->resolveChannels($userId);

            foreach ($channels as $channel) {
                $this->pushToChannel($channel, [
                    'user_id' => $userId,
                    'title'   => $title,
                    'content' => $content,
                    'type'    => $type,
                    'extra'   => $extra,
                ]);
            }

            Log::info("MessagePush: sent to user {$userId} via " . implode(',', $channels));
        } catch (\Throwable $e) {
            Log::error('MessagePush failed: ' . $e->getMessage(), [
                'user_id' => $userId,
                'error'   => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * 根据用户决定推送通道
     *
     * @return string[] 通道列表
     */
    protected function resolveChannels(int $userId): array // @phpstan-ignore-line
    {
        $channels = ['database'];

        $user = $this->userRepository->findModel($userId);
        if ($user && !empty($user->mini_openid)) {
            $channels[] = 'wechat_subscribe';
        }

        return $channels;
    }

    /**
     * 推送到指定通道
     */
    protected function pushToChannel(string $channel, array $message): void
    {
        match ($channel) {
            'database'          => $this->pushToDatabase($message),
            'wechat_subscribe'  => $this->pushToWechatSubscribe($message),
            default             => Log::warning("MessagePush: unknown channel {$channel}"),
        };
    }

    /**
     * 数据库记录（所有平台的兜底方案）
     */
    protected function pushToDatabase(array $message): void
    {
        // 消息已在 Service 层创建到数据库，此处不需要重复写入
        // 如果需要额外的推送记录表，可以在此处写入
        Log::debug('MessagePush: recorded to database for user ' . $message['user_id']);
    }

    /**
     * 微信订阅消息推送
     *
     * 需要配置:
     * - 小程序 appid/secret
     * - 订阅消息模板 ID
     * - 用户的 openid
     */
    protected function pushToWechatSubscribe(array $message): void
    {
        $userId = (int) ($message['user_id'] ?? 0);
        if (!$userId) {
            return;
        }

        $user = $this->userRepository->findModel($userId);
        if (!$user || empty($user->mini_openid)) {
            Log::debug("MessagePush: user {$userId} has no mini_openid, skip wechat subscribe");
            return;
        }

        $templateCode = $message['extra']['template_code'] ?? 'notification';
        $receivers = ['mini_openid' => $user->mini_openid];
        $data = [
            'title'   => $message['title'] ?? '',
            'content' => $message['content'] ?? '',
        ];

        $this->messageService->trySend($templateCode, $receivers, $data);
    }

}
