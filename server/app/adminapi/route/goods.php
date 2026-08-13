<?php
use think\facade\Route;

Route::group('goods', function () {

    // GoodsCategory 管理
    Route::group('goods-category', function () {
        Route::get('tree', 'v1.goods.GoodsCategoryController/tree');
        Route::get('by-ids', 'v1.goods.GoodsCategoryController/byIds');
        Route::get('', 'v1.goods.GoodsCategoryController/index');
        Route::post('batch-delete', 'v1.goods.GoodsCategoryController/batchDelete');
        Route::put(':id/status', 'v1.goods.GoodsCategoryController/status');
        Route::get(':id', 'v1.goods.GoodsCategoryController/show');
        Route::post('', 'v1.goods.GoodsCategoryController/store');
        Route::put(':id', 'v1.goods.GoodsCategoryController/update');
        Route::delete(':id', 'v1.goods.GoodsCategoryController/delete');
    });

    // GoodsBrand 管理
    Route::group('goods-brand', function () {
        Route::get('', 'v1.goods.GoodsBrandController/index');
        Route::post('batch-delete', 'v1.goods.GoodsBrandController/batchDelete');
        Route::put(':id/status', 'v1.goods.GoodsBrandController/status');
        Route::get(':id', 'v1.goods.GoodsBrandController/show');
        Route::post('', 'v1.goods.GoodsBrandController/store');
        Route::put(':id', 'v1.goods.GoodsBrandController/update');
        Route::delete(':id', 'v1.goods.GoodsBrandController/delete');
    });

    // GoodsUnit 管理
    Route::group('goods-unit', function () {
        Route::get('', 'v1.goods.GoodsUnitController/index');
        Route::get('stats', 'v1.goods.GoodsUnitController/stats');
        Route::get('conversions', 'v1.goods.GoodsUnitController/conversions');
        Route::post('batch-delete', 'v1.goods.GoodsUnitController/batchDelete');
        Route::put(':id/status', 'v1.goods.GoodsUnitController/status');
        Route::get(':id', 'v1.goods.GoodsUnitController/show');
        Route::post('', 'v1.goods.GoodsUnitController/store');
        Route::put(':id', 'v1.goods.GoodsUnitController/update');
        Route::delete(':id', 'v1.goods.GoodsUnitController/delete');
    });

    // GoodsUnitGroup 管理（计量单位分组）
    Route::group('goods-unit-group', function () {
        Route::get('', 'v1.goods.GoodsUnitGroupController/index');
        Route::post('', 'v1.goods.GoodsUnitGroupController/store');
        Route::put(':id', 'v1.goods.GoodsUnitGroupController/update');
        Route::delete(':id', 'v1.goods.GoodsUnitGroupController/delete');
    });

    // GoodsAttributeGroup 管理
    Route::group('goods-attribute-group', function () {
        Route::get('', 'v1.goods.GoodsAttributeGroupController/index');
        Route::get('with-attrs', 'v1.goods.GoodsAttributeGroupController/withAttrs');
        Route::post('batch-delete', 'v1.goods.GoodsAttributeGroupController/batchDelete');
        Route::get(':id', 'v1.goods.GoodsAttributeGroupController/show');
        Route::post('', 'v1.goods.GoodsAttributeGroupController/store');
        Route::put(':id', 'v1.goods.GoodsAttributeGroupController/update');
        Route::delete(':id', 'v1.goods.GoodsAttributeGroupController/delete');
    });

    // GoodsAttribute 管理
    Route::group('goods-attribute', function () {
        Route::get('', 'v1.goods.GoodsAttributeController/index');
        Route::post('batch-delete', 'v1.goods.GoodsAttributeController/batchDelete');
        Route::get(':id', 'v1.goods.GoodsAttributeController/show');
        Route::post('', 'v1.goods.GoodsAttributeController/store');
        Route::put(':id', 'v1.goods.GoodsAttributeController/update');
        Route::delete(':id', 'v1.goods.GoodsAttributeController/delete');
    });

    // GoodsSpecTemplate 管理（商品规格模板）
    Route::group('goods-spec-template', function () {
        Route::get('', 'v1.goods.GoodsSpecTemplateController/index');
        Route::post('batch-delete', 'v1.goods.GoodsSpecTemplateController/batchDelete');
        Route::get(':id', 'v1.goods.GoodsSpecTemplateController/show');
        Route::post('', 'v1.goods.GoodsSpecTemplateController/store');
        Route::put(':id', 'v1.goods.GoodsSpecTemplateController/update');
        Route::delete(':id', 'v1.goods.GoodsSpecTemplateController/delete');
    });

    // GoodsFreightTemplate 管理
    Route::group('goods-freight-template', function () {
        Route::get('', 'v1.goods.GoodsFreightTemplateController/index');
        Route::post('batch-delete', 'v1.goods.GoodsFreightTemplateController/batchDelete');
        Route::get(':id', 'v1.goods.GoodsFreightTemplateController/show');
        Route::post('', 'v1.goods.GoodsFreightTemplateController/store');
        Route::put(':id', 'v1.goods.GoodsFreightTemplateController/update');
        Route::delete(':id', 'v1.goods.GoodsFreightTemplateController/delete');
    });

    // SPU 商品管理
    Route::group('goods-spu', function () {
        Route::get('by-ids', 'v1.goods.GoodsSpuController/byIds');
        Route::get('skus/by-ids', 'v1.goods.GoodsSpuController/skusByIds');
        Route::get('', 'v1.goods.GoodsSpuController/index');
        Route::get('import/template', 'v1.goods.GoodsSpuController/importTemplate');
        Route::post('import/preview', 'v1.goods.GoodsSpuController/importPreview');
        Route::post('import/confirm', 'v1.goods.GoodsSpuController/importConfirm');
        Route::post('batch-on-sale', 'v1.goods.GoodsSpuController/batchOnSale');
        Route::post('batch-off-sale', 'v1.goods.GoodsSpuController/batchOffSale');
        Route::post('', 'v1.goods.GoodsSpuController/store');
        Route::get(':id', 'v1.goods.GoodsSpuController/show');
        Route::put(':id/status', 'v1.goods.GoodsSpuController/status');
        Route::put(':id', 'v1.goods.GoodsSpuController/update');
        Route::delete(':id', 'v1.goods.GoodsSpuController/delete');
    });

})->middleware(['admin_full']);
