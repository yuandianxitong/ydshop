<?php
declare(strict_types=1);

namespace core\ai\driver;

/**
 * 阿里云通义千问 (Qwen) 驱动
 *
 * 通义千问提供 OpenAI 兼容接口，直接继承 OpenAiDriver 并覆盖默认值。
 * 文档：https://help.aliyun.com/zh/model-studio/developer-reference/compatibility-of-openai-with-dashscope
 */
class QwenDriver extends OpenAiDriver
{
    protected string $defaultModel   = 'qwen-plus';
    protected string $defaultBaseUrl = 'https://dashscope.aliyuncs.com/compatible-mode/v1';

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'qwen';
    }
}
