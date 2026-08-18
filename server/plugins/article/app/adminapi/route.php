<?php
use think\facade\Route;

Route::group('article-category', function () {
    Route::get('list',         [\plugins\article\adminapi\controller\ArticleCategoryController::class, 'list']);
    Route::get('options',      [\plugins\article\adminapi\controller\ArticleCategoryController::class, 'options']);
    Route::post('',            [\plugins\article\adminapi\controller\ArticleCategoryController::class, 'create']);
    Route::put(':id/status',   [\plugins\article\adminapi\controller\ArticleCategoryController::class, 'updateStatus']);
    Route::put(':id',          [\plugins\article\adminapi\controller\ArticleCategoryController::class, 'update']);
    Route::delete(':id',       [\plugins\article\adminapi\controller\ArticleCategoryController::class, 'delete']);
})->middleware(['admin_full']);

Route::group('article', function () {
    Route::get('list',         [\plugins\article\adminapi\controller\ArticleController::class, 'list']);
    Route::get('detail/:id',   [\plugins\article\adminapi\controller\ArticleController::class, 'detail']);
    Route::post('',            [\plugins\article\adminapi\controller\ArticleController::class, 'create']);
    Route::put(':id/status',   [\plugins\article\adminapi\controller\ArticleController::class, 'updateStatus']);
    Route::put(':id',          [\plugins\article\adminapi\controller\ArticleController::class, 'update']);
    Route::delete(':id',       [\plugins\article\adminapi\controller\ArticleController::class, 'delete']);
})->middleware(['admin_full']);
