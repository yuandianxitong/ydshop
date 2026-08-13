<?php
declare(strict_types=1);

namespace core\payment\driver;

use core\payment\PaymentInterface;
use core\payment\wechat\WechatPayCredential;
use core\exception\BusinessException;
use WeChatPay\Builder;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Crypto\AesGcm;
use WeChatPay\Formatter;

class WechatPayDriver implements PaymentInterface
{
    protected array $config;
    protected WechatPayCredential $credential;
    protected $instance;

    public function __construct(array $config)
    {
        if ($this->allowedAppIds($config) === []) {
            throw new BusinessException(lang('business.wechat_pay_config_incomplete'));
        }

        $this->config     = $config;
        $this->credential = new WechatPayCredential($config);
        $this->initSdk();
    }

    protected function initSdk(): void
    {
        $this->instance = Builder::factory([
            // Guzzle 默认 timeout=0（无限等待）。支付创建必须有上界，避免旧
            // attempt 在对账释放后才迟到返回并污染新的创建尝试。
            'connect_timeout' => 5.0,
            'timeout'    => 15.0,
            'mchid'      => $this->credential->mchId(),
            'serial'     => $this->credential->serialNo(),
            'privateKey' => $this->credential->privateKey(),
            'certs'      => $this->credential->certs(),
            // SDK 只在校验应答时读 Wechatpay-Serial，不会自动在请求头声明；
            // 不声明则微信可能改用平台证书签名应答，导致 SDK 验签直接失败。
            'headers'    => ['Wechatpay-Serial' => $this->credential->publicKeyId()],
        ]);
    }

    public function getAccountId(): string
    {
        return $this->credential->mchId();
    }

    public function validateCreate(array $order): void
    {
        $tradeType = trim((string)($order['trade_type'] ?? 'native'));
        if (!in_array($tradeType, ['native', 'jsapi', 'h5', 'app'], true)) {
            throw new BusinessException("不支持的微信支付交易类型: {$tradeType}");
        }
        if (trim((string)($order['out_trade_no'] ?? '')) === '') {
            throw new BusinessException('微信支付缺少商户订单号');
        }
        if (trim((string)($order['subject'] ?? '')) === '') {
            throw new BusinessException('微信支付缺少订单标题');
        }
        if ($this->moneyToCents($order['total_amount'] ?? '') <= 0) {
            throw new BusinessException('微信支付金额必须大于零');
        }
        $appId = trim((string)($order['appid'] ?? $this->config['app_id'] ?? ''));
        $this->assertAllowedAppId($appId);
        if ($tradeType === 'jsapi' && trim((string)($order['openid'] ?? '')) === '') {
            throw new BusinessException('微信 JSAPI 支付缺少用户 openid');
        }
        $this->normalizeExpireAt($order['expire_at'] ?? null);
    }

    public function create(array $order): array
    {
        try {
            $this->validateCreate($order);
            $tradeType = $order['trade_type'] ?? 'native';
            $appId = $order['appid'] ?? $this->config['app_id'] ?? '';
            $this->assertAllowedAppId((string)$appId);

            $params = [
                'json' => [
                    'appid'        => $appId,
                    'mchid'        => $this->credential->mchId(),
                    'description'  => $order['subject'] ?? '',
                    'out_trade_no' => $order['out_trade_no'],
                    'notify_url'   => $this->resolveNotifyUrl($order['notify_url'] ?? $this->config['notify_url'] ?? ''),
                    'time_expire'  => $this->normalizeExpireAt($order['expire_at'] ?? null),
                    'amount'       => [
                        'total'    => $this->moneyToCents($order['total_amount']),
                        'currency' => 'CNY',
                    ],
                ],
            ];

            // 根据交易类型选择不同 API
            $endpoint = match ($tradeType) {
                'native' => 'v3/pay/transactions/native',
                'jsapi'  => 'v3/pay/transactions/jsapi',
                'h5'     => 'v3/pay/transactions/h5',
                'app'    => 'v3/pay/transactions/app',
                default  => throw new BusinessException("不支持的微信支付交易类型: {$tradeType}"),
            };

            // JSAPI 需要 payer 信息
            if ($tradeType === 'jsapi' && !empty($order['openid'])) {
                $params['json']['payer'] = ['openid' => $order['openid']];
            }

            // H5 需要场景信息
            if ($tradeType === 'h5') {
                $params['json']['scene_info'] = [
                    'payer_client_ip' => $order['client_ip'] ?? '127.0.0.1',
                    'h5_info'         => ['type' => 'Wap'],
                ];
            }

            $resp = $this->instance->chain($endpoint)->post($params);
            $result = json_decode($resp->getBody()->getContents(), true);

            // 根据交易类型组装前端所需的支付参数
            $payData = match ($tradeType) {
                'jsapi'  => $this->buildJsapiParams($appId, $result['prepay_id'] ?? ''),
                'app'    => $this->buildAppParams($appId, $result['prepay_id'] ?? ''),
                'native' => ['code_url' => $result['code_url'] ?? ''],
                'h5'     => ['h5_url' => $result['h5_url'] ?? ''],
                default  => $result,
            };

            return [
                'trade_type' => $tradeType,
                'data'       => $payData,
            ];
        } catch (BusinessException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new BusinessException(lang('business.wechat_pay_create_order_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * 组装 JSAPI（小程序/公众号）调起支付的签名参数
     */
    protected function buildJsapiParams(string $appId, string $prepayId): array
    {
        $params = [
            'appId'     => $appId,
            'timeStamp' => (string)Formatter::timestamp(),
            'nonceStr'  => Formatter::nonce(),
            'package'   => 'prepay_id=' . $prepayId,
            'signType'  => 'RSA',
        ];
        $params['paySign'] = Rsa::sign(
            Formatter::joinedByLineFeed($params['appId'], $params['timeStamp'], $params['nonceStr'], $params['package']),
            $this->credential->privateKey()
        );
        return $params;
    }

    /**
     * 组装 APP 调起支付的签名参数
     */
    protected function buildAppParams(string $appId, string $prepayId): array
    {
        $params = [
            'appid'     => $appId,
            'partnerid' => $this->credential->mchId(),
            'prepayid'  => $prepayId,
            'package'   => 'Sign=WXPay',
            'noncestr'  => Formatter::nonce(),
            'timestamp' => (string)Formatter::timestamp(),
        ];
        $params['sign'] = Rsa::sign(
            Formatter::joinedByLineFeed($params['appid'], $params['timestamp'], $params['noncestr'], $params['prepayid']),
            $this->credential->privateKey()
        );
        return $params;
    }

    /**
     * 解析回调通知 URL，相对路径自动补全当前域名
     */
    protected function resolveNotifyUrl(string $url): string
    {
        if (empty($url)) {
            return '';
        }
        // 已经是完整 URL
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }
        // 相对路径，从当前请求补全域名
        $request = app()->request;
        $scheme = $request->scheme();
        $host = $request->host();
        return $scheme . '://' . $host . '/' . ltrim($url, '/');
    }

    public function query(string $outTradeNo): array
    {
        try {
            // 使用 URI 模板避免 SDK normalize() 将大写订单号转为 kebab-case
            $resp = $this->instance
                ->chain('v3/pay/transactions/out-trade-no/{out_trade_no}')
                ->get([
                    'out_trade_no' => $outTradeNo,
                    'query'        => ['mchid' => $this->credential->mchId()],
                ]);

            $result = json_decode($resp->getBody()->getContents(), true);

            return $this->normalizeTransaction($result, $outTradeNo);
        } catch (BusinessException $e) {
            throw $e;
        } catch (\Exception $e) {
            // ORDER_NOT_EXIST 对于新创建的订单是正常的，返回 pending 而非报错
            if (str_contains($e->getMessage(), 'ORDER_NOT_EXIST')) {
                return [
                    'out_trade_no' => $outTradeNo,
                    'trade_no'     => '',
                    'total_amount' => '0.00',
                    'amount_cents' => 0,
                    'currency'     => 'CNY',
                    'status'       => 'pending',
                    'provider_status' => 'ORDER_NOT_EXIST',
                    'provider_exists' => false,
                    'provider_merchant_id' => $this->credential->mchId(),
                    'provider_account_id' => $this->getAccountId(),
                    'provider_app_id'      => '',
                    'identity_verified'    => true,
                    'identity_source'      => 'authenticated_request',
                    'raw'          => [],
                ];
            }
            throw new BusinessException(lang('business.wechat_pay_query_order_failed') . ': ' . $e->getMessage());
        }
    }

    public function close(string $outTradeNo): array
    {
        $outTradeNo = trim($outTradeNo);
        if ($outTradeNo === '') {
            throw new BusinessException('微信关闭订单缺少商户订单号');
        }

        try {
            $this->instance
                ->chain('v3/pay/transactions/out-trade-no/{out_trade_no}/close')
                ->post([
                    'out_trade_no' => $outTradeNo,
                    'json' => ['mchid' => $this->credential->mchId()],
                ]);

            return [
                'status' => 'closed',
                'out_trade_no' => $outTradeNo,
                'provider_status' => 'CLOSED',
                'provider_account_id' => $this->getAccountId(),
            ];
        } catch (\Throwable $e) {
            // 微信对“订单不存在/已关闭”的重复关闭可能返回业务错误；两者都证明
            // 该商户订单号已不可继续付款，可安全视为幂等关闭成功。
            $message = strtoupper($e->getMessage());
            if (str_contains($message, 'ORDER_NOT_EXIST') || str_contains($message, 'ORDER_CLOSED')) {
                return [
                    'status' => 'closed',
                    'out_trade_no' => $outTradeNo,
                    'provider_status' => str_contains($message, 'ORDER_NOT_EXIST')
                        ? 'ORDER_NOT_EXIST'
                        : 'CLOSED',
                    'provider_account_id' => $this->getAccountId(),
                ];
            }

            throw new BusinessException('微信关闭订单失败: ' . $e->getMessage());
        }
    }

    public function refund(array $refund): array
    {
        try {
            $params = [
                'json' => [
                    'out_trade_no'  => $refund['out_trade_no'],
                    'out_refund_no' => $refund['out_refund_no'] ?? ('R' . $refund['out_trade_no']),
                    'reason'        => $refund['reason'] ?? '退款',
                    'amount'        => [
                        'refund'   => $this->moneyToCents($refund['refund_amount']),
                        'total'    => $this->moneyToCents($refund['total_amount']),
                        'currency' => 'CNY',
                    ],
                ],
            ];

            $resp = $this->instance->chain('v3/refund/domestic/refunds')->post($params);
            $result = json_decode($resp->getBody()->getContents(), true);

            return $this->normalizeRefundResponse(is_array($result) ? $result : []);
        } catch (\Exception $e) {
            throw new BusinessException(lang('business.wechat_pay_refund_failed') . ': ' . $e->getMessage());
        }
    }

    public function queryRefund(array $refund): array
    {
        $outRefundNo = trim((string)($refund['out_refund_no'] ?? ''));
        if ($outRefundNo === '') {
            throw new BusinessException('微信退款查询缺少商户退款单号');
        }

        try {
            // 只按商户退款单号查询，和发起退款时的幂等键保持一致。
            $resp = $this->instance
                ->chain('v3/refund/domestic/refunds/{out_refund_no}')
                ->get(['out_refund_no' => $outRefundNo]);
            $result = json_decode($resp->getBody()->getContents(), true);

            return $this->normalizeRefundResponse(is_array($result) ? $result : []);
        } catch (BusinessException $e) {
            throw $e;
        } catch (\Exception $e) {
            $message = strtoupper($e->getMessage());
            if (str_contains($message, 'RESOURCE_NOT_EXISTS')
                || str_contains($message, 'REFUND_NOT_EXIST')
                || str_contains($message, 'REFUND_NOT_EXISTS')) {
                return [
                    'out_trade_no' => (string)($refund['out_trade_no'] ?? ''),
                    'out_refund_no' => $outRefundNo,
                    'payment_trade_no' => '',
                    'refund_trade_no' => '',
                    'refund_trade_no_source' => 'wechat_refund_id',
                    'refund_amount' => '',
                    'total_amount' => '',
                    'currency' => 'CNY',
                    'status' => 'failed',
                    'provider_status' => 'REFUND_NOT_EXIST',
                    'provider_refunded_at' => '',
                    'raw' => [],
                ];
            }
            throw new BusinessException('微信退款查询失败: ' . $e->getMessage());
        }
    }

    /** @return array<string, mixed> */
    protected function normalizeRefundResponse(array $result): array
    {
        $providerStatus = strtoupper(trim((string)($result['status'] ?? '')));
        $localStatus = match ($providerStatus) {
            'SUCCESS' => 'success',
            'PROCESSING' => 'processing',
            'CLOSED', 'ABNORMAL' => 'failed',
            default => 'unknown',
        };

        return [
            'out_trade_no'  => (string)($result['out_trade_no'] ?? ''),
            'out_refund_no' => (string)($result['out_refund_no'] ?? ''),
            // transaction_id 是原支付交易号，不能冒充退款交易号。
            'payment_trade_no' => (string)($result['transaction_id'] ?? ''),
            // refund_id 才是微信侧本次退款的唯一标识。
            'refund_trade_no' => (string)($result['refund_id'] ?? ''),
            'refund_trade_no_source' => 'wechat_refund_id',
            'refund_amount' => isset($result['amount']['refund'])
                ? $this->formatCents((int)$result['amount']['refund'])
                : '',
            'total_amount' => isset($result['amount']['total'])
                ? $this->formatCents((int)$result['amount']['total'])
                : '',
            'currency' => strtoupper((string)($result['amount']['currency'] ?? 'CNY')),
            'status' => $localStatus,
            'provider_status' => $providerStatus !== '' ? $providerStatus : 'UNKNOWN',
            // 仅 SUCCESS 才暴露渠道成功时间；PROCESSING/ABNORMAL 的同名字段
            // 不能被误用为本地退款完成时间。
            'provider_refunded_at' => $localStatus === 'success'
                ? trim((string)($result['success_time'] ?? ''))
                : '',
            'raw' => $result,
        ];
    }

    public function verifyNotify(array $params): array
    {
        try {
            // 1. 微信 v3 回调必须验证签名；缺头不能降级为“只解密”。
            if (!isset($params['_headers']) || !is_array($params['_headers'])) {
                throw new BusinessException(lang('business.wechat_callback_missing_signature'));
            }
            $this->verifySignature($params['_headers'], (string)($params['_body'] ?? ''));

            // 2. 解密回调数据
            $resource = $params['resource'] ?? [];
            $ciphertext = $resource['ciphertext'] ?? '';
            $nonce = $resource['nonce'] ?? '';
            $associatedData = $resource['associated_data'] ?? '';

            if (empty($ciphertext)) {
                throw new BusinessException(lang('business.callback_data_empty'));
            }

            $decrypted = AesGcm::decrypt($ciphertext, $this->credential->apiV3Key(), $nonce, $associatedData);
            $data = json_decode($decrypted, true);

            if (!is_array($data) || empty($data['out_trade_no'])) {
                throw new BusinessException(lang('business.callback_data_decrypt_error'));
            }

            return $this->normalizeTransaction($data);
        } catch (BusinessException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new BusinessException(lang('business.wechat_pay_callback_verify_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * 验证微信回调请求签名
     */
    protected function verifySignature(array $headers, string $body): void
    {
        $timestamp = $headers['Wechatpay-Timestamp'] ?? '';
        $nonce = $headers['Wechatpay-Nonce'] ?? '';
        $signature = $headers['Wechatpay-Signature'] ?? '';
        $serial = $headers['Wechatpay-Serial'] ?? '';

        if (empty($timestamp) || empty($nonce) || empty($signature)) {
            throw new BusinessException(lang('business.wechat_callback_missing_signature'));
        }

        // 检查时间戳是否在合理范围内（5分钟）
        if (abs(time() - (int)$timestamp) > 300) {
            throw new BusinessException(lang('business.wechat_callback_timestamp_expired'));
        }

        // 构造验签串
        $message = $timestamp . "\n" . $nonce . "\n" . $body . "\n";

        if (!$this->credential->matchesSerial((string)$serial)) {
            throw new BusinessException(lang('business.wechat_callback_serial_mismatch') . ': ' . $serial);
        }

        $verified = Rsa::verify($message, $signature, $this->credential->publicKey());
        if (!$verified) {
            throw new BusinessException(lang('business.wechat_callback_sign_failed'));
        }
    }

    public function successResponse(): string
    {
        return json_encode(['code' => 'SUCCESS', 'message' => '成功']);
    }

    public function getDriver(): string
    {
        return 'wechat';
    }

    protected function mapStatus(string $tradeState): string
    {
        return match ($tradeState) {
            'SUCCESS'    => 'paid',
            'CLOSED'     => 'closed',
            'NOTPAY'     => 'pending',
            'USERPAYING' => 'pending',
            'REFUND'     => 'refunded',
            default      => 'unknown',
        };
    }

    /**
     * 统一微信查询/回调的可信交易结构，并在 driver 边界校验商户号、AppID 与币种。
     */
    protected function normalizeTransaction(array $data, string $fallbackOrderNo = ''): array
    {
        $merchantId = trim((string)($data['mchid'] ?? ''));
        $appId = trim((string)($data['appid'] ?? ''));
        $expectedMchId = isset($this->credential)
            ? $this->credential->mchId()
            : trim((string)($this->config['mch_id'] ?? ''));
        if ($merchantId === '' || $expectedMchId === '' || !hash_equals($expectedMchId, $merchantId)) {
            throw new BusinessException('微信支付商户号不匹配');
        }
        $this->assertAllowedAppId($appId);

        $currency = strtoupper(trim((string)($data['amount']['currency'] ?? '')));
        if ($currency !== 'CNY') {
            throw new BusinessException('微信支付币种不匹配');
        }

        $amountCents = $this->parseProviderCents($data['amount']['total'] ?? null);
        return [
            'out_trade_no' => (string)($data['out_trade_no'] ?? $fallbackOrderNo),
            'trade_no'     => (string)($data['transaction_id'] ?? ''),
            'total_amount' => $this->formatCents($amountCents),
            'amount_cents' => $amountCents,
            'currency'     => $currency,
            'status'       => $this->mapStatus((string)($data['trade_state'] ?? '')),
            'provider_status' => strtoupper(trim((string)($data['trade_state'] ?? 'UNKNOWN'))),
            'provider_exists' => true,
            'provider_merchant_id' => $merchantId,
            'provider_account_id' => $merchantId,
            'provider_app_id'      => $appId,
            'identity_verified'    => true,
            'identity_source'      => 'provider',
            'raw'                  => $data,
        ];
    }

    /** @return string[] */
    protected function allowedAppIds(?array $config = null): array
    {
        $source = $config ?? $this->config;
        return array_values(array_unique(array_filter(array_map(
            static fn ($value): string => trim((string)$value),
            [
                $source['app_id'] ?? '',
                $source['mini_app_id'] ?? '',
                $source['official_app_id'] ?? '',
                $source['open_app_id'] ?? '',
                $source['mobile_app_id'] ?? '',
            ]
        ))));
    }

    protected function assertAllowedAppId(string $appId): void
    {
        if ($appId === '' || !in_array($appId, $this->allowedAppIds(), true)) {
            throw new BusinessException('微信支付 AppID 不匹配');
        }
    }

    protected function parseProviderCents(mixed $amount): int
    {
        $normalized = trim((string)$amount);
        if (!preg_match('/^\d+$/', $normalized)) {
            throw new BusinessException('微信支付金额格式非法');
        }
        return (int)$normalized;
    }

    protected function moneyToCents(string|int|float $amount): int
    {
        $normalized = trim((string)$amount);
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $normalized)) {
            throw new BusinessException('微信支付金额格式非法');
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
            throw new BusinessException('微信支付凭据失效时间非法');
        }
        if ($date->getTimestamp() <= time()) {
            throw new BusinessException('微信支付凭据失效时间必须晚于当前时间');
        }
        return $date->format(DATE_RFC3339);
    }
}
