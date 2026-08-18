<?php
declare(strict_types=1);

namespace app\job;

use app\service\plugin\MobileBuildService;
use think\queue\Job;

class MobileBuildJob
{
    public function fire(Job $job, array $data): void
    {
        $service = app(MobileBuildService::class);
        $service->run((int) ($data['build_id'] ?? 0));
        $job->delete();
    }
}
