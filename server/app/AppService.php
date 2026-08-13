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
        // 服务注册
    }

    public function boot()
    {
        // 启动插件系统（营销插件：优惠券、满减、秒杀、拼团）
        PluginManager::boot();
    }
}
