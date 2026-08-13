<?php
declare(strict_types=1);

namespace core\ai;

interface AiImageDriverInterface
{
    /**
     * 文生图
     * @param string $prompt
     * @param array  $options 支持 size('1024*1024')、n(1-4)、style
     * @return array { urls: string[], task_id: string|null }
     * @throws \core\exception\BusinessException
     */
    public function textToImage(string $prompt, array $options = []): array;

    public function getName(): string;

    /** 最近一次请求的图像数量（用于 ai_tasks.tokens_used 占位） */
    public function getLastTokenCount(): int;
}
