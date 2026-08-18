<?php
declare(strict_types=1);

namespace core\plugin;

use core\plugin\contracts\ShellExecutor;

/**
 * 仅供构建 worker 调用。PHP-FPM 安装路径不得 exec pnpm。
 */
class ProcShellExecutor implements ShellExecutor
{
    public function exec(string $cmd, string $cwd, int $timeoutSeconds = 600): array
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes, $cwd, null);
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
            if (time() - $start > $timeoutSeconds) {
                proc_terminate($process, 9);
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
}
