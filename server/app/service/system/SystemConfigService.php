<?php
/* ============================================================
 * 项目：元点Shop
 * 官网：https://www.dev007.cn
 * Slogan：提供高质量行业系统源码，帮助中小企业快速搭建专属应用
 * Author：mashanglai Team
 * ============================================================ */
declare(strict_types=1);

namespace app\service\system;

use app\repository\system\SystemConfigRepository;
use app\repository\payment\PaymentOrderRepository;
use core\base\Service;
use core\exception\BusinessException;

class SystemConfigService extends Service
{
    protected SystemConfigRepository $configRepository;
    protected PaymentOrderRepository $paymentOrderRepository;

    /**
     * 获取配置分组
     * @return array
     */
    public function getConfigGroups(): array
    {
        return [
            'basic'            => lang('messages.config_group_basic'),
            'email'            => lang('messages.config_group_email'),
            'sms'              => lang('messages.config_group_sms'),
            'storage'          => lang('messages.config_group_storage'),
            'payment'          => lang('messages.config_group_payment'),
            'amap'             => lang('messages.config_group_amap'),
        ];
    }

    /**
     * 根据分组获取配置列表
     * @param string $group
     * @return array
     */
    public function getConfigsByGroup(string $group = 'basic'): array
    {
        return $this->configRepository->getByGroup($group);
    }

    /**
     * 获取单个配置
     * @throws BusinessException
     */
    public function getConfigById(int $id): array
    {
        $config = $this->configRepository->find($id);
        if (!$config) {
            throw new BusinessException(lang('business.config_not_found'));
        }

        return $config;
    }

    /**
     * 更新配置
     * @throws BusinessException
     */
    public function updateConfig(int $id, array $data): bool
    {
        $config = $this->configRepository->find($id);
        if (!$config) {
            throw new BusinessException(lang('business.config_not_found'));
        }

        $value = $this->normalizeStoredValue($config, $data['config_value']);
        $channel = $this->paymentChannelForKey((string)$config['config_key']);
        $result = $this->runInTransaction(function () use ($id, $config, $value, $channel): bool {
            if ($channel !== null) {
                $this->configRepository->lockPaymentConfigForUpdate($channel);
                $lockedConfig = $this->configRepository->findByKeyForUpdate(
                    (string)$config['config_key']
                );
                if (!$lockedConfig) {
                    throw new BusinessException(lang('business.config_not_found'));
                }
                $this->assertPaymentConfigChangeAllowed($lockedConfig, $value, $channel);
            }
            return $this->configRepository->updateConfigValueById($id, $value);
        });

        if ($result) {
            $this->trigger('config.changed', ['keys' => [$config['config_key']]]);
        }

        return $result;
    }

    /**
     * 批量更新配置
     * @throws BusinessException
     */
    public function batchUpdateConfigs(array $configs): bool
    {
        $prepared = [];
        $channels = [];
        foreach ($configs as $configData) {
            if (!isset($configData['config_key']) || !isset($configData['config_value'])) {
                continue;
            }

            $config = $this->configRepository->findByKey($configData['config_key']);
            if (!$config) {
                throw new BusinessException(lang('business.config_not_found') . ': ' . $configData['config_key']);
            }

            $key = (string)$configData['config_key'];
            $channel = $this->paymentChannelForKey($key);
            if ($channel !== null) {
                $channels[$channel] = true;
            }
            $prepared[] = [
                'config' => $config,
                'key' => $key,
                'value' => $this->normalizeStoredValue($config, $configData['config_value']),
                'channel' => $channel,
            ];
        }

        $changedKeys = $this->runInTransaction(function () use ($prepared, $channels): array {
            $channelNames = array_keys($channels);
            sort($channelNames, SORT_STRING);
            foreach ($channelNames as $channel) {
                $this->configRepository->lockPaymentConfigForUpdate($channel);
            }

            $changed = [];
            foreach ($prepared as $entry) {
                if ($entry['channel'] !== null) {
                    $lockedConfig = $this->configRepository->findByKeyForUpdate($entry['key']);
                    if (!$lockedConfig) {
                        throw new BusinessException(lang('business.config_not_found'));
                    }
                    $this->assertPaymentConfigChangeAllowed(
                        $lockedConfig,
                        $entry['value'],
                        $entry['channel']
                    );
                }
                $this->configRepository->updateConfigValueByKey($entry['key'], $entry['value']);
                $changed[] = $entry['key'];
            }
            return $changed;
        });

        if (!empty($changedKeys)) {
            $this->trigger('config.changed', ['keys' => $changedKeys]);
        }

        return true;
    }

    /**
     * 获取全局配置（用于前端应用）
     * @return array
     */
    public function getGlobalConfigs(): array
    {
        return $this->configRepository->getAllConfigs();
    }

    /**
     * 获取配置值
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getConfigValue(string $key, $default = null)
    {
        return $this->configRepository->getConfigValue($key, $default);
    }

    /**
     * 设置配置值
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function setConfigValue(string $key, $value): bool
    {
        $config = $this->configRepository->findByKey($key);
        if (!$config) {
            return false;
        }
        $storedValue = $this->normalizeStoredValue($config, $value);
        $channel = $this->paymentChannelForKey($key);
        $result = $this->runInTransaction(function () use (
            $key,
            $config,
            $storedValue,
            $channel
        ): bool {
            if ($channel !== null) {
                $this->configRepository->lockPaymentConfigForUpdate($channel);
                $lockedConfig = $this->configRepository->findByKeyForUpdate($key);
                if (!$lockedConfig) {
                    return false;
                }
                $this->assertPaymentConfigChangeAllowed($lockedConfig, $storedValue, $channel);
            }
            return $this->configRepository->updateConfigValueByKey($key, $storedValue);
        });

        if ($result) {
            $this->trigger('config.changed', ['keys' => [$key]]);
        }

        return $result;
    }

    /** @param array<string, mixed> $config */
    private function normalizeStoredValue(array $config, mixed $value): string
    {
        if (($config['config_type'] ?? '') === 'json') {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);
            if (!is_string($encoded)) {
                throw new BusinessException('配置 JSON 编码失败');
            }
            return $encoded;
        }
        return (string)$value;
    }

    private function paymentChannelForKey(string $key): ?string
    {
        if (str_starts_with($key, 'pay_alipay_')) {
            return 'alipay';
        }
        if (str_starts_with($key, 'pay_wechat_') || in_array($key, [
            'wechat_mini_app_id',
            'wechat_official_app_id',
            'wechat_open_app_id',
            'wechat_app_app_id',
        ], true)) {
            return 'wechat';
        }
        return null;
    }

    /** @param array<string, mixed> $config */
    private function assertPaymentConfigChangeAllowed(
        array $config,
        string $newValue,
        string $channel
    ): void {
        if (hash_equals((string)($config['config_value'] ?? ''), $newValue)) {
            return;
        }

        $key = (string)($config['config_key'] ?? '');
        $accountKeys = [
            'pay_alipay_app_id',
            'pay_wechat_mch_id',
        ];
        if (in_array($key, $accountKeys, true)
            && $this->paymentOrderRepository->countAccountChangeBlockers($channel) > 0) {
            throw new BusinessException('仍有未决或可退款支付单，禁止切换收款主体');
        }

        $unresolvedSensitiveKeys = [
            'pay_alipay_private_key',
            'pay_alipay_public_key',
            'pay_wechat_app_id',
            'wechat_mini_app_id',
            'wechat_official_app_id',
            'wechat_open_app_id',
            'wechat_app_app_id',
            'pay_wechat_api_v3_key',
            'pay_wechat_serial_no',
            'pay_wechat_private_key_path',
            'pay_wechat_public_key_id',
            'pay_wechat_public_key_path',
        ];
        if (in_array($key, $unresolvedSensitiveKeys, true)
            && $this->paymentOrderRepository->countUnresolvedByChannel($channel) > 0) {
            throw new BusinessException('仍有未决支付凭据，禁止修改其验签或解密配置');
        }
    }

    /**
     * Upsert 配置：存在则改、不存在则按 template 建（用于业务模块首次写入设置时）
     *
     * @param array{config_group: string, config_type: string, config_name?: string, config_desc?: string, sort_order?: int, status?: int} $template
     */
    public function upsertConfigValue(string $key, $value, array $template): bool
    {
        $channel = $this->paymentChannelForKey($key);
        $result = $this->runInTransaction(function () use (
            $key,
            $value,
            $template,
            $channel
        ): bool {
            if ($channel !== null) {
                $this->configRepository->lockPaymentConfigForUpdate($channel);
                $current = $this->configRepository->findByKeyForUpdate($key);
                $type = (string)($current['config_type'] ?? $template['config_type'] ?? 'string');
                $storedValue = $this->normalizeStoredValue(
                    $current ?? ['config_type' => $type],
                    $value
                );
                $this->assertPaymentConfigChangeAllowed(
                    $current ?? [
                        'config_key' => $key,
                        'config_value' => '',
                    ],
                    $storedValue,
                    $channel
                );
            }
            return $this->configRepository->upsertConfigValue($key, $value, $template);
        });
        if ($result) {
            $this->trigger('config.changed', ['keys' => [$key]]);
        }
        return $result;
    }
}
