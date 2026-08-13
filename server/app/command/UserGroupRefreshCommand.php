<?php
declare(strict_types=1);

namespace app\command;

use app\repository\user\UserGroupRepository;
use app\service\user\UserGroupService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class UserGroupRefreshCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('user-group:refresh')
            ->setDescription('Refresh user groups that have auto_update=1 based on their rule definitions');
    }

    protected function execute(Input $input, Output $output): int
    {
        /** @var UserGroupRepository $repo */
        $repo = app(UserGroupRepository::class);

        /** @var UserGroupService $service */
        $service = app(UserGroupService::class);

        $groups = $repo->getAutoUpdateGroups();

        if (empty($groups)) {
            $output->writeln('No auto-update user groups found.');
            return 0;
        }

        $count = 0;
        foreach ($groups as $group) {
            try {
                $matched = $service->refreshGroup((int) $group['id']);
                $output->writeln("Refreshed group [{$group['id']}] {$group['name']}: {$matched} users matched.");
                $count++;
            } catch (\Throwable $e) {
                $output->writeln("Failed to refresh group [{$group['id']}] {$group['name']}: {$e->getMessage()}");
            }
        }

        $output->writeln("Done. Refreshed {$count} group(s).");
        return 0;
    }
}
