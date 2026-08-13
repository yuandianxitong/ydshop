<?php
// 事件定义文件
return [
    'bind'      => [
    ],

    'listen'    => [
        'AppInit'  => [],
        'HttpRun'  => [],
        'HttpEnd'  => [],
        'LogLevel' => [],
        'LogWrite' => [],

        // ---- 管理员事件 ----
        'admin.login.success' => [\app\listener\system\AdminLoginSuccessListener::class],
        'admin.login.failed'  => [\app\listener\system\AdminLoginFailedListener::class],

        // ---- 系统配置事件 ----
        'config.changed' => [\app\listener\system\ConfigChangedListener::class],

        // ---- 菜单事件 ----
        'menu.changed' => [\app\listener\system\MenuChangedListener::class],

        // ---- 用户事件 ----
        // new_user_gift listener is registered by plugins/new_user_gift/event.php on boot
        'user.register' => [
            \app\listener\user\UserRegisterListener::class,
        ],
        'user.login'    => [
            \app\listener\user\UserLoginListener::class,
            \app\listener\user\UserOpLogLoginListener::class,
        ],

        // ---- 用户操作日志（会员详情聚合）----
        'user.balance.adjusted' => [\app\listener\user\UserOpLogBalanceAdjustedListener::class],
        'user.points.adjusted'  => [\app\listener\user\UserOpLogPointsAdjustedListener::class],

        // ---- 支付事件 ----
        'payment.success' => [
            \app\listener\payment\PaymentSuccessListener::class,
            \app\listener\finance\FinanceTransactionListener::class,
            \app\listener\user\UserOpLogPaymentListener::class,
        ],

        // ---- 订单事件 ----
        // 预留扩展点（OrderService 已在 fire，未来可挂通知 / 库存反查 / CRM 同步等）
        'order.created'          => [],
        // 顺序敏感：DeliveryAutoDispatchListener 依赖 OrderPaidListener 先创建 delivery_orders 记录
        'order.paid'             => [
            \app\listener\order\OrderPaidListener::class,
            \app\listener\delivery\DeliveryAutoDispatchListener::class,
        ],
        // 预留扩展点（OrderShipService 已在 fire，未来可挂物流通知 / 自动入仓等）
        'order.shipped'          => [],
        'order.completed'        => [\app\listener\order\OrderCompletedListener::class],
        'order.cancelled'        => [\app\listener\order\OrderCancelledInvoiceListener::class],
        'order.refund.completed'       => [
            \app\listener\finance\FinanceTransactionListener::class,
            \app\listener\order\OrderMemberRewardRefundListener::class,
        ],
        'order.pickup_code.generated'  => [\app\listener\order\PickupCodeGeneratedListener::class],
        // 预留扩展点（OrderService.verifyPickup 已在 fire，未来可挂核销审计 / 通知）
        'order.pickup.verified'        => [],
        'order.pickup_timeout'         => [\app\listener\order\PickupTimeoutListener::class],
        // 拆单完成：merchant 子单幂等补建配送单
        'order.split.completed'        => [\app\listener\order\OrderSplitListener::class],
        // 合单完成：预留扩展点（通知/统计），勿与 order.cancelled 混用（语义不同：不释放资源）
        'order.merged'                 => [],

        // ---- 分销事件 ----
        'distribution.withdrawal.paid' => [\app\listener\finance\FinanceTransactionListener::class],

        // ---- 拼团事件 ----
        // group_buy.expired listener is registered by plugins/group_buy/event.php on boot

        // ---- 反馈事件 ----
        'feedback.created' => [\app\listener\feedback\FeedbackCreatedListener::class],

        // ---- 消息推送事件 ----
        'message.created' => [\app\listener\MessagePushListener::class],

        // ---- 预留扩展点（Service 已在 fire，未来按需挂监听） ----
        // AnnouncementService 已在 fire，可挂订阅推送 / 站内信
        'announcement.created'       => [],
        // ArticleService 已在 fire，可挂搜索索引刷新 / 推送
        'article.created'            => [],
        // UserNotificationService 已在 fire，可挂统计 / WebSocket 实时推送
        'user.notification.created'  => [],
        // DiyPageService 已在 fire，可挂缓存清理 / CDN 刷新
        'diy.page.default_changed'   => [],
    ],

    'subscribe' => [
        \app\listener\notification\UserNotificationSubscriber::class,
    ],
];
