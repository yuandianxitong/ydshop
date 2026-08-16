<?php
declare(strict_types=1);

namespace app\command;

use core\license\LicenseGuard;
use core\plugin\PluginException;
use core\plugin\PluginPacker;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;

/**
 * Pack plugins/<code>/ (+ mapped admin/pc/uniapp) into runtime/plugin-packages/.
 *
 * Usage:
 *   php think plugin:pack flash_sale
 *   php think plugin:pack flash_sale --force
 *   php think plugin:pack --all --force
 *   php think plugin:pack flash_sale --output=/tmp/packs
 */
class PluginPackCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('plugin:pack')
            ->setDescription('打包本地插件（含前端）到 runtime/plugin-packages/')
            ->addArgument('name', Argument::OPTIONAL, 'Plugin directory name (e.g. flash_sale)')
            ->addOption('all', 'a', Option::VALUE_NONE, '打包全部付费组件')
            ->addOption('output', 'o', Option::VALUE_REQUIRED, '输出目录（默认 runtime/plugin-packages/）')
            ->addOption('force', 'f', Option::VALUE_NONE, '覆盖已存在的 zip');
    }

    protected function execute(Input $input, Output $output): int
    {
        $all = (bool) $input->getOption('all');
        $code = trim((string) $input->getArgument('name'));
        $outputDir = $input->getOption('output');
        $force = (bool) $input->getOption('force');

        $codes = $all ? LicenseGuard::proPluginCodes() : ($code !== '' ? [$code] : []);
        if ($codes === []) {
            $output->error('请指定插件 code，或使用 --all 打包全部付费组件');
            return 1;
        }

        $failed = 0;
        foreach ($codes as $item) {
            try {
                $result = PluginPacker::pack(
                    $item,
                    $outputDir !== null ? (string) $outputDir : null,
                    $force
                );
            } catch (PluginException $e) {
                $output->error($item . '：' . $e->getMessage());
                $failed++;
                continue;
            } catch (\Throwable $e) {
                $output->error($item . ' 打包失败：' . $e->getMessage());
                $failed++;
                continue;
            }

            $path = $result['path'];
            $front = (int) $result['frontend_files'];
            $size = is_file($path) ? round(filesize($path) / 1024, 1) : 0;
            $output->writeln("<info>已打包：</info> {$path} ({$size} KB，前端 {$front} 个文件)");
        }

        return $failed > 0 ? 1 : 0;
    }
}
