<?php
declare(strict_types=1);

namespace app\model\help;

use core\base\Model;

class Help extends Model
{
    protected $name = 'helps';

    protected $fillable = [
        'category_id', 'title', 'summary', 'content',
        'view_count', 'status', 'admin_id',
    ];

    protected $type = [
        'category_id' => 'integer',
        'view_count'  => 'integer',
        'status'      => 'integer',
        'admin_id'    => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(HelpCategory::class, 'category_id', 'id');
    }
}
