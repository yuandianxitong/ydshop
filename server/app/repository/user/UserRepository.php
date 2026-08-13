<?php

declare(strict_types=1);

namespace app\repository\user;

use app\model\user\User;
use core\base\Repository;
use think\Model;
use think\facade\Db;

class UserRepository extends Repository
{
    protected function getModel(): Model
    {
        return new User();
    }

    /**
     * 根据 ID 数组批量查询用户
     */
    public function findByIds(array $ids, string $fields = '*'): array
    {
        if (empty($ids)) {
            return [];
        }
        return User::whereIn('id', $ids)->field($fields)->select()->toArray();
    }

    /**
     * 根据手机号查找用户
     */
    public function findByMobile(string $mobile): ?Model
    {
        return User::findByMobile($mobile);
    }

    /**
     * 根据账号查找用户（手机号或用户名，统一查 mobile 字段）
     */
    public function findByAccount(string $account): ?Model
    {
        return User::findByMobile($account);
    }

    /**
     * 根据公众号/开放平台 openid 查找用户
     */
    public function findByOpenid(string $openid): ?Model
    {
        return User::findByOpenid($openid);
    }

    /**
     * 根据小程序 openid 查找用户
     */
    public function findByMiniOpenid(string $openid): ?Model
    {
        return User::findByMiniOpenid($openid);
    }

    /**
     * 根据 unionid 查找用户
     */
    public function findByUnionid(string $unionid): ?Model
    {
        return $this->model->where('unionid', $unionid)->find();
    }

    /**
     * 获取用户模型实例（用于更新操作）
     */
    public function findModel(int $id): ?Model
    {
        return $this->model->find($id);
    }

    /**
     * 绑定公众号 / 开放平台 openid 到既有用户
     *
     * 场景：通过 unionid 关联到已有账号后，把当前 openid 写回 users 表，
     * 后续可直接通过 openid 命中（避免每次都靠 unionid 二次跳转）。
     */
    public function bindOpenid(int $userId, string $openid): bool
    {
        return $this->model->where('id', $userId)
            ->update(['openid' => $openid]) > 0;
    }

    /**
     * 绑定小程序 mini_openid 到既有用户，可选同时回填 unionid
     */
    public function bindMiniOpenid(int $userId, string $miniOpenid, ?string $unionid = null): bool
    {
        $data = ['mini_openid' => $miniOpenid];
        if ($unionid !== null && $unionid !== '') {
            $data['unionid'] = $unionid;
        }
        return $this->model->where('id', $userId)->update($data) > 0;
    }

    /**
     * 回填 unionid（仅当当前为空）
     *
     * 跨端账号打通的前提：任一端登录拿到 unionid 就写回，否则老用户永远只能靠
     * 单端 openid 命中，换端登录会被当成新用户。条件 UPDATE 保证不会覆盖已有值。
     */
    public function backfillUnionid(int $userId, string $unionid): bool
    {
        if ($unionid === '') {
            return false;
        }

        return $this->model->where('id', $userId)
            ->whereRaw("(unionid IS NULL OR unionid = '')")
            ->update(['unionid' => $unionid]) > 0;
    }

    /**
     * 根据分销邀请码查找用户
     */
    public function findByInviteCode(string $code): ?array
    {
        $user = $this->model->where('invite_code', $code)->find();
        return $user ? $user->toArray() : null;
    }

    /**
     * 申请分销商成功后，标记为分销商并写入邀请码/等级
     */
    public function markAsDistributor(int $userId, int $levelId, string $inviteCode): bool
    {
        return $this->model->where('id', $userId)->update([
            'is_distributor'       => 1,
            'invite_code'          => $inviteCode,
            'distributor_level_id' => $levelId,
        ]) > 0;
    }

    /** 调整分销商等级 */
    public function updateDistributorLevel(int $userId, int $levelId): bool
    {
        return $this->model->where('id', $userId)
            ->where('is_distributor', 1)
            ->update(['distributor_level_id' => $levelId]) > 0;
    }

    /** 永久绑定邀请人；条件更新防止并发请求覆盖已有关系。 */
    public function bindInviterId(int $userId, int $inviterId): bool
    {
        return $this->model->where('id', $userId)
            ->where('inviter_id', 0)
            ->update(['inviter_id' => $inviterId]) > 0;
    }

    /**
     * 加载用户档案（含会员等级关联），并把 memberLevel 关联键归一化为 member_level
     */
    public function findProfile(int $id): ?array
    {
        $user = $this->model->with(['memberLevel'])->find($id);
        if (!$user) {
            return null;
        }
        $data = $user->toArray();
        if (array_key_exists('memberLevel', $data)) {
            $data['member_level'] = $data['memberLevel'];
            unset($data['memberLevel']);
        }
        return $data;
    }

    /**
     * 统计某个会员等级下的用户数（删除会员等级前用作存在性校验）
     */
    public function countByMemberLevelId(int $levelId): int
    {
        return $this->model->where('member_level_id', $levelId)->count();
    }

    /**
     * 批量统计各会员等级下的用户数
     *
     * @param  int[]  $levelIds
     * @return array<int, int> level_id => count
     */
    public function countGroupedByMemberLevelIds(array $levelIds): array
    {
        $levelIds = array_values(array_unique(array_filter(array_map('intval', $levelIds))));
        if ($levelIds === []) {
            return [];
        }

        $rows = $this->model
            ->whereIn('member_level_id', $levelIds)
            ->field('member_level_id, COUNT(*) as cnt')
            ->group('member_level_id')
            ->select()
            ->toArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['member_level_id']] = (int)$row['cnt'];
        }
        return $map;
    }

    /**
     * 将尚未分配等级的用户（member_level_id=0/null）写入默认等级
     */
    public function assignMemberLevelWhereUnset(int $levelId): int
    {
        if ($levelId <= 0) {
            return 0;
        }

        return (int)$this->model
            ->where(function ($q) {
                $q->where('member_level_id', 0)->whereOr('member_level_id', null);
            })
            ->update(['member_level_id' => $levelId]);
    }

    /**
     * 增加用户成长值
     */
    public function incGrowthValue(int $userId, int $value): void
    {
        $this->model->where('id', $userId)->inc('growth_value', $value)->update();
    }

    /** 在调用方持有用户行锁时写入成长值。 */
    public function updateGrowthValue(int $userId, int $growthValue): bool
    {
        return $this->model->where('id', $userId)
            ->update(['growth_value' => $growthValue]) > 0;
    }

    /**
     * 设置用户会员等级
     */
    public function updateMemberLevel(int $userId, int $levelId): bool
    {
        return $this->model->where('id', $userId)->update(['member_level_id' => $levelId]) > 0;
    }

    /**
     * 订单完成时更新消费统计（total_consume +amount，order_count +1）
     */
    public function recordOrderConsumption(int $userId, float $amount): void
    {
        $this->model->where('id', $userId)
            ->inc('total_consume', $amount)
            ->inc('order_count', 1)
            ->update();
    }

    /**
     * 更新最近登录信息（last_login_at / last_login_ip / login_count）
     */
    public function recordLogin(int $userId, string $ip): void
    {
        $this->model->where('id', $userId)
            ->inc('login_count', 1)
            ->update([
                'last_login_time' => date('Y-m-d H:i:s'),
                'last_login_ip'   => $ip,
            ]);
    }

    /**
     * 标记新人礼包已发放（防止 user.register 事件重放导致重复发放）
     */
    public function markNewUserGiftClaimed(int $userId): bool
    {
        return $this->model->where('id', $userId)
            ->update(['new_user_gift_claimed_at' => date('Y-m-d H:i:s')]) > 0;
    }

    /**
     * 加锁应用积分变动（delta 可正可负，必须在事务内调用）
     *
     * 返回变更前后积分（log 写入时需要 before/after）。当 delta < 0 且余额不足时返回 null
     * （调用方按业务语义抛 BusinessException）。
     *
     * @return array{before:int, after:int}|null
     */
    public function applyPointsDelta(int $userId, int $delta, bool $incrementTotalForPositive = true): ?array
    {
        $user = $this->model->where('id', $userId)->lock(true)->find();
        if (!$user) {
            return null;
        }
        $before = (int)$user->points;
        if ($delta < 0 && $before + $delta < 0) {
            return null;
        }
        $after = $before + $delta;

        $payload = ['points' => $after];
        if ($delta > 0 && $incrementTotalForPositive) {
            $payload['total_points'] = (int)$user->total_points + $delta;
        }
        $this->model->where('id', $userId)->update($payload);

        return ['before' => $before, 'after' => $after];
    }

    /**
     * 获取用户模型并暴露密码字段（用于密码校验）
     */
    public function findModelWithPassword(int $id): ?Model
    {
        $user = $this->model->find($id);
        if ($user) {
            $user->hidden([]);
        }
        return $user;
    }

    /**
     * 用户列表（admin 后台用）
     */
    public function getSearchList(array $params, int $page = 1, int $limit = 20): array
    {
        $query = User::order('id', 'desc');
        if (!empty($params['keyword'])) {
            $query->where('nickname|mobile', 'like', "%{$params['keyword']}%");
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', $params['status']);
        }
        // 会员等级筛选（前端 level_id / OpenAPI member_level_id）
        $levelId = $params['member_level_id'] ?? $params['level_id'] ?? '';
        if ($levelId !== '' && $levelId !== null) {
            $query->where('member_level_id', (int)$levelId);
        }
        // 按标签筛选（多选，取并集：有任意一个标签即命中）
        if (!empty($params['tag_ids'])) {
            $tagIds  = array_filter(array_map('intval', (array) $params['tag_ids']));
            if (!empty($tagIds)) {
                $userIds = \app\model\user\UserTagRelation::whereIn('tag_id', $tagIds)->column('user_id');
                $userIds = array_unique($userIds);
                if (empty($userIds)) {
                    return [
                        'list'       => [],
                        'total'      => 0,
                        'pagination' => [
                            'current_page' => $page,
                            'per_page'     => $limit,
                            'total'        => 0,
                            'last_page'    => 1,
                        ],
                    ];
                }
                $query->whereIn('id', $userIds);
            }
        }
        $total = $query->count();
        $list  = $query->page($page, $limit)
            ->field('id,nickname,avatar,mobile,balance,points,status,member_level_id,total_consume,order_count,is_distributor,last_login_ip,last_login_time,login_count,created_at')
            ->select()->toArray();

        // 为每个用户附加标签与会员等级
        if (!empty($list)) {
            $userIds   = array_column($list, 'id');
            $relations = \app\model\user\UserTagRelation::whereIn('user_id', $userIds)->select()->toArray();
            $tagIds    = array_unique(array_column($relations, 'tag_id'));
            $tagMap    = [];
            if (!empty($tagIds)) {
                $tags = \app\model\user\UserTag::whereIn('id', $tagIds)->select()->toArray();
                $tags = array_column($tags, null, 'id');
                foreach ($relations as $rel) {
                    if (isset($tags[$rel['tag_id']])) {
                        $tagMap[$rel['user_id']][] = $tags[$rel['tag_id']];
                    }
                }
            }

            $levelIds = array_values(array_unique(array_filter(array_map(
                static fn ($row) => (int)($row['member_level_id'] ?? 0),
                $list
            ))));
            $levelMap = [];
            if ($levelIds !== []) {
                $levels = \app\model\member\MemberLevel::whereIn('id', $levelIds)
                    ->field('id,name,icon,growth_min,discount,points_rate,status')
                    ->select()
                    ->toArray();
                $levelMap = array_column($levels, null, 'id');
            }

            foreach ($list as &$item) {
                $item['tags'] = $tagMap[$item['id']] ?? [];
                $lid = (int)($item['member_level_id'] ?? 0);
                $item['member_level'] = $lid > 0 ? ($levelMap[$lid] ?? null) : null;
            }
            unset($item);
        }

        return [
            'list'       => $list,
            'total'      => $total,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $limit,
                'total'        => $total,
                'last_page'    => (int) ceil($total / max($limit, 1)),
            ],
        ];
    }

    /**
     * 根据昵称或手机号模糊搜索用户ID
     */
    public function searchIdsByKeyword(string $keyword): array
    {
        return User::where('nickname|mobile', 'like', "%{$keyword}%")->column('id');
    }

    /**
     * 查找用户并加行锁（FOR UPDATE）
     * 注意：返回 Model 实例（非数组），调用方使用 $user->balance 而非 $user['balance']
     */
    public function findForUpdate(int $id): ?User
    {
        return User::where('id', $id)->lock(true)->find();
    }

    /**
     * 在调用方已持有用户行锁时写入余额。
     */
    public function updateBalance(int $id, float $balance): bool
    {
        return $this->model->where('id', $id)->update([
            'balance' => round($balance, 2),
        ]) > 0;
    }

    /** 在调用方已持有用户行锁时写入积分。 */
    public function updatePoints(int $id, int $points): bool
    {
        return $this->model->where('id', $id)->update(['points' => $points]) > 0;
    }

    /**
     * 调用方持有 users 行锁时，写入可用积分与积分债务。
     * 正向实际入账积分同步累计到 total_points；用于抵债的部分不算入账。
     */
    public function updatePointsAndDebt(
        int $id,
        int $points,
        int $pointsDebt,
        int $positiveCredited = 0
    ): bool {
        $data = [
            'points'      => max(0, $points),
            'points_debt' => max(0, $pointsDebt),
        ];
        if ($positiveCredited > 0) {
            $data['total_points'] = Db::raw(
                'total_points + ' . $positiveCredited
            );
        }
        return $this->model->where('id', $id)->update($data) > 0;
    }

    /**
     * 调用方持有 users 行锁时发放订单完成的非积分权益。
     */
    public function addOrderMemberRewardMetrics(
        int $id,
        int $growth,
        float $consumeAmount,
        int $orderCount
    ): bool {
        return $this->model->where('id', $id)->update([
            'growth_value' => Db::raw('growth_value + ' . max(0, $growth)),
            'total_consume' => Db::raw(
                'total_consume + ' . number_format(max(0.0, $consumeAmount), 2, '.', '')
            ),
            'order_count' => Db::raw('order_count + ' . max(0, $orderCount)),
        ]) > 0;
    }

    /**
     * 调用方持有 users 行锁时按绝对值写入奖励冲正后的会员统计。
     */
    public function updateMemberRewardState(
        int $id,
        int $points,
        int $pointsDebt,
        int $totalPoints,
        int $growth,
        float $totalConsume,
        int $orderCount
    ): bool {
        return $this->model->where('id', $id)->update([
            'points'        => max(0, $points),
            'points_debt'   => max(0, $pointsDebt),
            'total_points'  => max(0, $totalPoints),
            'growth_value'  => max(0, $growth),
            'total_consume' => round(max(0.0, $totalConsume), 2),
            'order_count'   => max(0, $orderCount),
        ]) > 0;
    }

    /**
     * 调用方已持有 users 行锁时，同时更新余额和佣金债务。
     */
    public function updateBalanceAndCommissionDebt(int $id, float $balance, float $commissionDebt): bool
    {
        return $this->model->where('id', $id)->update([
            'balance'         => round($balance, 2),
            'commission_debt' => round(max(0.0, $commissionDebt), 2),
        ]) > 0;
    }

    /**
     * 按规则条件匹配 user_id 列表（供 UserRuleEngine 调用）
     *
     * @param array<int, array{field:string,op:string,value:mixed}> $includes
     * @param array<int, array{field:string,op:string,value:mixed}> $excludes
     * @param string $logic AND | OR
     * @return int[]
     */
    public function matchIdsByRules(array $includes, array $excludes, string $logic): array
    {
        $logic = strtoupper($logic) === 'OR' ? 'OR' : 'AND';
        $query = User::field('id');

        if (!empty($includes)) {
            if ($logic === 'OR') {
                $query->where(function ($q) use ($includes) {
                    foreach ($includes as $c) {
                        if (empty($c['field'])) {
                            continue;
                        }
                        $q->whereOr($c['field'], $c['op'] ?? '=', $c['value'] ?? null);
                    }
                });
            } else {
                foreach ($includes as $c) {
                    if (empty($c['field'])) {
                        continue;
                    }
                    $query->where($c['field'], $c['op'] ?? '=', $c['value'] ?? null);
                }
            }
        }

        // excludes 永远 AND-NOT
        foreach ($excludes as $c) {
            if (empty($c['field'])) {
                continue;
            }
            $query->where($c['field'], $c['op'] ?? '!=', $c['value'] ?? null);
        }

        return array_map('intval', $query->column('id'));
    }

    /**
     * 分销商列表（分页 + 关键字 + 等级 + 状态过滤）
     */
    public function getDistributorPageList(array $params, int $page, int $limit): array
    {
        $query = User::where('is_distributor', 1);

        if (!empty($params['keyword'])) {
            $kw = $params['keyword'];
            $query->where(function ($q) use ($kw) {
                $q->where('nickname', 'like', "%{$kw}%")
                  ->whereOr('mobile', 'like', "%{$kw}%");
            });
        }
        if (!empty($params['level_id'])) {
            $query->where('distributor_level_id', (int)$params['level_id']);
        }
        if (isset($params['status']) && $params['status'] !== '' && $params['status'] !== null) {
            $query->where('status', (int)$params['status']);
        }

        $total = $query->count();
        $list  = $query->order('id', 'desc')->page($page, $limit)->select()->toArray();

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 分销商导出（不分页）— 复用 getDistributorPageList 过滤逻辑
     */
    public function getDistributorAllForExport(array $params, int $maxRows): array
    {
        $query = User::where('is_distributor', 1);

        if (!empty($params['keyword'])) {
            $kw = $params['keyword'];
            $query->where(function ($q) use ($kw) {
                $q->where('nickname', 'like', "%{$kw}%")
                  ->whereOr('mobile', 'like', "%{$kw}%");
            });
        }
        if (!empty($params['level_id'])) {
            $query->where('distributor_level_id', (int)$params['level_id']);
        }
        if (isset($params['status']) && $params['status'] !== '' && $params['status'] !== null) {
            $query->where('status', (int)$params['status']);
        }

        return $query->order('id', 'desc')->limit($maxRows + 1)->select()->toArray();
    }

    /**
     * 统计某人邀请的注册用户总数（含非分销员）
     */
    public function countInvitedUsers(int $inviterId): int
    {
        return User::where('inviter_id', $inviterId)->count();
    }

    /**
     * 统计某人邀请的、且已是分销员的下线数量
     */
    public function countInvitedDistributors(int $inviterId): int
    {
        return (int)User::where('inviter_id', $inviterId)
            ->where('is_distributor', 1)
            ->count();
    }

    /**
     * 统计某人邀请的下单用户数（至少有一笔已完成订单的下线）
     */
    public function countInvitedOrderUsers(int $inviterId): int
    {
        return (int)User::alias('u')
            ->whereExists(function ($q) {
                $q->table('order_orders')->whereRaw('user_id = u.id AND status = "completed"');
            })
            ->where('u.inviter_id', $inviterId)
            ->count();
    }

    /**
     * 我的团队（直接下级）分页：返回 inviter_id = me 的所有用户
     * 隐私字段（mobile）已脱敏，密码字段始终不返回
     *
     * @return array{list: array<int, array<string, mixed>>, total: int}
     */
    public function paginateInvitedUsers(int $inviterId, int $page = 1, int $limit = 20, ?int $isDistributor = null): array
    {
        $query = User::where('inviter_id', $inviterId);
        if ($isDistributor !== null) {
            $query->where('is_distributor', $isDistributor);
        }
        $total = (clone $query)->count();
        $list  = $query
            ->field(['id', 'nickname', 'avatar', 'is_distributor', 'distributor_level_id', 'created_at'])
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();
        return ['list' => $list, 'total' => $total];
    }

    /**
     * 按 inviter_id 批量统计直接团队人数，用于分销商列表
     *
     * @param int[] $inviterIds
     * @return array<int, int> inviter_id => team_count
     */
    public function countTeamByInviterIds(array $inviterIds): array
    {
        if (empty($inviterIds)) {
            return [];
        }
        $rows = User::field(['inviter_id', 'COUNT(*) AS cnt'])
            ->whereIn('inviter_id', $inviterIds)
            ->group('inviter_id')
            ->select()
            ->toArray();
        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r['inviter_id']] = (int)$r['cnt'];
        }
        return $out;
    }

    /**
     * 根据公众号 openid 查找用户
     */
    public function findByOaOpenid(string $openid): ?Model
    {
        return User::where('oa_openid', $openid)->find();
    }

    /**
     * 更新用户的公众号 openid
     */
    public function updateOaOpenid(int $userId, string $oaOpenid): bool
    {
        return User::where('id', $userId)->update(['oa_openid' => $oaOpenid]) !== false;
    }

    /**
     * 更新用户的公众号 openid 和 unionid
     */
    public function updateOaOpenidAndUnionid(int $userId, string $oaOpenid, ?string $unionid): bool
    {
        $data = ['oa_openid' => $oaOpenid];
        if ($unionid !== null) {
            $data['unionid'] = $unionid ?: null;
        }
        return User::where('id', $userId)->update($data) !== false;
    }

    /**
     * 根据 ID 获取指定字段的值
     */
    public function getFieldById(int $userId, string $field): mixed
    {
        $user = User::where('id', $userId)->field($field)->find();
        return $user?->$field;
    }

    /**
     * 用户总数
     */
    public function getTotalCount(): int
    {
        return User::count();
    }

    /**
     * 全体用户余额合计
     */
    public function sumBalance(): float
    {
        return (float)User::sum('balance');
    }

    /**
     * 今日新增用户数
     */
    public function getTodayNewCount(): int
    {
        return User::whereTime('created_at', 'today')->count();
    }

    /**
     * 上周同日新增用户数
     */
    public function getLastWeekSameDayNewCount(): int
    {
        $date = date('Y-m-d', strtotime('-7 days'));
        return User::whereDay('created_at', $date)->count();
    }

    /**
     * 活跃用户数（最近7天有登录记录）
     */
    public function getActiveCount(int $days = 7): int
    {
        $startDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return User::where('last_login_time', '>=', $startDate)->count();
    }

    /**
     * 上周活跃用户数（用于环比对比）
     */
    public function getLastWeekActiveCount(): int
    {
        $lastWeekStart = date('Y-m-d H:i:s', strtotime('-14 days'));
        $lastWeekEnd = date('Y-m-d H:i:s', strtotime('-7 days'));
        return User::where('last_login_time', '>=', $lastWeekStart)
            ->where('last_login_time', '<', $lastWeekEnd)
            ->count();
    }

    /**
     * 用户注册趋势（最近N天）
     */
    public function getRegisterTrend(int $days = 7): array
    {
        $startDate = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
        $rows = User::where('created_at', '>=', $startDate . ' 00:00:00')
            ->fieldRaw('DATE(created_at) as date, COUNT(*) as count')
            ->group('date')
            ->select()
            ->toArray();

        $countMap = [];
        foreach ($rows as $row) {
            $countMap[$row['date']] = (int)$row['count'];
        }

        $trend = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $trend[] = [
                'date'  => date('m-d', strtotime($date)),
                'count' => $countMap[$date] ?? 0,
            ];
        }
        return $trend;
    }
}
