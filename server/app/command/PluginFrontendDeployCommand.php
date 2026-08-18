<?php
declare(strict_types=1);

namespace app\command;

use core\plugin\PluginFrontendDeployer;
use core\plugin\PluginManager;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;

class PluginFrontendDeployCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('plugin:frontend-deploy')
            ->setDescription('软链同步插件前端到宿主并合并 pages.json（不编译）')
            ->addArgument('name', Argument::OPTIONAL, '插件 code')
            ->addOption('all', 'a', Option::VALUE_NONE, '部署全部已存在的插件目录');
    }

    protected function execute(Input $input, Output $output): int
    {
        $all  = (bool) $input->getOption('all');
        $code = trim((string) $input->getArgument('name'));
        $codes = [];
        if ($all) {
            $dir = PluginManager::pluginsPath();
            foreach (scandir($dir) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                if (is_file($dir . $entry . DIRECTORY_SEPARATOR . 'plugin.json')) {
                    $codes[] = $entry;
                }
            }
        } elseif ($code !== '') {
            $codes[] = $code;
        } else {
            $output->error('请指定插件 code 或使用 --all');
            return 1;
        }

        foreach ($codes as $item) {
            $n = PluginFrontendDeployer::deployFromPluginDir($item);
            $output->writeln("<info>[{$item}] 同步 {$n} 个软链/文件</info>");
        }
        $output->writeln('<comment>开发机重启 Vite；生产请等待 frontend-builds 队列或手动重建</comment>');
        return 0;
    }
}
