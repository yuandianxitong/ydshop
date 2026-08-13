<?php
declare(strict_types=1);

namespace app\model\user;

use core\base\Model;

class UserGroup extends Model
{
    protected $table = 'user_groups';

    protected $fillable = [
        'name', 'description', 'rules', 'auto_update', 'user_count', 'sort', 'status',
    ];

    protected $type = [
        'rules'       => 'json',
        'auto_update' => 'integer',
        'user_count'  => 'integer',
        'sort'        => 'integer',
        'status'      => 'integer',
    ];
}
