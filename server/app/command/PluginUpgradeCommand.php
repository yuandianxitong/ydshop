<?php
declare(strict_types=1);

namespace app\command;

use core\plugin\PluginException;
use core\plugin\PluginManager;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;

/**
 * Upgrade a plugin via PluginManager (database/updates/v*.sql + registry).
 *
 * Usage:
 *   php think plugin:upgrade coupon
 */
class PluginUpgradeCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('plugin:upgrade')
            ->setDescription('Upgrade a plugin to on-disk plugin.json version')
            ->addArgument('name', Argument::REQUIRED, 'Plugin directory name (e.g. coupon)');
    }

    protected function execute(Input $input, Output $output): int
    {
        $code = trim((string) $input->getArgument('name'));
        if ($code === '') {
            $output->error('Plugin name is required');
            return 1;
        }

        try {
            $output->writeln("<info>Upgrading plugin: {$code}</info>");
            PluginManager::upgrade($code);
            $output->writeln("<info>Plugin '{$code}' upgraded.</info>");
            return 0;
        } catch (PluginException $e) {
            $output->error($e->getMessage());
            return 1;
        } catch (\Throwable $e) {
            $output->error('Upgrade failed: ' . $e->getMessage());
            return 1;
        }
    }
}
