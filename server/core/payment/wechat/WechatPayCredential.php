<?php
declare(strict_types=1);

namespace core\payment\wechat;

use core\exception\BusinessException;
use WeChatPay\Crypto\Rsa;

/**
 * 微信支付「公钥模式」凭证装配（服务器文件路径）
 *
 * 从配置中的 *_path 读取 PEM 文件，构造时一次性完成校验，之后只提供只读访问。
 * 不含网络与数据库访问，可独立单测。
 *
 * 注意：serial_no 是「商户API证书序列号」（用于请求签名的 Authorization 头），
 * 与验签用的 public_key_id（PUB_KEY_ID_...）是两个不同的东西，公钥模式下两者都必需。
 */
class WechatPayCredential
{
    public const PUBLIC_KEY_ID_PREFIX = 'PUB_KEY_ID_';

    private string $mchId;
    private string $serialNo;
    private string $apiV3Key;
    private string $publicKeyId;
    private string $privateKeyPath;
    private string $publicKeyPath;

    /** @var \OpenSSLAsymmetricKey|resource */
    private $privateKey;

    /** @var \OpenSSLAsymmetricKey|resource */
    private $publicKey;

    public function __construct(array $config)
    {
        $this->mchId       = trim((string)($config['mch_id'] ?? ''));
        $this->serialNo    = strtoupper(trim((string)($config['serial_no'] ?? '')));
        $this->apiV3Key    = trim((string)($config['api_v3_key'] ?? ''));
        $this->publicKeyId = trim((string)($config['public_key_id'] ?? ''));

        $privateKeyPath = trim((string)($config['private_key_path'] ?? ''));
        $publicKeyPath  = trim((string)($config['public_key_path'] ?? ''));

        if ($this->mchId === '' || $this->serialNo === '' || $this->apiV3Key === '' || $privateKeyPath === '') {
            throw new BusinessException(lang('business.wechat_pay_config_incomplete'));
        }

        if ($publicKeyPath === '' || $this->publicKeyId === '') {
            throw new BusinessException(lang('business.wechat_pay_public_key_missing'));
        }

        if (!str_starts_with($this->publicKeyId, self::PUBLIC_KEY_ID_PREFIX)) {
            throw new BusinessException(lang('business.wechat_pay_public_key_id_invalid'));
        }

        $this->privateKeyPath = self::resolveFilePath($privateKeyPath);
        $this->publicKeyPath  = self::resolveFilePath($publicKeyPath);

        if (!is_file($this->privateKeyPath)) {
            throw new BusinessException(lang('business.wechat_pay_key_not_found') . ': ' . $this->privateKeyPath);
        }
        if (!is_file($this->publicKeyPath)) {
            throw new BusinessException(lang('business.wechat_pay_public_key_not_found') . ': ' . $this->publicKeyPath);
        }

        $this->privateKey = $this->loadKeyFromFile(
            $this->privateKeyPath,
            Rsa::KEY_TYPE_PRIVATE,
            'business.wechat_pay_private_key_invalid'
        );
        $this->publicKey = $this->loadKeyFromFile(
            $this->publicKeyPath,
            Rsa::KEY_TYPE_PUBLIC,
            'business.wechat_pay_public_key_invalid'
        );
    }

    /**
     * 绝对路径原样返回；相对路径基于 server/ 根目录。
     */
    public static function resolveFilePath(string $path): string
    {
        return str_starts_with($path, '/') ? $path : app()->getRootPath() . $path;
    }

    /**
     * @return \OpenSSLAsymmetricKey|resource
     */
    private function loadKeyFromFile(string $path, string $type, string $langKey)
    {
        try {
            return Rsa::from('file://' . $path, $type);
        } catch (\Throwable $e) {
            $previous = $e instanceof \Exception
                ? $e
                : new \Exception($e->getMessage(), (int)$e->getCode(), $e);
            throw new BusinessException(lang($langKey), 400, [], $previous);
        }
    }

    public function mchId(): string
    {
        return $this->mchId;
    }

    public function serialNo(): string
    {
        return $this->serialNo;
    }

    public function apiV3Key(): string
    {
        return $this->apiV3Key;
    }

    public function publicKeyId(): string
    {
        return $this->publicKeyId;
    }

    public function privateKeyPath(): string
    {
        return $this->privateKeyPath;
    }

    public function publicKeyPath(): string
    {
        return $this->publicKeyPath;
    }

    /**
     * @return \OpenSSLAsymmetricKey|resource
     */
    public function privateKey()
    {
        return $this->privateKey;
    }

    /**
     * @return \OpenSSLAsymmetricKey|resource
     */
    public function publicKey()
    {
        return $this->publicKey;
    }

    /**
     * SDK 验签材料表：[微信支付公钥ID => 公钥实例]
     */
    public function certs(): array
    {
        return [$this->publicKeyId => $this->publicKey];
    }

    /**
     * 回调/应答头声明的 Wechatpay-Serial 是否为本商户配置的公钥ID
     */
    public function matchesSerial(string $serial): bool
    {
        return $serial !== '' && hash_equals($this->publicKeyId, $serial);
    }
}
