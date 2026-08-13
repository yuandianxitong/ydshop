<?php
declare(strict_types=1);

namespace app\repository\system;

use app\model\system\FileCategory;
use core\base\Repository;
use think\Model;

class FileCategoryRepository extends Repository
{
    protected function getModel(): Model
    {
        return new FileCategory();
    }
}
