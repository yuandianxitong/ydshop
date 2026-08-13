<?php
declare(strict_types=1);

namespace app\command;

use app\model\plugin\Plugin;
use core\plugin\PluginException;
use core\plugin\PluginManager;
use core\plugin\PluginManifest;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;

/**
 * Install a plugin via PluginManager (database/install.sql + registry).
 *
 * Usage:
 *   php think plugin:install coupon
 */
class PluginInstallCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('plugin:install')
            ->setDescription('Install a plugin (SqlRunner install.sql + PluginManager registry)')
            ->addArgument('name', Argument::REQUIRED, 'Plugin directory name (e.g. coupon)');
    }

    protected function execute(Input $input, Output $output): int
    {
        $code = trim((string) $input->getArgument('name'));
        $pluginDir = PluginManager::pluginsPath() . $code;
        if (!is_dir($pluginDir)) {
            $output->error("Plugin directory not found: {$pluginDir}");
            return 1;
        }

        $manifestPath = $pluginDir . DIRECTORY_SEPARATOR . 'plugin.json';
        if (!is_file($manifestPath)) {
            $output->error("plugin.json not found: {$manifestPath}");
            return 1;
        }

        try {
            $manifest = PluginManifest::fromFile($manifestPath);
            $output->writeln("<info>Installing plugin: {$manifest->code} v{$manifest->version}</info>");
            PluginManager::install($manifest, Plugin::SOURCE_BUNDLED);
            $output->writeln("<info>Plugin '{$manifest->code}' installed.</info>");
            return 0;
        } catch (PluginException $e) {
            $output->error($e->getMessage());
            return 1;
        } catch (\Throwable $e) {
            $output->error('Install failed: ' . $e->getMessage());
            return 1;
        }
    }
}
