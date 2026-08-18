<?php
use think\facade\Route;

Route::group('diy', function () {
    Route::get('catalog', 'v1.diy.DiyPageController/catalog');
    Route::group('page', function () {
        Route::get('', 'v1.diy.DiyPageController/index');
        Route::post('', 'v1.diy.DiyPageController/store');
        Route::post('preview-data', 'v1.diy.DiyPageController/previewData');
        Route::put(':id/publish', 'v1.diy.DiyPageController/publish');
        Route::put(':id/set-default', 'v1.diy.DiyPageController/setDefault');
        Route::get(':id/versions', 'v1.diy.DiyPageController/listVersions');
        Route::get(':id/versions/:vid', 'v1.diy.DiyPageController/showVersion');
        Route::post(':id/versions/:vid/restore', 'v1.diy.DiyPageController/restoreVersion');
        Route::get(':id', 'v1.diy.DiyPageController/show');
        Route::put(':id', 'v1.diy.DiyPageController/update');
        Route::delete(':id', 'v1.diy.DiyPageController/delete');
    });
    Route::group('template', function () {
        Route::get('', 'v1.diy.DiyTemplateController/index');
        Route::get(':id', 'v1.diy.DiyTemplateController/show');
        Route::post('', 'v1.diy.DiyTemplateController/store');
        Route::put(':id', 'v1.diy.DiyTemplateController/update');
        Route::delete(':id', 'v1.diy.DiyTemplateController/delete');
    });
})->middleware(['admin_full']);
