<?php
use think\facade\Route;

Route::group('article-category', function () {
    Route::get('list', [\plugins\article\controller\api\ArticleCategoryController::class, 'getList']);
});

Route::group('article', function () {
    Route::get('list',       [\plugins\article\controller\api\ArticleController::class, 'getList']);
    Route::get('detail/:id', [\plugins\article\controller\api\ArticleController::class, 'getDetail']);
});
