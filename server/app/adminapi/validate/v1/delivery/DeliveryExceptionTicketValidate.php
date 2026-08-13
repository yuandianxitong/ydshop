<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\delivery;

use core\base\Validate;

class DeliveryExceptionTicketValidate extends Validate
{
    protected $rule = [
        'type'            => 'require|in:delay,wrong_delivery,complaint,reassign,weather',
        'title'           => 'require|max:100',
        'description'     => 'max:5000',
        'reporter'        => 'max:50',
        'contact'         => 'max:100',
        'to'              => 'require|in:in_progress,resolved,closed',
        'resolution_note' => 'max:5000',
    ];

    protected $message = [
        'type.require'    => '请选择工单类型',
        'type.in'         => '工单类型非法',
        'title.require'   => '请填写工单简述',
        'title.max'       => '简述最长 100 字',
        'description.max' => '详情最长 5000 字',
        'to.require'      => '请指定流转目标状态',
        'to.in'           => '目标状态非法',
    ];

    protected $scene = [
        'create'     => ['type', 'title', 'description', 'reporter', 'contact'],
        'update'     => ['type', 'title', 'description', 'reporter', 'contact'],
        'transition' => ['to', 'resolution_note'],
    ];
}
