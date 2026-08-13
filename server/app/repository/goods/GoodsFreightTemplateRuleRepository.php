<?php
declare(strict_types=1);

namespace app\repository\goods;

use app\model\goods\GoodsFreightTemplateRule;
use core\base\Repository;
use think\Model as ThinkModel;

class GoodsFreightTemplateRuleRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new GoodsFreightTemplateRule();
    }

    public function findByTemplateId(int $templateId): array
    {
        return $this->model->where('template_id', $templateId)->select()->toArray();
    }

    public function deleteByTemplateId(int $templateId): int
    {
        return $this->model->where('template_id', $templateId)->delete();
    }
}
