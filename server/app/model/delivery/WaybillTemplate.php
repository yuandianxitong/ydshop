<?php
declare(strict_types=1);

namespace app\model\delivery;

use core\base\Model;

class WaybillTemplate extends Model
{
    protected $table = 'waybill_templates';

    protected $fillable = [
        'name',
        'express_code',
        'express_name',
        'exp_type',
        'exp_type_name',
        'template_size',
        'template_size_label',
        'pay_type',
        'need_pickup',
        'is_default',
        'status',
        'sort',
    ];

    protected $type = [
        'pay_type'     => 'integer',
        'need_pickup'  => 'integer',
        'is_default'   => 'integer',
        'status'       => 'integer',
        'sort'         => 'integer',
    ];
}
