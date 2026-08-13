<?php
use think\facade\Route;

Route::group('help-categories', function () {
    Route::get('list', 'v1.help.HelpCategoryController/list');
    Route::get('all', 'v1.help.HelpCategoryController/all');
    Route::get(':id', 'v1.help.HelpCategoryController/detail');
    Route::post('', 'v1.help.HelpCategoryController/create');
    Route::put(':id', 'v1.help.HelpCategoryController/update');
    Route::delete(':id', 'v1.help.HelpCategoryController/delete');
})->middleware(['admin_full']);

Route::group('helps', function () {
    Route::get('list', 'v1.help.HelpController/list');
    Route::get(':id', 'v1.help.HelpController/detail');
    Route::post('', 'v1.help.HelpController/create');
    Route::put(':id', 'v1.help.HelpController/update');
    Route::delete(':id', 'v1.help.HelpController/delete');
    Route::post('batch-status', 'v1.help.HelpController/batchStatus');
})->middleware(['admin_full']);
