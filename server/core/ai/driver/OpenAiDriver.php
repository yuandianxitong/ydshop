<?php
declare(strict_types=1);

namespace core\ai\driver;

use core\ai\AiDriverInterface;
use core\exception\BusinessException;

/**
 * OpenAI 驱动
 *
 * 兼容所有 OpenAI Chat Completions API 的服务（包括 OpenAI 官方）。
 * Qwen / DeepSeek 等兼容驱动通过继承此类并覆盖默认值来实现。
 */
class OpenAiDriver implements AiDriverInterface
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;

    /** 最近一次请求消耗的 token 总数 */
    protected int $lastTokenCount = 0;

    /** 默认模型（子类可覆盖） */
    protected string $defaultModel = 'gpt-4o';

    /** 默认 API 基础 URL（子类可覆盖） */
    protected string $defaultBaseUrl = 'https://api.openai.com/v1';

    /**
     * @param array $config ['api_key' => '', 'model' => '', 'base_url' => '']
     */
    public function __construct(array $config)
    {
        $this->apiKey  = $config['api_key']  ?? '';
        $this->model   = !empty($config['model'])    ? $config['model']    : $this->defaultModel;
        $this->baseUrl = !empty($config['base_url']) ? rtrim($config['base_url'], '/') : $this->defaultBaseUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function chat(string $prompt, array $context = []): string
    {
        $messages = $this->buildMessages($prompt, $context);

        $payload = [
            'model'    => $this->model,
            'messages' => $messages,
        ];

        $response = $this->post('/chat/completions', $payload);

        $this->lastTokenCount = $response['usage']['total_tokens'] ?? 0;

        return $response['choices'][0]['message']['content'] ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function stream(string $prompt, array $context = [], ?callable $onChunk = null): void
    {
        $messages = $this->buildMessages($prompt, $context);

        $payload = [
            'model'    => $this->model,
            'messages' => $messages,
            'stream'   => true,
        ];

        $this->postStream('/chat/completions', $payload, function (string $line) use ($onChunk): void {
            // SSE 行格式：data: {...} 或 data: [DONE]
            if (!str_starts_with($line, 'data: ')) {
                return;
            }

            $json = substr($line, 6);
            if ($json === '[DONE]') {
                return;
            }

            $data = json_decode($json, true);
            $delta = $data['choices'][0]['delta']['content'] ?? null;

            if ($delta !== null && $delta !== '' && $onChunk !== null) {
                $onChunk($delta);
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function json(string $prompt, array $schema, array $context = []): array
    {
        $messages = $this->buildMessages($prompt, $context);

        $payload = [
            'model'           => $this->model,
            'messages'        => $messages,
            'response_format' => ['type' => 'json_object'],
        ];

        $response = $this->post('/chat/completions', $payload);

        $this->lastTokenCount = $response['usage']['total_tokens'] ?? 0;

        $content = $response['choices'][0]['message']['content'] ?? '{}';

        return json_decode($content, true) ?: [];
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'openai';
    }

    /**
     * {@inheritdoc}
     */
    public function getLastTokenCount(): int
    {
        return $this->lastTokenCount;
    }

    // -------------------------------------------------------------------------
    // 内部辅助方法
    // -------------------------------------------------------------------------

    /**
     * 根据 prompt 和 context 构造 messages 数组
     */
    protected function buildMessages(string $prompt, array $context): array
    {
        $messages = [];

        // 系统提示词
        if (!empty($context['system'])) {
            $messages[] = ['role' => 'system', 'content' => (string)$context['system']];
        }

        // 历史消息
        if (!empty($context['history']) && is_array($context['history'])) {
            foreach ($context['history'] as $item) {
                if (!empty($item['role']) && isset($item['content'])) {
                    $messages[] = ['role' => $item['role'], 'content' => (string)$item['content']];
                }
            }
        }

        // 当前用户消息
        $messages[] = ['role' => 'user', 'content' => $prompt];

        return $messages;
    }

    /**
     * 发送 POST 请求并返回解析后的 JSON 数组
     *
     * @throws BusinessException
     */
    protected function post(string $path, array $payload): array
    {
        $url  = $this->baseUrl . $path;
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_HTTPHEADER     => $this->buildHeaders(),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new BusinessException("AI 请求失败 [{$this->getName()}]: {$curlError}");
        }

        $data = json_decode((string)$response, true);

        if ($httpCode !== 200) {
            $errMsg = $data['error']['message'] ?? $response;
            throw new BusinessException("AI API 错误 [{$this->getName()}] HTTP {$httpCode}: {$errMsg}");
        }

        return $data ?? [];
    }

    /**
     * 发送流式 POST 请求，逐行调用 $lineCallback
     *
     * @throws BusinessException
     */
    protected function postStream(string $path, array $payload, callable $lineCallback): void
    {
        $url  = $this->baseUrl . $path;
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);

        // 用于在 CURLOPT_WRITEFUNCTION 中拼接不完整的行
        $buffer = '';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL        => $url,
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_TIMEOUT    => 300,
            CURLOPT_HTTPHEADER => $this->buildHeaders(),
            CURLOPT_WRITEFUNCTION => function ($curl, $chunk) use (&$buffer, $lineCallback): int {
                $buffer .= $chunk;

                // 按换行切割，处理完整行
                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = rtrim(substr($buffer, 0, $pos));
                    $buffer = substr($buffer, $pos + 1);

                    if ($line !== '') {
                        $lineCallback($line);
                    }
                }

                return strlen($chunk);
            },
        ]);

        curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new BusinessException("AI 流式请求失败 [{$this->getName()}]: {$curlError}");
        }

        if ($httpCode !== 200) {
            throw new BusinessException("AI 流式 API 错误 [{$this->getName()}] HTTP {$httpCode}");
        }
    }

    /**
     * 构造 HTTP 请求头
     */
    protected function buildHeaders(): array
    {
        return [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
        ];
    }
}
