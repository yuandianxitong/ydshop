<?php
declare(strict_types=1);

namespace app\model\help;

use core\base\Model;

class HelpCategory extends Model
{
    protected $name = 'help_categories';

    protected $fillable = ['name', 'icon', 'sort', 'status'];

    protected $type = [
        'sort'   => 'integer',
        'status' => 'integer',
    ];

    protected $updateTime = 'updated_at';
    protected $deleteTime = false;

    public function helps()
    {
        return $this->hasMany(Help::class, 'category_id', 'id');
    }
}
