<?php
use think\facade\Route;

Route::group('marketing/coupon', function () {
    Route::get('available',  [\plugins\coupon\api\controller\CouponController::class, 'available']);
    Route::get('receivable', [\plugins\coupon\api\controller\CouponController::class, 'receivable']);
    Route::post('claim',     [\plugins\coupon\api\controller\CouponController::class, 'claim']);
    Route::get('my',         [\plugins\coupon\api\controller\CouponController::class, 'my']);
})->middleware(['api_auth']);
