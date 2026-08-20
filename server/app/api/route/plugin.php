<?php
/**
 * MultiApp 已将当前应用定为 api 后再加载本文件。
 * 插件 C 端路由必须在这里注册，不能在 AppService::boot() 里提前 require。
 */
\core\plugin\PluginManager::registerHttpRoutes();
