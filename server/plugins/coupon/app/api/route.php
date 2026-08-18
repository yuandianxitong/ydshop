<?php
use think\facade\Route;

Route::group('marketing/coupon', function () {
    Route::get('available', 'plugins\coupon\api\controller\CouponController@available');
    Route::get('receivable', 'plugins\coupon\api\controller\CouponController@receivable');
    Route::post('claim', 'plugins\coupon\api\controller\CouponController@claim');
    Route::get('my', 'plugins\coupon\api\controller\CouponController@my');
})->middleware(['api_auth']);
