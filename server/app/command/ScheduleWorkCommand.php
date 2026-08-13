<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Console;
use think\facade\Db;

/**
 * 常驻调度工人：周期调用 schedule:run-due，提供 start/stop/restart/status。
 *
 * 非 Docker / 宝塔场景可用；Docker 仍可用 supervisord 直接跑 schedule:run-due。
 * 每轮 tick 前关闭 PDO，避免 MySQL wait_timeout 导致 MySQL server has gone away。
 */
class ScheduleWorkCommand extends Command
{
    private const DEFAULT_INTERVAL = 60;

    private bool $shouldStop = false;

    protected function configure(): void
    {
        $this->setName('schedule:work')
            ->setDescription('Long-lived worker that polls schedule:run-due (start|stop|restart|status)')
            ->addArgument('action', Argument::REQUIRED, 'start|stop|restart|status')
            ->addOption('d', null, Option::VALUE_NONE, 'Run as daemon (background)')
            ->addOption('interval', 'i', Option::VALUE_REQUIRED, 'Seconds between polls', (string)self::DEFAULT_INTERVAL);
    }

    protected function execute(Input $input, Output $output): int
    {
        $action = strtolower(trim((string)$input->getArgument('action')));
        $daemon = (bool)$input->getOption('d');
        $interval = max(1, (int)$input->getOption('interval'));

        return match ($action) {
            'start' => $this->start($output, $daemon, $interval),
            'stop' => $this->stop($output),
            'restart' => $this->restart($output, $daemon, $interval),
            'status' => $this->status($output),
            default => $this->unknownAction($output, $action),
        };
    }

    private function start(Output $output, bool $daemon, int $interval): int
    {
        $runningPid = $this->runningPid();
        if ($runningPid > 0) {
            $output->writeln(sprintf('<error>schedule:work already running (pid %d).</error>', $runningPid));
            return 1;
        }

        if ($daemon) {
            return $this->daemonizeAndStart($output, $interval);
        }

        $this->writePid(getmypid() ?: 0);
        $output->writeln(sprintf(
            'schedule:work started in foreground (pid %d, interval %ds). Ctrl+C to stop.',
            getmypid() ?: 0,
            $interval
        ));

        return $this->runLoop($output, $interval);
    }

    private function stop(Output $output): int
    {
        $pid = $this->readPid();
        if ($pid <= 0 || !$this->isProcessAlive($pid)) {
            $this->clearPid();
            $output->writeln('schedule:work is not running.');
            return 0;
        }

        if (!$this->terminateProcess($pid)) {
            $output->writeln(sprintf('<error>Failed to stop schedule:work (pid %d).</error>', $pid));
            return 1;
        }

        $this->clearPid();
        $output->writeln(sprintf('schedule:work stopped (pid %d).', $pid));
        return 0;
    }

    private function restart(Output $output, bool $daemon, int $interval): int
    {
        $this->stop($output);
        // 给旧进程一点时间释放 PID / 句柄
        usleep(200000);
        return $this->start($output, $daemon, $interval);
    }

    private function status(Output $output): int
    {
        $pid = $this->runningPid();
        if ($pid > 0) {
            $output->writeln(sprintf('schedule:work is running (pid %d).', $pid));
            return 0;
        }
        $this->clearPid();
        $output->writeln('schedule:work is not running.');
        return 1;
    }

    private function unknownAction(Output $output, string $action): int
    {
        $output->writeln(sprintf(
            '<error>Unknown action "%s". Use start|stop|restart|status.</error>',
            $action
        ));
        return 1;
    }

    private function daemonizeAndStart(Output $output, int $interval): int
    {
        if ($this->canForkDaemon()) {
            return $this->forkDaemon($output, $interval);
        }

        return $this->spawnNohupDaemon($output, $interval);
    }

    private function canForkDaemon(): bool
    {
        return function_exists('pcntl_fork')
            && function_exists('posix_setsid')
            && function_exists('posix_kill');
    }

    private function forkDaemon(Output $output, int $interval): int
    {
        $pid = pcntl_fork();
        if ($pid === -1) {
            $output->writeln('<error>pcntl_fork failed.</error>');
            return 1;
        }
        if ($pid > 0) {
            // 父进程：等子进程写好 PID 再退出
            usleep(150000);
            $child = $this->runningPid();
            if ($child > 0) {
                $output->writeln(sprintf(
                    'schedule:work started as daemon (pid %d, interval %ds). log: %s',
                    $child,
                    $interval,
                    $this->logFile()
                ));
                return 0;
            }
            $output->writeln('<error>Daemon started but pid file missing.</error>');
            return 1;
        }

        // 子进程脱离终端
        if (posix_setsid() === -1) {
            exit(1);
        }

        $this->installSignalHandlers();
        $this->writePid(getmypid() ?: 0);

        // daemon 子进程无终端；调度输出写入 schedule-work.log
        $nullOutput = new Output('nothing');
        exit($this->runLoop($nullOutput, $interval));
    }

    private function spawnNohupDaemon(Output $output, int $interval): int
    {
        if (!function_exists('proc_open')) {
            $output->writeln(
                '<error>Neither pcntl_fork nor proc_open is available. '
                . 'Please enable: proc_open pcntl_signal pcntl_signal_dispatch '
                . 'pcntl_fork pcntl_wait pcntl_alarm</error>'
            );
            return 1;
        }

        $php = PHP_BINARY !== '' ? PHP_BINARY : 'php';
        $think = rtrim($this->rootPath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'think';
        $log = $this->logFile();
        $this->ensureParentDir($log);

        // 子进程前台循环，由 nohup 挂到后台；勿再传 --d，避免递归守护
        $command = sprintf(
            'nohup %s %s schedule:work start --interval=%d >> %s 2>&1 & echo $!',
            escapeshellarg($php),
            escapeshellarg($think),
            $interval,
            escapeshellarg($log)
        );

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($command, $descriptors, $pipes, $this->rootPath());
        if (!is_resource($process)) {
            $output->writeln('<error>Failed to spawn schedule:work via proc_open/nohup.</error>');
            return 1;
        }

        fclose($pipes[0]);
        $pidText = trim(stream_get_contents($pipes[1]) ?: '');
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        $pid = (int)$pidText;
        if ($pid <= 0) {
            $output->writeln('<error>nohup spawn did not return a pid.</error>');
            return 1;
        }

        // 子进程自己也会写 PID；这里先落一份，便于立刻 status
        $this->writePid($pid);
        usleep(200000);
        $alive = $this->runningPid();
        if ($alive <= 0) {
            $output->writeln('<error>schedule:work failed to stay running. Check log: '
                . $log . '</error>');
            return 1;
        }

        $output->writeln(sprintf(
            'schedule:work started as daemon via nohup (pid %d, interval %ds). log: %s',
            $alive,
            $interval,
            $log
        ));
        $output->writeln(
            '<comment>Tip: enable pcntl_* for native daemonize '
            . '(pcntl_fork / posix_setsid).</comment>'
        );
        return 0;
    }

    private function runLoop(Output $output, int $interval): int
    {
        $this->installSignalHandlers();

        while (!$this->shouldStop) {
            try {
                // 长驻进程复用的 PDO 会被 MySQL wait_timeout / 云库空闲策略掐断；
                // 每轮先 close，下次查询时 think-orm 会重新 connect。
                $this->refreshDatabaseConnection();
                $buffer = Console::call('schedule:run-due');
                // Output::fetch 经 buffer driver 的 __call 提供
                $text = trim((string)$buffer->fetch());
                if ($text !== '') {
                    $output->writeln($text);
                    $this->appendLog($text . PHP_EOL);
                }
            } catch (\Throwable $e) {
                $line = 'schedule:work tick failed: ' . $e->getMessage();
                $output->writeln('<error>' . $line . '</error>');
                $this->appendLog($line . PHP_EOL);
                // 失败后再清一次，避免下一轮继续拿着死连接
                $this->refreshDatabaseConnection();
            }

            if ($this->shouldStop) {
                break;
            }

            // 可中断 sleep，便于尽快响应 stop
            for ($i = 0; $i < $interval; $i++) {
                if ($this->shouldStop) {
                    break 2;
                }
                if (function_exists('pcntl_signal_dispatch')) {
                    pcntl_signal_dispatch();
                }
                sleep(1);
            }
        }

        $this->clearPid();
        $output->writeln('schedule:work loop exited.');
        return 0;
    }

    /**
     * 释放当前库连接，供下一轮查询重新建立。
     */
    private function refreshDatabaseConnection(): void
    {
        try {
            Db::connect()->close();
        } catch (\Throwable) {
            // close 本身失败不阻断调度
        }
    }

    private function installSignalHandlers(): void
    {
        if (!function_exists('pcntl_signal')) {
            return;
        }
        $handler = function () {
            $this->shouldStop = true;
        };
        pcntl_signal(SIGTERM, $handler);
        pcntl_signal(SIGINT, $handler);
        if (defined('SIGHUP')) {
            pcntl_signal(SIGHUP, SIG_IGN);
        }
    }

    private function appendLog(string $text): void
    {
        $log = $this->logFile();
        $this->ensureParentDir($log);
        @file_put_contents($log, '[' . date('Y-m-d H:i:s') . '] ' . $text, FILE_APPEND);
    }

    private function runningPid(): int
    {
        $pid = $this->readPid();
        if ($pid <= 0) {
            return 0;
        }
        if (!$this->isProcessAlive($pid)) {
            $this->clearPid();
            return 0;
        }
        return $pid;
    }

    private function readPid(): int
    {
        $file = $this->pidFile();
        if (!is_file($file)) {
            return 0;
        }
        $raw = trim((string)@file_get_contents($file));
        return max(0, (int)$raw);
    }

    private function writePid(int $pid): void
    {
        if ($pid <= 0) {
            return;
        }
        $file = $this->pidFile();
        $this->ensureParentDir($file);
        file_put_contents($file, (string)$pid);
    }

    private function clearPid(): void
    {
        $file = $this->pidFile();
        if (is_file($file)) {
            @unlink($file);
        }
    }

    private function isProcessAlive(int $pid): bool
    {
        if ($pid <= 0) {
            return false;
        }
        if (function_exists('posix_kill')) {
            return @posix_kill($pid, 0);
        }
        // 无 posix 时用 /proc（Linux）兜底
        return is_dir('/proc/' . $pid);
    }

    private function terminateProcess(int $pid): bool
    {
        if (function_exists('posix_kill')) {
            @posix_kill($pid, SIGTERM);
            for ($i = 0; $i < 20; $i++) {
                if (!$this->isProcessAlive($pid)) {
                    return true;
                }
                usleep(100000);
            }
            @posix_kill($pid, SIGKILL);
            usleep(100000);
            return !$this->isProcessAlive($pid);
        }

        // 无 posix_kill：尽力用 kill 命令
        if (function_exists('exec')) {
            @exec('kill -TERM ' . (int)$pid);
            usleep(200000);
            if (!$this->isProcessAlive($pid)) {
                return true;
            }
            @exec('kill -KILL ' . (int)$pid);
            usleep(100000);
            return !$this->isProcessAlive($pid);
        }

        return false;
    }

    private function pidFile(): string
    {
        return rtrim($this->rootPath(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'runtime'
            . DIRECTORY_SEPARATOR . 'schedule-work.pid';
    }

    private function logFile(): string
    {
        return rtrim($this->rootPath(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'runtime'
            . DIRECTORY_SEPARATOR . 'schedule-work.log';
    }

    private function rootPath(): string
    {
        return app()->getRootPath();
    }

    private function ensureParentDir(string $file): void
    {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }
}
