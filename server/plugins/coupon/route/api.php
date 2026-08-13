<?php
use think\facade\Route;

Route::group('marketing/coupon', function () {
    Route::get('available', 'plugins\coupon\controller\api\CouponController@available');
    Route::get('receivable', 'plugins\coupon\controller\api\CouponController@receivable');
    Route::post('claim', 'plugins\coupon\controller\api\CouponController@claim');
    Route::get('my', 'plugins\coupon\controller\api\CouponController@my');
})->middleware(['api_auth']);
