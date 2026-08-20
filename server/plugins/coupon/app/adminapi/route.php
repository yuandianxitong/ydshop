<?php
use think\facade\Route;

Route::group('marketing/coupon', function () {
    Route::get('',        [\plugins\coupon\adminapi\controller\CouponController::class, 'index']);
    Route::post('',       [\plugins\coupon\adminapi\controller\CouponController::class, 'store']);
    Route::put(':id',     [\plugins\coupon\adminapi\controller\CouponController::class, 'update']);
    Route::delete(':id',  [\plugins\coupon\adminapi\controller\CouponController::class, 'delete']);
})->middleware(['admin_auth', 'admin_permission', 'admin_log']);
