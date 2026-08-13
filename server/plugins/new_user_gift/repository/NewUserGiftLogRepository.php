<?php
declare(strict_types=1);

namespace plugins\new_user_gift\repository;

use plugins\new_user_gift\model\NewUserGiftLog;
use app\model\order\OrderOrder;
use app\model\user\User;
use core\base\Repository;
use think\facade\Db;
use think\Model as ThinkModel;

class NewUserGiftLogRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new NewUserGiftLog();
    }

    /**
     * 分页 + 关联 user 显示昵称/头像
     */
    public function getPageList(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $query = $this->model
            ->with([
                'user' => function ($q) {
                    $q->field('id, nickname, avatar');
                },
            ])
            ->order('id', 'desc');

        if (!empty($filters['user_id'])) {
            $query->where('user_id', (int)$filters['user_id']);
        }
        if (!empty($filters['gift_id'])) {
            $query->where('gift_id', (int)$filters['gift_id']);
        }
        if (!empty($filters['date_start'])) {
            $query->where('created_at', '>=', $filters['date_start'] . ' 00:00:00');
        }
        if (!empty($filters['date_end'])) {
            $query->where('created_at', '<=', $filters['date_end'] . ' 23:59:59');
        }

        $total = $query->count();
        $list  = $query->page($page, $limit)->select()->toArray();

        foreach ($list as &$row) {
            $row['user_nickname'] = $row['user']['nickname'] ?? '-';
            $row['user_avatar']   = $row['user']['avatar'] ?? '';
            unset($row['user']);
        }
        unset($row);

        return $this->buildPagination($list, $page, $limit, $total);
    }

    /**
     * KPI 4 个数字（本月）
     *
     * 返回：
     * - new_users: 本月注册用户数
     * - recipients: 本月领过礼包的用户数（去重）
     * - conversion_rate: 0-100，一位小数
     * - gmv: 已转化用户的首单金额合计
     */
    public function getStats(): array
    {
        $monthStart = date('Y-m-01 00:00:00');

        // 1. 本月新人
        $newUsers = (int) User::where('created_at', '>=', $monthStart)->count();

        // 2. 本月领取（去重）
        $recipients = (int) $this->model
            ->where('created_at', '>=', $monthStart)
            ->count('DISTINCT user_id');

        // 3 + 4. 转化率 + GMV
        $converted = 0;
        $gmv = 0.0;

        if ($recipients > 0) {
            // 子查询：本月每用户最早的领取时间
            $claimedSub = $this->model
                ->field('user_id, MIN(created_at) AS first_claim_at')
                ->where('created_at', '>=', $monthStart)
                ->group('user_id')
                ->buildSql();

            // 每用户领取后的最早一笔已支付订单
            $rows = Db::table($claimedSub . ' c')
                ->join('order_orders o', 'o.user_id = c.user_id', 'INNER')
                ->whereIn('o.status', ['paid', 'shipped', 'completed'])
                ->where('o.created_at', '>=', Db::raw('c.first_claim_at'))
                ->group('c.user_id')
                ->field('c.user_id, MIN(o.id) AS first_order_id')
                ->select()
                ->toArray();

            $converted = count($rows);

            if ($converted > 0) {
                $firstOrderIds = array_map(fn($r) => (int)$r['first_order_id'], $rows);
                $gmv = (float) OrderOrder::whereIn('id', $firstOrderIds)->sum('pay_amount');
            }
        }

        $conversionRate = $recipients > 0 ? round($converted * 100 / $recipients, 1) : 0.0;

        return [
            'new_users'       => $newUsers,
            'recipients'      => $recipients,
            'conversion_rate' => $conversionRate,
            'gmv'             => $gmv,
        ];
    }
}
