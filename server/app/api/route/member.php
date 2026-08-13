<?php
use think\facade\Route;

Route::group('address', function () {
    Route::get('', 'v1.member.AddressController/index');
    Route::get('default', 'v1.member.AddressController/getDefault');
    Route::post('', 'v1.member.AddressController/store');
    Route::put(':id', 'v1.member.AddressController/update');
    Route::delete(':id', 'v1.member.AddressController/delete');
})->middleware(['api_auth']);

Route::group('cart', function () {
    Route::get('', 'v1.member.CartController/index');
    Route::post('add', 'v1.member.CartController/add');
    Route::put(':id', 'v1.member.CartController/update');
    Route::delete(':id', 'v1.member.CartController/remove');
    Route::post(':id/toggle-select', 'v1.member.CartController/toggleSelect');
    Route::post('select-all', 'v1.member.CartController/selectAll');
    Route::get('selected', 'v1.member.CartController/selectedItems');
})->middleware(['api_auth']);

Route::group('favorite', function () {
    Route::get('', 'v1.member.FavoriteController/index');
    Route::post('toggle', 'v1.member.FavoriteController/toggle');
    Route::get('check/:spu_id', 'v1.member.FavoriteController/check');
})->middleware(['api_auth']);

Route::group('browse-history', function () {
    Route::get('', 'v1.member.BrowseHistoryController/index');
    Route::post('record', 'v1.member.BrowseHistoryController/record');
    Route::delete(':id', 'v1.member.BrowseHistoryController/remove');
    Route::delete('', 'v1.member.BrowseHistoryController/clear');
})->middleware(['api_auth']);

Route::get('recharge/packages', 'v1.member.RechargeController/packages');

Route::group('recharge', function () {
    Route::post('create', 'v1.member.RechargeController/create');
})->middleware(['api_auth']);

Route::group('member', function () {
    Route::get('profile', 'v1.member.MemberController/profile');
    Route::get('levels', 'v1.member.MemberController/levels');
    Route::get('points-log', 'v1.member.MemberController/pointsLog');
    Route::get('balance-log', 'v1.member.MemberController/balanceLog');
})->middleware(['api_auth']);
