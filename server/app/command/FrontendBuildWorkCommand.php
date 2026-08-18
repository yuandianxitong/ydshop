<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 在带 Node 的 sidecar / CI 上消费 frontend-builds。
 * PHP-FPM 容器不要跑此命令。
 */
class FrontendBuildWorkCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('frontend-build:work')
            ->setDescription('消费 frontend-builds 队列（须在带 Node/pnpm 的 worker 上运行）');
    }

    protected function execute(Input $input, Output $output): int
    {
        $output->writeln('<comment>请在 frontend-builder 容器或本机（已装 Node）执行：</comment>');
        $output->writeln('  php think queue:work --queue=frontend-builds --tries=1 --timeout=900 --sleep=3');
        $output->writeln('<comment>PHP-FPM 镜像没有 Node，不要在 php 容器里跑构建。</comment>');
        return 0;
    }
}
