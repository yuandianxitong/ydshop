<?php
declare(strict_types=1);

namespace app\model\delivery;

use core\base\Model;

class ExpressCompany extends Model
{
    protected $table = 'express_companies';
    protected $deleteTime = false;

    protected $fillable = ['name', 'code', 'logo', 'sort', 'status'];

    protected $type = [
        'sort'   => 'integer',
        'status' => 'integer',
    ];
}
