<?php
use think\facade\Route;

Route::group('marketing/sign-config', function () {
    Route::get('',      [\plugins\sign\adminapi\controller\SignConfigController::class, 'getConfig']);
    Route::put('',      [\plugins\sign\adminapi\controller\SignConfigController::class, 'updateConfig']);
    Route::get('logs',  [\plugins\sign\adminapi\controller\SignLogController::class, 'list']);
    Route::get('stats', [\plugins\sign\adminapi\controller\SignLogController::class, 'stats']);
})->middleware(['admin_full']);
