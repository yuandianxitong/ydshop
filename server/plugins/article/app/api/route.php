<?php
use think\facade\Route;

Route::group('article-category', function () {
    Route::get('list', [\plugins\article\api\controller\ArticleCategoryController::class, 'getList']);
});

Route::group('article', function () {
    Route::get('list',       [\plugins\article\api\controller\ArticleController::class, 'getList']);
    Route::get('detail/:id', [\plugins\article\api\controller\ArticleController::class, 'getDetail']);
});
