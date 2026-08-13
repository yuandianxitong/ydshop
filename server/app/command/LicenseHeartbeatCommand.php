<?php
declare(strict_types=1);

namespace app\command;

use core\license\LicenseClient;
use core\license\LicenseState;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 产品授权心跳：php think license:heartbeat
 */
class LicenseHeartbeatCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('license:heartbeat')
            ->setDescription('向官网授权中心发送产品授权心跳');
    }

    protected function execute(Input $input, Output $output): int
    {
        $state = LicenseState::load();
        $licenseKey = (string) ($state['license_key'] ?? '');
        if ($licenseKey === '') {
            $output->writeln('<comment>未配置授权码，跳过</comment>');
            return 0;
        }

        try {
            $client = new LicenseClient();
            $data = $client->heartbeat($licenseKey, (string) ($state['domain'] ?? ''));
            $status = (string) (($data['license']['status'] ?? 'unknown'));
            $output->writeln('<info>心跳成功，状态: ' . $status . '</info>');
            return 0;
        } catch (\Throwable $e) {
            $output->writeln('<error>心跳失败: ' . $e->getMessage() . '</error>');
            return 1;
        }
    }
}
