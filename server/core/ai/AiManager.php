<?php
declare(strict_types=1);

namespace core\ai;

use core\ai\driver\ClaudeDriver;
use core\ai\driver\OpenAiDriver;
use core\ai\driver\QwenDriver;
use core\ai\driver\DeepSeekDriver;
use core\ai\driver\QwenImageDriver;
use core\exception\BusinessException;
use app\model\system\SystemConfig;

/**
 * AI 驱动管理器
 *
 * 用法：
 *   AiManager::driver()->chat('你好');          // 使用默认驱动
 *   AiManager::driver('claude')->chat('你好');  // 指定驱动
 */
class AiManager
{
    /** @var array<string, AiDriverInterface> 已实例化的驱动缓存 */
    protected static array $instances = [];

    /** 驱动名称 → 驱动类的静态映射 */
    protected static array $driverMap = [
        'openai'   => OpenAiDriver::class,
        'claude'   => ClaudeDriver::class,
        'qwen'     => QwenDriver::class,
        'deepseek' => DeepSeekDriver::class,
    ];

    /** @var array<string, AiImageDriverInterface> 图像驱动实例缓存 */
    protected static array $imageInstances = [];

    /** 图像驱动名 → 类映射 */
    protected static array $imageDriverMap = [
        'qwen' => QwenImageDriver::class,
    ];

    /**
     * 获取指定（或默认）驱动实例（懒加载 + 缓存）
     *
     * @param string|null $name 驱动名称；null 时读取系统配置 ai.default_driver
     * @return AiDriverInterface
     * @throws BusinessException
     */
    public static function driver(?string $name = null): AiDriverInterface
    {
        $name = $name ?? self::getDefaultDriver();

        if (isset(self::$instances[$name])) {
            return self::$instances[$name];
        }

        self::$instances[$name] = self::createDriver($name);
        return self::$instances[$name];
    }

    /**
     * 创建驱动实例
     */
    protected static function createDriver(string $name): AiDriverInterface
    {
        if (!isset(self::$driverMap[$name])) {
            throw new BusinessException("不支持的 AI 驱动: {$name}");
        }

        $class = self::$driverMap[$name];
        $config = self::getDriverConfig($name);

        return new $class($config);
    }

    /**
     * 从系统配置中读取驱动配置
     * 配置键格式：ai.{name}.api_key / ai.{name}.model / ai.{name}.base_url
     */
    protected static function getDriverConfig(string $name): array
    {
        return [
            'api_key'  => (string)SystemConfig::getConfigValue("ai.{$name}.api_key", ''),
            'model'    => (string)SystemConfig::getConfigValue("ai.{$name}.model", ''),
            'base_url' => (string)SystemConfig::getConfigValue("ai.{$name}.base_url", ''),
        ];
    }

    /**
     * 获取默认驱动名称（读取系统配置，未配置时回退到 openai）
     */
    public static function getDefaultDriver(): string
    {
        return (string)SystemConfig::getConfigValue('ai.default_driver', 'openai');
    }

    /**
     * 返回所有已注册的驱动名称列表
     *
     * @return string[]
     */
    public static function getAvailableDrivers(): array
    {
        return array_keys(self::$driverMap);
    }

    /**
     * 获取图像驱动实例（懒加载）
     */
    public static function imageDriver(?string $name = null): AiImageDriverInterface
    {
        $name = $name ?? 'qwen';
        if (isset(self::$imageInstances[$name])) {
            return self::$imageInstances[$name];
        }
        self::$imageInstances[$name] = self::createImageDriver($name);
        return self::$imageInstances[$name];
    }

    protected static function createImageDriver(string $name): AiImageDriverInterface
    {
        if (!isset(self::$imageDriverMap[$name])) {
            throw new BusinessException("不支持的图像 AI 驱动: {$name}");
        }
        $class = self::$imageDriverMap[$name];
        return new $class(self::getImageDriverConfig($name));
    }

    /**
     * 图像驱动配置：复用文本驱动的 api_key/base_url，独立的 image_model
     */
    protected static function getImageDriverConfig(string $name): array
    {
        return [
            'api_key'  => (string)SystemConfig::getConfigValue("ai.{$name}.api_key", ''),
            'model'    => (string)SystemConfig::getConfigValue("ai.{$name}.image_model", ''),
            'base_url' => (string)SystemConfig::getConfigValue("ai.{$name}.base_url", ''),
        ];
    }

    /**
     * 重置所有缓存实例（配置变更后调用）
     */
    public static function reset(): void
    {
        self::$instances = [];
        self::$imageInstances = [];
    }
}
