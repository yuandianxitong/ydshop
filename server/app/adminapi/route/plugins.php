<?php
use think\facade\Route;

Route::group('plugins', function () {
    Route::get('list',           'v1.plugin.PluginController/list');
    Route::get('logs',           'v1.plugin.PluginController/logs');
    Route::delete(':code',       'v1.plugin.PluginController/uninstall')->pattern(['code' => '\w+']);
    Route::post(':code/upgrade', 'v1.plugin.PluginController/upgrade')->pattern(['code' => '\w+']);
    Route::post(':code/enable',  'v1.plugin.PluginController/enable')->pattern(['code' => '\w+']);
    Route::post(':code/disable', 'v1.plugin.PluginController/disable')->pattern(['code' => '\w+']);
})->middleware(['admin_full']);

Route::group('market', function () {
    Route::get('catalog', 'v1.plugin.MarketController/catalog');
    Route::post('upload', 'v1.plugin.MarketController/upload');
})->middleware(['admin_full']);
