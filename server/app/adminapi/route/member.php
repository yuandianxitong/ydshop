<?php
use think\facade\Route;

Route::group('member', function () {
    Route::group('member-level', function () {
        Route::get('', 'v1.member.MemberLevelController/index');
        Route::post('', 'v1.member.MemberLevelController/store');
        Route::put(':id', 'v1.member.MemberLevelController/update');
        Route::delete(':id', 'v1.member.MemberLevelController/delete');
    });

    Route::group('user', function () {
        Route::get('', 'v1.member.MemberController/index');
        Route::get(':id/stats', 'v1.member.MemberController/stats');
        Route::get(':id/preference', 'v1.member.MemberController/preference');
        Route::get(':id/lifecycle', 'v1.member.MemberController/lifecycle');
        Route::get(':id/operation-logs', 'v1.member.MemberController/operationLogs');
        Route::get(':id/remarks', 'v1.member.MemberController/remarks');
        Route::post(':id/remarks', 'v1.member.MemberController/addRemark');
        Route::delete(':id/remarks/:rid', 'v1.member.MemberController/deleteRemark');
        Route::get(':id/coupons', 'v1.member.MemberController/coupons');
        Route::post(':id/coupons', 'v1.member.MemberController/issueCoupon');
        Route::post(':id/sms', 'v1.member.MemberController/sendSms');
        Route::get(':id', 'v1.member.MemberController/show');
        Route::put(':id', 'v1.member.MemberController/update');
        Route::post(':id/adjust-balance', 'v1.member.MemberController/adjustBalance');
        Route::post(':id/adjust-points', 'v1.member.MemberController/adjustPoints');
    });

    Route::group('user-helpers', function () {
        Route::get('issuable-coupons', 'v1.member.MemberController/issuableCoupons');
        Route::get('sms-templates', 'v1.member.MemberController/smsTemplates');
    });

    Route::group('reward-review', function () {
        Route::get('recharges', 'v1.member.MemberRewardReviewController/rechargeIndex');
        Route::post('recharges/:id/resolve', 'v1.member.MemberRewardReviewController/resolveRecharge');
        Route::get('', 'v1.member.MemberRewardReviewController/index');
        Route::post(':id/resolve', 'v1.member.MemberRewardReviewController/resolve');
    });

    Route::group('user-tag', function () {
        Route::get('all', 'v1.member.UserTagController/all');
        Route::get('', 'v1.member.UserTagController/index');
        Route::get(':id', 'v1.member.UserTagController/show');
        Route::post('', 'v1.member.UserTagController/store');
        Route::put(':id', 'v1.member.UserTagController/update');
        Route::delete(':id', 'v1.member.UserTagController/delete');
        Route::post(':id/refresh', 'v1.member.UserTagController/refresh');
        Route::post('assign', 'v1.member.UserTagController/assign');
        Route::post('remove', 'v1.member.UserTagController/remove');
    });

    Route::group('user-group', function () {
        Route::get('', 'v1.member.UserGroupController/index');
        Route::post('', 'v1.member.UserGroupController/store');
        Route::get(':id', 'v1.member.UserGroupController/show');
        Route::put(':id', 'v1.member.UserGroupController/update');
        Route::delete(':id', 'v1.member.UserGroupController/delete');
        Route::post(':id/refresh', 'v1.member.UserGroupController/refresh');
        Route::post(':id/add-users', 'v1.member.UserGroupController/addUsers');
        Route::post(':id/remove-users', 'v1.member.UserGroupController/removeUsers');
    });

    Route::group('statistics', function () {
        Route::get('overview', 'v1.member.MemberStatisticsController/overview');
        Route::get('register-trend', 'v1.member.MemberStatisticsController/registerTrend');
        Route::get('active', 'v1.member.MemberStatisticsController/activeAnalysis');
        Route::get('level-distribution', 'v1.member.MemberStatisticsController/levelDistribution');
        Route::get('consume-distribution', 'v1.member.MemberStatisticsController/consumeDistribution');
        Route::get('source-distribution', 'v1.member.MemberStatisticsController/sourceDistribution');
        Route::get('retention-matrix', 'v1.member.MemberStatisticsController/retentionMatrix');
    });

    Route::group('recharge-package', function () {
        Route::get('', 'v1.member.RechargePackageController/index');
        Route::post('', 'v1.member.RechargePackageController/store');
        Route::put(':id', 'v1.member.RechargePackageController/update');
        Route::delete(':id', 'v1.member.RechargePackageController/delete');
    });

    // 地址簿（管理后台只读 + 删除 + 会员详情 CRUD）
    Route::group('address', function () {
        Route::get('', 'v1.member.AddressBookController/index');
        Route::get('stats', 'v1.member.AddressBookController/stats');
        Route::get('export', 'v1.member.AddressBookController/export');
        Route::post('', 'v1.member.AddressBookController/store');
        Route::put(':id', 'v1.member.AddressBookController/update');
        Route::post(':id/default', 'v1.member.AddressBookController/setDefault');
        Route::delete(':id', 'v1.member.AddressBookController/delete');
    });

    // 账户资金（聚合余额变动 / 充值记录 / 提现申请）
    Route::group('fund', function () {
        Route::get('stats', 'v1.member.AccountFundController/stats');
        Route::get('export', 'v1.member.AccountFundController/export');
        Route::get('balance-logs', 'v1.member.AccountFundController/balanceLogs');
        Route::get('recharge-orders', 'v1.member.AccountFundController/rechargeOrders');
        Route::get('withdrawals', 'v1.member.AccountFundController/withdrawals');
        Route::post('withdrawal/:id/approve', 'v1.member.AccountFundController/approveWithdrawal');
        Route::post('withdrawal/:id/reject', 'v1.member.AccountFundController/rejectWithdrawal');
        Route::post('withdrawal/:id/pay', 'v1.member.AccountFundController/payWithdrawal');
    });
})->middleware(['admin_full']);
