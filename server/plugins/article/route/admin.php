<?php
use think\facade\Route;

Route::group('article-category', function () {
    Route::get('list',         [\plugins\article\controller\admin\ArticleCategoryController::class, 'list']);
    Route::get('options',      [\plugins\article\controller\admin\ArticleCategoryController::class, 'options']);
    Route::post('',            [\plugins\article\controller\admin\ArticleCategoryController::class, 'create']);
    Route::put(':id/status',   [\plugins\article\controller\admin\ArticleCategoryController::class, 'updateStatus']);
    Route::put(':id',          [\plugins\article\controller\admin\ArticleCategoryController::class, 'update']);
    Route::delete(':id',       [\plugins\article\controller\admin\ArticleCategoryController::class, 'delete']);
})->middleware(['admin_full']);

Route::group('article', function () {
    Route::get('list',         [\plugins\article\controller\admin\ArticleController::class, 'list']);
    Route::get('detail/:id',   [\plugins\article\controller\admin\ArticleController::class, 'detail']);
    Route::post('',            [\plugins\article\controller\admin\ArticleController::class, 'create']);
    Route::put(':id/status',   [\plugins\article\controller\admin\ArticleController::class, 'updateStatus']);
    Route::put(':id',          [\plugins\article\controller\admin\ArticleController::class, 'update']);
    Route::delete(':id',       [\plugins\article\controller\admin\ArticleController::class, 'delete']);
})->middleware(['admin_full']);
