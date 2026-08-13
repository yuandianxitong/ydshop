<?php
declare(strict_types=1);

namespace app\command;

use app\service\user\UserTagService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 自动打标签：将所有 auto_update=1 的标签按规则重算覆盖用户
 *
 * crontab 示例（每 30 分钟执行一次）：
 *   *\/30 * * * * cd /path/to/server && php think user-tag:refresh >> /tmp/user-tag-refresh.log 2>&1
 */
class UserTagRefreshCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('user-tag:refresh')
            ->setDescription('Refresh all user tags with auto_update=1 by re-evaluating rules');
    }

    protected function execute(Input $input, Output $output): int
    {
        /** @var UserTagService $service */
        $service = app(UserTagService::class);

        $result = $service->refreshAutoTags();
        $success = (int)($result['success'] ?? 0);
        $failed  = $result['failed'] ?? [];

        $output->writeln(sprintf('[user-tag:refresh] success=%d, failed=%d', $success, count($failed)));
        foreach ($failed as $f) {
            $output->writeln(sprintf('  - tag #%d "%s": %s', (int)$f['id'], (string)$f['name'], (string)$f['reason']));
        }
        return 0;
    }
}
