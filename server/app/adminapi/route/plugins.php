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

Route::group('plugin-builds', function () {
    Route::get('list', 'v1.plugin.PluginBuildController/list');
    Route::post('rebuild', 'v1.plugin.PluginBuildController/rebuild');
})->middleware(['admin_full']);

Route::group('mobile-builds', function () {
    Route::get('list', 'v1.plugin.MobileBuildController/list');
    Route::post('create', 'v1.plugin.MobileBuildController/create');
    Route::get('channel', 'v1.plugin.MobileBuildController/channel');
    Route::post('channel', 'v1.plugin.MobileBuildController/saveChannel');
    Route::delete('channel', 'v1.plugin.MobileBuildController/clearChannel');
    Route::post(':id/upload', 'v1.plugin.MobileBuildController/upload')->pattern(['id' => '\d+']);
    Route::post(':id/cancel', 'v1.plugin.MobileBuildController/cancel')->pattern(['id' => '\d+']);
    Route::delete(':id', 'v1.plugin.MobileBuildController/delete')->pattern(['id' => '\d+']);
})->middleware(['admin_full']);

Route::group('market', function () {
    Route::get('catalog', 'v1.plugin.MarketController/catalog');
    Route::get('session', 'v1.plugin.MarketController/session');
    Route::post('connect/initiate', 'v1.plugin.MarketController/initiate');
    Route::post('connect/exchange', 'v1.plugin.MarketController/exchange');
    Route::post('disconnect', 'v1.plugin.MarketController/disconnect');
    Route::post('install', 'v1.plugin.MarketController/install');
    Route::post('upload', 'v1.plugin.MarketController/upload');
})->middleware(['admin_full']);
