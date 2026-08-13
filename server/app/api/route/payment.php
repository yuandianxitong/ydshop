<?php
use think\facade\Route;

Route::group('payment', function () {
    // 公开（异步回调）
    Route::post('notify/alipay', 'v1.payment.PaymentController/alipayNotify');
    Route::post('notify/wechat', 'v1.payment.PaymentController/wechatNotify');

    // 需登录
    Route::post('create', 'v1.payment.PaymentController/create')->middleware(['api_auth']);
    Route::get('query', 'v1.payment.PaymentController/query')->middleware(['api_auth']);
});
