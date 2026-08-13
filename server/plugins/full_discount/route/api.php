<?php
use think\facade\Route;

Route::group('marketing/full-discount', function () {
    Route::get('goods/:spuId', 'plugins\full_discount\controller\api\FullDiscountController@getRulesForGoods')->pattern(['spuId' => '\d+']);
});
