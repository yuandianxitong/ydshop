<?php
declare (strict_types = 1);

namespace app;

use think\Service;
use core\plugin\PluginManager;

/**
 * 应用服务类
 */
class AppService extends Service
{
    public function register()
    {
        $this->app->bind(\core\plugin\contracts\ShellExecutor::class, fn () => new \core\plugin\ProcShellExecutor());
        $this->app->bind(\core\plugin\PluginBuilder::class, function () {
            return new \core\plugin\PluginBuilder($this->app->make(\core\plugin\contracts\ShellExecutor::class));
        });
        $this->app->bind(\core\mobile\UniBuildRunner::class, function () {
            return new \core\mobile\UniBuildRunner($this->app->make(\core\plugin\contracts\ShellExecutor::class));
        });
    }

    public function boot()
    {
        // 启动插件系统（营销插件：优惠券、满减、秒杀、拼团）
        PluginManager::boot();
    }
}
