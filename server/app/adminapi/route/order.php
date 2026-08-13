<?php
use think\facade\Route;

Route::group('order', function () {
    Route::group('order', function () {
        Route::get('', 'v1.order.OrderController/index');
        // 静态路径与带后缀的 :id/* 必须注册在裸 ':id' 之前，避免被通配吞掉
        Route::post('merge', 'v1.order.OrderAdjustController/merge');
        Route::get(':id/adjust-logs', 'v1.order.OrderAdjustController/adjustLogs');
        Route::post(':id/cancel', 'v1.order.OrderController/cancel');
        Route::post(':id/remark', 'v1.order.OrderController/remark');
        Route::put(':id/address', 'v1.order.OrderController/updateAddress');
        Route::post(':id/split', 'v1.order.OrderAdjustController/split');
        Route::post(':id/price-adjust', 'v1.order.OrderAdjustController/priceAdjust');
        Route::delete(':id', 'v1.order.OrderController/delete');
        Route::get(':id', 'v1.order.OrderController/show');
    });

    Route::group('ship', function () {
        Route::post('', 'v1.order.OrderShipController/ship');
        Route::put(':id/logistics', 'v1.order.OrderShipController/updateLogistics');
        Route::get(':id/tracking', 'v1.order.OrderShipController/tracking');
    });

    Route::group('waybill', function () {
        Route::post('batch-generate', 'v1.order.OrderShipController/batchGenerateWaybill');
    });

    Route::group('refund', function () {
        Route::get('', 'v1.order.OrderRefundController/index');
        Route::get(':id', 'v1.order.OrderRefundController/show');
        Route::post(':id/approve', 'v1.order.OrderRefundController/approve');
        Route::post(':id/reject', 'v1.order.OrderRefundController/reject');
        Route::post(':id/confirm-received', 'v1.order.OrderRefundController/confirmReceived');
        Route::post(':id/retry', 'v1.order.OrderRefundController/retry');
    });

    Route::group('review', function () {
        Route::get('', 'v1.order.OrderReviewController/index');
        Route::post(':id/reply', 'v1.order.OrderReviewController/reply');
    });

    Route::group('settings', function () {
        Route::get('', 'v1.order.OrderSettingsController/getSettings');
        Route::put('', 'v1.order.OrderSettingsController/updateSettings');
    });

    Route::group('refund-reason', function () {
        Route::get('', 'v1.order.OrderRefundReasonController/index');
        Route::post('', 'v1.order.OrderRefundReasonController/store');
        Route::put(':id', 'v1.order.OrderRefundReasonController/update');
        Route::delete(':id', 'v1.order.OrderRefundReasonController/delete');
    });

    Route::group('invoice', function () {
        Route::get('', 'v1.order.OrderInvoiceController/index');
        Route::get('stats', 'v1.order.OrderInvoiceController/stats');
        Route::get(':id', 'v1.order.OrderInvoiceController/show');
        Route::post(':id/process', 'v1.order.OrderInvoiceController/process');
        Route::post(':id/issue', 'v1.order.OrderInvoiceController/issue');
        Route::post(':id/cancel', 'v1.order.OrderInvoiceController/cancel');
        Route::put(':id', 'v1.order.OrderInvoiceController/update');
        Route::delete(':id', 'v1.order.OrderInvoiceController/delete');
    });
})->middleware(['admin_full']);
