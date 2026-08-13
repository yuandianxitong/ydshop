<?php
use think\facade\Route;

Route::group('common', function () {
    // 公开
    Route::get('config', 'v1.common.CommonController/config');
    Route::post('sms-code', 'v1.common.CommonController/sendSmsCode')->middleware('api_sms_rate_limit');

    // 需登录
    Route::post('upload/image', 'v1.common.CommonController/uploadImage')->middleware(['api_auth']);
});
