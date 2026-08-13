<?php
declare(strict_types=1);

namespace app\listener\user;

use app\service\user\UserOperationLogService;

class UserOpLogPointsAdjustedListener
{
    public function handle(array $event): void
    {
        $userId = (int)($event['user_id'] ?? 0);
        if ($userId <= 0) return;

        $points = (int)($event['points'] ?? 0);
        $remark = (string)($event['remark'] ?? '');
        $type   = (string)($event['type_text'] ?? '');
        $sign   = $points >= 0 ? '+' : '';

        app(UserOperationLogService::class)->recordAsset(
            $userId,
            'points.adjust',
            '积分变动',
            sprintf('%s%s%s 积分%s', $type ? $type . ' · ' : '', $sign, number_format($points), $remark ? ' · ' . $remark : ''),
            ['points' => $points, 'remark' => $remark]
        );
    }
}
