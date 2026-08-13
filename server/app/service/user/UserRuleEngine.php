<?php
declare(strict_types=1);

namespace app\service\user;

use app\repository\user\UserRepository;
use core\base\Service;

/**
 * 通用用户规则求值引擎
 *
 * rules 形态：
 * {
 *   "logic": "AND" | "OR",
 *   "conditions": [
 *     {"field": "total_consume", "op": ">=", "value": 1000, "exclude": false},
 *     {"field": "last_login_time", "op": "<", "value": "90_days_ago", "exclude": true}
 *   ]
 * }
 *
 * 业务调用方（user-group / user-tag）共享同一引擎，避免重复实现。
 * 引擎仅负责规则解析与值规整，实际查询委托给 UserRepository。
 */
class UserRuleEngine extends Service
{
    protected UserRepository $userRepository;

    /**
     * 按规则求出匹配的 user_id 列表
     *
     * @return int[] 匹配的 user_id 数组（可能为空）
     */
    public function matchUserIds(?array $rules): array
    {
        if (empty($rules) || empty($rules['conditions'])) {
            return [];
        }

        $conditions = $rules['conditions'];
        $logic      = strtoupper((string)($rules['logic'] ?? 'AND'));

        $includes = [];
        $excludes = [];
        foreach ($conditions as $c) {
            if (empty($c['field'])) continue;
            $op = (string)($c['op'] ?? '=');
            $row = [
                'field' => (string)$c['field'],
                'value' => $this->resolveValue($c['value'] ?? null),
            ];
            if (!empty($c['exclude'])) {
                $row['op'] = $this->negateOp($op);
                $excludes[] = $row;
            } else {
                $row['op'] = $op;
                $includes[] = $row;
            }
        }

        if (empty($includes) && empty($excludes)) {
            return [];
        }

        return $this->userRepository->matchIdsByRules($includes, $excludes, $logic);
    }

    /**
     * 解析时间相对值（如 30_days_ago / today / yesterday）
     */
    private function resolveValue(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }
        $map = [
            '30_days_ago'  => fn() => date('Y-m-d H:i:s', strtotime('-30 days')),
            '7_days_ago'   => fn() => date('Y-m-d H:i:s', strtotime('-7 days')),
            '14_days_ago'  => fn() => date('Y-m-d H:i:s', strtotime('-14 days')),
            '90_days_ago'  => fn() => date('Y-m-d H:i:s', strtotime('-90 days')),
            '180_days_ago' => fn() => date('Y-m-d H:i:s', strtotime('-180 days')),
            '365_days_ago' => fn() => date('Y-m-d H:i:s', strtotime('-365 days')),
            'today'        => fn() => date('Y-m-d') . ' 00:00:00',
            'yesterday'    => fn() => date('Y-m-d', strtotime('-1 day')) . ' 00:00:00',
        ];
        return isset($map[$value]) ? ($map[$value])() : $value;
    }

    /**
     * 取反操作符（用于排除条件）
     */
    private function negateOp(string $op): string
    {
        return [
            '='      => '!=',
            '!='     => '=',
            '>'      => '<=',
            '>='     => '<',
            '<'      => '>=',
            '<='     => '>',
            'in'     => 'not in',
            'not in' => 'in',
        ][$op] ?? '!=';
    }
}
