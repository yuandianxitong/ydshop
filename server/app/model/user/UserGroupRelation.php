<?php
declare(strict_types=1);

namespace app\model\user;

use core\base\Model;

class UserGroupRelation extends Model
{
    protected $table = 'user_group_relations';

    protected $updateTime = false;
    protected $deleteTime = false;

    protected $fillable = [
        'user_id', 'group_id',
    ];

    protected $type = [
        'user_id'  => 'integer',
        'group_id' => 'integer',
    ];
}
