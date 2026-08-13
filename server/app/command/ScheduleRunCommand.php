<?php
declare(strict_types=1);

namespace app\command;

use app\repository\system\CronJobRepository;
use app\service\system\CronJobService;
use app\support\CronExpressionMatcher;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Cache;
use think\facade\Log;

class ScheduleRunCommand extends Command
{
    private const LAST_RUN_CACHE_KEY = 'schedule:last-run-minute';

    protected function configure(): void
    {
        $this->setName('schedule:run-due')
            ->setDescription('Poll enabled cron jobs and run those due at the current minute');
    }

    protected function execute(Input $input, Output $output): int
    {
        $timestamp = time();
        $currentMinute = date('Y-m-d H:i', $timestamp);

        // 防同分钟重复：同一分钟内被多次触发（如进程重启、并行实例）时直接跳过整轮
        if ((string) Cache::get(self::LAST_RUN_CACHE_KEY) === $currentMinute) {
            $output->writeln(sprintf('Schedule skipped: minute %s already dispatched.', $currentMinute));
            return 0;
        }
        Cache::set(self::LAST_RUN_CACHE_KEY, $currentMinute, 120);

        $jobs = $this->resolveCronJobRepository()->getEnabledJobs();
        $cronJobService = $this->resolveCronJobService();

        $due = 0;
        $succeeded = 0;
        $failed = 0;

        foreach ($jobs as $job) {
            if (!CronExpressionMatcher::isDue((string) ($job['expression'] ?? ''), $timestamp)) {
                continue;
            }
            $due++;

            try {
                $result = $cronJobService->runCronJob((int) $job['id']);
                if ((int) ($result['status'] ?? 0) === 1) {
                    $succeeded++;
                    $output->writeln(sprintf(
                        'Job #%d [%s] succeeded in %dms.',
                        (int) $job['id'],
                        (string) ($job['command'] ?? ''),
                        (int) ($result['duration'] ?? 0)
                    ));
                } else {
                    $failed++;
                    $output->writeln(sprintf(
                        'Job #%d [%s] failed: %s',
                        (int) $job['id'],
                        (string) ($job['command'] ?? ''),
                        (string) ($result['error'] ?? 'unknown error')
                    ));
                }
            } catch (\Throwable $e) {
                // 单条任务异常不阻断其余任务
                $failed++;
                Log::error(sprintf(
                    'Schedule run job #%d [%s] threw: %s',
                    (int) ($job['id'] ?? 0),
                    (string) ($job['command'] ?? ''),
                    $e->getMessage()
                ));
                $output->writeln(sprintf(
                    'Job #%d [%s] threw: %s',
                    (int) ($job['id'] ?? 0),
                    (string) ($job['command'] ?? ''),
                    $e->getMessage()
                ));
            }
        }

        $output->writeln(sprintf(
            'Schedule run at %s: %d enabled, %d due, %d succeeded, %d failed.',
            $currentMinute,
            count($jobs),
            $due,
            $succeeded,
            $failed
        ));
        return $failed === 0 ? 0 : 1;
    }

    protected function resolveCronJobRepository(): CronJobRepository
    {
        return app()->make(CronJobRepository::class);
    }

    protected function resolveCronJobService(): CronJobService
    {
        return app()->make(CronJobService::class);
    }
}
