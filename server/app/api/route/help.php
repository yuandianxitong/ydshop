<?php
use think\facade\Route;

// C端帮助中心（无需登录）
Route::group('help', function () {
    Route::get('categories', 'v1.help.HelpController/categories');
    Route::get('list', 'v1.help.HelpController/list');
    Route::get(':id', 'v1.help.HelpController/detail');
});
