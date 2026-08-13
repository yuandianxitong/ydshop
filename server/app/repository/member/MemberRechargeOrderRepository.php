<?php
declare(strict_types=1);

namespace app\repository\member;

use app\model\member\MemberRechargeOrder;
use core\base\Repository;
use think\facade\Db;
use think\Model as ThinkModel;

class MemberRechargeOrderRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new MemberRechargeOrder();
    }

    public function getPageList(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $query = $this->model->with([
            'user' => function ($q) {
                $q->field('id, nickname, mobile, avatar');
            },
        ])->order('id', 'desc');

        if (!empty($filters['keyword'])) {
            $query->where('order_no', 'like', '%' . $filters['keyword'] . '%');
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', (int)$filters['status']);
        }
        if (!empty($filters['user_id'])) {
            $query->where('user_id', (int)$filters['user_id']);
        }
        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date'] . ' 00:00:00');
        }
        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date'] . ' 23:59:59');
        }

        $total = $query->count();
        $list  = $query->page($page, $limit)->select()->toArray();

        foreach ($list as &$row) {
            $row['user_nickname'] = $row['user']['nickname'] ?? '-';
            $row['user_mobile']   = $row['user']['mobile'] ?? '';
            $row['user_avatar']   = $row['user']['avatar'] ?? '';
            unset($row['user']);
        }
        unset($row);

        return $this->buildPagination($list, $page, $limit, $total);
    }

    /**
     * 导出所有匹配的充值订单（不分页）
     *
     * 复用 getPageList 过滤参数（keyword / status / user_id / start_date / end_date），
     * eager load user 并 flatten user_nickname/user_mobile。
     */
    public function getAllForExport(array $filters, int $maxRows): array
    {
        $query = $this->model->with([
            'user' => function ($q) {
                $q->field('id, nickname, mobile, avatar');
            },
        ])->order('id', 'desc');

        if (!empty($filters['keyword'])) {
            $query->where('order_no', 'like', '%' . $filters['keyword'] . '%');
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', (int)$filters['status']);
        }
        if (!empty($filters['user_id'])) {
            $query->where('user_id', (int)$filters['user_id']);
        }
        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date'] . ' 00:00:00');
        }
        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date'] . ' 23:59:59');
        }

        $list = $query->limit($maxRows + 1)->select()->toArray();

        foreach ($list as &$row) {
            $row['user_nickname'] = $row['user']['nickname'] ?? '-';
            $row['user_mobile']   = $row['user']['mobile'] ?? '';
            $row['user_avatar']   = $row['user']['avatar'] ?? '';
            unset($row['user']);
        }
        unset($row);

        return $list;
    }

    public function sumThisMonth(): float
    {
        return (float)$this->model
            ->where('status', 1)
            ->whereTime('paid_at', 'm')
            ->sum('amount');
    }

    /** 按充值业务单号查询（支付历史数据 biz_type 修复等场景）。 */
    public function findByOrderNo(string $orderNo): ?array
    {
        $row = $this->model->where('order_no', $orderNo)->find();
        return $row ? $row->toArray() : null;
    }

    /** 充值结算加锁读取；允许 status=1 但 settled_at 为空的历史半成品补偿。 */
    public function findByOrderNoForUpdate(string $orderNo): ?array
    {
        $row = $this->model->where('order_no', $orderNo)->lock(true)->find();
        return $row ? $row->toArray() : null;
    }

    /** 支付创建返回后，通过 Repository 回写真实支付单 ID。 */
    public function setPaymentOrderId(int $id, int $paymentOrderId): bool
    {
        if ($id <= 0 || $paymentOrderId <= 0) {
            return false;
        }

        $affected = $this->model
            ->where('id', $id)
            ->where(function ($query) use ($paymentOrderId) {
                $query->whereNull('payment_order_id')
                    ->whereOr('payment_order_id', 0)
                    ->whereOr('payment_order_id', $paymentOrderId);
            })
            ->update(['payment_order_id' => $paymentOrderId]);

        if ($affected > 0) {
            return true;
        }

        // MySQL 默认只报告“实际变化行数”：快速回调已先绑定同一支付单时，
        // 相同值更新会返回 0。必须读取并确认行存在且绑定值一致，不能把任意
        // 0 行更新都视为成功。
        $row = $this->model->where('id', $id)->field('id,payment_order_id')->find();
        return $row !== null && (int)$row->payment_order_id === $paymentOrderId;
    }

    /** 快速回调早于创建流程回写时，在充值行锁内补绑真实支付单 ID。 */
    public function bindPaymentOrderIdIfEmpty(int $id, int $paymentOrderId): int
    {
        return $this->model
            ->where('id', $id)
            ->where(function ($query) {
                $query->whereNull('payment_order_id')->whereOr('payment_order_id', 0);
            })
            ->update(['payment_order_id' => $paymentOrderId]);
    }

    /**
     * 所有可确定资产均成功后，最后一步原子完成充值单；历史成长值歧义必须与
     * settled_at 同一次 UPDATE 持久化，不能出现“已结算但审计任务丢失”的窗口。
     *
     * @param array<string,mixed> $growthReview
     */
    public function markSettledIfUnsettled(int $id, string $payType, array $growthReview = []): int
    {
        $settledAt = date('Y-m-d H:i:s');
        $reviewStatus = (string)($growthReview['growth_review_status']
            ?? MemberRechargeOrder::GROWTH_REVIEW_NONE);
        if (!in_array($reviewStatus, [
            MemberRechargeOrder::GROWTH_REVIEW_NONE,
            MemberRechargeOrder::GROWTH_REVIEW_PENDING,
        ], true)) {
            $reviewStatus = MemberRechargeOrder::GROWTH_REVIEW_NONE;
        }
        $update = [
            'status'     => 1,
            'pay_type'   => $payType,
            // 补偿旧 status=1 半成品时保留真实付款发生时间，避免财务补偿
            // 把历史充值收入错误计入今天；新单 paid_at 为空才使用当前时间。
            'paid_at'    => Db::raw("COALESCE(paid_at, '{$settledAt}')"),
            'settled_at' => $settledAt,
            'expected_growth_value' => max(0, (int)($growthReview['expected_growth_value'] ?? 0)),
            'growth_review_status' => $reviewStatus,
        ];
        if ($reviewStatus === MemberRechargeOrder::GROWTH_REVIEW_PENDING) {
            $update['growth_review_reason'] = mb_substr(
                trim((string)($growthReview['growth_review_reason'] ?? '历史充值缺少订单级成长值流水，无法自动确认是否已发放')),
                0,
                255
            );
        }

        return $this->model
            ->where('id', $id)
            ->whereNull('settled_at')
            ->whereIn('status', [0, 1])
            ->update($update);
    }

    public function findByIdForUpdate(int $id): ?array
    {
        $row = $this->model->where('id', $id)->lock(true)->find();
        return $row ? $row->toArray() : null;
    }

    /** 管理后台历史充值成长值复核列表。 */
    public function getGrowthReviewPage(array $filters, int $page, int $limit): array
    {
        $query = $this->model
            ->alias('recharge')
            ->leftJoin('users member', 'member.id = recharge.user_id');

        $reviewStatus = trim((string)($filters['review_status']
            ?? MemberRechargeOrder::GROWTH_REVIEW_PENDING));
        if (in_array($reviewStatus, [
            MemberRechargeOrder::GROWTH_REVIEW_NONE,
            MemberRechargeOrder::GROWTH_REVIEW_PENDING,
            MemberRechargeOrder::GROWTH_REVIEW_RESOLVED,
        ], true)) {
            $query->where('recharge.growth_review_status', $reviewStatus);
        }
        $resolution = trim((string)($filters['resolution'] ?? ''));
        if (in_array($resolution, [
            MemberRechargeOrder::GROWTH_RESOLUTION_CONFIRMED_APPLIED,
            MemberRechargeOrder::GROWTH_RESOLUTION_CONFIRMED_MISSING,
        ], true)) {
            $query->where('recharge.growth_review_resolution', $resolution);
        }
        $keyword = trim((string)($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $query->where(function ($search) use ($keyword): void {
                $search->whereLike('recharge.order_no', '%' . $keyword . '%')
                    ->whereOr('member.nickname', 'like', '%' . $keyword . '%')
                    ->whereOr('member.mobile', 'like', '%' . $keyword . '%');
            });
        }

        $total = (clone $query)->count();
        $list = $query
            ->field([
                'recharge.*',
                'member.nickname AS user_nickname',
                'member.mobile AS user_mobile',
            ])
            ->order('recharge.id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        return $this->buildPagination($list, $page, $limit, $total);
    }

    /** @return array{pending:int,resolved:int,expected_growth:int,credited_after_review:int} */
    public function getGrowthReviewSummary(): array
    {
        $row = $this->model
            ->field([
                "SUM(CASE WHEN growth_review_status = 'pending' THEN 1 ELSE 0 END) AS pending",
                "SUM(CASE WHEN growth_review_status = 'resolved' THEN 1 ELSE 0 END) AS resolved",
                "SUM(CASE WHEN growth_review_status = 'pending' THEN expected_growth_value ELSE 0 END) AS expected_growth",
                "SUM(CASE WHEN growth_review_resolution = 'confirmed_missing' THEN 1 ELSE 0 END) AS credited_after_review",
            ])
            ->find();
        $data = $row ? $row->toArray() : [];
        return [
            'pending' => (int)($data['pending'] ?? 0),
            'resolved' => (int)($data['resolved'] ?? 0),
            'expected_growth' => (int)($data['expected_growth'] ?? 0),
            'credited_after_review' => (int)($data['credited_after_review'] ?? 0),
        ];
    }

    /** @param array<string,mixed> $data */
    public function resolveGrowthReviewIfPending(int $id, array $data): int
    {
        return $this->model
            ->where('id', $id)
            ->where('growth_review_status', MemberRechargeOrder::GROWTH_REVIEW_PENDING)
            ->update($data);
    }

    /**
     * 按业务单号查找未支付充值单（status=0），用于支付回调幂等处理
     */
    public function findPendingByOrderNo(string $orderNo): ?array
    {
        $row = $this->model->where('order_no', $orderNo)
            ->where('status', 0)
            ->find();
        return $row ? $row->toArray() : null;
    }

    /**
     * 标记充值单为已支付
     */
    public function markPaid(int $id, string $payType): bool
    {
        return $this->model->where('id', $id)->update([
            'status'  => 1,
            'pay_type' => $payType,
            'paid_at' => date('Y-m-d H:i:s'),
        ]) > 0;
    }

    /**
     * 创建充值单并返回 Model 实例（创建后还要回写 payment_order_id）
     */
    public function createModel(array $data): \app\model\member\MemberRechargeOrder
    {
        return $this->model->create($data);
    }

    /**
     * 按业务单号查找未支付充值单的 Model 实例（用于支付成功回调写入余额前的状态翻转）
     */
    public function findPendingModelByOrderNo(string $orderNo): ?\app\model\member\MemberRechargeOrder
    {
        return $this->model->where('order_no', $orderNo)->where('status', 0)->find();
    }
}
