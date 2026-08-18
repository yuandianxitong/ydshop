<?php
use think\facade\Route;

Route::group('agreement', function () {
    Route::get('list',        [\plugins\content_mgmt\adminapi\controller\AgreementController::class, 'list']);
    Route::get('detail/:id',  [\plugins\content_mgmt\adminapi\controller\AgreementController::class, 'detail']);
    Route::post('',           [\plugins\content_mgmt\adminapi\controller\AgreementController::class, 'create']);
    Route::put(':id',         [\plugins\content_mgmt\adminapi\controller\AgreementController::class, 'update']);
    Route::delete(':id',      [\plugins\content_mgmt\adminapi\controller\AgreementController::class, 'delete']);
})->middleware(['admin_full']);
