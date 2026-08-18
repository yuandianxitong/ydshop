<?php
declare(strict_types=1);

namespace core\plugin;

use core\plugin\contracts\ShellExecutor;

/**
 * 宿主 SPA 云编译（admin / pc）。必须在带 Node 的 worker 里跑，不在 PHP-FPM 里 exec。
 *
 *   1. 包管理器 install
 *   2. sync-plugins.mjs
 *   3. BUILD_TMP=1 构建到 public/{target}.build-tmp
 *   4. 无条件 sync --clean
 *   5. 校验产物后原子切换 public/{target}
 */
class PluginBuilder
{
    public function __construct(
        private readonly ShellExecutor $shell,
    ) {
    }

    /**
     * @return array{exitCode: int, log: string, artifactPath: string}
     */
    public function build(string $target, string $sourceDir, string $publicDir): array
    {
        $logBuffer = '';
        $tmpDir    = rtrim($publicDir, '/\\') . DIRECTORY_SEPARATOR . $target . '.build-tmp';
        $finalDir  = rtrim($publicDir, '/\\') . DIRECTORY_SEPARATOR . $target;
        $repoRoot  = dirname($sourceDir);
        $syncJs    = $repoRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'sync-plugins.mjs';
        $syncCmd   = 'node ' . escapeshellarg($syncJs) . ' --target ' . escapeshellarg($target);
        $cleanCmd  = $syncCmd . ' --clean';

        $pm = $this->packageManager($sourceDir);
        if ($pm === null) {
            return ['exitCode' => 127, 'log' => '[builder] pnpm/npm not found; run this job on the frontend-builder worker', 'artifactPath' => ''];
        }

        $r = $this->shell->exec($pm . ' install', $sourceDir);
        $logBuffer .= "\n== {$pm} install ==\n" . $r['stdout'] . "\n" . $r['stderr'];
        if ($r['exitCode'] !== 0) {
            return ['exitCode' => $r['exitCode'], 'log' => $logBuffer, 'artifactPath' => ''];
        }

        $r = $this->shell->exec($syncCmd, $repoRoot);
        $logBuffer .= "\n== sync-plugins ==\n" . $r['stdout'] . "\n" . $r['stderr'];
        if ($r['exitCode'] !== 0) {
            return ['exitCode' => $r['exitCode'], 'log' => $logBuffer, 'artifactPath' => ''];
        }

        $buildCmd = $target === 'pc'
            ? $this->scriptCommand($pm, 'generate')
            : $this->scriptCommand($pm, 'build');
        $buildEnv = 'BUILD_TMP=1 ' . $buildCmd;
        $buildResult = $this->shell->exec($buildEnv, $sourceDir, 900);
        $logBuffer .= "\n== {$buildEnv} ==\n" . $buildResult['stdout'] . "\n" . $buildResult['stderr'];

        $r = $this->shell->exec($cleanCmd, $repoRoot);
        $logBuffer .= "\n== sync-plugins --clean ==\n" . $r['stdout'] . "\n" . $r['stderr'];

        if ($buildResult['exitCode'] !== 0) {
            $this->rrmdir($tmpDir);
            return ['exitCode' => $buildResult['exitCode'], 'log' => $logBuffer, 'artifactPath' => ''];
        }

        if ($target === 'pc' && !is_dir($tmpDir) && is_dir($sourceDir . '/.output/public')) {
            $this->copyTree($sourceDir . '/.output/public', $tmpDir);
        }

        if (!is_file($tmpDir . '/index.html')) {
            $logBuffer .= "\n[builder] missing artifact: {$tmpDir}/index.html";
            $this->rrmdir($tmpDir);
            return ['exitCode' => 1, 'log' => $logBuffer, 'artifactPath' => ''];
        }

        $backupDir = '';
        if (is_dir($finalDir)) {
            $backupDir = $finalDir . '.bak-' . time();
            if (!@rename($finalDir, $backupDir)) {
                $logBuffer .= "\n[builder] failed to rename old artifact to backup";
                return ['exitCode' => 1, 'log' => $logBuffer, 'artifactPath' => ''];
            }
        }
        if (!@rename($tmpDir, $finalDir)) {
            if ($backupDir !== '' && is_dir($backupDir)) {
                @rename($backupDir, $finalDir);
            }
            $logBuffer .= "\n[builder] failed to promote new artifact";
            return ['exitCode' => 1, 'log' => $logBuffer, 'artifactPath' => ''];
        }
        if ($backupDir !== '' && is_dir($backupDir)) {
            $this->rrmdir($backupDir);
        }

        return ['exitCode' => 0, 'log' => $logBuffer, 'artifactPath' => $finalDir];
    }

    private function packageManager(string $sourceDir): ?string
    {
        if ($this->commandExists('pnpm')) {
            return 'pnpm';
        }
        if ($this->commandExists('npm')) {
            return 'npm';
        }
        return null;
    }

    private function scriptCommand(string $pm, string $script): string
    {
        return $pm === 'pnpm' ? 'pnpm ' . $script : 'npm run ' . $script;
    }

    private function commandExists(string $bin): bool
    {
        $r = $this->shell->exec('command -v ' . escapeshellarg($bin), getcwd() ?: '/');
        return $r['exitCode'] === 0 && trim($r['stdout']) !== '';
    }

    private function copyTree(string $from, string $to): void
    {
        if (!is_dir($to) && !@mkdir($to, 0755, true) && !is_dir($to)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($from, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($items as $item) {
            $rel = substr($item->getPathname(), strlen($from) + 1);
            $dest = $to . DIRECTORY_SEPARATOR . $rel;
            if ($item->isDir()) {
                if (!is_dir($dest)) {
                    @mkdir($dest, 0755, true);
                }
                continue;
            }
            $parent = dirname($dest);
            if (!is_dir($parent)) {
                @mkdir($parent, 0755, true);
            }
            @copy($item->getPathname(), $dest);
        }
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $f) {
            $p = $dir . DIRECTORY_SEPARATOR . $f;
            is_dir($p) ? $this->rrmdir($p) : @unlink($p);
        }
        @rmdir($dir);
    }
}
