<?php
declare(strict_types=1);

namespace app\service\finance;

use app\model\system\SystemConfig;
use app\service\member\MemberLevelService;
use core\base\Service;
use core\plugin\HookManager;

class PointsRulesService extends Service
{
    protected MemberLevelService $memberLevelService;

    /**
     * 聚合 3 类积分规则当前值
     *
     * 新人礼包已表驱动：不再读废弃的 new_user_gift.* system_configs；
     * 插件未装/停用时 register_gift 降级为 disabled。
     */
    public function getOverview(): array
    {
        $sign = SystemConfig::getConfigsByGroup('sign');

        $levels = array_map(fn($l) => [
            'id'          => (int) $l['id'],
            'name'        => $l['name'] ?? '',
            'points_rate' => (float) ($l['points_rate'] ?? 0),
        ], $this->memberLevelService->getAll());

        return [
            'register_gift' => $this->resolveRegisterGiftOverview(),
            'sign_in' => [
                'base'                    => (int) ($sign['sign.points_base'] ?? 0),
                'increment'               => (int) ($sign['sign.points_increment'] ?? 0),
                'max'                     => (int) ($sign['sign.points_max'] ?? 0),
                'continuous_bonus_days'   => (int) ($sign['sign.continuous_bonus_days'] ?? 0),
                'continuous_bonus_points' => (int) ($sign['sign.continuous_bonus_points'] ?? 0),
                'config_url'              => '/marketing/sign-config',
            ],
            'member_levels' => [
                'levels'     => $levels,
                'config_url' => '/member/member-level',
            ],
        ];
    }

    /**
     * @return array{enabled: bool, points: int, config_url: string}
     */
    private function resolveRegisterGiftOverview(): array
    {
        $fallback = [
            'enabled'    => false,
            'points'     => 0,
            'config_url' => '/marketing/new-user-gift',
        ];

        return HookManager::apply('finance.register_gift_overview', [], $fallback);
    }
}
