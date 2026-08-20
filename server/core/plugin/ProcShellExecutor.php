<?php
declare(strict_types=1);

namespace core\plugin;

use core\plugin\contracts\ShellExecutor;

/**
 * 仅供构建 worker 调用。PHP-FPM 安装路径不得 exec pnpm。
 */
class ProcShellExecutor implements ShellExecutor
{
    public function exec(string $cmd, string $cwd, int $timeoutSeconds = 600, ?callable $shouldAbort = null): array
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $spawn = $shouldAbort !== null ? $this->wrapProcessGroup($cmd) : $cmd;
        $process = proc_open($spawn, $descriptors, $pipes, $cwd, null);
        if (!is_resource($process)) {
            return ['exitCode' => 127, 'stdout' => '', 'stderr' => "Failed to spawn: {$cmd}"];
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout = '';
        $stderr = '';
        $start = time();
        while (true) {
            $status = proc_get_status($process);
            $stdout .= stream_get_contents($pipes[1]) ?: '';
            $stderr .= stream_get_contents($pipes[2]) ?: '';
            if (!$status['running']) {
                break;
            }
            if ($shouldAbort !== null && $shouldAbort()) {
                $this->killProcess($process, (int) ($status['pid'] ?? 0));
                $stderr .= "\n[ProcShellExecutor] cancelled";
                break;
            }
            if (time() - $start > $timeoutSeconds) {
                $this->killProcess($process, (int) ($status['pid'] ?? 0));
                $stderr .= "\n[ProcShellExecutor] timeout after {$timeoutSeconds}s, killed";
                break;
            }
            usleep(100000);
        }
        $stdout .= stream_get_contents($pipes[1]) ?: '';
        $stderr .= stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return ['exitCode' => $exitCode, 'stdout' => $stdout, 'stderr' => $stderr];
    }

    private function wrapProcessGroup(string $cmd): string
    {
        if (stripos(PHP_OS, 'WIN') === 0) {
            return $cmd;
        }
        foreach (['/usr/bin/setsid', '/bin/setsid'] as $bin) {
            if (is_executable($bin)) {
                return $bin . ' ' . $cmd;
            }
        }
        return $cmd;
    }

    /**
     * @param resource $process
     */
    private function killProcess($process, int $pid): void
    {
        if ($pid > 0 && function_exists('posix_kill')) {
            @posix_kill(-$pid, SIGTERM);
            usleep(200000);
            @posix_kill(-$pid, SIGKILL);
            @posix_kill($pid, SIGKILL);
            return;
        }
        if ($pid > 0) {
            @exec('kill -TERM -' . $pid);
            usleep(200000);
            @exec('kill -KILL -' . $pid);
        }
        proc_terminate($process, 9);
    }
}
