<?php
use think\facade\Route;

Route::group('marketing/coupon', function () {
    Route::get('', 'plugins\coupon\adminapi\controller\CouponController@index');
    Route::post('', 'plugins\coupon\adminapi\controller\CouponController@store');
    Route::put(':id', 'plugins\coupon\adminapi\controller\CouponController@update');
    Route::delete(':id', 'plugins\coupon\adminapi\controller\CouponController@delete');
})->middleware(['admin_auth', 'admin_permission', 'admin_log']);
