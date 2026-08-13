<?php
use think\facade\Route;

Route::group('marketing/coupon', function () {
    Route::get('', 'plugins\coupon\controller\admin\CouponController@index');
    Route::post('', 'plugins\coupon\controller\admin\CouponController@store');
    Route::put(':id', 'plugins\coupon\controller\admin\CouponController@update');
    Route::delete(':id', 'plugins\coupon\controller\admin\CouponController@delete');
})->middleware(['admin_auth', 'admin_permission', 'admin_log']);
