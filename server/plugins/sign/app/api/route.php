<?php
use think\facade\Route;

Route::group('sign', function () {
    Route::post('checkin', [\plugins\sign\api\controller\SignController::class, 'checkin']);
    Route::post('makeup',  [\plugins\sign\api\controller\SignController::class, 'makeup']);
    Route::get('calendar', [\plugins\sign\api\controller\SignController::class, 'calendar']);
})->middleware(['api_auth']);

Route::get('sign/config', [\plugins\sign\api\controller\SignController::class, 'config']);
