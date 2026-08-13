<?php
declare(strict_types=1);

namespace core\ai\driver;

use core\ai\AiImageDriverInterface;
use core\exception\BusinessException;

/**
 * 阿里云通义万相（文生图）驱动
 * DashScope 异步任务：
 *   1. POST /api/v1/services/aigc/text2image/image-synthesis → task_id
 *   2. 轮询 GET /api/v1/tasks/{task_id} 直到 SUCCEEDED / FAILED
 *   3. 返回 results[].url
 */
class QwenImageDriver implements AiImageDriverInterface
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;

    protected string $defaultModel   = 'wanx2.1-t2i-turbo';
    protected string $defaultBaseUrl = 'https://dashscope.aliyuncs.com';

    protected int $lastImageCount = 0;

    public function __construct(array $config)
    {
        $this->apiKey  = $config['api_key'] ?? '';
        $this->model   = !empty($config['model']) ? $config['model'] : $this->defaultModel;
        $this->baseUrl = !empty($config['base_url']) ? rtrim($config['base_url'], '/') : $this->defaultBaseUrl;
    }

    public function getName(): string { return 'qwen-image'; }
    public function getLastTokenCount(): int { return $this->lastImageCount; }

    public function textToImage(string $prompt, array $options = []): array
    {
        $this->lastImageCount = 0;
        if ($this->apiKey === '') {
            throw new BusinessException('通义万相未配置 API Key（请在 AI 配置页填写 qwen 密钥）');
        }
        $size = (string)($options['size'] ?? '1024*1024');
        $n    = max(1, min(4, (int)($options['n'] ?? 1)));

        $submit = $this->httpPost(
            '/api/v1/services/aigc/text2image/image-synthesis',
            [
                'model'      => $this->model,
                'input'      => ['prompt' => $prompt],
                'parameters' => ['size' => $size, 'n' => $n],
            ],
            ['X-DashScope-Async: enable']
        );

        $taskId = $submit['output']['task_id'] ?? null;
        if (!$taskId) {
            throw new BusinessException('通义万相任务提交失败：' . ($submit['message'] ?? '未知错误'));
        }

        for ($i = 0; $i < 30; $i++) {
            sleep(3);
            $detail = $this->httpGet("/api/v1/tasks/{$taskId}");
            $status = $detail['output']['task_status'] ?? '';
            if ($status === 'SUCCEEDED') {
                $urls = [];
                foreach ($detail['output']['results'] ?? [] as $r) {
                    if (!empty($r['url'])) $urls[] = (string)$r['url'];
                }
                if (empty($urls)) {
                    throw new BusinessException('通义万相任务成功但未返回图像 URL');
                }
                $this->lastImageCount = count($urls);
                return ['urls' => $urls, 'task_id' => $taskId];
            }
            if ($status === 'FAILED') {
                throw new BusinessException('通义万相任务失败：' . ($detail['output']['message'] ?? '任务失败'));
            }
        }
        throw new BusinessException('通义万相任务超时（90s 未完成）');
    }

    private function httpPost(string $path, array $payload, array $extraHeaders = []): array
    {
        $ch = curl_init($this->baseUrl . $path);
        $headers = array_merge([
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
        ], $extraHeaders);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $body     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);
        if ($body === false) {
            throw new BusinessException("通义万相请求失败：{$err}");
        }
        $data = json_decode($body, true);
        if (!is_array($data)) {
            throw new BusinessException('通义万相响应不是合法 JSON');
        }
        if ($httpCode !== 200) {
            $errMsg = $data['message'] ?? ($data['error']['message'] ?? substr((string)$body, 0, 200));
            throw new BusinessException("通义万相 API 错误 HTTP {$httpCode}：{$errMsg}");
        }
        return $data;
    }

    private function httpGet(string $path): array
    {
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $this->apiKey],
            CURLOPT_TIMEOUT        => 30,
        ]);
        $body     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);
        if ($body === false) {
            throw new BusinessException("通义万相查询失败：{$err}");
        }
        $data = json_decode($body, true);
        if (!is_array($data)) {
            throw new BusinessException('通义万相查询响应非 JSON');
        }
        if ($httpCode !== 200) {
            $errMsg = $data['message'] ?? ($data['error']['message'] ?? substr((string)$body, 0, 200));
            throw new BusinessException("通义万相查询 API 错误 HTTP {$httpCode}：{$errMsg}");
        }
        return $data;
    }
}
