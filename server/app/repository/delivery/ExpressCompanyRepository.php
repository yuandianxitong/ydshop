<?php
declare(strict_types=1);

namespace app\repository\delivery;

use app\model\delivery\ExpressCompany;
use core\base\Repository;
use think\Model as ThinkModel;

class ExpressCompanyRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new ExpressCompany();
    }

    /**
     * 通过公司名称查找快递公司编码（找不到返回空串）
     */
    public function findCodeByName(string $name): string
    {
        $row = $this->model->where('name', $name)->find();
        return $row ? (string)$row['code'] : '';
    }

    /**
     * 将公司展示名称或已保存的标准编码统一解析为标准编码。
     *
     * 兼容旧版本电子面单曾把 express_company 从名称覆盖成编码的数据。
     */
    public function resolveCode(string $nameOrCode): string
    {
        $value = trim($nameOrCode);
        if ($value === '') {
            return '';
        }

        $row = $this->model
            ->where(function ($query) use ($value) {
                $query->where('name', $value)->whereOr('code', $value);
            })
            ->find();

        return $row ? (string)$row['code'] : $value;
    }

    /**
     * 分页列表
     */
    public function getPageList(array $where = [], int $page = 1, int $limit = 15, string $order = 'id desc'): array
    {
        $query = $this->model->where($where);

        $total = $query->count();
        $list = $query->page($page, $limit)
            ->order($order)
            ->select()
            ->toArray();

        return [
            'list'       => $list,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $limit,
                'total'        => $total,
                'last_page'    => (int)ceil($total / $limit),
            ],
        ];
    }
}
