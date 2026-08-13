<?php
use think\facade\Route;

// 订单（需要登录）
Route::group('order', function () {
    Route::post('',          'v1.order.OrderController/create');
    Route::get('',           'v1.order.OrderController/index');
    // 字面量路径必须在通配符 :id 之前，避免被 OrderController/show 抢占
    Route::post('calc',         'v1.order.OrderController/calc');
    Route::get('counts',        'v1.order.OrderController/counts');
    // 发票申请
    Route::post('invoice',     'v1.order.OrderInvoiceController/submit');
    Route::get('invoice',      'v1.order.OrderInvoiceController/index');
    Route::get('invoice/:id',  'v1.order.OrderInvoiceController/show');
    // 通配符路由放最后；:id/tracking 段数更多，先注册避免歧义
    Route::get(':id/tracking', 'v1.order.OrderController/tracking');
    Route::get(':id',        'v1.order.OrderController/show');
    Route::post(':id/cancel',  'v1.order.OrderController/cancel');
    Route::post(':id/confirm', 'v1.order.OrderController/confirmReceive');
})->middleware(['api_auth']);

// 退款（需要登录）
Route::group('order-refund', function () {
    Route::get('',                    'v1.order.OrderRefundController/index');
    Route::post('apply',              'v1.order.OrderRefundController/apply');
    // :id 限定数字，避免拦截组外注册的公开路由 order-refund/reasons
    Route::get(':id',                 'v1.order.OrderRefundController/show')->pattern(['id' => '\d+']);
    Route::post(':id/logistics',      'v1.order.OrderRefundController/submitLogistics');
})->middleware(['api_auth']);

// 评价（提交需登录，查看公开）
Route::group('order-review', function () {
    Route::post('', 'v1.order.OrderReviewController/create')->middleware(['api_auth']);
    Route::get('spu/:spu_id', 'v1.order.OrderReviewController/listBySpu');
});

// 退款原因（公开，无需登录）
Route::get('order-refund/reasons', 'v1.order.OrderRefundReasonController/list');
