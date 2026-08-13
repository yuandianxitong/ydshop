<?php
declare(strict_types=1);

namespace app\repository\member;

use app\model\member\MemberAddress;
use core\base\Repository;
use think\Model as ThinkModel;

class MemberAddressRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new MemberAddress();
    }

    public function countByUserId(int $userId): int
    {
        return $this->model->where('user_id', $userId)->count();
    }

    /**
     * 单用户收货地区分布（按省+市聚合，TOP 4 + 其他）
     */
    public function districtDistribution(int $userId): array
    {
        $rows = $this->model
            ->field([
                "CONCAT_WS(' · ', province, city) AS region",
                'COUNT(*) AS cnt',
            ])
            ->where('user_id', $userId)
            ->where('province', '<>', '')
            ->group('province, city')
            ->order('cnt', 'desc')
            ->select()
            ->toArray();

        if (empty($rows)) return [];

        $total = 0;
        foreach ($rows as $r) $total += (int)$r['cnt'];
        if ($total === 0) return [];

        $out = [];
        $top = array_slice($rows, 0, 4);
        $rest = array_slice($rows, 4);
        $sumTop = 0;
        foreach ($top as $r) {
            $sumTop += (int)$r['cnt'];
            $out[] = [
                'name'    => (string)$r['region'],
                'count'   => (int)$r['cnt'],
                'percent' => (int)round((int)$r['cnt'] * 100 / $total),
            ];
        }
        if (!empty($rest)) {
            $restCnt = $total - $sumTop;
            $out[] = [
                'name'    => '其他',
                'count'   => $restCnt,
                'percent' => (int)round($restCnt * 100 / $total),
            ];
        }
        return $out;
    }

    /**
     * 设为默认地址（清掉同用户其他默认 + 设当前为默认）
     */
    public function setDefault(int $id, int $userId): bool
    {
        $this->model->where('user_id', $userId)->update(['is_default' => 0]);
        return $this->model->where('id', $id)->where('user_id', $userId)->update(['is_default' => 1]) >= 0;
    }

    /**
     * 用户全部地址（按默认/id 倒序）
     */
    public function findAllByUser(int $userId): array
    {
        return $this->model->where('user_id', $userId)
            ->order('is_default', 'desc')
            ->order('id', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * 取用户的默认地址；无默认则返回第一条（按 id 升序）
     */
    public function findDefaultOrFirst(int $userId): ?array
    {
        $row = $this->model->where('user_id', $userId)
            ->where('is_default', 1)
            ->find();
        if (!$row) {
            $row = $this->model->where('user_id', $userId)
                ->order('id', 'asc')
                ->find();
        }
        return $row ? $row->toArray() : null;
    }

    /**
     * 按 id + user_id 双约束查找
     */
    public function findByIdAndUser(int $addressId, int $userId): ?array
    {
        $row = $this->model->where('id', $addressId)
            ->where('user_id', $userId)
            ->find();
        return $row ? $row->toArray() : null;
    }

    /**
     * 清掉某用户全部 is_default=1 标记（除指定的 excludeId 外）
     */
    public function clearDefaults(int $userId, ?int $excludeId = null): int
    {
        $query = $this->model->where('user_id', $userId);
        if ($excludeId !== null) {
            $query->where('id', '<>', $excludeId);
        }
        return $query->update(['is_default' => 0]);
    }

    public function getPageList(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $query = $this->model->with([
            'user' => function ($q) {
                $q->field('id, nickname, mobile, avatar');
            },
        ])->order('is_default', 'desc')->order('id', 'desc');

        if (!empty($filters['keyword'])) {
            $kw = $filters['keyword'];
            $query->where(function ($q) use ($kw) {
                $q->whereLike('name', "%{$kw}%")
                  ->whereOr('phone', 'like', "%{$kw}%")
                  ->whereOr('detail', 'like', "%{$kw}%");
            });
        }
        if (isset($filters['is_default']) && $filters['is_default'] !== '') {
            $query->where('is_default', (int)$filters['is_default']);
        }
        if (!empty($filters['user_id'])) {
            $query->where('user_id', (int)$filters['user_id']);
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
     * 导出所有匹配的地址（不分页）
     *
     * 复用 getPageList 过滤参数（keyword / is_default / user_id），
     * 同样 eager load user 并 flatten user_nickname/user_mobile。
     */
    public function getAllForExport(array $filters, int $maxRows): array
    {
        $query = $this->model->with([
            'user' => function ($q) {
                $q->field('id, nickname, mobile, avatar');
            },
        ])->order('is_default', 'desc')->order('id', 'desc');

        if (!empty($filters['keyword'])) {
            $kw = $filters['keyword'];
            $query->where(function ($q) use ($kw) {
                $q->whereLike('name', "%{$kw}%")
                  ->whereOr('phone', 'like', "%{$kw}%")
                  ->whereOr('detail', 'like', "%{$kw}%");
            });
        }
        if (isset($filters['is_default']) && $filters['is_default'] !== '') {
            $query->where('is_default', (int)$filters['is_default']);
        }
        if (!empty($filters['user_id'])) {
            $query->where('user_id', (int)$filters['user_id']);
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

    public function getStats(): array
    {
        $total       = $this->model->count();
        $defaultCnt  = $this->model->where('is_default', 1)->count();
        $userCnt     = $this->model->distinct(true)->count('user_id');
        $avg         = $userCnt > 0 ? round($total / $userCnt, 1) : 0;
        return [
            'total'    => $total,
            'default'  => $defaultCnt,
            'users'    => $userCnt,
            'avg'      => $avg,
        ];
    }
}
