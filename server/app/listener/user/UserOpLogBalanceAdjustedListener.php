<?php
declare(strict_types=1);

namespace app\listener\user;

use app\service\user\UserOperationLogService;

class UserOpLogBalanceAdjustedListener
{
    public function handle(array $event): void
    {
        $userId = (int)($event['user_id'] ?? 0);
        if ($userId <= 0) return;

        $amount = (float)($event['amount'] ?? 0);
        $remark = (string)($event['remark'] ?? '');
        $type   = (string)($event['type_text'] ?? '');
        $sign   = $amount >= 0 ? '+' : '-';

        app(UserOperationLogService::class)->recordAsset(
            $userId,
            'balance.adjust',
            '余额变动',
            sprintf('%s%s¥ %s%s', $type ? $type . ' · ' : '', $sign, number_format(abs($amount), 2), $remark ? ' · ' . $remark : ''),
            ['amount' => $amount, 'remark' => $remark]
        );
    }
}
