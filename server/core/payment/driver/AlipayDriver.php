<?php
declare(strict_types=1);

namespace core\payment\driver;

use core\payment\PaymentInterface;
use core\exception\BusinessException;
use Alipay\EasySDK\Kernel\Factory as AlipayFactory;
use Alipay\EasySDK\Kernel\Config as AlipayConfig;

class AlipayDriver implements PaymentInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        if (empty($config['app_id']) || empty($config['private_key']) || empty($config['public_key'])) {
            throw new BusinessException(lang('business.alipay_config_incomplete'));
        }

        $this->config = $config;
        $this->initSdk();
    }

    protected function initSdk(): void
    {
        $options = new AlipayConfig();
        $options->protocol = 'https';
        $options->gatewayHost = 'openapi.alipay.com';
        $options->signType = 'RSA2';
        $options->appId = $this->config['app_id'];
        $options->merchantPrivateKey = $this->config['private_key'];
        $options->alipayPublicKey = $this->config['public_key'];
        $options->notifyUrl = $this->config['notify_url'] ?? '';

        AlipayFactory::setOptions($options);
    }

    public function getAccountId(): string
    {
        return trim((string)$this->config['app_id']);
    }

    public function validateCreate(array $order): void
    {
        $tradeType = trim((string)($order['trade_type'] ?? 'page'));
        if (!in_array($tradeType, ['page', 'wap', 'app'], true)) {
            throw new BusinessException("不支持的支付宝交易类型: {$tradeType}");
        }
        if (trim((string)($order['out_trade_no'] ?? '')) === '') {
            throw new BusinessException('支付宝支付缺少商户订单号');
        }
        if (trim((string)($order['subject'] ?? '')) === '') {
            throw new BusinessException('支付宝支付缺少订单标题');
        }
        if ($this->moneyToCents($order['total_amount'] ?? '') <= 0) {
            throw new BusinessException('支付宝支付金额必须大于零');
        }
        $this->normalizeExpireAt($order['expire_at'] ?? null);
    }

    public function create(array $order): array
    {
        try {
            $this->validateCreate($order);
            $tradeType = $order['trade_type'] ?? 'page';
            $totalAmount = $this->formatCents($this->moneyToCents($order['total_amount']));
            $expireAt = $this->normalizeExpireAt($order['expire_at'] ?? null);

            $result = match ($tradeType) {
                'page' => AlipayFactory::payment()->page()->optional('time_expire', $expireAt)->pay(
                    $order['subject'],
                    $order['out_trade_no'],
                    $totalAmount,
                    $order['return_url'] ?? ''
                ),
                'wap' => AlipayFactory::payment()->wap()->optional('time_expire', $expireAt)->pay(
                    $order['subject'],
                    $order['out_trade_no'],
                    $totalAmount,
                    $order['quit_url'] ?? '',
                    $order['return_url'] ?? ''
                ),
                'app' => AlipayFactory::payment()->app()->optional('time_expire', $expireAt)->pay(
                    $order['subject'],
                    $order['out_trade_no'],
                    $totalAmount
                ),
                default => throw new BusinessException("不支持的支付宝交易类型: {$tradeType}"),
            };

            return [
                'trade_type' => $tradeType,
                // 与 WechatPayDriver 保持一致的嵌套结构 `{trade_type, data: {...}}`，
                // 前端统一通过 `payment_data.data.<field>` 访问
                'data'       => [
                    'body' => $result->body,
                ],
            ];
        } catch (BusinessException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new BusinessException(lang('business.alipay_create_order_failed') . ': ' . $e->getMessage());
        }
    }

    public function query(string $outTradeNo): array
    {
        try {
            $result = AlipayFactory::payment()->common()->query($outTradeNo);
            $data = json_decode($result->httpBody, true);
            $response = $data['alipay_trade_query_response'] ?? [];

            $code = trim((string)($response['code'] ?? ''));
            $subCode = strtoupper(trim((string)($response['sub_code'] ?? '')));
            if ($code !== '10000') {
                return [
                    'out_trade_no' => $outTradeNo,
                    'trade_no' => '',
                    'total_amount' => '0.00',
                    'amount_cents' => 0,
                    'currency' => 'CNY',
                    'status' => $subCode === 'ACQ.TRADE_NOT_EXIST' ? 'pending' : 'unknown',
                    'provider_status' => $subCode !== '' ? $subCode : ($code !== '' ? $code : 'UNKNOWN'),
                    'provider_exists' => $subCode !== 'ACQ.TRADE_NOT_EXIST',
                    'provider_merchant_id' => '',
                    'provider_account_id' => $this->getAccountId(),
                    'provider_app_id' => (string)$this->config['app_id'],
                    'identity_verified' => true,
                    'identity_source' => 'authenticated_request',
                    'raw' => $response,
                ];
            }

            // 支付宝查询响应通常不回传 app_id；请求本身由配置 app_id 签名，故明确
            // 标为 authenticated_request。若响应带 app_id/auth_app_id，仍必须匹配。
            return $this->normalizeTransaction($response, $outTradeNo, false);
        } catch (BusinessException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new BusinessException(lang('business.alipay_query_order_failed') . ': ' . $e->getMessage());
        }
    }

    public function close(string $outTradeNo): array
    {
        $outTradeNo = trim($outTradeNo);
        if ($outTradeNo === '') {
            throw new BusinessException('支付宝关闭订单缺少商户订单号');
        }

        try {
            $result = AlipayFactory::util()->generic()->execute(
                'alipay.trade.close',
                [],
                ['out_trade_no' => $outTradeNo]
            );
            $data = json_decode($result->httpBody, true);
            $response = is_array($data)
                ? (array)($data['alipay_trade_close_response'] ?? [])
                : [];
            $code = trim((string)($response['code'] ?? ''));
            $subCode = strtoupper(trim((string)($response['sub_code'] ?? '')));

            if ($code !== '10000' && $subCode !== 'ACQ.TRADE_NOT_EXIST') {
                throw new BusinessException(
                    '支付宝关闭订单失败: '
                    . (string)($response['sub_msg'] ?? $response['msg'] ?? '未知错误')
                );
            }

            return [
                'status' => 'closed',
                // 交易不存在时 provider 不回传订单号；该值来自本次已签名请求。
                'out_trade_no' => (string)($response['out_trade_no'] ?? $outTradeNo),
                'provider_status' => $subCode === 'ACQ.TRADE_NOT_EXIST'
                    ? 'TRADE_NOT_EXIST'
                    : 'TRADE_CLOSED',
                'provider_account_id' => $this->getAccountId(),
            ];
        } catch (BusinessException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new BusinessException('支付宝关闭订单失败: ' . $e->getMessage());
        }
    }

    public function refund(array $refund): array
    {
        try {
            // EasySDK common()->refund() 不暴露 out_request_no，重试时无法保证幂等。
            // 通过 Generic API 显式传入售后单号，同一售后单重试始终使用同一值。
            $result = AlipayFactory::util()->generic()->execute(
                'alipay.trade.refund',
                [],
                [
                    'out_trade_no'  => $refund['out_trade_no'],
                    'refund_amount' => $this->formatCents($this->moneyToCents($refund['refund_amount'])),
                    'out_request_no' => $refund['out_refund_no'],
                    'refund_reason' => $refund['reason'] ?? '退款',
                ]
            );
            $data = json_decode($result->httpBody, true);
            $response = $data['alipay_trade_refund_response'] ?? [];

            if (($response['code'] ?? '') !== '10000') {
                throw new BusinessException(lang('business.alipay_refund_failed') . ': ' . ($response['sub_msg'] ?? $response['msg'] ?? lang('messages.unknown_error')));
            }

            return $this->normalizeRefundResponse($response, $refund);
        } catch (BusinessException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new BusinessException(lang('business.alipay_refund_failed') . ': ' . $e->getMessage());
        }
    }

    public function queryRefund(array $refund): array
    {
        $outTradeNo = trim((string)($refund['out_trade_no'] ?? ''));
        $outRefundNo = trim((string)($refund['out_refund_no'] ?? ''));
        if ($outTradeNo === '' || $outRefundNo === '') {
            throw new BusinessException('支付宝退款查询缺少商户订单号或退款单号');
        }

        try {
            $result = AlipayFactory::util()->generic()->execute(
                'alipay.trade.fastpay.refund.query',
                [],
                [
                    'out_trade_no' => $outTradeNo,
                    'out_request_no' => $outRefundNo,
                ]
            );
            $data = json_decode($result->httpBody, true);
            $response = is_array($data)
                ? (array)($data['alipay_trade_fastpay_refund_query_response'] ?? [])
                : [];

            return $this->normalizeRefundQueryResponse($response, $refund);
        } catch (BusinessException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new BusinessException('支付宝退款查询失败: ' . $e->getMessage());
        }
    }

    /**
     * 支付宝成功响应可能省略商户订单号和 out_request_no；两者均来自本次
     * 已签名请求，可安全回填后交给 PaymentService 做严格本地一致性校验。
     *
     * @return array<string, mixed>
     */
    protected function normalizeRefundResponse(array $response, array $refund): array
    {
        return [
            'out_trade_no'  => $response['out_trade_no'] ?? $refund['out_trade_no'],
            // trade_no 是原支付交易号；alipay.trade.refund 不提供独立退款交易号。
            'payment_trade_no' => $response['trade_no'] ?? '',
            'refund_amount' => $response['refund_fee'] ?? '',
            'total_amount' => $response['total_amount'] ?? '',
            'currency' => strtoupper((string)($response['currency'] ?? 'CNY')),
            // 支付宝该接口响应不回传 out_request_no；此字段来自本次已签名请求，
            // PaymentService 仍会与本地预占退款单号做严格匹配。
            'out_refund_no' => $refund['out_refund_no'],
            'refund_trade_no' => $refund['out_refund_no'],
            'refund_trade_no_source' => 'alipay_out_request_no',
            'status' => 'success',
            'provider_status' => 'REFUND_SUCCESS',
            'provider_refunded_at' => trim((string)($response['gmt_refund_pay'] ?? '')),
            'raw' => $response,
        ];
    }

    /**
     * 支付宝查询只有明确的 REFUND_SUCCESS 才可推进本地结算。接口成功但未返回
     * 该状态、或业务错误，均归一为 unknown/failed，交由人工或后续查询继续确认。
     *
     * @return array<string, mixed>
     */
    protected function normalizeRefundQueryResponse(array $response, array $refund): array
    {
        $code = trim((string)($response['code'] ?? ''));
        $refundStatus = strtoupper(trim((string)($response['refund_status'] ?? '')));
        $providerStatus = $refundStatus !== ''
            ? $refundStatus
            : strtoupper(trim((string)($response['sub_code'] ?? ($code !== '' ? $code : 'UNKNOWN'))));
        $localStatus = match ($refundStatus) {
            'REFUND_SUCCESS' => 'success',
            'REFUND_PROCESSING', 'PROCESSING' => 'processing',
            'REFUND_CLOSED', 'REFUND_FAILED', 'FAILED' => 'failed',
            default => in_array($providerStatus, [
                'ACQ.TRADE_NOT_EXIST',
                'ACQ.REFUND_NOT_EXIST',
                'REFUND_NOT_EXIST',
            ], true) ? 'failed' : 'unknown',
        };

        return [
            // 查询请求由本地配置签名；provider 省略商户标识时可使用签名请求回填。
            'out_trade_no' => $response['out_trade_no'] ?? $refund['out_trade_no'],
            'out_refund_no' => $response['out_request_no'] ?? $refund['out_refund_no'],
            'payment_trade_no' => $response['trade_no'] ?? '',
            'refund_trade_no' => $refund['out_refund_no'],
            'refund_trade_no_source' => 'alipay_out_request_no',
            'refund_amount' => $response['refund_amount'] ?? $response['refund_fee'] ?? '',
            'total_amount' => $response['total_amount'] ?? '',
            'currency' => strtoupper((string)($response['currency'] ?? 'CNY')),
            'status' => $localStatus,
            'provider_status' => $providerStatus !== '' ? $providerStatus : 'UNKNOWN',
            'provider_refunded_at' => $localStatus === 'success'
                ? trim((string)($response['gmt_refund_pay'] ?? ''))
                : '',
            'raw' => $response,
        ];
    }

    public function verifyNotify(array $params): array
    {
        try {
            $verified = AlipayFactory::payment()->common()->verifyNotify($params);

            if (!$verified) {
                throw new BusinessException(lang('business.alipay_callback_verify_failed'));
            }

            return $this->normalizeTransaction($params, '', true);
        } catch (BusinessException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new BusinessException(lang('business.alipay_callback_process_failed') . ': ' . $e->getMessage());
        }
    }

    public function successResponse(): string
    {
        return 'success';
    }

    public function getDriver(): string
    {
        return 'alipay';
    }

    protected function mapStatus(string $tradeStatus): string
    {
        return match ($tradeStatus) {
            'TRADE_SUCCESS', 'TRADE_FINISHED' => 'paid',
            'TRADE_CLOSED' => 'closed',
            'WAIT_BUYER_PAY' => 'pending',
            default => 'unknown',
        };
    }

    /**
     * 统一支付宝查询/回调结构；回调必须携带 provider app_id，查询则允许使用
     * 已签名请求所绑定的配置 app_id。
     */
    protected function normalizeTransaction(
        array $data,
        string $fallbackOrderNo = '',
        bool $requireProviderAppId = false
    ): array {
        $reportedAppId = trim((string)($data['app_id'] ?? $data['auth_app_id'] ?? ''));
        if ($requireProviderAppId && $reportedAppId === '') {
            throw new BusinessException('支付宝回调缺少 AppID');
        }
        if ($reportedAppId !== '' && !hash_equals((string)$this->config['app_id'], $reportedAppId)) {
            throw new BusinessException('支付宝 AppID 不匹配');
        }

        $currency = strtoupper(trim((string)($data['currency'] ?? 'CNY')));
        if ($currency !== 'CNY') {
            throw new BusinessException('支付宝币种不匹配');
        }

        $amountCents = $this->moneyToCents($data['total_amount'] ?? '0');
        return [
            'out_trade_no' => (string)($data['out_trade_no'] ?? $fallbackOrderNo),
            'trade_no'     => (string)($data['trade_no'] ?? ''),
            'total_amount' => $this->formatCents($amountCents),
            'amount_cents' => $amountCents,
            'currency'     => $currency,
            'status'       => $this->mapStatus((string)($data['trade_status'] ?? '')),
            'provider_status' => strtoupper(trim((string)($data['trade_status'] ?? 'UNKNOWN'))),
            'provider_exists' => true,
            'provider_merchant_id' => '',
            'provider_account_id' => $this->getAccountId(),
            'provider_app_id'      => $reportedAppId !== '' ? $reportedAppId : (string)$this->config['app_id'],
            'identity_verified'    => true,
            'identity_source'      => $reportedAppId !== '' ? 'provider' : 'authenticated_request',
            'raw'                  => $data,
        ];
    }

    protected function moneyToCents(string|int|float $amount): int
    {
        $normalized = trim((string)$amount);
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $normalized)) {
            throw new BusinessException('支付宝金额格式非法');
        }
        [$integer, $decimal] = array_pad(explode('.', $normalized, 2), 2, '');
        return ((int)$integer * 100) + (int)str_pad($decimal, 2, '0');
    }

    protected function formatCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    private function normalizeExpireAt(mixed $value): string
    {
        $expireAt = trim((string)$value);
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $expireAt);
        $errors = \DateTimeImmutable::getLastErrors();
        if (!$date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new BusinessException('支付宝支付凭据失效时间非法');
        }
        if ($date->getTimestamp() <= time()) {
            throw new BusinessException('支付宝支付凭据失效时间必须晚于当前时间');
        }
        return $date->format('Y-m-d H:i:s');
    }
}
