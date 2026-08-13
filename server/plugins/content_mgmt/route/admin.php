<?php
use think\facade\Route;

Route::group('agreement', function () {
    Route::get('list',        [\plugins\content_mgmt\controller\admin\AgreementController::class, 'list']);
    Route::get('detail/:id',  [\plugins\content_mgmt\controller\admin\AgreementController::class, 'detail']);
    Route::post('',           [\plugins\content_mgmt\controller\admin\AgreementController::class, 'create']);
    Route::put(':id',         [\plugins\content_mgmt\controller\admin\AgreementController::class, 'update']);
    Route::delete(':id',      [\plugins\content_mgmt\controller\admin\AgreementController::class, 'delete']);
})->middleware(['admin_full']);
