<?php
use think\facade\Route;

Route::group('diy', function () {
    Route::get('page', 'v1.diy.DiyPageController/page');
    Route::get('page/:id', 'v1.diy.DiyPageController/detail');
});
