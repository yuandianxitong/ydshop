<?php
declare(strict_types=1);

namespace app\command;

use core\plugin\PluginException;
use core\plugin\PluginManager;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;

/**
 * Uninstall a plugin via PluginManager.
 *
 * Usage:
 *   php think plugin:uninstall coupon
 *   php think plugin:uninstall coupon --purge-data
 */
class PluginUninstallCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('plugin:uninstall')
            ->setDescription('Uninstall a plugin (default: keep business tables; --purge-data runs uninstall.sql)')
            ->addArgument('name', Argument::REQUIRED, 'Plugin directory name (e.g. coupon)')
            ->addOption('purge-data', null, Option::VALUE_NONE, 'Also execute database/uninstall.sql to drop plugin tables');
    }

    protected function execute(Input $input, Output $output): int
    {
        $code = trim((string) $input->getArgument('name'));
        $purge = (bool) $input->getOption('purge-data');

        if ($code === '') {
            $output->error('Plugin name is required');
            return 1;
        }

        try {
            $output->writeln('<info>Uninstalling plugin: ' . $code . ($purge ? ' (purge data)' : '') . '</info>');
            PluginManager::uninstall($code, $purge);
            $output->writeln("<info>Plugin '{$code}' uninstalled.</info>");
            return 0;
        } catch (PluginException $e) {
            $output->error($e->getMessage());
            return 1;
        } catch (\Throwable $e) {
            $output->error('Uninstall failed: ' . $e->getMessage());
            return 1;
        }
    }
}
