<?php
use think\facade\Route;

Route::group('finance', function () {
    Route::get('overview', 'v1.finance.FinanceController/overview');
    Route::get('trend', 'v1.finance.FinanceController/trend');
    Route::get('income-composition', 'v1.finance.FinanceController/incomeComposition');
    Route::get('withdrawal-stats', 'v1.finance.FinanceController/withdrawalStats');
    Route::get('transactions', 'v1.finance.FinanceController/transactionList');
    Route::get('transactions/export', 'v1.finance.FinanceController/transactionExport');
    Route::get('overview/export-month', 'v1.finance.FinanceController/overviewExportMonth');
    Route::get('points-rules', 'v1.finance.FinanceController/pointsRules');
})->middleware(['admin_full']);
