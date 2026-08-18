<?php
declare(strict_types=1);

namespace app\job;

use app\service\plugin\PluginBuildService;
use think\queue\Job;

class PluginBuildJob
{
    public function fire(Job $job, array $data): void
    {
        $service = app(PluginBuildService::class);
        $service->run((int) ($data['build_id'] ?? 0));
        $job->delete();
    }
}
