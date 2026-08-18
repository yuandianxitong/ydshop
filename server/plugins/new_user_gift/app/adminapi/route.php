<?php
use think\facade\Route;

Route::group('marketing/new-user-gift', function () {
    Route::get('rules',  [\plugins\new_user_gift\adminapi\controller\NewUserGiftController::class, 'getRules']);
    Route::put('rules',  [\plugins\new_user_gift\adminapi\controller\NewUserGiftController::class, 'updateRules']);
    Route::get('stats',  [\plugins\new_user_gift\adminapi\controller\NewUserGiftController::class, 'getStats']);
    Route::get('logs',   [\plugins\new_user_gift\adminapi\controller\NewUserGiftController::class, 'logs']);
    Route::get('',       [\plugins\new_user_gift\adminapi\controller\NewUserGiftController::class, 'index']);
    Route::post('',      [\plugins\new_user_gift\adminapi\controller\NewUserGiftController::class, 'store']);
    Route::put(':id',    [\plugins\new_user_gift\adminapi\controller\NewUserGiftController::class, 'update']);
    Route::delete(':id', [\plugins\new_user_gift\adminapi\controller\NewUserGiftController::class, 'delete']);
})->middleware(['admin_full']);
