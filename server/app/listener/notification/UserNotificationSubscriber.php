<?php
declare(strict_types=1);

namespace app\listener\notification;

use app\model\notification\UserNotification;
use app\repository\order\OrderOrderRepository;
use app\service\notification\UserNotificationService;
use think\Event;
use think\facade\Log;

/**
 * 将领域事件转换为 C 端站内消息。
 *
 * 通知属于不影响主流程的副作用：任何写入异常只记录日志；领域事件键与数据库
 * 唯一索引共同保证支付、退款和补偿任务重放时不会重复生成消息。
 */
class UserNotificationSubscriber
{
    protected ?UserNotificationService $notificationService = null;
    protected ?OrderOrderRepository $orderRepository = null;

    public function subscribe(Event $event): void
    {
        $event->listen('user.register', [$this, 'onUserRegistered']);
        $event->listen('payment.success', [$this, 'onPaymentSuccess']);
        $event->listen('order.created', [$this, 'onOrderCreated']);
        $event->listen('order.paid', [$this, 'onOrderPaid']);
        $event->listen('order.shipped', [$this, 'onOrderShipped']);
        $event->listen('order.completed', [$this, 'onOrderCompleted']);
        $event->listen('order.cancelled', [$this, 'onOrderCancelled']);
        $event->listen('order.refund.completed', [$this, 'onOrderRefundCompleted']);
        $event->listen('feedback.created', [$this, 'onFeedbackCreated']);
        $event->listen('feedback.replied', [$this, 'onFeedbackReplied']);
        $event->listen('feedback.closed', [$this, 'onFeedbackClosed']);
        $event->listen('announcement.published', [$this, 'onAnnouncementPublished']);
    }

    public function onUserRegistered(array $event): void
    {
        $userId = (int) ($event['user_id'] ?? 0);
        $this->safely('user.register', function () use ($userId): void {
            if ($userId <= 0) {
                return;
            }
            $this->notifications()->createNotification([
                'user_id'   => $userId,
                'title'     => '欢迎加入',
                'content'   => '账号注册成功，欢迎使用元点Shop。',
                'type'      => UserNotification::TYPE_SYSTEM,
                'event_key' => 'user.register:' . $userId,
                'extra'     => [
                    'pc_path'     => '/user/profile',
                    'uniapp_path' => '/pages/my/index',
                ],
            ]);
        });
    }

    public function onPaymentSuccess(array $event): void
    {
        // 商城订单由 order.paid 生成带订单详情入口的消息；这里只处理余额充值。
        if (($event['biz_type'] ?? '') !== 'recharge') {
            return;
        }

        $this->safely('payment.success', function () use ($event): void {
            $userId = (int) ($event['user_id'] ?? 0);
            if ($userId <= 0) {
                return;
            }
            $paymentId = (int) ($event['payment_order_id'] ?? 0);
            $eventKey = trim((string) ($event['event_key'] ?? ''));
            $this->notifications()->createNotification([
                'user_id'   => $userId,
                'title'     => '充值成功',
                'content'   => '余额充值 ¥' . (string) ($event['amount'] ?? '0.00') . ' 已到账。',
                'type'      => UserNotification::TYPE_PAYMENT,
                'biz_id'    => $paymentId,
                'event_key' => ($eventKey !== '' ? $eventKey : 'payment.success:recharge:' . $paymentId)
                    . ':user-notification',
                'extra'     => [
                    'pc_path'     => '/user/balance',
                    'uniapp_path' => '/modules/user/pages/balance',
                ],
            ]);
        });
    }

    public function onOrderCreated(array $event): void
    {
        $this->withOrder('order.created', $event, function (array $order): void {
            $orderNo = (string) ($order['order_no'] ?? '');
            $this->createOrderNotification(
                $order,
                '订单已提交',
                '订单 ' . $orderNo . ' 已提交，请在有效期内完成支付。',
                'order.created:' . (int) $order['id']
            );
        });
    }

    public function onOrderPaid(array $event): void
    {
        $this->withOrder('order.paid', $event, function (array $order) use ($event): void {
            $eventKey = trim((string) ($event['event_key'] ?? ''));
            $this->createOrderNotification(
                $order,
                '订单支付成功',
                '订单 ' . (string) ($order['order_no'] ?? '') . ' 已支付成功，我们会尽快处理。',
                ($eventKey !== '' ? $eventKey : 'order.paid:' . (int) $order['id']) . ':user-notification'
            );
        });
    }

    public function onOrderShipped(array $event): void
    {
        $this->withOrder('order.shipped', $event, function (array $order) use ($event): void {
            $expressNo = trim((string) ($event['express_no'] ?? ''));
            $suffix = $expressNo !== '' ? '，运单号：' . $expressNo : '';
            $this->createOrderNotification(
                $order,
                '订单已发货',
                '订单 ' . (string) ($order['order_no'] ?? '') . ' 已发货' . $suffix . '。',
                'order.shipped:' . (int) $order['id']
            );
        });
    }

    public function onOrderCompleted(array $event): void
    {
        $this->withOrder('order.completed', $event, function (array $order): void {
            $this->createOrderNotification(
                $order,
                '订单已完成',
                '订单 ' . (string) ($order['order_no'] ?? '') . ' 已完成，感谢你的支持。',
                'order.completed:' . (int) $order['id']
            );
        });
    }

    public function onOrderCancelled(array $event): void
    {
        $this->withOrder('order.cancelled', $event, function (array $order) use ($event): void {
            $reason = trim((string) ($event['reason'] ?? ''));
            $content = '订单 ' . (string) ($order['order_no'] ?? '') . ' 已取消。';
            if ($reason !== '') {
                $content .= '原因：' . $this->plainText($reason, 120);
            }
            $this->createOrderNotification(
                $order,
                '订单已取消',
                $content,
                'order.cancelled:' . (int) $order['id']
            );
        });
    }

    public function onOrderRefundCompleted(array $event): void
    {
        $this->withOrder('order.refund.completed', $event, function (array $order) use ($event): void {
            $refundId = (int) ($event['refund_id'] ?? 0);
            $this->createOrderNotification(
                $order,
                '退款已到账',
                '订单 ' . (string) ($order['order_no'] ?? '') . ' 已退款 ¥'
                    . (string) ($event['refund_amount'] ?? '0.00') . '。',
                'order.refund.completed:' . ($refundId > 0 ? $refundId : (int) $order['id'])
            );
        });
    }

    public function onFeedbackCreated(array $event): void
    {
        $this->safely('feedback.created', function () use ($event): void {
            $feedbackId = (int) ($event['feedback_id'] ?? 0);
            $userId = (int) ($event['user_id'] ?? 0);
            if ($feedbackId <= 0 || $userId <= 0) {
                return;
            }
            $this->notifications()->createNotification([
                'user_id'   => $userId,
                'title'     => '反馈已提交',
                'content'   => '我们已收到你的反馈，将尽快处理。',
                'type'      => UserNotification::TYPE_FEEDBACK,
                'biz_id'    => $feedbackId,
                'event_key' => 'feedback.created:' . $feedbackId,
                'extra'     => $this->feedbackPaths($feedbackId),
            ]);
        });
    }

    public function onFeedbackReplied(array $event): void
    {
        $this->safely('feedback.replied', function () use ($event): void {
            $feedbackId = (int) ($event['feedback_id'] ?? 0);
            $userId = (int) ($event['user_id'] ?? 0);
            $reply = trim((string) ($event['reply'] ?? ''));
            if ($feedbackId <= 0 || $userId <= 0 || $reply === '') {
                return;
            }
            $this->notifications()->createNotification([
                'user_id'   => $userId,
                'title'     => '你的反馈有新回复',
                'content'   => $this->plainText($reply, 500),
                'type'      => UserNotification::TYPE_FEEDBACK,
                'biz_id'    => $feedbackId,
                'event_key' => 'feedback.replied:' . $feedbackId . ':' . hash('sha256', $reply),
                'extra'     => $this->feedbackPaths($feedbackId),
            ]);
        });
    }

    public function onFeedbackClosed(array $event): void
    {
        $this->safely('feedback.closed', function () use ($event): void {
            $feedbackId = (int) ($event['feedback_id'] ?? 0);
            $userId = (int) ($event['user_id'] ?? 0);
            if ($feedbackId <= 0 || $userId <= 0) {
                return;
            }
            $this->notifications()->createNotification([
                'user_id'   => $userId,
                'title'     => '反馈已关闭',
                'content'   => '你的反馈已处理并关闭，可在反馈记录中查看详情。',
                'type'      => UserNotification::TYPE_FEEDBACK,
                'biz_id'    => $feedbackId,
                'event_key' => 'feedback.closed:' . $feedbackId,
                'extra'     => $this->feedbackPaths($feedbackId),
            ]);
        });
    }

    public function onAnnouncementPublished(array $event): void
    {
        $this->safely('announcement.published', function () use ($event): void {
            $announcementId = (int) ($event['announcement_id'] ?? 0);
            $title = trim((string) ($event['title'] ?? ''));
            if ($announcementId <= 0 || $title === '') {
                return;
            }
            $this->notifications()->createNotification([
                'user_id'   => 0,
                'title'     => $title,
                'content'   => $this->plainText((string) ($event['content'] ?? ''), 500),
                'type'      => UserNotification::TYPE_SYSTEM,
                'biz_id'    => $announcementId,
                'event_key' => 'announcement.published:' . $announcementId,
                'extra'     => [
                    'pc_path'     => '/announcement/' . $announcementId,
                    'uniapp_path' => '/modules/announcement/pages/announcement-detail?id=' . $announcementId,
                ],
            ]);
        });
    }

    private function withOrder(string $eventName, array $event, callable $callback): void
    {
        $this->safely($eventName, function () use ($event, $callback): void {
            $orderId = (int) ($event['order_id'] ?? 0);
            if ($orderId <= 0) {
                return;
            }
            $order = $this->orders()->find($orderId);
            if (!$order || (int) ($order['user_id'] ?? 0) <= 0) {
                return;
            }
            $callback($order);
        });
    }

    private function createOrderNotification(
        array $order,
        string $title,
        string $content,
        string $eventKey
    ): void {
        $orderId = (int) $order['id'];
        $this->notifications()->createNotification([
            'user_id'   => (int) $order['user_id'],
            'title'     => $title,
            'content'   => $content,
            'type'      => UserNotification::TYPE_ORDER,
            'biz_id'    => $orderId,
            'event_key' => $eventKey,
            'extra'     => [
                'order_no'    => (string) ($order['order_no'] ?? ''),
                'pc_path'     => '/order/' . $orderId,
                'uniapp_path' => '/modules/order/pages/detail?id=' . $orderId,
            ],
        ]);
    }

    private function feedbackPaths(int $feedbackId): array
    {
        return [
            'pc_path'     => '/user/feedback',
            'uniapp_path' => '/modules/feedback/pages/feedback?tab=history&id=' . $feedbackId,
        ];
    }

    private function plainText(string $content, int $maxLength): string
    {
        $content = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $content = preg_replace('/\s+/u', ' ', $content) ?? '';
        return mb_substr(trim($content), 0, $maxLength);
    }

    private function safely(string $eventName, callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $exception) {
            try {
                Log::warning('用户站内通知写入失败：' . $exception->getMessage(), [
                    'event' => $eventName,
                ]);
            } catch (\Throwable) {
            }
        }
    }

    /** 延迟解析，避免应用/CLI 启动阶段仅加载事件配置就访问数据库。 */
    protected function notifications(): UserNotificationService
    {
        return $this->notificationService ??= app(UserNotificationService::class);
    }

    /** 延迟解析，只有确实处理订单事件时才初始化订单仓储。 */
    protected function orders(): OrderOrderRepository
    {
        return $this->orderRepository ??= app(OrderOrderRepository::class);
    }
}
