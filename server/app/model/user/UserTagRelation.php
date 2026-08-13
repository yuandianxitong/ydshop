<?php
declare(strict_types=1);

namespace app\model\user;

use core\base\Model;

class UserTagRelation extends Model
{
    protected $table = 'user_tag_relations';
    protected $deleteTime = false;
    protected $updateTime = false;

    protected $fillable = ['user_id', 'tag_id'];

    protected $type = [
        'user_id' => 'integer',
        'tag_id'  => 'integer',
    ];
}
