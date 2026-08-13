<?php
declare(strict_types=1);

namespace plugins\full_discount\model;

use think\model\concern\SoftDelete;
use core\base\Model;

/**
 * 满减活动 Model
 *
 * @property int    $id
 * @property string $name
 * @property string $type        reduce|percent|freight
 * @property array  $rules       阶梯规则 [{"min":100,"value":10},...]
 * @property string $use_scope   all|category|spu
 * @property array  $scope_ids
 * @property int    $stackable
 * @property string $start_at
 * @property string $end_at
 * @property int    $status
 */
class MarketingFullDiscount extends Model
{
    use SoftDelete;

    protected $name = 'marketing_full_discounts';

    protected $deleteTime = 'deleted_at';

    /**
     * JSON 字段自动序列化/反序列化
     */
    protected $json = ['rules', 'scope_ids'];

    /**
     * 字段类型转换
     */
    protected $type = [
        'rules'     => 'json',
        'scope_ids' => 'json',
        'stackable' => 'integer',
        'status'    => 'integer',
    ];
}
