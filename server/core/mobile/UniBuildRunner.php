<?php
declare(strict_types=1);

namespace core\mobile;

use core\plugin\contracts\ShellExecutor;

/**
 * 在带 Node 的 worker 里跑 uni build。PHP-FPM 不得调用。
 */
class UniBuildRunner
{
    private const DEFAULT_TIMEOUT_SEC = 600;
    private const DEFAULT_NODE_MB = 1536;
    private const DEFAULT_THREADS = 2;
    private const STAMP_FILE = '.shop-build-deps.sha';

    public function __construct(
        private readonly ShellExecutor $shell,
    ) {
    }

    /**
     * @param (callable(): bool)|null $shouldAbort
     * @return array{success: bool, log: string, artifactPath: string}
     */
    public function run(string $uniappDir, string $platform, ?callable $shouldAbort = null): array
    {
        $platformDir = match ($platform) {
            'h5' => 'h5',
            'mp-weixin' => 'mp-weixin',
            default => null,
        };
        if ($platformDir === null) {
            return ['success' => false, 'log' => "[runner] unknown platform: {$platform}", 'artifactPath' => ''];
        }

        $timeout = (int) (env('MOBILE_BUILD_TIMEOUT_SEC', self::DEFAULT_TIMEOUT_SEC));
        $pm = $this->packageManager($uniappDir);
        if ($pm === null) {
            return ['success' => false, 'log' => '[runner] pnpm/npm not found; use frontend-builder worker', 'artifactPath' => ''];
        }

        $log = $this->envelopeLog();
        if ($this->shouldInstall($uniappDir)) {
            $installCmd = $this->decorate($pm . ' install');
            $install = $this->shell->exec($installCmd, $uniappDir, $timeout, $shouldAbort);
            $log .= "== {$installCmd} ==\n" . $install['stdout'] . $install['stderr'] . "\n";
            if ($this->aborted($shouldAbort)) {
                return ['success' => false, 'log' => $log . "[runner] cancelled\n", 'artifactPath' => ''];
            }
            if ($install['exitCode'] !== 0) {
                return ['success' => false, 'log' => $log, 'artifactPath' => ''];
            }
            $this->writeStamp($uniappDir);
        } else {
            $log .= "[runner] skip {$pm} install (lockfile unchanged)\n";
        }

        $buildCmd = $this->decorate(
            $platform === 'h5'
                ? $this->scriptCommand($pm, 'build:h5')
                : $this->scriptCommand($pm, 'build:mp-weixin')
        );
        $build = $this->shell->exec($buildCmd, $uniappDir, $timeout, $shouldAbort);
        $log .= "== {$buildCmd} ==\n" . $build['stdout'] . $build['stderr'] . "\n";
        if ($this->aborted($shouldAbort)) {
            return ['success' => false, 'log' => $log . "[runner] cancelled\n", 'artifactPath' => ''];
        }
        if ($build['exitCode'] !== 0) {
            return ['success' => false, 'log' => $log, 'artifactPath' => ''];
        }

        $artifact = rtrim($uniappDir, '/\\') . '/dist/build/' . $platformDir;
        if (!is_dir($artifact)) {
            return ['success' => false, 'log' => $log . "\n[runner] artifact missing: {$artifact}", 'artifactPath' => ''];
        }

        return ['success' => true, 'log' => $log, 'artifactPath' => $artifact];
    }

    private function packageManager(string $cwd): ?string
    {
        $pnpm = $this->shell->exec('command -v pnpm', $cwd);
        if ($pnpm['exitCode'] === 0 && trim($pnpm['stdout']) !== '') {
            return 'pnpm';
        }
        $npm = $this->shell->exec('command -v npm', $cwd);
        if ($npm['exitCode'] === 0 && trim($npm['stdout']) !== '') {
            return 'npm';
        }
        return null;
    }

    private function scriptCommand(string $pm, string $script): string
    {
        return $pm === 'pnpm' ? 'pnpm ' . $script : 'npm run ' . $script;
    }

    /**
     * @param (callable(): bool)|null $shouldAbort
     */
    private function aborted(?callable $shouldAbort): bool
    {
        return $shouldAbort !== null && $shouldAbort();
    }

    private function nodeMb(): int
    {
        return max(256, (int) (env('MOBILE_BUILD_NODE_MB', self::DEFAULT_NODE_MB)));
    }

    private function threads(): int
    {
        return max(1, (int) (env('MOBILE_BUILD_THREADS', self::DEFAULT_THREADS)));
    }

    private function decorate(string $cmd): string
    {
        $inner = 'env NODE_OPTIONS=' . escapeshellarg('--max-old-space-size=' . $this->nodeMb())
            . ' UV_THREADPOOL_SIZE=' . $this->threads()
            . ' ' . $cmd;
        $ionice = $this->bin('ionice');
        if ($ionice !== null) {
            $inner = $ionice . ' -c 3 ' . $inner;
        }
        $nice = $this->bin('nice');
        if ($nice !== null) {
            $inner = $nice . ' -n 15 ' . $inner;
        }
        return $inner;
    }

    private function envelopeLog(): string
    {
        return sprintf(
            "[runner] envelope nice=%s ionice=%s NODE_OPTIONS=--max-old-space-size=%d UV_THREADPOOL_SIZE=%d\n",
            $this->bin('nice') !== null ? '15' : 'off',
            $this->bin('ionice') !== null ? 'idle' : 'off',
            $this->nodeMb(),
            $this->threads()
        );
    }

    private function bin(string $name): ?string
    {
        foreach (['/usr/bin/' . $name, '/bin/' . $name] as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }
        return null;
    }

    private function shouldInstall(string $dir): bool
    {
        if (!is_dir(rtrim($dir, '/\\') . '/node_modules')) {
            return true;
        }
        $hash = $this->lockfileHash($dir);
        if ($hash === '') {
            return true;
        }
        $stamp = @file_get_contents(rtrim($dir, '/\\') . '/' . self::STAMP_FILE);
        return trim((string) $stamp) !== $hash;
    }

    private function writeStamp(string $dir): void
    {
        $hash = $this->lockfileHash($dir);
        if ($hash === '') {
            return;
        }
        @file_put_contents(rtrim($dir, '/\\') . '/' . self::STAMP_FILE, $hash . "\n");
    }

    private function lockfileHash(string $dir): string
    {
        $root = rtrim($dir, '/\\');
        foreach (['pnpm-lock.yaml', 'package-lock.json'] as $name) {
            $path = $root . '/' . $name;
            if (is_file($path)) {
                return hash_file('sha256', $path) ?: '';
            }
        }
        return '';
    }
}
