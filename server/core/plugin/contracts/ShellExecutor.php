<?php
declare(strict_types=1);

namespace core\plugin\contracts;

interface ShellExecutor
{
    /**
     * @param (callable(): bool)|null $shouldAbort 返回 true 时终止进程
     * @return array{exitCode: int, stdout: string, stderr: string}
     */
    public function exec(string $cmd, string $cwd, int $timeoutSeconds = 600, ?callable $shouldAbort = null): array;
}
