<?php
declare(strict_types=1);

namespace core\payment;

use core\payment\driver\AlipayDriver;
use core\payment\driver\WechatPayDriver;
use core\exception\BusinessException;
use app\model\system\SystemConfig;

class PaymentManager
{
    /**
     * 获取支付驱动实例
     * @param string $channel 支付渠道：alipay / wechat
     * @return PaymentInterface
     */
    public static function channel(string $channel): PaymentInterface
    {
        // 查询、回调、关单和退款必须在渠道“停止接单”后继续可用，否则已签发
        // 的支付凭据会失去验签/退款能力。enabled 只约束新建支付。
        return self::createDriver($channel);
    }

    /** 仅供新建支付使用，会检查渠道接单开关。 */
    public static function channelForCreate(string $channel): PaymentInterface
    {
        self::assertEnabled($channel);
        return self::createDriver($channel);
    }

    protected static function createDriver(string $channel): PaymentInterface
    {
        return match ($channel) {
            'alipay' => new AlipayDriver(self::getAlipayConfig()),
            'wechat' => new WechatPayDriver(self::getWechatConfig()),
            default  => throw new BusinessException("不支持的支付渠道: {$channel}"),
        };
    }

    private static function assertEnabled(string $channel): void
    {
        $enabledKey = match ($channel) {
            'alipay' => 'pay_alipay_enabled',
            'wechat' => 'pay_wechat_enabled',
            default => throw new BusinessException("不支持的支付渠道: {$channel}"),
        };
        if (!SystemConfig::getConfigValue($enabledKey, false)) {
            $messageKey = $channel === 'alipay'
                ? 'business.alipay_not_enabled'
                : 'business.wechat_pay_not_enabled';
            throw new BusinessException(lang($messageKey));
        }
    }

    protected static function getAlipayConfig(): array
    {
        return [
            'app_id'      => (string)SystemConfig::getConfigValue('pay_alipay_app_id', ''),
            'private_key' => (string)SystemConfig::getConfigValue('pay_alipay_private_key', ''),
            'public_key'  => (string)SystemConfig::getConfigValue('pay_alipay_public_key', ''),
            'notify_url'  => (string)SystemConfig::getConfigValue('pay_alipay_notify_url', ''),
        ];
    }

    public static function getWechatConfig(): array
    {
        // 公钥模式：商户私钥/微信支付公钥均通过服务器文件路径加载（不入库 PEM 正文）
        return [
            'app_id'           => (string)SystemConfig::getConfigValue('pay_wechat_app_id', ''),
            'mch_id'           => (string)SystemConfig::getConfigValue('pay_wechat_mch_id', ''),
            'api_v3_key'       => (string)SystemConfig::getConfigValue('pay_wechat_api_v3_key', ''),
            'serial_no'        => (string)SystemConfig::getConfigValue('pay_wechat_serial_no', ''),
            'private_key_path' => (string)SystemConfig::getConfigValue('pay_wechat_private_key_path', ''),
            'public_key_id'    => (string)SystemConfig::getConfigValue('pay_wechat_public_key_id', ''),
            'public_key_path'  => (string)SystemConfig::getConfigValue('pay_wechat_public_key_path', ''),
            'notify_url'       => (string)SystemConfig::getConfigValue('pay_wechat_notify_url', ''),
            // 多端 appid
            'mini_app_id'      => (string)SystemConfig::getConfigValue('wechat_mini_app_id', ''),
            'official_app_id'  => (string)SystemConfig::getConfigValue('wechat_official_app_id', ''),
            'open_app_id'      => (string)SystemConfig::getConfigValue('wechat_open_app_id', ''),
            'mobile_app_id'    => (string)SystemConfig::getConfigValue('wechat_app_app_id', ''),
        ];
    }

    /**
     * 重置缓存实例（配置变更后调用）
     */
    public static function reset(): void
    {
        // 驱动不再跨请求缓存。保留该方法兼容配置变更监听器和第三方扩展。
    }
}
