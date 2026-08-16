<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
$commands = [
    \app\command\YdUpdateCommand::class,
    \app\command\MakeCrudCommand::class,
    \app\command\GenerateApiDocCommand::class,
    'log:archive' => \app\command\LogArchiveCommand::class,
    \app\command\OrderAutoCancelCommand::class,
    \app\command\OrderAutoConfirmCommand::class,
    \app\command\OrderAutoReviewCommand::class,
    \app\command\OrderMemberRewardReconcileCommand::class,
    \app\command\PluginInstallCommand::class,
    \app\command\PluginUninstallCommand::class,
    \app\command\PluginUpgradeCommand::class,
    \app\command\PluginPackCommand::class,
    \app\command\PluginEnrollBundled::class,
    \app\command\UserGroupRefreshCommand::class,
    \app\command\UserTagRefreshCommand::class,
    \app\command\PickupScanTimeoutCommand::class,
    \app\command\PaymentResync::class,
    \app\command\PaymentReconcileCommand::class,
    \app\command\RefundReconcileCommand::class,
    \app\command\FinanceReconcileCommand::class,
    \app\command\ScheduleRunCommand::class,
    \app\command\ScheduleWorkCommand::class,
    \app\command\LicenseHeartbeatCommand::class,
];

$optionalCommands = [
    dirname(__DIR__) . '/plugins/distribution/command/DistributionSettleCommand.php'
        => \plugins\distribution\command\DistributionSettleCommand::class,
    dirname(__DIR__) . '/plugins/distribution/command/DistributionCommissionReconcileCommand.php'
        => \plugins\distribution\command\DistributionCommissionReconcileCommand::class,
    dirname(__DIR__) . '/plugins/group_buy/command/GroupBuyExpireCommand.php'
        => \plugins\group_buy\command\GroupBuyExpireCommand::class,
];
foreach ($optionalCommands as $file => $class) {
    if (is_file($file)) {
        $commands[] = $class;
    }
}

return [
    'commands' => $commands,
];
