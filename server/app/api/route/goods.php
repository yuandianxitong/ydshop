<?php
use think\facade\Route;

// 商品列表和详情（无需登录）
Route::group('goods', function () {
    Route::get('list', 'v1.goods.GoodsController/index');
    Route::get(':id', 'v1.goods.GoodsController/show');
});

// 商品分类（无需登录）
Route::group('category', function () {
    Route::get('tree', 'v1.goods.CategoryController/tree');
});
