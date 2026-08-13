<?php
declare(strict_types=1);

namespace app\repository\member;

use app\model\member\MemberLevel;
use core\base\Repository;
use think\Model as ThinkModel;

class MemberLevelRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new MemberLevel();
    }

    /**
     * 后台分页列表：支持 status / keyword 过滤，按 sort+id 升序
     */
    public function getSearchList(array $params, int $page = 1, int $limit = 15): array
    {
        $query = $this->model->order('sort asc, id asc');

        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', (int)$params['status']);
        }

        if (!empty($params['keyword'])) {
            $query->where('name', 'like', "%{$params['keyword']}%");
        }

        $total = $query->count();
        $list  = $query->page($page, $limit)->select()->toArray();

        return $this->buildPagination($list, $page, $limit, $total);
    }

    /**
     * 全部等级（不分页），按 sort+id 升序
     */
    public function getAllOrdered(): array
    {
        return $this->model->order('sort asc, id asc')->select()->toArray();
    }

    /**
     * C 端展示用：启用中的等级，按 growth_min 升序
     */
    public function getEnabledList(): array
    {
        return $this->model->where('status', 1)
            ->order('growth_min asc, id asc')
            ->select()
            ->toArray();
    }

    /**
     * 默认会员等级：启用中、成长值门槛最低的一条（通常为「普通会员」）
     */
    public function findDefaultEnabled(): ?array
    {
        $row = $this->model->where('status', 1)
            ->order('growth_min asc, id asc')
            ->find();

        return $row ? $row->toArray() : null;
    }

    /**
     * 升级判定用：启用中的等级，按 growth_min 降序（从高到低逐级匹配）
     */
    public function getEnabledForUpgrade(): array
    {
        return $this->model->where('status', 1)
            ->order('growth_min', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * 获取模型实例（用于需要直接操作 Model 对象的场景，如级联删除前的存在性校验）
     */
    public function findModel(int $id): ?MemberLevel
    {
        /** @var MemberLevel|null $level */
        $level = $this->model->find($id);
        return $level;
    }
}
