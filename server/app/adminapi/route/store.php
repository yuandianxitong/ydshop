<?php
use think\facade\Route;

// 门店管理（v2.4.0 自提模块）
Route::group('store', function () {
    Route::get('',         'v1.StoreController/index');
    Route::get(':id',      'v1.StoreController/show');
    Route::post('',        'v1.StoreController/store');
    Route::put(':id',      'v1.StoreController/update');
    Route::delete(':id',   'v1.StoreController/destroy');
})->middleware(['admin_full']);

// 订单自提核销（挂在 order 组下）
Route::put('order/order/:id/pickup-verify', 'v1.order.OrderController/pickupVerify')
    ->middleware(['admin_full']);
