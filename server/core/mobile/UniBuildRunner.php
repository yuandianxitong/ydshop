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

    public function __construct(
        private readonly ShellExecutor $shell,
    ) {
    }

    /**
     * @return array{success: bool, log: string, artifactPath: string}
     */
    public function run(string $uniappDir, string $platform): array
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

        $log = '';
        $install = $this->shell->exec($pm . ' install', $uniappDir, $timeout);
        $log .= "== {$pm} install ==\n" . $install['stdout'] . $install['stderr'] . "\n";
        if ($install['exitCode'] !== 0) {
            return ['success' => false, 'log' => $log, 'artifactPath' => ''];
        }

        $buildCmd = $platform === 'h5'
            ? $this->scriptCommand($pm, 'build:h5')
            : $this->scriptCommand($pm, 'build:mp-weixin');
        $build = $this->shell->exec($buildCmd, $uniappDir, $timeout);
        $log .= "== {$buildCmd} ==\n" . $build['stdout'] . $build['stderr'] . "\n";
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
}
