<?php
declare(strict_types=1);

namespace app\repository\goods;

use app\model\goods\GoodsFreightTemplateFreeRule;
use core\base\Repository;
use think\Model as ThinkModel;

class GoodsFreightTemplateFreeRuleRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new GoodsFreightTemplateFreeRule();
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
