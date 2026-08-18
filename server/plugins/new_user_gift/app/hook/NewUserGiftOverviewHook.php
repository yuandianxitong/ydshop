<?php
declare(strict_types=1);

namespace plugins\new_user_gift\hook;

use plugins\new_user_gift\service\NewUserGiftService;

class NewUserGiftOverviewHook
{
    public string $hook = 'finance.register_gift_overview';
    public int $priority = 10;

    public function handle(array $context, mixed $prev): array
    {
        $fallback = is_array($prev) ? $prev : [
            'enabled'    => false,
            'points'     => 0,
            'config_url' => '/marketing/new-user-gift',
        ];
        try {
            $gifts  = app(NewUserGiftService::class)->getActiveGifts();
            $points = 0;
            foreach ($gifts as $gift) {
                $points += (int) ($gift['points'] ?? 0);
            }
            return [
                'enabled'    => $gifts !== [],
                'points'     => $points,
                'config_url' => '/marketing/new-user-gift',
            ];
        } catch (\Throwable) {
            return $fallback;
        }
    }
}
