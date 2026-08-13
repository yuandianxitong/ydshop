<?php
use think\facade\Route;

Route::group('delivery', function () {

    // 电子面单模版 / 目录
    Route::group('waybill', function () {
        Route::get('catalog', 'v1.delivery.WaybillTemplateController/catalog');
        Route::get('templates/options', 'v1.delivery.WaybillTemplateController/options');
        Route::get('templates', 'v1.delivery.WaybillTemplateController/index');
        Route::post('templates', 'v1.delivery.WaybillTemplateController/store');
        Route::get('templates/:id', 'v1.delivery.WaybillTemplateController/show');
        Route::put('templates/:id', 'v1.delivery.WaybillTemplateController/update');
        Route::put('templates/:id/status', 'v1.delivery.WaybillTemplateController/status');
        Route::delete('templates/:id', 'v1.delivery.WaybillTemplateController/delete');
    });

    // 物流公司管理
    Route::group('express-company', function () {
        Route::get('', 'v1.delivery.ExpressCompanyController/index');
        Route::get('options', 'v1.delivery.ExpressCompanyController/options');
        Route::post('batch-delete', 'v1.delivery.ExpressCompanyController/batchDelete');
        Route::put(':id/status', 'v1.delivery.ExpressCompanyController/status');
        Route::get(':id', 'v1.delivery.ExpressCompanyController/show');
        Route::post('', 'v1.delivery.ExpressCompanyController/store');
        Route::put(':id', 'v1.delivery.ExpressCompanyController/update');
        Route::delete(':id', 'v1.delivery.ExpressCompanyController/delete');
    });

    // 配送员管理
    Route::group('staff', function () {
        Route::get('', 'v1.delivery.DeliveryStaffController/index');
        Route::get('options', 'v1.delivery.DeliveryStaffController/options');
        Route::get('export', 'v1.delivery.DeliveryStaffController/export');
        Route::post('batch-delete', 'v1.delivery.DeliveryStaffController/batchDelete');
        Route::put(':id/status', 'v1.delivery.DeliveryStaffController/status');
        Route::get(':id', 'v1.delivery.DeliveryStaffController/show');
        Route::post('', 'v1.delivery.DeliveryStaffController/store');
        Route::put(':id', 'v1.delivery.DeliveryStaffController/update');
        Route::delete(':id', 'v1.delivery.DeliveryStaffController/delete');
    });

    // 配送记录
    Route::group('order', function () {
        Route::get('', 'v1.delivery.DeliveryOrderController/index');
        Route::get('export', 'v1.delivery.DeliveryOrderController/export');
        // 静态路径必须放在 :id 通配之前
        Route::get('platform-options', 'v1.delivery.DeliveryOrderController/platformOptions');
        Route::post('auto-dispatch', 'v1.delivery.DeliveryOrderController/autoDispatch');
        Route::post(':id/assign', 'v1.delivery.DeliveryOrderController/assign');
        Route::post(':id/status', 'v1.delivery.DeliveryOrderController/updateStatus');
        Route::post(':id/dispatch', 'v1.delivery.DeliveryOrderController/dispatch');
        Route::post(':id/sync', 'v1.delivery.DeliveryOrderController/sync');
        Route::get(':id/tracks', 'v1.delivery.DeliveryOrderController/tracks');
        Route::get(':id', 'v1.delivery.DeliveryOrderController/show');
    });

    // 异常工单
    Route::group('exception-tickets', function () {
        Route::get('', 'v1.delivery.DeliveryExceptionTicketController/index');
        Route::put(':id/transition', 'v1.delivery.DeliveryExceptionTicketController/transition');
        Route::get(':id', 'v1.delivery.DeliveryExceptionTicketController/show');
        Route::post('', 'v1.delivery.DeliveryExceptionTicketController/store');
        Route::put(':id', 'v1.delivery.DeliveryExceptionTicketController/update');
        Route::delete(':id', 'v1.delivery.DeliveryExceptionTicketController/destroy');
    });

    // 实时地图
    Route::group('map', function () {
        Route::get('config', 'v1.delivery.DeliveryMapController/config');
        Route::get('orders', 'v1.delivery.DeliveryMapController/orders');
    });

    // 排班管理
    Route::group('shifts', function () {
        Route::get('', 'v1.delivery.DeliveryShiftController/index');
        Route::get(':id', 'v1.delivery.DeliveryShiftController/show');
        Route::post('', 'v1.delivery.DeliveryShiftController/store');
        Route::put(':id', 'v1.delivery.DeliveryShiftController/update');
        Route::delete(':id', 'v1.delivery.DeliveryShiftController/destroy');
    });

})->middleware(['admin_full']);
