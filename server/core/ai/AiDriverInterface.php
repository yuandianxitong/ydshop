<?php
declare(strict_types=1);

namespace core\ai;

/**
 * 统一 AI 驱动接口
 */
interface AiDriverInterface
{
    /**
     * 普通对话（返回完整字符串）
     *
     * @param string $prompt  用户输入
     * @param array  $context 上下文，支持以下键：
     *                        - system  (string)  系统提示词
     *                        - history (array)   历史消息，每项为 {role, content}
     * @return string AI 回复内容
     */
    public function chat(string $prompt, array $context = []): string;

    /**
     * 流式对话（逐块回调）
     *
     * @param string        $prompt   用户输入
     * @param array         $context  同 chat()
     * @param callable|null $onChunk  每收到一段文本时回调，签名：function(string $chunk): void
     */
    public function stream(string $prompt, array $context = [], ?callable $onChunk = null): void;

    /**
     * 结构化 JSON 对话（返回解析后的数组）
     *
     * @param string $prompt  用户输入
     * @param array  $schema  期望的 JSON 结构描述（用于提示词构造，非强制校验）
     * @param array  $context 同 chat()
     * @return array 解析后的 JSON 数组
     */
    public function json(string $prompt, array $schema, array $context = []): array;

    /**
     * 获取驱动名称标识
     *
     * @return string 如 'openai'、'claude'、'qwen'、'deepseek'
     */
    public function getName(): string;

    /**
     * 获取最近一次请求消耗的 token 总数
     *
     * @return int
     */
    public function getLastTokenCount(): int;
}
