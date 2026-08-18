<?php
use think\facade\Route;

/*
 * 满减活动管理路由（Admin）
 *
 * 此文件由 PluginManager 在 adminapi 应用上下文中加载，
 * URL 前缀: /adminapi/marketing/full-discount
 * 中间件: admin_auth + admin_permission + admin_log（与核心模块保持一致）
 */
Route::group('marketing/full-discount', function () {

    Route::get('', [\plugins\full_discount\adminapi\controller\FullDiscountController::class, 'index']);
    Route::post('', [\plugins\full_discount\adminapi\controller\FullDiscountController::class, 'store']);
    Route::get(':id', [\plugins\full_discount\adminapi\controller\FullDiscountController::class, 'show'])->pattern(['id' => '\d+']);
    Route::put(':id', [\plugins\full_discount\adminapi\controller\FullDiscountController::class, 'update'])->pattern(['id' => '\d+']);
    Route::put(':id/status', [\plugins\full_discount\adminapi\controller\FullDiscountController::class, 'status'])->pattern(['id' => '\d+']);
    Route::delete(':id', [\plugins\full_discount\adminapi\controller\FullDiscountController::class, 'delete'])->pattern(['id' => '\d+']);

})->middleware(['admin_auth', 'admin_permission', 'admin_log']);
