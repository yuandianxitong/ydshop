<?php
use think\facade\Route;

Route::group('marketing/sign-config', function () {
    Route::get('',      [\plugins\sign\controller\admin\SignConfigController::class, 'getConfig']);
    Route::put('',      [\plugins\sign\controller\admin\SignConfigController::class, 'updateConfig']);
    Route::get('logs',  [\plugins\sign\controller\admin\SignLogController::class, 'list']);
    Route::get('stats', [\plugins\sign\controller\admin\SignLogController::class, 'stats']);
})->middleware(['admin_full']);
