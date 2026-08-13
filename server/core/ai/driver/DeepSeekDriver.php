<?php
declare(strict_types=1);

namespace core\ai\driver;

/**
 * DeepSeek 驱动
 *
 * DeepSeek 提供 OpenAI 兼容接口，直接继承 OpenAiDriver 并覆盖默认值。
 * 文档：https://platform.deepseek.com/api-docs/
 */
class DeepSeekDriver extends OpenAiDriver
{
    protected string $defaultModel   = 'deepseek-chat';
    protected string $defaultBaseUrl = 'https://api.deepseek.com/v1';

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'deepseek';
    }
}
