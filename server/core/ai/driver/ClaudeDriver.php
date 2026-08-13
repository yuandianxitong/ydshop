<?php
declare(strict_types=1);

namespace core\ai\driver;

use core\ai\AiDriverInterface;
use core\exception\BusinessException;

/**
 * Anthropic Claude 驱动
 *
 * 使用 Anthropic Messages API。
 * 文档：https://docs.anthropic.com/en/api/messages
 */
class ClaudeDriver implements AiDriverInterface
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;

    /** 最近一次请求消耗的 token 总数 */
    protected int $lastTokenCount = 0;

    protected string $defaultModel   = 'claude-sonnet-4-20250514';
    protected string $defaultBaseUrl = 'https://api.anthropic.com/v1';

    /** Anthropic API 版本 */
    protected string $anthropicVersion = '2023-06-01';

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
        [$system, $messages] = $this->buildPayloadParts($prompt, $context);

        $payload = [
            'model'      => $this->model,
            'max_tokens' => 4096,
            'messages'   => $messages,
        ];

        if ($system !== '') {
            $payload['system'] = $system;
        }

        $response = $this->post('/messages', $payload);

        $this->lastTokenCount = ($response['usage']['input_tokens'] ?? 0)
            + ($response['usage']['output_tokens'] ?? 0);

        return $response['content'][0]['text'] ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function stream(string $prompt, array $context = [], ?callable $onChunk = null): void
    {
        [$system, $messages] = $this->buildPayloadParts($prompt, $context);

        $payload = [
            'model'      => $this->model,
            'max_tokens' => 4096,
            'messages'   => $messages,
            'stream'     => true,
        ];

        if ($system !== '') {
            $payload['system'] = $system;
        }

        $this->postStream('/messages', $payload, function (string $line) use ($onChunk): void {
            // Anthropic SSE 格式：
            //   event: content_block_delta
            //   data: {"type":"content_block_delta","index":0,"delta":{"type":"text_delta","text":"..."}}
            if (!str_starts_with($line, 'data: ')) {
                return;
            }

            $json = substr($line, 6);
            $data = json_decode($json, true);

            if (($data['type'] ?? '') === 'content_block_delta'
                && ($data['delta']['type'] ?? '') === 'text_delta'
            ) {
                $text = $data['delta']['text'] ?? '';
                if ($text !== '' && $onChunk !== null) {
                    $onChunk($text);
                }
            }
        });
    }

    /**
     * {@inheritdoc}
     *
     * Claude 不支持 response_format，改为在系统提示词中要求输出 JSON。
     */
    public function json(string $prompt, array $schema, array $context = []): array
    {
        // 在系统提示词中注入 JSON 指令
        $jsonInstruction = "You must respond with valid JSON only. Do not include any explanation or markdown. "
            . "The response must be a valid JSON object.";

        if (!empty($schema)) {
            $jsonInstruction .= " Expected schema: " . json_encode($schema, JSON_UNESCAPED_UNICODE);
        }

        $existingSystem = $context['system'] ?? '';
        $context['system'] = $existingSystem !== ''
            ? $existingSystem . "\n\n" . $jsonInstruction
            : $jsonInstruction;

        $content = $this->chat($prompt, $context);

        // 尝试提取 JSON（有时模型会在 ```json ... ``` 中包裹）
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $content, $matches)) {
            $content = trim($matches[1]);
        }

        return json_decode(trim($content), true) ?: [];
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'claude';
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
     * 从 prompt 和 context 中提取 system 字符串与 messages 数组
     *
     * @return array{0: string, 1: array}
     */
    protected function buildPayloadParts(string $prompt, array $context): array
    {
        $system = (string)($context['system'] ?? '');

        $messages = [];

        if (!empty($context['history']) && is_array($context['history'])) {
            foreach ($context['history'] as $item) {
                if (!empty($item['role']) && isset($item['content'])) {
                    $messages[] = ['role' => $item['role'], 'content' => (string)$item['content']];
                }
            }
        }

        $messages[] = ['role' => 'user', 'content' => $prompt];

        return [$system, $messages];
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

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new BusinessException("AI 请求失败 [claude]: {$curlError}");
        }

        $data = json_decode((string)$response, true);

        if ($httpCode !== 200) {
            $errMsg = $data['error']['message'] ?? $response;
            throw new BusinessException("AI API 错误 [claude] HTTP {$httpCode}: {$errMsg}");
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
        $url    = $this->baseUrl . $path;
        $body   = json_encode($payload, JSON_UNESCAPED_UNICODE);
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

                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line   = rtrim(substr($buffer, 0, $pos));
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
            throw new BusinessException("AI 流式请求失败 [claude]: {$curlError}");
        }

        if ($httpCode !== 200) {
            throw new BusinessException("AI 流式 API 错误 [claude] HTTP {$httpCode}");
        }
    }

    /**
     * 构造 Anthropic 专用 HTTP 请求头
     */
    protected function buildHeaders(): array
    {
        return [
            'x-api-key: ' . $this->apiKey,
            'anthropic-version: ' . $this->anthropicVersion,
            'Content-Type: application/json',
        ];
    }
}
