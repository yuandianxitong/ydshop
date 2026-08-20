<?php
declare(strict_types=1);

namespace app\command;

use app\model\plugin\Plugin;
use core\plugin\PluginFrontendDeployer;
use core\plugin\PluginManager;
use core\plugin\PluginManifest;
use core\plugin\PluginMigrationRunner;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * One-shot bundled-plugin enrollment.
 *
 * For every plugins/<code>/plugin.json not yet in the plugins table:
 * run database/install.sql via PluginManager::install (SqlRunner + tip
 * semver baseline), then register menus/permissions/plugins row.
 *
 * Already-enrolled plugins only apply pending database/updates (semver),
 * never re-run install.sql.
 *
 * Used by:
 *   - upgrades from a pre-vNext version (operator runs it once)
 *   - recovery when bundled plugins were skipped at install time
 *
 * Fresh web installs use install.class.php (same SQL + tip-baseline semantics).
 */
class PluginEnrollBundled extends Command
{
    /**
     * Bundled plugin install order (dependencies first).
     * Keep in sync with install.class.php BUNDLED_PLUGIN_INSTALL_ORDER.
     */
    private const INSTALL_ORDER = [
        'content_mgmt',
        'article',
        'coupon',
        'full_discount',
        'sign',
        'new_user_gift',
    ];

    protected function configure(): void
    {
        $this->setName('plugin:enroll-bundled')
            ->setDescription('Enroll every plugins/<code>/plugin.json as installed in the plugins table.');
    }

    protected function execute(Input $input, Output $output): int
    {
        $pluginsDir = root_path() . 'plugins' . DIRECTORY_SEPARATOR;
        if (!is_dir($pluginsDir)) {
            $output->error("插件目录不存在：$pluginsDir");
            return 1;
        }

        $pending = [];
        foreach (scandir($pluginsDir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $dir = $pluginsDir . $entry . DIRECTORY_SEPARATOR;
            if (!is_dir($dir)) {
                continue;
            }
            $manifestPath = $dir . 'plugin.json';
            if (!is_file($manifestPath)) {
                continue;
            }

            try {
                $manifest = PluginManifest::fromFile($manifestPath);
            } catch (\Throwable $e) {
                $output->error("[$entry] 清单无效：" . $e->getMessage());
                continue;
            }
            if ($manifest->category === 'value_added') {
                $output->info("[$entry] 付费组件，跳过捆绑入册（请从官网市场安装）");
                continue;
            }
            $pending[] = $manifest;
        }

        $order = array_flip(self::INSTALL_ORDER);
        usort($pending, static function (PluginManifest $a, PluginManifest $b) use ($order): int {
            $ai = $order[$a->code] ?? 1000;
            $bi = $order[$b->code] ?? 1000;
            if ($ai === $bi) {
                return strcmp($a->code, $b->code);
            }
            return $ai <=> $bi;
        });

        $total   = 0;
        $skipped = 0;
        PluginManager::$runFrontendQueue = false;
        try {
            foreach ($pending as $manifest) {
                if (Plugin::where('code', $manifest->code)->find()) {
                    // Already enrolled: apply pending semver updates only (tip adopt if needed).
                    try {
                        $applied = PluginMigrationRunner::run($manifest->code);
                        if ($applied) {
                            $output->info("[{$manifest->code}] 已入册，应用升级: " . implode(', ', $applied));
                        } else {
                            $output->info("[{$manifest->code}] 已入册，跳过");
                        }
                    } catch (\Throwable $e) {
                        $output->warning("[{$manifest->code}] 升级失败：" . $e->getMessage());
                    }
                    $skipped++;
                    continue;
                }

                // Fresh enroll: install.sql + tip semver baseline via PluginManager.
                // Do NOT call PluginMigrationRunner before the plugins row exists —
                // that would treat from=0.0.0 and re-apply all updates incorrectly.
                try {
                    PluginManager::install($manifest, Plugin::SOURCE_BUNDLED);
                    $total++;
                    $output->info("[{$manifest->code}] 已入册 v{$manifest->version}");
                } catch (\Throwable $e) {
                    $output->error("[{$manifest->code}] 失败：" . $e->getMessage());
                }
            }

            foreach ($pending as $manifest) {
                try {
                    $n = PluginFrontendDeployer::deployFromPluginDir($manifest->code);
                    $output->info("[{$manifest->code}] 前端部署 {$n} 个文件");
                } catch (\Throwable $e) {
                    $output->warning("[{$manifest->code}] 前端部署失败：" . $e->getMessage());
                }
            }
        } finally {
            PluginManager::$runFrontendQueue = true;
        }

        $output->info("完成。本次入册 $total 个插件，跳过 $skipped 个。");
        $output->writeln('<comment>已同步前端。开发机重启 Vite；生产请重新部署已构建的 public/admin（如有新后台页）</comment>');
        return 0;
    }
}
