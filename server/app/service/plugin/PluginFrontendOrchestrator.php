<?php
declare(strict_types=1);

namespace app\service\plugin;

use core\base\Service;
use core\plugin\PluginFrontendDeployer;
use core\plugin\PluginFrontendSync;
use core\plugin\PluginPagesJsonMerger;

/**
 * 安装后：软链或拷贝同步 + 合 pages.json。不入队云编，不在 FPM 里 exec pnpm。
 */
class PluginFrontendOrchestrator extends Service
{
    /**
     * @return array{frontend: int, mode: string, admin_pc: list<array<string, mixed>>, mobile: list<array<string, mixed>>}
     */
    public function afterInstall(string $code, string $trigger = 'install'): array
    {
        $sync = PluginFrontendSync::sync($code);
        PluginPagesJsonMerger::merge($code);
        return [
            'frontend' => $sync['count'],
            'mode'     => 'sync',
            'admin_pc' => [],
            'mobile'   => [],
        ];
    }

    /**
     * @return array{mode: string, admin_pc: list<array<string, mixed>>, mobile: list<array<string, mixed>>}
     */
    public function afterUninstall(string $code): array
    {
        PluginFrontendDeployer::remove($code);
        PluginFrontendSync::remove($code);
        return [
            'mode'     => 'sync',
            'admin_pc' => [],
            'mobile'   => [],
        ];
    }
}
