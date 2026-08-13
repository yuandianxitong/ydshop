<?php
use think\facade\Route;

// 三方同城配送平台回调（公开，不挂鉴权中间件；验签在各平台 Adapter 内完成）
// ⚠️ 只允许一个 group：同前缀连续 Route::group 会导致中间件串读（历史踩坑）
Route::group('delivery/callback', function () {
    Route::post(':platform', 'v1.delivery.DeliveryCallbackController/notify');
});
