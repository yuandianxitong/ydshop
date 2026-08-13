<?php
use think\facade\Route;

Route::group('auth', function () {
    // 公开
    Route::post('login', 'v1.auth.AuthController/login');
    Route::post('sms-login', 'v1.auth.AuthController/smsLogin');
    Route::post('wechat-login', 'v1.auth.AuthController/wechatLogin');
    Route::post('wechat-web-login', 'v1.auth.AuthController/wechatWebLogin');
    Route::post('wechat-quick-login', 'v1.auth.AuthController/wechatQuickLogin');
    Route::post('wechat-bindphone', 'v1.auth.AuthController/wechatBindPhone');
    Route::post('wechat-h5-login', 'v1.auth.AuthController/wechatH5Login');
    Route::post('register', 'v1.auth.AuthController/register');

    // 需登录
    Route::get('info', 'v1.auth.AuthController/info')->middleware(['api_auth']);
    Route::post('logout', 'v1.auth.AuthController/logout')->middleware(['api_auth']);
    Route::post('refresh-token', 'v1.auth.AuthController/refreshToken')->middleware(['api_auth']);
});
