<?php
use think\facade\Route;

// 广告位管理
Route::group('ad-positions', function () {
    Route::get('list', 'v1.marketing.MarketingAdPositionController/list');
    Route::get('all', 'v1.marketing.MarketingAdPositionController/all');
    Route::get(':id', 'v1.marketing.MarketingAdPositionController/detail');
    Route::post('', 'v1.marketing.MarketingAdPositionController/create');
    Route::put(':id', 'v1.marketing.MarketingAdPositionController/update');
    Route::delete(':id', 'v1.marketing.MarketingAdPositionController/delete');
})->middleware(['admin_full']);

// 广告管理
Route::group('ads', function () {
    Route::get('list', 'v1.marketing.MarketingAdController/list');
    Route::get(':id', 'v1.marketing.MarketingAdController/detail');
    Route::post('', 'v1.marketing.MarketingAdController/create');
    Route::put(':id', 'v1.marketing.MarketingAdController/update');
    Route::delete(':id', 'v1.marketing.MarketingAdController/delete');
    Route::post('batch-status', 'v1.marketing.MarketingAdController/batchStatus');
})->middleware(['admin_full']);
