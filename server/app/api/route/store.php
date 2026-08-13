<?php
use think\facade\Route;

// 门店（用户端，公开）
Route::group('store', function () {
    Route::get('list', 'v1.StoreController/list');
    Route::get(':id',  'v1.StoreController/show');
});
