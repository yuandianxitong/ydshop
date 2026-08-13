<?php
declare(strict_types=1);

namespace app\service\payment;

use app\model\order\OrderOrder;
use app\model\payment\PaymentOrder;
use app\repository\member\MemberRechargeOrderRepository;
use app\repository\payment\PaymentOrderRepository;
use app\repository\system\SystemConfigRepository;
use app\repository\user\UserRepository;
use app\service\order\OrderPayService;
use core\base\Service;
use core\exception\BusinessException;
use core\payment\PaymentInterface;
use core\payment\PaymentManager;
use think\facade\Log;

class PaymentService extends Service
{
    protected PaymentOrderRepository $orderRepository;
    protected UserRepository $userRepository;
    protected \app\repository\order\OrderOrderRepository $orderOrderRepository;
    protected MemberRechargeOrderRepository $memberRechargeOrderRepository;
    protected SystemConfigRepository $systemConfigRepository;
    protected OrderPayService $orderPayService;

    /**
     * C 端为当前用户自己的商城订单创建支付单。
     *
     * 普通支付入口不接受前端自定义金额，也不能脱离业务订单创建支付单；
     * 充值等内部业务继续调用 createOrder() 并显式传入 user_id/biz_type。
     */
    public function createOrderForUser(int $userId, string $channel, array $params): array
    {
        $businessOrderNo = trim((string)($params['order_no'] ?? ''));
        if ($businessOrderNo === '') {
            throw new BusinessException('订单号不能为空');
        }

        $businessOrder = $this->orderOrderRepository->findByOrderNoAndUser($businessOrderNo, $userId);
        if (!$businessOrder) {
            // 不区分“订单不存在”和“订单不属于当前用户”，避免泄露订单是否存在。
            throw new BusinessException('订单不存在');
        }

        // 零元订单（优惠券全额抵扣等）：完成支付而非拉起渠道
        if ((float)($businessOrder['pay_amount'] ?? 0) <= 0) {
            return $this->completeZeroPayForUser($businessOrder);
        }

        if (($businessOrder['status'] ?? '') !== 'pending') {
            throw new BusinessException('当前订单状态不可支付');
        }

        $params['order_no'] = $businessOrderNo;
        $params['user_id'] = $userId;

        return $this->createOrder($channel, $params);
    }

    /**
     * 零元商城订单：完成支付并返回 need_pay=false（兼容旧客户端仍调 payment/create）。
     *
     * @param array<string, mixed> $businessOrder
     * @return array{need_pay: bool, paid: bool, order_no: string, pay_amount: string}
     */
    protected function completeZeroPayForUser(array $businessOrder): array
    {
        $orderId = (int)($businessOrder['id'] ?? 0);
        $orderNo = (string)($businessOrder['order_no'] ?? '');
        $status = (string)($businessOrder['status'] ?? '');

        if ($status === OrderOrder::STATUS_PENDING) {
            $payload = $this->runInTransaction(
                fn () => $this->orderPayService->completeZeroPay($orderId)
            );
            $this->orderPayService->dispatchZeroPaidEvent($payload);
        } elseif (!in_array($status, [
            OrderOrder::STATUS_PAID,
            OrderOrder::STATUS_SHIPPED,
            OrderOrder::STATUS_COMPLETED,
        ], true)) {
            throw new BusinessException('当前订单状态不可支付');
        }

        return [
            'need_pay'   => false,
            'paid'       => true,
            'order_no'   => $orderNo,
            'pay_amount' => '0.00',
        ];
    }

    /**
     * 创建支付订单
     * @param string $channel alipay / wechat
     * @param array $params [out_trade_no, total_amount, subject, body, trade_type, notify_url, ...]
     * @return array
     */
    public function createOrder(string $channel, array $params): array
    {
        return $this->createOrderAttempt($channel, $params, true);
    }

    /**
     * @param bool $recoverCreating 遇到本地 creating 屏障时是否先渠道对账再重试一次
     */
    private function createOrderAttempt(string $channel, array $params, bool $recoverCreating): array
    {
        $channel = strtolower(trim($channel));
        $params['trade_type'] = $this->normalizeTradeType(
            $channel,
            (string)($params['trade_type'] ?? '')
        );
        $businessOrderNo = trim((string)($params['order_no'] ?? ''));
        $prepared = $this->runInTransaction(function () use (
            $channel,
            $params,
            $businessOrderNo
        ): array {
            $existing = null;
            $hasPriorAttempts = false;
            $providerExpiresAt = '';
            // 支付创建与取消统一锁序为 business order -> payment order。重复拉起
            // 支付时也必须锁 payment 行，绝不能让旧请求把并发回调的 paid 倒退。
            if ($businessOrderNo !== '') {
                $businessOrder = $this->orderOrderRepository->findByOrderNoForUpdate($businessOrderNo);
                if (!$businessOrder) {
                    throw new BusinessException('订单不存在');
                }
                if (isset($params['user_id'])
                    && (int)$businessOrder['user_id'] !== (int)$params['user_id']) {
                    throw new BusinessException('订单不存在');
                }
                if (($businessOrder['status'] ?? '') !== 'pending') {
                    throw new BusinessException('当前订单状态不可支付');
                }
                $providerExpiresAt = $this->resolveProviderExpiresAt(
                    $businessOrder['auto_cancel_at'] ?? null
                );
                $businessAmountCents = $this->moneyToCents($businessOrder['pay_amount'] ?? '');
                if ($businessAmountCents <= 0) {
                    // 正常应在 createOrderForUser 提前完成；此处防止误走渠道
                    throw new BusinessException('订单无需在线支付，请刷新订单状态');
                }
                $candidates = $this->orderRepository->getBusinessOrderCandidatesForUpdate(
                    $businessOrderNo
                );
                // 兼容未升级的第三方 Repository 替身；正式实现始终返回数组并
                // 覆盖 exact + ORD_ 两种候选，生产不会退回单号猜测。
                if (!is_array($candidates)) {
                    $legacy = $this->orderRepository->findByOrderNoForUpdate($businessOrderNo);
                    $candidates = $legacy ? [$this->paymentModelSnapshot($legacy)] : [];
                }
                foreach ($candidates as $candidate) {
                    $this->assertBusinessPaymentIdentity(
                        $candidate,
                        $businessOrderNo,
                        (int)$businessOrder['user_id'],
                        $businessAmountCents
                    );
                }
                $hasPriorAttempts = $candidates !== [];
                $existing = $this->selectActiveBusinessPayment($candidates);
                if ($existing !== null) {
                    if ((string)($existing['channel'] ?? '') !== $channel) {
                        throw new BusinessException('支付方式已锁定，请继续使用原支付方式');
                    }
                }

                // 同一业务订单可保留多条已关闭历史尝试，但任意时刻只能有一条
                // active/paid 尝试。首次支付兼容原业务单号；历史尝试已关闭后，
                // 新请求使用独立商户单号并通过 business_order_no 明确关联。
                $params['out_trade_no'] = $existing !== null
                    ? (string)$existing['order_no']
                    : ($hasPriorAttempts ? $this->generateOrderNo($channel) : $businessOrderNo);
                $params['total_amount'] = $this->formatCents($businessAmountCents);
                $params['subject'] = $params['subject'] ?? ('订单 ' . $businessOrderNo);
                $params['user_id'] = (int)$businessOrder['user_id'];
                $params['biz_type'] = 'order';
            } else {
                $providerExpiresAt = $this->resolveProviderExpiresAt(null);
            }

            // 与 SystemConfigService 共用同一配置行锁：先固定收款主体/验签配置，
            // 再签发本地 creating。配置切换因此不可能穿过“0 未决单”检查。
            $this->systemConfigRepository->lockPaymentConfigForUpdate($channel);
            $driver = $this->resolveCreateDriver($channel);
            $providerAccountId = trim($driver->getAccountId());
            if ($providerAccountId === '') {
                throw new BusinessException('支付渠道收款主体未配置');
            }

            $orderNo = trim((string)($params['out_trade_no'] ?? ''));
            if ($orderNo === '') {
                $orderNo = $this->generateOrderNo($channel);
                $params['out_trade_no'] = $orderNo;
            }
            $amountCents = $this->moneyToCents($params['total_amount'] ?? '');
            if ($amountCents <= 0) {
                throw new BusinessException('支付金额必须大于零');
            }
            $params['total_amount'] = $this->formatCents($amountCents);
            $params['expire_at'] = $providerExpiresAt;

            if ($businessOrderNo === '') {
                $existingModel = $this->orderRepository->findByOrderNoForUpdate($orderNo);
                $existing = $existingModel !== null
                    ? $this->paymentModelSnapshot($existingModel)
                    : null;
            }

            $providerAppId = $channel === 'alipay'
                ? $providerAccountId
                : trim((string)($params['appid'] ?? ''));

            // 所有会确定性失败的本地参数/配置校验都在写 creating 前完成。
            $driver->validateCreate($params);
            $requestHash = $this->paymentRequestHash($channel, $params, $providerAppId);

            if ($existing) {
                $existingStatus = (string)($existing['status'] ?? '');
                if ($existingStatus === PaymentOrder::STATUS_PAID) {
                    throw new BusinessException('订单已支付，请勿重复支付');
                }
                if ($existingStatus === PaymentOrder::STATUS_CREATING) {
                    // 事务外对账后再重试；此处只带回支付单身份，不改状态。
                    return ['recover_creating' => $existing];
                }
                if ($existingStatus === PaymentOrder::STATUS_CLOSING) {
                    throw new BusinessException('支付订单正在关闭，不能重新发起支付');
                }
                if ($existingStatus !== PaymentOrder::STATUS_PENDING) {
                    throw new BusinessException('支付订单已关闭，不能重新发起支付');
                }
                if (($existing['user_id'] ?? null) !== null
                    && isset($params['user_id'])
                    && (int)$existing['user_id'] !== (int)$params['user_id']) {
                    throw new BusinessException('支付订单归属异常');
                }
                if ((string)($existing['channel'] ?? '') !== $channel) {
                    throw new BusinessException('支付方式已锁定，请继续使用原支付方式');
                }
                if ($this->moneyToCents($existing['total_amount'] ?? '') !== $amountCents) {
                    throw new BusinessException('支付单金额与业务订单不一致');
                }
                $existingAccountId = trim((string)($existing['provider_account_id'] ?? ''));
                if ($existingAccountId === '') {
                    throw new BusinessException('历史未决支付单缺少收款主体，需人工核对后处理');
                }
                if (!hash_equals($existingAccountId, $providerAccountId)) {
                    throw new BusinessException('支付单收款主体已变化，禁止重新签发');
                }
                $existingAppId = trim((string)($existing['provider_app_id'] ?? ''));
                if ($existingAppId !== '' && !hash_equals($existingAppId, $providerAppId)) {
                    throw new BusinessException('支付单 AppID 已变化，禁止重新签发');
                }

                $cachedPaymentData = $this->freshCachedPaymentData($existing, $requestHash);
                if ($cachedPaymentData !== null) {
                    return [
                        'order_no' => (string)$existing['order_no'],
                        'payment_order_id' => (int)$existing['id'],
                        'params' => $params,
                        'driver' => $driver,
                        'cached_payment_data' => $cachedPaymentData,
                    ];
                }

                // provider 外呼前先用 creating 占用创建权；取消流程见到该状态必须
                // 等待本次外呼结束，不能把 ORDER_NOT_EXIST 当作可取消证明。
                $attemptToken = $this->newPaymentAttemptToken();
                if ($this->orderRepository->markCreatingIfPending((int)$existing['id'], [
                    'trade_type'  => $params['trade_type'] ?? $existing['trade_type'] ?? null,
                    'subject'     => $params['subject'] ?? $existing['subject'] ?? '',
                    'body'        => $params['body'] ?? $existing['body'] ?? '',
                    'client_type' => $params['client_type'] ?? $existing['client_type'] ?? null,
                    'biz_type'    => $params['biz_type'] ?? $existing['biz_type'] ?? null,
                    'business_order_no' => $businessOrderNo !== '' ? $businessOrderNo : null,
                    'user_id'     => $params['user_id'] ?? $existing['user_id'] ?? null,
                    'provider_account_id' => $providerAccountId,
                    'provider_app_id' => $providerAppId !== '' ? $providerAppId : null,
                    'provider_expires_at' => $providerExpiresAt,
                    'provider_attempt_token' => $attemptToken,
                    'provider_request_hash' => $requestHash,
                ]) !== 1) {
                    throw new BusinessException('支付单状态已变化，请重试');
                }
                $paymentOrderId = (int)$existing['id'];
            } else {
                $attemptToken = $this->newPaymentAttemptToken();
                $paymentOrder = $this->orderRepository->createOrder([
                    'order_no'     => $orderNo,
                    'channel'      => $channel,
                    'trade_type'   => $params['trade_type'] ?? 'native',
                    'subject'      => $params['subject'] ?? '',
                    'body'         => $params['body'] ?? '',
                    'total_amount' => $params['total_amount'],
                    'status'       => PaymentOrder::STATUS_CREATING,
                    'user_id'      => $params['user_id'] ?? null,
                    'biz_type'     => $params['biz_type'] ?? null,
                    'business_order_no' => $businessOrderNo !== '' ? $businessOrderNo : null,
                    'client_type'  => $params['client_type'] ?? null,
                    'provider_account_id' => $providerAccountId,
                    'provider_app_id' => $providerAppId !== '' ? $providerAppId : null,
                    'provider_expires_at' => $providerExpiresAt,
                    'provider_attempt_token' => $attemptToken,
                    'provider_request_hash' => $requestHash,
                ]);
                $paymentOrderId = (int)$paymentOrder->id;
            }

            return [
                'order_no' => $orderNo,
                'payment_order_id' => $paymentOrderId,
                'params' => $params,
                'driver' => $driver,
                'attempt_token' => $attemptToken,
                'request_hash' => $requestHash,
            ];
        });

        if (isset($prepared['recover_creating']) && is_array($prepared['recover_creating'])) {
            if (!$recoverCreating) {
                throw new BusinessException('支付订单正在创建，请稍后重试');
            }
            try {
                $this->reconcileProviderPayment($prepared['recover_creating']);
            } catch (\Throwable $e) {
                $this->paymentWarn('payment.creating_reconcile_on_create_failed', [
                    'payment_order_id' => (int)($prepared['recover_creating']['id'] ?? 0),
                    'error' => $e->getMessage(),
                ]);
                // 配置/签名等确定性失败：渠道侧不可能留下可支付交易，本地释放后再重试。
                if (!$this->releaseCreatingBarrierOnDefiniteRejection(
                    $prepared['recover_creating'],
                    $e,
                    false
                )) {
                    throw new BusinessException('支付订单正在创建，请稍后重试');
                }
            }
            return $this->createOrderAttempt($channel, $params, false);
        }

        $orderNo = (string)$prepared['order_no'];
        $params = $prepared['params'];

        if (array_key_exists('cached_payment_data', $prepared)) {
            return [
                'order_no' => $orderNo,
                'payment_id' => (int)$prepared['payment_order_id'],
                'payment_data' => $prepared['cached_payment_data'],
            ];
        }

        // provider 网络调用位于本地事务外，但 creating 屏障保持到响应结束。
        $params['out_trade_no'] = $orderNo;
        /** @var PaymentInterface $driver */
        $driver = $prepared['driver'];
        try {
            $result = $driver->create($params);
        } catch (\Throwable $e) {
            // 明确渠道无单或配置/签名确定性拒绝时释放屏障，便于立即重试；
            // 网络超时等歧义失败仍保留 creating，交给对账任务。
            $this->tryReleaseCreatingAfterProviderCreateFailure(
                $driver,
                $orderNo,
                (int)$prepared['payment_order_id'],
                (string)$prepared['attempt_token'],
                $e
            );
            throw $e;
        }

        $mustCloseProvider = $this->releaseCreateBarrier(
            (int)$prepared['payment_order_id'],
            $businessOrderNo,
            true,
            (string)$prepared['attempt_token'],
            [
                'payment_data' => $result,
                'stored_at' => date('Y-m-d H:i:s'),
                'attempt_token' => (string)$prepared['attempt_token'],
                'request_hash' => (string)$prepared['request_hash'],
            ]
        );
        if ($mustCloseProvider) {
            $this->closeCreatedPayment(
                $driver,
                $orderNo,
                (int)$prepared['payment_order_id'],
                (string)$prepared['attempt_token']
            );
            throw new BusinessException('业务订单状态已变化，支付入口已关闭');
        }

        return [
            'order_no'      => $orderNo,
            'payment_id'    => (int)$prepared['payment_order_id'],
            'payment_data'  => $result,
        ];
    }

    /**
     * 在取消商城订单前关闭其渠道支付入口。外部调用位于事务外；成功后再以
     * payment 行锁把本地 pending 原子推进为 closed。任何已支付/歧义记录都中止取消。
     *
     * @return array{status:string,payment_order_id:int|null,provider_status:string}
     */
    public function closePendingBusinessOrder(
        string $businessOrderNo,
        int $expectedUserId,
        string|int|float $expectedAmount,
        bool $recoverCreating = true
    ): array {
        $businessOrderNo = trim($businessOrderNo);
        if ($businessOrderNo === '' || $expectedUserId <= 0) {
            throw new BusinessException('取消订单缺少可信业务标识');
        }
        $expectedCents = $this->moneyToCents($expectedAmount);

        $candidate = $this->runInTransaction(function () use (
            $businessOrderNo,
            $expectedUserId,
            $expectedCents
        ): ?array {
            // 与支付创建相同的 order -> payment 锁序，并在外呼 close 前把
            // pending 原子 claim 为 closing，阻止并发重拉支付。
            $businessOrder = $this->orderOrderRepository->findByOrderNoForUpdate($businessOrderNo);
            if (!$businessOrder
                || (int)($businessOrder['user_id'] ?? 0) !== $expectedUserId
                || $this->moneyToCents($businessOrder['pay_amount'] ?? 0) !== $expectedCents) {
                throw new BusinessException('业务订单与取消请求不匹配');
            }
            if (($businessOrder['status'] ?? '') !== 'pending') {
                throw new BusinessException('仅待付款订单可以关闭支付入口');
            }

            $candidates = $this->orderRepository->getBusinessOrderCandidatesForUpdate(
                $businessOrderNo
            );
            if ($candidates === []) {
                return null;
            }

            foreach ($candidates as $candidateRow) {
                $this->assertBusinessPaymentIdentity(
                    $candidateRow,
                    $businessOrderNo,
                    $expectedUserId,
                    $expectedCents
                );
            }
            $candidate = $this->selectActiveBusinessPayment($candidates);
            if ($candidate === null) {
                // 只剩已由 provider 明确关闭的历史尝试，业务订单可以继续取消。
                return $candidates[array_key_last($candidates)];
            }
            $status = (string)($candidate['status'] ?? '');
            if ($status === PaymentOrder::STATUS_CREATING) {
                // 事务外对账后再重试取消；此处不 claim closing。
                return $candidate;
            }
            if ($status === PaymentOrder::STATUS_CLOSING) {
                throw new BusinessException('支付订单正在关闭，请稍后重试取消');
            }
            if ($status !== PaymentOrder::STATUS_PENDING) {
                throw new BusinessException('订单已支付或支付状态异常，不能直接取消');
            }
            if ($this->orderRepository->markClosingIfPending((int)$candidate['id']) !== 1) {
                throw new BusinessException('支付单状态已变化，请重试');
            }
            $candidate['status'] = PaymentOrder::STATUS_CLOSING;
            return $candidate;
        });

        if ($candidate === null) {
            return ['status' => 'none', 'payment_order_id' => null, 'provider_status' => 'NOT_CREATED'];
        }
        $paymentOrderId = (int)$candidate['id'];
        $status = (string)($candidate['status'] ?? '');
        if ($status === PaymentOrder::STATUS_CREATING) {
            if (!$recoverCreating) {
                throw new BusinessException('支付订单正在创建，请稍后重试取消');
            }
            try {
                $this->reconcileProviderPayment($candidate);
            } catch (\Throwable $e) {
                $this->paymentWarn('payment.creating_reconcile_on_cancel_failed', [
                    'payment_order_id' => $paymentOrderId,
                    'error' => $e->getMessage(),
                ]);
                // 配置错误导致查单/关单都不可用时，creating 无渠道交易可证明存在，
                // 直接本地关闭屏障，避免用户永久无法取消订单。
                if (!$this->releaseCreatingBarrierOnDefiniteRejection($candidate, $e, true)) {
                    throw new BusinessException('支付订单正在创建，请稍后重试取消');
                }
            }
            return $this->closePendingBusinessOrder(
                $businessOrderNo,
                $expectedUserId,
                $expectedAmount,
                false
            );
        }
        if ($status === PaymentOrder::STATUS_CLOSED) {
            return ['status' => 'closed', 'payment_order_id' => $paymentOrderId, 'provider_status' => 'LOCAL_CLOSED'];
        }

        $providerOrderNo = (string)$candidate['order_no'];
        try {
            $driver = $this->resolveDriver((string)$candidate['channel']);
            $this->assertDriverAccountMatchesPayment($driver, $candidate);
        } catch (\Throwable $e) {
            $this->releaseClosingPayment($paymentOrderId);
            throw $e;
        }
        try {
            $result = $driver->close($providerOrderNo);
        } catch (\Throwable $e) {
            // close 外呼已经发出，异常无法证明渠道未关；保留 closing，交给
            // 渠道对账/人工核对，禁止重新签发第二笔凭据。
            throw $e;
        }
        if (($result['status'] ?? '') !== PaymentOrder::STATUS_CLOSED
            || trim((string)($result['out_trade_no'] ?? '')) === ''
            || !hash_equals($providerOrderNo, (string)$result['out_trade_no'])) {
            throw new BusinessException('支付渠道未确认订单关闭');
        }
        $resultAccountId = trim((string)($result['provider_account_id'] ?? ''));
        if ($resultAccountId === '' || !hash_equals($driver->getAccountId(), $resultAccountId)) {
            throw new BusinessException('支付渠道收款主体与支付单不匹配');
        }
        if ((string)$candidate['channel'] === 'alipay'
            && strtoupper((string)($result['provider_status'] ?? '')) === 'TRADE_NOT_EXIST'
            && !$this->isAlipayNotExistCloseSafe($candidate)) {
            $this->releaseClosingPayment($paymentOrderId);
            throw new BusinessException('支付宝支付凭据尚未明确失效，需到期后再取消');
        }

        $this->runInTransaction(function () use (
            $paymentOrderId,
            $businessOrderNo,
            $expectedUserId,
            $expectedCents
        ): void {
            $this->orderOrderRepository->findByOrderNoForUpdate($businessOrderNo);
            $locked = $this->orderRepository->findForUpdate($paymentOrderId);
            if (!$locked) {
                throw new BusinessException('支付单不存在');
            }
            $this->assertBusinessPaymentIdentity(
                $locked->toArray(),
                $businessOrderNo,
                $expectedUserId,
                $expectedCents
            );
            if ((string)$locked->status === PaymentOrder::STATUS_CLOSED) {
                return;
            }
            if ((string)$locked->status !== PaymentOrder::STATUS_CLOSING) {
                throw new BusinessException('支付单已确认付款，不能取消订单');
            }
            if ($this->orderRepository->markClosedIfClosing($paymentOrderId) !== 1) {
                throw new BusinessException('支付单状态已变化，请重试');
            }
        });

        return [
            'status' => 'closed',
            'payment_order_id' => $paymentOrderId,
            'provider_status' => (string)($result['provider_status'] ?? 'CLOSED'),
        ];
    }

    /**
     * 查询当前用户自己的支付单状态。
     *
     * 支付渠道以服务端支付单记录为准，禁止前端指定渠道探测任意订单号。
     */
    public function queryOrderForUser(int $userId, string $orderNo): array
    {
        $paymentOrder = $this->orderRepository->findByOrderNoAndUser($orderNo, $userId);
        if (!$paymentOrder) {
            throw new BusinessException('支付订单不存在');
        }

        return $this->queryOrder((string)$paymentOrder->channel, $orderNo);
    }

    /**
     * 查询订单状态
     */
    public function queryOrder(string $channel, string $orderNo): array
    {
        // 查询入口也先以本地支付单为准，不能让调用方借错误渠道探测或推进订单。
        $localOrder = $this->orderRepository->findByOrderNo($orderNo);
        if (!$localOrder) {
            throw new BusinessException('支付订单不存在');
        }
        if (!hash_equals((string)$localOrder->channel, $channel)) {
            throw new BusinessException('支付渠道与支付单不匹配');
        }

        $driver = $this->resolveDriver($channel);
        $result = $driver->query($orderNo);

        // 只有 provider 明确确认 paid 时才进入统一终结器。closed/refunded 等状态
        // 不能在查询链路直接覆盖本地已支付状态。
        if (($result['status'] ?? '') === PaymentOrder::STATUS_PAID) {
            $finalized = $this->finalizePaid($channel, $orderNo, $result);
            $result['status'] = PaymentOrder::STATUS_PAID;
            $result['trade_no'] = $finalized['event']['trade_no'];
            $result['total_amount'] = $finalized['event']['amount'];
            $result['amount_cents'] = $finalized['event']['amount_cents'];
        }

        return $result;
    }

    /**
     * 受限窗口内的渠道主动对账。该入口只处理本地仍未终结的支付单；provider
     * 明确 paid 走与回调相同的严格终结器，未知/网络异常向上抛给游标任务记录。
     *
     * @return array{payment_order_id:int,order_no:string,status:string,provider_status:string}
     */
    public function reconcileProviderPayment(array $row): array
    {
        $paymentOrderId = (int)($row['id'] ?? 0);
        $orderNo = trim((string)($row['order_no'] ?? ''));
        $channel = trim((string)($row['channel'] ?? ''));
        if ($paymentOrderId <= 0 || $orderNo === '' || !in_array($channel, ['alipay', 'wechat'], true)) {
            throw new BusinessException('渠道对账支付单身份不完整');
        }

        $local = $this->orderRepository->findForUpdate($paymentOrderId);
        if (!$local || !hash_equals((string)$local->order_no, $orderNo)
            || (string)$local->channel !== $channel) {
            throw new BusinessException('渠道对账支付单与本地记录不匹配');
        }
        $localStatus = (string)$local->status;
        $observedAttemptToken = trim((string)($local->provider_attempt_token ?? ''));
        if (in_array($localStatus, [PaymentOrder::STATUS_PAID, PaymentOrder::STATUS_REFUNDED], true)) {
            return [
                'payment_order_id' => $paymentOrderId,
                'order_no' => $orderNo,
                'status' => $localStatus,
                'provider_status' => 'LOCAL_TERMINAL',
            ];
        }

        $driver = $this->resolveDriver($channel);
        $result = $driver->query($orderNo);
        $providerOrderNo = trim((string)($result['out_trade_no'] ?? ''));
        if ($providerOrderNo === '' || !hash_equals($orderNo, $providerOrderNo)) {
            throw new BusinessException('渠道对账订单号不匹配');
        }
        $this->assertProviderIdentityForLocalPayment($local, $result);

        if (($result['status'] ?? '') === PaymentOrder::STATUS_PAID) {
            $this->finalizePaid($channel, $orderNo, $result);
            return [
                'payment_order_id' => $paymentOrderId,
                'order_no' => $orderNo,
                'status' => PaymentOrder::STATUS_PAID,
                'provider_status' => (string)($result['provider_status'] ?? 'PAID'),
            ];
        }

        $providerStatus = strtoupper(trim((string)($result['provider_status'] ?? 'UNKNOWN')));
        if (($result['status'] ?? '') === PaymentOrder::STATUS_CLOSED) {
            $this->markProviderClosed($paymentOrderId, $observedAttemptToken);
            return [
                'payment_order_id' => $paymentOrderId,
                'order_no' => $orderNo,
                'status' => PaymentOrder::STATUS_CLOSED,
                'provider_status' => $providerStatus,
            ];
        }

        if (($result['status'] ?? '') === PaymentOrder::STATUS_PENDING) {
            if ($localStatus === PaymentOrder::STATUS_CREATING) {
                if (($result['provider_exists'] ?? true) === false) {
                    // 明确不存在渠道交易时才释放 creating；provider 已存在但本地
                    // 响应/凭据可能丢失，先保持 creating 并由下一轮 fenced 查询处理，
                    // 禁止重签同一商户单号造成双凭据。
                    $this->runInTransaction(function () use ($paymentOrderId, $observedAttemptToken): void {
                        $locked = $this->orderRepository->findForUpdate($paymentOrderId);
                        if ($locked
                            && (string)$locked->status === PaymentOrder::STATUS_CREATING
                            && $observedAttemptToken !== ''
                            && hash_equals(
                                $observedAttemptToken,
                                trim((string)$locked->provider_attempt_token)
                            )) {
                            $this->orderRepository->markPendingIfCreating(
                                $paymentOrderId,
                                [],
                                $observedAttemptToken
                            );
                        }
                    });
                } else {
                    // provider 已有待支付交易但本地没有可靠调起凭据；关闭该交易
                    // 后才能安全重试。外呼异常保留 creating，等待下一轮对账。
                    $closeResult = $driver->close($orderNo);
                    $closeAccount = trim((string)($closeResult['provider_account_id'] ?? ''));
                    if (($closeResult['status'] ?? '') === PaymentOrder::STATUS_CLOSED
                        && $closeAccount !== ''
                        && hash_equals($driver->getAccountId(), $closeAccount)) {
                        $this->markProviderClosed($paymentOrderId, $observedAttemptToken);
                    }
                }
            } elseif ($localStatus === PaymentOrder::STATUS_CLOSING) {
                $exists = ($result['provider_exists'] ?? true) === true;
                if ($exists) {
                    $closeResult = $driver->close($orderNo);
                    if (($closeResult['status'] ?? '') !== PaymentOrder::STATUS_CLOSED
                        || !hash_equals($orderNo, (string)($closeResult['out_trade_no'] ?? ''))) {
                        throw new BusinessException('渠道对账关单未确认');
                    }
                    $this->markProviderClosed($paymentOrderId, $observedAttemptToken);
                    $providerStatus = (string)($closeResult['provider_status'] ?? 'CLOSED');
                } elseif ($channel === 'alipay' && $this->isAlipayNotExistCloseSafe($local->toArray())) {
                    $this->markProviderClosed($paymentOrderId, $observedAttemptToken);
                } else {
                    $this->releaseClosingPayment($paymentOrderId);
                }
            }
        }

        return [
            'payment_order_id' => $paymentOrderId,
            'order_no' => $orderNo,
            'status' => (string)($this->orderRepository->findForUpdate($paymentOrderId)?->status ?? $localStatus),
            'provider_status' => $providerStatus,
        ];
    }

    /**
     * 请求支付渠道执行一笔售后退款。
     *
     * 本方法只负责调用外部支付 provider，不修改本地退款单或支付单。
     * OrderRefundService 会先在独立事务中预占可退额度，然后在事务外调用
     * 本方法，最后再以一个新事务结算累计退款金额。
     *
     * @internal 仅供 OrderRefundService 调用。
     */
    public function refund(string $channel, array $params): array
    {
        if (empty($params['out_trade_no'])) {
            throw new BusinessException(lang('business.order_no_required'));
        }

        if (empty($params['out_refund_no'])) {
            throw new BusinessException('退款单号不能为空');
        }

        $refundAmountCents = $this->moneyToCents($params['refund_amount'] ?? '');
        if ($refundAmountCents <= 0) {
            throw new BusinessException(lang('business.refund_amount_positive'));
        }

        $paymentOrder = $this->orderRepository->findByOrderNo($params['out_trade_no']);
        if (!$paymentOrder) {
            throw new BusinessException(lang('business.payment_order_not_found'));
        }

        if ((string)$paymentOrder->channel !== $channel) {
            throw new BusinessException('支付渠道与支付单不匹配');
        }

        if ($paymentOrder->status !== PaymentOrder::STATUS_PAID) {
            throw new BusinessException(lang('business.order_status_no_refund'));
        }

        $totalAmountCents = $this->moneyToCents($paymentOrder->total_amount);
        $refundedAmountCents = $this->moneyToCents($paymentOrder->refund_amount);
        if ($refundAmountCents > $totalAmountCents - $refundedAmountCents) {
            throw new BusinessException(lang('business.refund_exceed_amount'));
        }

        // 传给驱动的金额也统一成两位小数字符串，避免浮点边界在 SDK 层放大。
        $params['refund_amount'] = $this->formatCents($refundAmountCents);
        $params['total_amount'] = $this->formatCents($totalAmountCents);

        $driver = $this->resolveDriver($channel);
        $result = $driver->refund($params);

        $status = (string)($result['status'] ?? '');
        if (!in_array($status, ['success', 'processing'], true)) {
            throw new BusinessException('支付渠道未接受退款请求');
        }

        $this->validateRefundResult(
            $params,
            $result,
            $status,
            $refundAmountCents,
            $totalAmountCents,
            trim((string)($paymentOrder->trade_no ?? ''))
        );
        $result = $this->normalizeRefundIdentity($channel, $params, $result, $status);

        return $result;
    }

    /**
     * 查询支付渠道上的退款状态。只读查询仍以本地支付单为信任根，并对商户订单号、
     * 商户退款单号、原支付交易号和金额做与发起退款相同的严格校验。
     *
     * @internal 仅供 OrderRefundService 的退款同步/补偿使用。
     */
    public function queryRefund(string $channel, array $params): array
    {
        $orderNo = trim((string)($params['out_trade_no'] ?? ''));
        $refundNo = trim((string)($params['out_refund_no'] ?? ''));
        if ($orderNo === '') {
            throw new BusinessException(lang('business.order_no_required'));
        }
        if ($refundNo === '') {
            throw new BusinessException('退款单号不能为空');
        }

        $refundAmountCents = $this->moneyToCents($params['refund_amount'] ?? '');
        if ($refundAmountCents <= 0) {
            throw new BusinessException(lang('business.refund_amount_positive'));
        }

        $paymentOrder = $this->orderRepository->findByOrderNo($orderNo);
        if (!$paymentOrder) {
            throw new BusinessException(lang('business.payment_order_not_found'));
        }
        if (!hash_equals((string)$paymentOrder->channel, $channel)) {
            throw new BusinessException('支付渠道与支付单不匹配');
        }
        if (!in_array(
            (string)$paymentOrder->status,
            [PaymentOrder::STATUS_PAID, PaymentOrder::STATUS_REFUNDED],
            true
        )) {
            throw new BusinessException('支付单当前状态不可查询退款');
        }

        $totalAmountCents = $this->moneyToCents($paymentOrder->total_amount);
        if ($refundAmountCents > $totalAmountCents) {
            throw new BusinessException(lang('business.refund_exceed_amount'));
        }

        $params['out_trade_no'] = $orderNo;
        $params['out_refund_no'] = $refundNo;
        $params['refund_amount'] = $this->formatCents($refundAmountCents);
        $params['total_amount'] = $this->formatCents($totalAmountCents);

        $result = $this->resolveDriver($channel)->queryRefund($params);
        $status = trim((string)($result['status'] ?? 'unknown'));
        if (!in_array($status, ['success', 'processing', 'failed', 'unknown'], true)) {
            throw new BusinessException('支付渠道退款查询状态非法');
        }

        $this->validateRefundResult(
            $params,
            $result,
            $status,
            $refundAmountCents,
            $totalAmountCents,
            trim((string)($paymentOrder->trade_no ?? ''))
        );

        return $this->normalizeRefundIdentity($channel, $params, $result, $status);
    }

    /**
     * 退款同步响应必须与本次预占的商户订单、退款单严格对应。provider 返回
     * success 时还必须精确到分核对金额；processing 至少核对两个幂等标识，
     * 若渠道同时返回金额也一并核对。
     */
    protected function validateRefundResult(
        array $params,
        array $result,
        string $status,
        int $refundAmountCents,
        int $totalAmountCents,
        string $localPaymentTradeNo
    ): void {
        $expectedOrderNo = trim((string)$params['out_trade_no']);
        $expectedRefundNo = trim((string)$params['out_refund_no']);
        $providerOrderNo = trim((string)($result['out_trade_no'] ?? ''));
        $providerRefundNo = trim((string)($result['out_refund_no'] ?? ''));

        if (
            $providerOrderNo === ''
            || !hash_equals($expectedOrderNo, $providerOrderNo)
            || $providerRefundNo === ''
            || !hash_equals($expectedRefundNo, $providerRefundNo)
        ) {
            throw new BusinessException('支付渠道退款响应标识与本次退款不匹配');
        }

        $providerAmount = $result['refund_amount'] ?? null;
        if ($status === 'success' && ($providerAmount === null || trim((string)$providerAmount) === '')) {
            throw new BusinessException('支付渠道退款响应缺少退款金额');
        }
        if ($providerAmount !== null && trim((string)$providerAmount) !== '') {
            try {
                $providerAmountCents = $this->moneyToCents($providerAmount);
            } catch (BusinessException) {
                throw new BusinessException('支付渠道退款金额格式非法');
            }
            if ($providerAmountCents !== $refundAmountCents) {
                throw new BusinessException('支付渠道退款金额与本次退款不匹配');
            }
        }

        $providerTotal = $result['total_amount'] ?? null;
        if ($providerTotal !== null && trim((string)$providerTotal) !== '') {
            try {
                $providerTotalCents = $this->moneyToCents($providerTotal);
            } catch (BusinessException) {
                throw new BusinessException('支付渠道原订单金额格式非法');
            }
            if ($providerTotalCents !== $totalAmountCents) {
                throw new BusinessException('支付渠道原订单金额与本地支付单不匹配');
            }
        }

        $currency = strtoupper(trim((string)($result['currency'] ?? 'CNY')));
        if ($currency !== 'CNY') {
            throw new BusinessException('支付渠道退款币种不匹配');
        }

        $providerPaymentTradeNo = trim((string)($result['payment_trade_no'] ?? ''));
        if (
            $providerPaymentTradeNo !== ''
            && $localPaymentTradeNo !== ''
            && !hash_equals($localPaymentTradeNo, $providerPaymentTradeNo)
        ) {
            throw new BusinessException('支付渠道原支付交易号与本地支付单不匹配');
        }
    }

    /** @return array<string, mixed> */
    protected function normalizeRefundIdentity(
        string $channel,
        array $params,
        array $result,
        string $status
    ): array {
        // 统一明确“原支付交易号”与“退款标识”。微信必须使用 refund_id；
        // 支付宝退款接口没有独立退款流水号，使用本次签名请求的 out_request_no。
        if ($channel === 'wechat') {
            $refundTradeNo = trim((string)($result['refund_trade_no'] ?? ''));
            if (in_array($status, ['success', 'processing'], true) && $refundTradeNo === '') {
                throw new BusinessException('微信退款响应缺少 refund_id');
            }
            $result['refund_trade_no'] = $refundTradeNo;
            $result['refund_trade_no_source'] = 'wechat_refund_id';
        } elseif ($channel === 'alipay') {
            $result['refund_trade_no'] = trim((string)$params['out_refund_no']);
            $result['refund_trade_no_source'] = 'alipay_out_request_no';
        } else {
            $result['refund_trade_no'] = trim((string)($result['refund_trade_no'] ?? $params['out_refund_no']));
            $result['refund_trade_no_source'] = trim((string)($result['refund_trade_no_source'] ?? 'merchant_refund_no'));
        }
        $result['provider_status'] = trim((string)($result['provider_status'] ?? strtoupper($status)));

        return $result;
    }

    /**
     * 处理支付回调
     */
    public function handleNotify(string $channel, array $params): string
    {
        $driver = $this->resolveDriver($channel);

        try {
            $data = $driver->verifyNotify($params);
        } catch (\Throwable $e) {
            $this->reportPaymentError("支付回调验签失败 [{$channel}]: " . $e->getMessage());
            return 'fail';
        }

        if (($data['status'] ?? '') === PaymentOrder::STATUS_PAID) {
            try {
                $this->finalizePaid(
                    $channel,
                    (string)($data['out_trade_no'] ?? ''),
                    $data
                );
            } catch (\Throwable $e) {
                $this->reportPaymentError("支付回调业务校验失败 [{$channel}]: " . $e->getMessage(), [
                    'order_no' => $data['out_trade_no'] ?? '',
                ]);
                return 'fail';
            }
        }

        return $driver->successResponse();
    }

    /**
     * 查询与异步通知共用的支付终结器。
     *
     * provider 数据只用于证明“哪笔交易已成功”；事件中的业务字段全部从持锁的
     * payment_orders 读取。合法 paid 重放仍会再次分发事件，用于修复上次遗漏的
     * 消费者；每个消费者必须以 event_key 自身幂等。
     *
     * @return array{transitioned: bool, event: array<string, mixed>}
     */
    protected function finalizePaid(
        string $channel,
        string $expectedOrderNo,
        array $providerData
    ): array {
        $providerOrderNo = trim((string)($providerData['out_trade_no'] ?? ''));
        $tradeNo = trim((string)($providerData['trade_no'] ?? ''));

        if ($expectedOrderNo === '' || $providerOrderNo === '' || !hash_equals($expectedOrderNo, $providerOrderNo)) {
            throw new BusinessException('支付平台订单号与本地请求不匹配');
        }
        if (($providerData['status'] ?? '') !== PaymentOrder::STATUS_PAID) {
            throw new BusinessException('支付平台尚未确认付款成功');
        }
        if (($providerData['identity_verified'] ?? false) !== true) {
            throw new BusinessException('支付平台商户身份未通过校验');
        }
        if (strtoupper((string)($providerData['currency'] ?? '')) !== 'CNY') {
            throw new BusinessException('支付币种不匹配');
        }
        if ($tradeNo === '') {
            throw new BusinessException('支付平台交易号不能为空');
        }
        if (!array_key_exists('amount_cents', $providerData) || !is_int($providerData['amount_cents'])) {
            throw new BusinessException('支付平台金额格式非法');
        }

        $result = $this->runInTransaction(function () use (
            $channel,
            $providerOrderNo,
            $tradeNo,
            $providerData
        ): array {
            $paymentOrder = $this->orderRepository->findByOrderNoForUpdate($providerOrderNo);
            if (!$paymentOrder) {
                throw new BusinessException('支付订单不存在');
            }

            if (!hash_equals((string)$paymentOrder->order_no, $providerOrderNo)) {
                throw new BusinessException('支付平台订单号与本地支付单不匹配');
            }
            if (!hash_equals((string)$paymentOrder->channel, $channel)) {
                throw new BusinessException('支付渠道与支付单不匹配');
            }

            $localCents = $this->moneyToCents($paymentOrder->total_amount);
            if ($localCents !== $providerData['amount_cents']) {
                throw new BusinessException('支付金额与本地支付单不匹配');
            }

            $providerAccountId = trim((string)($providerData['provider_account_id'] ?? ''));
            $storedAccountId = trim((string)($paymentOrder->provider_account_id ?? ''));
            if ($storedAccountId !== ''
                && ($providerAccountId === '' || !hash_equals($storedAccountId, $providerAccountId))) {
                throw new BusinessException('支付平台收款主体与本地支付单不匹配');
            }
            $storedAppId = trim((string)($paymentOrder->provider_app_id ?? ''));
            $providerAppId = trim((string)($providerData['provider_app_id'] ?? ''));
            if ($storedAppId !== ''
                && ($providerAppId === '' || !hash_equals($storedAppId, $providerAppId))) {
                throw new BusinessException('支付平台 AppID 与本地支付单不匹配');
            }

            $status = (string)$paymentOrder->status;
            if (!in_array($status, [
                PaymentOrder::STATUS_CREATING,
                PaymentOrder::STATUS_PENDING,
                PaymentOrder::STATUS_CLOSING,
                PaymentOrder::STATUS_PAID,
            ], true)) {
                throw new BusinessException('当前支付单状态不可确认付款');
            }

            $persistedTradeNo = trim((string)($paymentOrder->trade_no ?? ''));
            if ($status === PaymentOrder::STATUS_PAID && $persistedTradeNo !== '' && !hash_equals($persistedTradeNo, $tradeNo)) {
                throw new BusinessException('支付平台交易号与已入账记录不匹配');
            }

            $transitioned = false;
            if (in_array($status, [
                PaymentOrder::STATUS_CREATING,
                PaymentOrder::STATUS_PENDING,
                PaymentOrder::STATUS_CLOSING,
            ], true)) {
                $rawJson = json_encode($providerData['raw'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if (!is_string($rawJson)) {
                    $rawJson = '{}';
                }
                $affected = $this->orderRepository->markPaidIfPending(
                    (int)$paymentOrder->id,
                    $tradeNo,
                    $rawJson
                );
                if ($affected !== 1) {
                    throw new BusinessException('支付单状态已变化，请重试');
                }
                $transitioned = true;
                $persistedTradeNo = $tradeNo;
            } elseif ($persistedTradeNo === '') {
                $this->orderRepository->fillPaidTradeNoIfEmpty((int)$paymentOrder->id, $tradeNo);
                $persistedTradeNo = $tradeNo;
            }

            $storedBusinessOrderNo = $this->paymentBusinessOrderNo($paymentOrder);
            $bizType = $this->resolveLocalBizType(
                trim((string)($paymentOrder->biz_type ?? '')),
                (string)$paymentOrder->order_no,
                $storedBusinessOrderNo
            );
            $resolvedBusinessOrderNo = $this->resolveOrderBusinessNo(
                $bizType,
                (string)$paymentOrder->order_no,
                $storedBusinessOrderNo
            );
            $eventKey = sprintf(
                'payment.success:%s:%s',
                $bizType,
                (string)$paymentOrder->order_no
            );

            return [
                'transitioned' => $transitioned,
                'event' => [
                    'payment_order_id' => (int)$paymentOrder->id,
                    'event_key'        => $eventKey,
                    'order_no'        => (string)$paymentOrder->order_no,
                    'business_order_no' => $resolvedBusinessOrderNo,
                    'trade_no'        => $persistedTradeNo,
                    'amount'          => $this->formatCents($localCents),
                    'amount_cents'    => $localCents,
                    'currency'        => 'CNY',
                    'channel'         => (string)$paymentOrder->channel,
                    'user_id'         => $paymentOrder->user_id !== null ? (int)$paymentOrder->user_id : null,
                    'biz_type'        => $bizType,
                ],
            ];
        });

        $this->dispatchPaymentSuccess($result['event']);
        return $result;
    }

    /**
     * 仅以本地已支付记录重放 payment.success 消费者，不查询支付平台。
     *
     * 供受控命令修复“payment_orders 已 paid，但某个同步消费者漏执行”的场景。
     * 状态、金额、渠道、用户和业务类型均从持锁的本地支付单构造。
     *
     * @return array<string, mixed> 重放的可信事件
     */
    public function replayPaidLocal(int $paymentOrderId): array
    {
        $event = $this->runInTransaction(function () use ($paymentOrderId): array {
            $paymentOrder = $this->orderRepository->findForUpdate($paymentOrderId);
            if (!$paymentOrder || (string)$paymentOrder->status !== PaymentOrder::STATUS_PAID) {
                throw new BusinessException('本地支付单不是已支付状态');
            }

            $tradeNo = trim((string)($paymentOrder->trade_no ?? ''));
            if ($tradeNo === '') {
                throw new BusinessException('本地支付单缺少第三方交易号，需人工对账');
            }

            $orderNo = (string)$paymentOrder->order_no;
            $storedBusinessOrderNo = $this->paymentBusinessOrderNo($paymentOrder);
            $bizType = $this->resolveLocalBizType(
                trim((string)($paymentOrder->biz_type ?? '')),
                $orderNo,
                $storedBusinessOrderNo
            );
            $resolvedBusinessOrderNo = $this->resolveOrderBusinessNo(
                $bizType,
                $orderNo,
                $storedBusinessOrderNo
            );
            $amountCents = $this->moneyToCents($paymentOrder->total_amount);
            $paidAt = '';
            try {
                $paidAt = trim((string)($paymentOrder->paid_at ?? ''));
            } catch (\Throwable) {
                // 兼容极旧记录/测试替身缺少 paid_at；自动补偿仓储已排除该类行。
            }

            return [
                'payment_order_id' => (int)$paymentOrder->id,
                'event_key'        => sprintf('payment.success:%s:%s', $bizType, $orderNo),
                'order_no'        => $orderNo,
                'business_order_no' => $resolvedBusinessOrderNo,
                'trade_no'        => $tradeNo,
                'amount'          => $this->formatCents($amountCents),
                'amount_cents'    => $amountCents,
                'currency'        => 'CNY',
                'channel'         => (string)$paymentOrder->channel,
                'user_id'         => $paymentOrder->user_id !== null ? (int)$paymentOrder->user_id : null,
                'biz_type'        => $bizType,
                'occurred_at'     => $paidAt !== '' ? $paidAt : null,
                'replayed'        => true,
            ];
        });

        // 人工补偿必须把消费者异常反馈给命令，不能复用在线回调的“记录后吞掉”
        // 策略，否则 payment:resync 会把仍未修好的记录误报为成功。
        $this->dispatchPaymentSuccessStrict($event);
        return $event;
    }

    /** 手工重放使用的严格分发：任一同步消费者失败都向上抛出。 */
    protected function dispatchPaymentSuccessStrict(array $event): void
    {
        $this->trigger('payment.success', $event);
    }

    /** 支付成功消费者失败不能让 provider 回调反复扣打；后续 paid 重放会补偿。 */
    protected function dispatchPaymentSuccess(array $event): void
    {
        try {
            $this->trigger('payment.success', $event);
        } catch (\Throwable $e) {
            $this->reportPaymentError('payment.success listener 异常: ' . $e->getMessage(), [
                'event_key' => $event['event_key'] ?? '',
                'trace'     => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * 断言业务订单的全部候选支付单都已安全关闭（取消前置校验）。
     *
     * 自 OrderService::assertPaymentsClosedForCancellation 抽取，供订单取消
     * 与拆/合单等资金敏感操作复用。空数组视为无支付尝试，直接通过。
     *
     * @param array<int, array<string, mixed>> $payments
     */
    public function assertBusinessPaymentClosed(
        array $payments,
        string $orderNo,
        int $userId,
        string $expectedAmount
    ): void {
        if ($payments === []) {
            return;
        }

        foreach ($payments as $payment) {
            $linkedBusinessOrderNo = trim((string)($payment['business_order_no'] ?? ''));
            $matchesExplicitLink = $linkedBusinessOrderNo !== ''
                && hash_equals($orderNo, $linkedBusinessOrderNo);
            $matchesLegacyNumber = $linkedBusinessOrderNo === ''
                && in_array(
                    (string)($payment['order_no'] ?? ''),
                    [$orderNo, 'ORD_' . $orderNo],
                    true
                );
            if (!$matchesExplicitLink && !$matchesLegacyNumber) {
                throw new BusinessException('支付单与业务订单不匹配');
            }
            $paymentUserId = (int)($payment['user_id'] ?? 0);
            if ($paymentUserId > 0 && $paymentUserId !== $userId) {
                throw new BusinessException('支付单与业务订单归属不一致');
            }
            if ($this->moneyToCents($payment['total_amount'] ?? 0)
                !== $this->moneyToCents($expectedAmount)) {
                throw new BusinessException('支付单金额与业务订单不一致');
            }
            if (($payment['status'] ?? '') !== PaymentOrder::STATUS_CLOSED) {
                throw new BusinessException('支付单尚未安全关闭，请重试');
            }
        }
    }

    /** @param array<string, mixed> $payment */
    private function assertBusinessPaymentIdentity(
        array $payment,
        string $businessOrderNo,
        int $expectedUserId,
        int $expectedCents
    ): void {
        $paymentOrderNo = trim((string)($payment['order_no'] ?? ''));
        $linkedBusinessOrderNo = trim((string)($payment['business_order_no'] ?? ''));
        $matchesExplicitLink = $linkedBusinessOrderNo !== ''
            && hash_equals($businessOrderNo, $linkedBusinessOrderNo);
        $matchesLegacyNumber = $linkedBusinessOrderNo === ''
            && in_array($paymentOrderNo, [$businessOrderNo, 'ORD_' . $businessOrderNo], true);
        if (!$matchesExplicitLink && !$matchesLegacyNumber) {
            throw new BusinessException('支付单与业务订单不匹配');
        }
        $bizType = trim((string)($payment['biz_type'] ?? ''));
        if ($bizType !== '' && $bizType !== 'order') {
            throw new BusinessException('支付单业务类型异常');
        }
        $paymentUserId = (int)($payment['user_id'] ?? 0);
        if ($paymentUserId > 0 && $paymentUserId !== $expectedUserId) {
            throw new BusinessException('支付单归属异常');
        }
        if ($expectedCents <= 0
            || $this->moneyToCents($payment['total_amount'] ?? '') !== $expectedCents) {
            throw new BusinessException('支付单金额与业务订单不一致');
        }
        if ((int)($payment['id'] ?? 0) <= 0 || trim((string)($payment['channel'] ?? '')) === '') {
            throw new BusinessException('支付单身份不完整');
        }
    }

    /**
     * 从同一业务订单的历史尝试中选出唯一未终结尝试。closed 可保留多条；
     * paid/refunded 永久阻止再签发；未知状态一律失败关闭。
     *
     * @param array<int, array<string, mixed>> $candidates
     * @return array<string, mixed>|null
     */
    private function selectActiveBusinessPayment(array $candidates): ?array
    {
        $active = [];
        foreach ($candidates as $candidate) {
            $status = (string)($candidate['status'] ?? '');
            if (in_array($status, [PaymentOrder::STATUS_PAID, PaymentOrder::STATUS_REFUNDED], true)) {
                throw new BusinessException('订单已支付，请勿重复支付');
            }
            if (in_array($status, [
                PaymentOrder::STATUS_CREATING,
                PaymentOrder::STATUS_PENDING,
                PaymentOrder::STATUS_CLOSING,
            ], true)) {
                $active[] = $candidate;
                continue;
            }
            if ($status !== PaymentOrder::STATUS_CLOSED) {
                throw new BusinessException('支付单状态异常，需人工核对');
            }
        }

        if (count($active) > 1) {
            throw new BusinessException('业务订单存在多条未终结支付单，需人工核对');
        }
        return $active[0] ?? null;
    }

    /** @param array<string, mixed> $payment */
    private function assertDriverAccountMatchesPayment(
        PaymentInterface $driver,
        array $payment
    ): void {
        $stored = trim((string)($payment['provider_account_id'] ?? ''));
        if ($stored === '') {
            throw new BusinessException('支付单缺少签发收款主体，不能安全关单');
        }
        if (!hash_equals($stored, trim($driver->getAccountId()))) {
            throw new BusinessException('当前支付渠道收款主体与原支付单不一致');
        }
    }

    /** @param array<string, mixed> $payment */
    private function isAlipayNotExistCloseSafe(array $payment): bool
    {
        $raw = trim((string)($payment['provider_expires_at'] ?? ''));
        if ($raw === '') {
            return false;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $raw);
        $errors = \DateTimeImmutable::getLastErrors();
        if (!$date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return false;
        }
        // 留出渠道时钟偏差，避免刚到 expiry 的旧表单仍被网关接受。
        return $date->getTimestamp() + 60 <= time();
    }

    private function releaseClosingPayment(int $paymentOrderId): void
    {
        $this->runInTransaction(function () use ($paymentOrderId): void {
            $locked = $this->orderRepository->findForUpdate($paymentOrderId);
            if (!$locked || (string)$locked->status !== PaymentOrder::STATUS_CLOSING) {
                return;
            }
            $this->orderRepository->markPendingIfClosing($paymentOrderId);
        });
    }

    /** @param object $local */
    private function assertProviderIdentityForLocalPayment(object $local, array $provider): void
    {
        $storedAccount = trim((string)($local->provider_account_id ?? ''));
        $providerAccount = trim((string)($provider['provider_account_id'] ?? ''));
        if ($storedAccount !== ''
            && ($providerAccount === '' || !hash_equals($storedAccount, $providerAccount))) {
            throw new BusinessException('渠道对账收款主体不匹配');
        }
        $storedApp = trim((string)($local->provider_app_id ?? ''));
        $providerApp = trim((string)($provider['provider_app_id'] ?? ''));
        $providerExists = ($provider['provider_exists'] ?? true) === true;
        if ($providerExists && $storedApp !== '' && ($providerApp === '' || !hash_equals($storedApp, $providerApp))) {
            throw new BusinessException('渠道对账 AppID 不匹配');
        }
        if (isset($provider['amount_cents']) && (int)$provider['amount_cents'] > 0
            && $this->moneyToCents($local->total_amount) !== (int)$provider['amount_cents']) {
            throw new BusinessException('渠道对账金额不匹配');
        }
    }

    private function markProviderClosed(int $paymentOrderId, string $observedAttemptToken = ''): void
    {
        $this->runInTransaction(function () use ($paymentOrderId, $observedAttemptToken): void {
            $locked = $this->orderRepository->findForUpdate($paymentOrderId);
            if (!$locked) {
                throw new BusinessException('渠道对账支付单不存在');
            }
            $status = (string)$locked->status;
            if (in_array($status, [PaymentOrder::STATUS_PAID, PaymentOrder::STATUS_REFUNDED], true)) {
                throw new BusinessException('支付单已付款，不能以渠道关闭覆盖');
            }
            if ($status === PaymentOrder::STATUS_CLOSED) {
                return;
            }
            if ($observedAttemptToken !== '' && !hash_equals(
                $observedAttemptToken,
                trim((string)$locked->provider_attempt_token)
            )) {
                throw new BusinessException('渠道对账尝试已变化，旧查询结果已隔离');
            }
            $affected = match ($status) {
                PaymentOrder::STATUS_PENDING => $this->orderRepository->markClosedIfPending($paymentOrderId),
                PaymentOrder::STATUS_CREATING => $this->orderRepository->markClosedIfCreating(
                    $paymentOrderId,
                    $observedAttemptToken !== '' ? $observedAttemptToken : null
                ),
                PaymentOrder::STATUS_CLOSING => $this->orderRepository->markClosedIfClosing($paymentOrderId),
                default => 0,
            };
            if ($affected !== 1) {
                throw new BusinessException('渠道关闭落库状态已变化');
            }
        });
    }

    /**
     * provider create 失败后：若查单确认渠道无交易，或创建/查单属于配置与签名类
     * 确定性拒绝，立即把 creating 放回 pending；网络超时等歧义失败仍保留屏障。
     */
    private function tryReleaseCreatingAfterProviderCreateFailure(
        PaymentInterface $driver,
        string $orderNo,
        int $paymentOrderId,
        string $attemptToken,
        ?\Throwable $createError = null
    ): void {
        if ($paymentOrderId <= 0 || $orderNo === '' || $attemptToken === '') {
            return;
        }

        $shouldRelease = false;
        try {
            $result = $driver->query($orderNo);
            $shouldRelease = ($result['provider_exists'] ?? true) === false;
        } catch (\Throwable $e) {
            // 查单失败时：仅当「创建本身已被配置/签名确定性拒绝」才释放。
            // 创建超时后再碰上查单配置失败，仍属歧义，保留屏障。
            $shouldRelease = $this->isDefiniteProviderRejection($createError);
            $this->paymentWarn('payment.create_failure_probe_query_failed', [
                'payment_order_id' => $paymentOrderId,
                'order_no' => $orderNo,
                'error' => $e->getMessage(),
                'release' => $shouldRelease,
            ]);
        }

        if (!$shouldRelease) {
            return;
        }

        try {
            $this->runInTransaction(function () use ($paymentOrderId, $attemptToken): void {
                $locked = $this->orderRepository->findForUpdate($paymentOrderId);
                if (!$locked
                    || (string)$locked->status !== PaymentOrder::STATUS_CREATING
                    || !hash_equals(
                        $attemptToken,
                        trim((string)$locked->provider_attempt_token)
                    )) {
                    return;
                }
                $this->orderRepository->markPendingIfCreating(
                    $paymentOrderId,
                    [],
                    $attemptToken
                );
            });
        } catch (\Throwable $e) {
            $this->paymentWarn('payment.create_failure_release_creating_failed', [
                'payment_order_id' => $paymentOrderId,
                'order_no' => $orderNo,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 对账失败时，仅在「配置/签名等确定性拒绝」下本地释放 creating。
     * $close=true 用于取消（直接 closed）；false 用于再次支付（回到 pending）。
     *
     * @param array<string, mixed> $candidate
     */
    private function releaseCreatingBarrierOnDefiniteRejection(
        array $candidate,
        \Throwable $error,
        bool $close
    ): bool {
        if (!$this->isDefiniteProviderRejection($error)) {
            return false;
        }

        $paymentOrderId = (int)($candidate['id'] ?? 0);
        $attemptToken = trim((string)($candidate['provider_attempt_token'] ?? ''));
        if ($paymentOrderId <= 0) {
            return false;
        }

        try {
            return (bool)$this->runInTransaction(
                function () use ($paymentOrderId, $attemptToken, $close): bool {
                    $locked = $this->orderRepository->findForUpdate($paymentOrderId);
                    if (!$locked) {
                        return false;
                    }
                    $status = (string)$locked->status;
                    if ($status === PaymentOrder::STATUS_CLOSED) {
                        return true;
                    }
                    if ($status === PaymentOrder::STATUS_PENDING) {
                        return !$close;
                    }
                    if ($status !== PaymentOrder::STATUS_CREATING) {
                        return false;
                    }
                    $token = trim((string)($locked->provider_attempt_token ?? ''));
                    if ($attemptToken !== '' && $token !== '' && !hash_equals($attemptToken, $token)) {
                        return false;
                    }
                    $fenceToken = $attemptToken !== '' ? $attemptToken : ($token !== '' ? $token : null);
                    if ($close) {
                        return $this->orderRepository->markClosedIfCreating(
                            $paymentOrderId,
                            $fenceToken
                        ) === 1;
                    }
                    return $this->orderRepository->markPendingIfCreating(
                        $paymentOrderId,
                        [],
                        $fenceToken
                    ) === 1;
                }
            );
        } catch (\Throwable $e) {
            $this->paymentWarn('payment.creating_definite_rejection_release_failed', [
                'payment_order_id' => $paymentOrderId,
                'close' => $close,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /** @param array<string, mixed> $context */
    protected function paymentWarn(string $message, array $context = []): void
    {
        Log::warning($message, $context);
    }

    /**
     * 判断渠道错误是否属于「请求被确定性拒绝 / 本地凭证不可用」。
     * 这类错误下渠道不会留下可继续支付的新单；网络超时等歧义失败不得匹配。
     */
    private function isDefiniteProviderRejection(?\Throwable $error): bool
    {
        if ($error === null) {
            return false;
        }
        $message = strtoupper($error->getMessage());
        if ($message === '') {
            return false;
        }

        // 网络/超时歧义：宁可保留 creating。
        foreach ([
            'TIMEOUT', 'TIMED OUT', 'CURL', 'CONNECTION', 'CONNECT',
            'ECONNRESET', 'ECONNREFUSED', 'BROKEN PIPE', 'RESET BY PEER',
            'SSL CONNECT', '网络', '超时', '连接',
        ] as $ambiguous) {
            if (str_contains($message, strtoupper($ambiguous))) {
                return false;
            }
        }

        foreach ([
            'SIGN_ERROR', 'SIGNATURE', 'PARAM_ERROR', 'APPID_MCHID_NOT_MATCH',
            'MCH_NOT_EXISTS', 'NO_AUTH', 'INVALID_REQUEST', 'FREQUENCY_LIMITED',
            '微信支付配置', 'WECHAT_PAY_CONFIG', 'PUBLIC_KEY', '公钥',
            'PRIVATE_KEY', '私钥', 'KEY_NOT_FOUND', '配置不完整', 'CONFIG_INCOMPLETE',
            'SERIAL', 'API_V3_KEY', '商户证书', '证书序列号',
        ] as $needle) {
            if (str_contains($message, strtoupper($needle))) {
                return true;
            }
        }

        return false;
    }

    /**
     * provider create 返回/异常后释放 creating 屏障。业务订单若已不可支付则
     * 保留 creating 并要求调用方先关闭渠道单，再落本地 closed。
     */
    private function releaseCreateBarrier(
        int $paymentOrderId,
        string $businessOrderNo,
        bool $providerRequestReturned,
        string $attemptToken,
        array $credential = []
    ): bool {
        return $this->runInTransaction(function () use (
            $paymentOrderId,
            $businessOrderNo,
            $providerRequestReturned,
            $attemptToken,
            $credential
        ): bool {
            $businessCanPay = true;
            if ($businessOrderNo !== '') {
                $businessOrder = $this->orderOrderRepository->findByOrderNoForUpdate($businessOrderNo);
                $businessCanPay = $businessOrder !== null
                    && ($businessOrder['status'] ?? '') === 'pending';
            }

            $payment = $this->orderRepository->findForUpdate($paymentOrderId);
            if (!$payment) {
                throw new BusinessException('创建中的支付单不存在');
            }
            $status = (string)$payment->status;
            if ($status === PaymentOrder::STATUS_PAID) {
                return false;
            }
            if ($status === PaymentOrder::STATUS_CLOSED) {
                return $providerRequestReturned;
            }
            if ($status === PaymentOrder::STATUS_PENDING) {
                return !$businessCanPay && $providerRequestReturned;
            }
            if ($status !== PaymentOrder::STATUS_CREATING) {
                throw new BusinessException('支付创建屏障状态异常，需人工核对');
            }
            if (!hash_equals($attemptToken, trim((string)$payment->provider_attempt_token))) {
                throw new BusinessException('支付创建尝试已失效，旧渠道响应已隔离');
            }
            if (!$businessCanPay) {
                return true;
            }
            $extra = [];
            if ($credential !== []) {
                $encoded = json_encode(
                    ['payment_credential' => $credential],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
                if (!is_string($encoded)) {
                    throw new BusinessException('支付凭据缓存失败，请人工核对');
                }
                $extra['extra'] = $encoded;
            }
            if ($this->orderRepository->markPendingIfCreating(
                $paymentOrderId,
                $extra,
                $attemptToken
            ) !== 1) {
                throw new BusinessException('支付创建屏障释放失败，请重试');
            }
            return false;
        });
    }

    /** provider 已创建但业务订单不可支付时，关闭渠道单并结束本地 creating。 */
    private function closeCreatedPayment(
        PaymentInterface $driver,
        string $orderNo,
        int $paymentOrderId,
        string $attemptToken
    ): void {
        $closeResult = $driver->close($orderNo);
        if (($closeResult['status'] ?? '') !== PaymentOrder::STATUS_CLOSED
            || !hash_equals($orderNo, (string)($closeResult['out_trade_no'] ?? ''))) {
            throw new BusinessException('业务订单已取消，但新建渠道支付单关闭失败');
        }
        $accountId = trim((string)($closeResult['provider_account_id'] ?? ''));
        if ($accountId === '' || !hash_equals($driver->getAccountId(), $accountId)) {
            throw new BusinessException('新建渠道支付单收款主体不匹配');
        }
        $this->runInTransaction(function () use ($paymentOrderId, $attemptToken): void {
            $locked = $this->orderRepository->findForUpdate($paymentOrderId);
            if (!$locked) {
                throw new BusinessException('新建支付单不存在，需人工核对');
            }
            if ((string)$locked->status === PaymentOrder::STATUS_CLOSED) {
                return;
            }
            if ((string)$locked->status !== PaymentOrder::STATUS_CREATING
                || !hash_equals($attemptToken, trim((string)$locked->provider_attempt_token))
                || $this->orderRepository->markClosedIfCreating($paymentOrderId, $attemptToken) !== 1) {
                throw new BusinessException('新建支付单本地关闭失败，需人工核对');
            }
        });
    }

    /** 测试可替换的 driver 解析点。 */
    protected function resolveDriver(string $channel): PaymentInterface
    {
        return PaymentManager::channel($channel);
    }

    /** 新建支付专用解析点；enabled 只在这里阻止继续签发新凭据。 */
    protected function resolveCreateDriver(string $channel): PaymentInterface
    {
        return PaymentManager::channelForCreate($channel);
    }

    private function normalizeTradeType(string $channel, string $tradeType): string
    {
        $allowed = match ($channel) {
            'wechat' => ['native', 'jsapi', 'h5', 'app'],
            'alipay' => ['page', 'wap', 'app'],
            default => throw new BusinessException('不支持的支付渠道'),
        };
        $normalized = strtolower(trim($tradeType));
        if ($normalized === '') {
            $normalized = $channel === 'wechat' ? 'native' : 'page';
        }
        if (!in_array($normalized, $allowed, true)) {
            throw new BusinessException('当前支付渠道不支持该交易类型');
        }
        return $normalized;
    }

    /**
     * 凭据时效只由服务端产生。商城订单优先复用可信 auto_cancel_at；充值等
     * 内部业务使用固定 30 分钟 TTL，禁止客户端延长旧表单/二维码寿命。
     */
    private function resolveProviderExpiresAt(mixed $businessAutoCancelAt): string
    {
        $now = time();
        $raw = trim((string)($businessAutoCancelAt ?? ''));
        if ($raw === '') {
            return date('Y-m-d H:i:s', $now + 1800);
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $raw);
        $errors = \DateTimeImmutable::getLastErrors();
        if (!$date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new BusinessException('订单自动取消时间非法，不能创建支付');
        }
        if ($date->getTimestamp() <= $now + 30) {
            throw new BusinessException('订单已到支付截止时间，请重新下单');
        }
        return $date->format('Y-m-d H:i:s');
    }

    /** @return array<string, mixed> */
    private function paymentModelSnapshot(object $payment): array
    {
        $keys = [
            'id', 'order_no', 'status', 'channel', 'trade_type', 'subject', 'body',
            'total_amount', 'user_id', 'biz_type', 'business_order_no', 'client_type',
            'provider_account_id', 'provider_app_id', 'provider_expires_at',
            'provider_attempt_token', 'provider_request_hash',
            'extra',
        ];
        $snapshot = [];
        foreach ($keys as $key) {
            try {
                $snapshot[$key] = $payment->{$key};
            } catch (\Throwable) {
                $snapshot[$key] = null;
            }
        }
        return $snapshot;
    }

    private function paymentBusinessOrderNo(object $payment): string
    {
        try {
            return trim((string)$payment->business_order_no);
        } catch (\Throwable) {
            // 兼容升级前模型快照与不初始化 ORM Attribute 容器的单元测试替身。
            return '';
        }
    }

    /** @param array<string, mixed> $payment */
    private function freshCachedPaymentData(array $payment, string $requestHash): ?array
    {
        $storedRequestHash = trim((string)($payment['provider_request_hash'] ?? ''));
        if ($storedRequestHash === '' || !hash_equals($storedRequestHash, $requestHash)) {
            return null;
        }
        $expiresAt = trim((string)($payment['provider_expires_at'] ?? ''));
        if ($expiresAt === '') {
            return null;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $expiresAt);
        $errors = \DateTimeImmutable::getLastErrors();
        if (!$date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->getTimestamp() <= time() + 60) {
            return null;
        }
        $raw = $payment['extra'] ?? '';
        if (is_array($raw)) {
            $decoded = $raw;
        } else {
            $decoded = json_decode((string)$raw, true);
        }
        $credential = is_array($decoded) ? ($decoded['payment_credential'] ?? null) : null;
        if (!is_array($credential)
            || !hash_equals($requestHash, trim((string)($credential['request_hash'] ?? '')))
            || !hash_equals(
                trim((string)($payment['provider_attempt_token'] ?? '')),
                trim((string)($credential['attempt_token'] ?? ''))
            )) {
            return null;
        }
        $data = $credential['payment_data'] ?? null;
        return is_array($data) ? $data : null;
    }

    /** 同一 payment row 上每次渠道 create 都必须有不可复用的 fencing token。 */
    private function newPaymentAttemptToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * 缓存凭据只能返回给创建它的客户端上下文。手机号、subject 等不影响
     * SDK 调起，真正影响凭据的是渠道交易类型、客户端、AppID 与 openid。
     */
    private function paymentRequestHash(string $channel, array $params, string $providerAppId): string
    {
        $context = [
            'channel' => $channel,
            'trade_type' => strtolower(trim((string)($params['trade_type'] ?? ''))),
            'client_type' => strtolower(trim((string)($params['client_type'] ?? ''))),
            'provider_app_id' => $providerAppId,
            'openid' => trim((string)($params['openid'] ?? '')),
        ];
        $encoded = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($encoded)) {
            throw new BusinessException('支付客户端参数指纹生成失败');
        }
        return hash('sha256', $encoded);
    }

    /** 测试可替换的支付错误日志边界。 */
    protected function reportPaymentError(string $message, array $context = []): void
    {
        Log::error($message, $context);
    }

    protected function moneyToCents(string|int|float $amount): int
    {
        $normalized = trim((string)$amount);
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $normalized)) {
            throw new BusinessException('本地支付金额格式非法');
        }
        [$integer, $decimal] = array_pad(explode('.', $normalized, 2), 2, '');
        return ((int)$integer * 100) + (int)str_pad($decimal, 2, '0');
    }

    protected function formatCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    /**
     * 仅从本地业务表修复早期 payment_orders.biz_type 空值；无法确认时拒绝入账，
     * 避免生成 unknown 事件导致部分消费者永远漏记。
     */
    protected function resolveLocalBizType(
        string $bizType,
        string $orderNo,
        string $businessOrderNo = ''
    ): string
    {
        if (in_array($bizType, ['order', 'recharge'], true)) {
            return $bizType;
        }

        $businessOrderNo = trim($businessOrderNo);
        if ($businessOrderNo !== '') {
            if ($this->orderOrderRepository->findByOrderNo($businessOrderNo)) {
                return 'order';
            }
            throw new BusinessException('支付单业务订单关联无效，需人工对账');
        }

        $businessCandidates = [];
        foreach (array_values(array_unique([
            $orderNo,
            str_starts_with($orderNo, 'ORD_') ? substr($orderNo, 4) : 'ORD_' . $orderNo,
        ])) as $candidateNo) {
            if ($candidateNo !== '' && $this->orderOrderRepository->findByOrderNo($candidateNo)) {
                $businessCandidates[] = $candidateNo;
            }
        }
        if (count($businessCandidates) === 1) {
            return 'order';
        }
        if (count($businessCandidates) > 1) {
            throw new BusinessException('支付单业务订单号存在多义映射，需人工对账');
        }

        $rechargeOrderNo = str_starts_with($orderNo, 'RCH_') ? substr($orderNo, 4) : $orderNo;
        if ($rechargeOrderNo !== '' && $this->memberRechargeOrderRepository->findByOrderNo($rechargeOrderNo)) {
            return 'recharge';
        }

        throw new BusinessException('支付单业务类型异常，需人工对账');
    }

    private function resolveOrderBusinessNo(
        string $bizType,
        string $providerOrderNo,
        string $storedBusinessOrderNo
    ): ?string {
        if ($bizType !== 'order') {
            return null;
        }
        $storedBusinessOrderNo = trim($storedBusinessOrderNo);
        if ($storedBusinessOrderNo !== '') {
            return $storedBusinessOrderNo;
        }
        $legacy = str_starts_with($providerOrderNo, 'ORD_')
            ? substr($providerOrderNo, 4)
            : $providerOrderNo;
        if ($legacy === '') {
            throw new BusinessException('支付单缺少业务订单号，需人工对账');
        }
        return $legacy;
    }

    /**
     * 根据订单号获取支付渠道
     */
    public function getChannelByOrderNo(string $orderNo): ?string
    {
        $order = $this->orderRepository->findByOrderNo($orderNo);
        return $order?->channel;
    }

    /**
     * 生成商户订单号
     */
    protected function generateOrderNo(string $channel): string
    {
        $prefix = match ($channel) {
            'alipay' => 'A',
            'wechat' => 'W',
            default  => 'P',
        };

        return $prefix . date('YmdHis') . bin2hex(random_bytes(6));
    }

    /**
     * 根据客户端类型解析微信支付参数
     */
    public function resolveWechatPayParams(string $clientType, int $userId): array
    {
        $config = PaymentManager::getWechatConfig();
        $defaultAppId = $config['app_id']; // pay_wechat_app_id 兜底

        return match ($clientType) {
            'miniapp'    => [
                'trade_type' => 'jsapi',
                'appid'      => $config['mini_app_id'] ?: $defaultAppId,
                'openid'     => $this->getUserOpenid($userId, 'mini_openid'),
            ],
            'wechat_h5'  => [
                'trade_type' => 'jsapi',
                'appid'      => $config['official_app_id'] ?: $defaultAppId,
                'openid'     => $this->getUserOpenid($userId, 'oa_openid'),
            ],
            'h5'         => [
                'trade_type' => 'h5',
                'appid'      => $config['official_app_id'] ?: $defaultAppId,
                'openid'     => null,
            ],
            'app'        => [
                'trade_type' => 'app',
                'appid'      => $config['mobile_app_id'] ?: $defaultAppId,
                'openid'     => null,
            ],
            'pc'         => [
                'trade_type' => 'native',
                'appid'      => $config['open_app_id'] ?: $defaultAppId,
                'openid'     => null,
            ],
            default => throw new BusinessException('不支持的客户端类型: ' . $clientType),
        };
    }

    protected function getUserOpenid(int $userId, string $field): string
    {
        $openid = $this->userRepository->getFieldById($userId, $field) ?? '';
        if (empty($openid)) {
            throw new BusinessException('请先完成微信授权后再支付');
        }
        return $openid;
    }
}
