<?php
declare(strict_types=1);

namespace app\service\user;

use app\repository\user\UserOperationLogRepository;
use core\base\Service;

/**
 * 用户操作日志写入助手
 *
 * Listener / Service 调用此处，统一封装 icon / tone / category 默认值，
 * 避免每个 Listener 自己拼图标和颜色。
 */
class UserOperationLogService extends Service
{
    protected UserOperationLogRepository $repo;

    /**
     * 登录类
     */
    public function recordLogin(int $userId, string $title, string $description, array $meta = []): void
    {
        $this->repo->record($userId, 'login', [
            'event_code'  => $meta['event_code'] ?? 'login',
            'title'       => $title,
            'description' => $description,
            'icon'        => 'i-lucide:log-in',
            'tone'        => '#0ea5e9',
            'meta'        => $meta,
        ]);
    }

    /**
     * 资产类（充值、提现、积分变动等）
     */
    public function recordAsset(
        int $userId,
        string $eventCode,
        string $title,
        string $description,
        array $meta = [],
        ?string $eventKey = null
    ): void
    {
        $iconMap = [
            'balance.recharge' => 'i-lucide:wallet',
            'balance.consume'  => 'i-lucide:wallet',
            'balance.adjust'   => 'i-lucide:wallet',
            'points.adjust'    => 'i-lucide:gift',
            'points.exchange'  => 'i-lucide:gift',
        ];
        $toneMap = [
            'balance.recharge' => '#4f6bff',
            'balance.consume'  => '#94a3b8',
            'balance.adjust'   => '#4f6bff',
            'points.adjust'    => '#f43f5e',
            'points.exchange'  => '#f43f5e',
        ];
        $this->repo->record($userId, 'asset', [
            'event_code'  => $eventCode,
            'title'       => $title,
            'description' => $description,
            'icon'        => $iconMap[$eventCode] ?? 'i-lucide:wallet',
            'tone'        => $toneMap[$eventCode] ?? '#4f6bff',
            'ref_type'    => $meta['ref_type'] ?? '',
            'ref_id'      => $meta['ref_id']   ?? null,
            'meta'        => $meta,
            'event_key'   => $eventKey,
        ]);
    }

    /**
     * 等级类（升级 / 降级）
     */
    public function recordLevel(int $userId, string $title, string $description, array $meta = []): void
    {
        $this->repo->record($userId, 'level', [
            'event_code'  => 'level.changed',
            'title'       => $title,
            'description' => $description,
            'icon'        => 'i-lucide:medal',
            'tone'        => '#f59e0b',
            'meta'        => $meta,
        ]);
    }

    /**
     * 订单类（下单 / 支付 / 发货 / 完成）
     */
    public function recordOrder(
        int $userId,
        string $eventCode,
        string $title,
        string $description,
        array $meta = [],
        ?string $eventKey = null
    ): void
    {
        $iconMap = [
            'order.placed'   => 'i-lucide:shopping-cart',
            'order.paid'     => 'i-lucide:credit-card',
            'order.shipped'  => 'i-lucide:truck',
            'order.received' => 'i-lucide:package-check',
        ];
        $toneMap = [
            'order.placed'   => '#10b981',
            'order.paid'     => '#10b981',
            'order.shipped'  => '#0ea5e9',
            'order.received' => '#0ea5e9',
        ];
        $this->repo->record($userId, 'order', [
            'event_code'  => $eventCode,
            'title'       => $title,
            'description' => $description,
            'icon'        => $iconMap[$eventCode] ?? 'i-lucide:shopping-cart',
            'tone'        => $toneMap[$eventCode] ?? '#10b981',
            'ref_type'    => 'order',
            'ref_id'      => $meta['order_id'] ?? null,
            'meta'        => $meta,
            'event_key'   => $eventKey,
        ]);
    }

    /**
     * 客服类
     */
    public function recordService(int $userId, string $title, string $description, array $meta = []): void
    {
        $this->repo->record($userId, 'service', [
            'event_code'  => 'service.session',
            'title'       => $title,
            'description' => $description,
            'icon'        => 'i-lucide:message-square',
            'tone'        => '#8b5cf6',
            'meta'        => $meta,
        ]);
    }

    /**
     * 资料类
     */
    public function recordProfile(int $userId, string $title, string $description, array $meta = []): void
    {
        $this->repo->record($userId, 'profile', [
            'event_code'  => 'profile.updated',
            'title'       => $title,
            'description' => $description,
            'icon'        => 'i-lucide:user-circle',
            'tone'        => '#6366f1',
            'meta'        => $meta,
        ]);
    }
}
