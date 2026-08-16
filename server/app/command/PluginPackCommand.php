<?php
declare(strict_types=1);

namespace app\command;

use core\plugin\PluginException;
use core\plugin\PluginPacker;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;

/**
 * Pack plugins/<code>/ into runtime/plugin-packages/<code>-<version>.zip.
 *
 * Usage:
 *   php think plugin:pack flash_sale
 *   php think plugin:pack flash_sale --force
 *   php think plugin:pack flash_sale --output=/tmp/packs
 */
class PluginPackCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('plugin:pack')
            ->setDescription('打包本地插件到 runtime/plugin-packages/')
            ->addArgument('name', Argument::REQUIRED, 'Plugin directory name (e.g. flash_sale)')
            ->addOption('output', 'o', Option::VALUE_REQUIRED, '输出目录（默认 runtime/plugin-packages/）')
            ->addOption('force', 'f', Option::VALUE_NONE, '覆盖已存在的 zip');
    }

    protected function execute(Input $input, Output $output): int
    {
        $code = trim((string) $input->getArgument('name'));
        $outputDir = $input->getOption('output');
        $force = (bool) $input->getOption('force');

        try {
            $path = PluginPacker::pack(
                $code,
                $outputDir !== null ? (string) $outputDir : null,
                $force
            );
        } catch (PluginException $e) {
            $output->error($e->getMessage());
            return 1;
        } catch (\Throwable $e) {
            $output->error('打包失败：' . $e->getMessage());
            return 1;
        }

        $size = is_file($path) ? round(filesize($path) / 1024, 1) : 0;
        $output->writeln("<info>已打包：</info> {$path} ({$size} KB)");
        return 0;
    }
}
