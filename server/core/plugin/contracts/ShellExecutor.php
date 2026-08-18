<?php
declare(strict_types=1);

namespace core\plugin\contracts;

interface ShellExecutor
{
    /**
     * @return array{exitCode: int, stdout: string, stderr: string}
     */
    public function exec(string $cmd, string $cwd, int $timeoutSeconds = 600): array;
}
