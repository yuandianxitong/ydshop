<?php
/* ============================================================
 * 项目：元点Shop
 * 官网：https://www.dev007.cn
 * Slogan：提供高质量行业系统源码，帮助中小企业快速搭建专属应用
 * Author：mashanglai Team
 * ============================================================ */
use think\facade\Route;

Route::group('auth', function () {
    // 公开（仅限流）
    Route::get('captcha', 'v1.auth.AuthController/captcha')
        ->middleware([\app\adminapi\middleware\LoginRateLimitMiddleware::class]);
    Route::post('login', 'v1.auth.AuthController/login')
        ->middleware([\app\adminapi\middleware\LoginRateLimitMiddleware::class]);

    // 需登录
    Route::get('info', 'v1.auth.AuthController/info')
        ->middleware(['admin_full']);
    Route::post('refresh', 'v1.auth.AuthController/refresh')
        ->middleware(['admin_full']);
    Route::post('logout', 'v1.auth.AuthController/logout')
        ->middleware(['admin_full']);
});
