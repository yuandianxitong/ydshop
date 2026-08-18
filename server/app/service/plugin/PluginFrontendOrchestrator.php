<?php
declare(strict_types=1);

namespace app\service\plugin;

use core\base\Service;
use core\plugin\PluginFrontendDeployer;
use core\plugin\PluginFrontendSync;
use core\plugin\PluginPagesJsonMerger;

/**
 * 安装后：软链同步 + 合 pages.json + 入队云编。不在 FPM 里 exec pnpm。
 */
class PluginFrontendOrchestrator extends Service
{
    protected PluginBuildService $pluginBuildService;
    protected MobileBuildService $mobileBuildService;

    /**
     * @return array{frontend: int, mode: string, admin_pc: list<array<string, mixed>>, mobile: list<array<string, mixed>>}
     */
    public function afterInstall(string $code, string $trigger = 'install'): array
    {
        $sync = PluginFrontendSync::sync($code);
        PluginPagesJsonMerger::merge($code);
        $mode = PluginBuildService::shouldCloudBuild() ? 'cloud' : 'dev';
        return [
            'frontend' => $sync['count'],
            'mode'     => $mode,
            'admin_pc' => $this->pluginBuildService->enqueueForPlugin($code, $trigger),
            'mobile'   => $this->mobileBuildService->enqueueForPlugin($code, $trigger),
        ];
    }

    /**
     * @return array{mode: string, admin_pc: list<array<string, mixed>>, mobile: list<array<string, mixed>>}
     */
    public function afterUninstall(string $code): array
    {
        PluginFrontendDeployer::remove($code);
        PluginFrontendSync::remove($code);
        $mode = PluginBuildService::shouldCloudBuild() ? 'cloud' : 'dev';
        return [
            'mode'     => $mode,
            'admin_pc' => $this->pluginBuildService->enqueueForPlugin($code, 'uninstall'),
            'mobile'   => $this->mobileBuildService->enqueueForPlugin($code, 'uninstall'),
        ];
    }
}
