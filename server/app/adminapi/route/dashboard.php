<?php
/* ============================================================
 * 项目：元点Shop
 * 官网：https://www.dev007.cn
 * Slogan：提供高质量行业系统源码，帮助中小企业快速搭建专属应用
 * Author：mashanglai Team
 * ============================================================ */
use think\facade\Route;

// 仪表板相关路由
Route::group('dashboard', function () {
    Route::get('stats', 'v1.dashboard.DashboardController/stats');
    Route::get('recent-logs', 'v1.dashboard.DashboardController/recentLogs');
    Route::get('recent-activities', 'v1.dashboard.DashboardController/recentActivities');
    Route::get('active-ranking', 'v1.dashboard.DashboardController/activeRanking');

    // 电商仪表板
    Route::get('ecommerce-stats', 'v1.dashboard.DashboardController/ecommerceStats');
    Route::get('sales-trend', 'v1.dashboard.DashboardController/salesTrend');
    Route::get('order-status', 'v1.dashboard.DashboardController/orderStatus');
    Route::get('hot-products', 'v1.dashboard.DashboardController/hotProducts');
    Route::get('pending-tasks', 'v1.dashboard.DashboardController/pendingTasks');
    Route::get('realtime-kpi', 'v1.dashboard.DashboardController/realtimeKpi');
    Route::get('payment-mix', 'v1.dashboard.DashboardController/paymentMix');
    Route::get('recent-orders', 'v1.dashboard.DashboardController/recentOrders');
})->middleware(['admin_full']);
