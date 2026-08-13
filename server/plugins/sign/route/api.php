<?php
use think\facade\Route;

Route::group('sign', function () {
    Route::post('checkin', [\plugins\sign\controller\api\SignController::class, 'checkin']);
    Route::post('makeup',  [\plugins\sign\controller\api\SignController::class, 'makeup']);
    Route::get('calendar', [\plugins\sign\controller\api\SignController::class, 'calendar']);
})->middleware(['api_auth']);

Route::get('sign/config', [\plugins\sign\controller\api\SignController::class, 'config']);
