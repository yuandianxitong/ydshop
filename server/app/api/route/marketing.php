<?php
use think\facade\Route;

// 广告（公开）
Route::group('ad', function () {
    Route::get('by-position/:code', 'v1.marketing.MarketingAdController/byPosition');
});
