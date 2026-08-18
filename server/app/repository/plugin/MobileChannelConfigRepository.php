<?php
declare(strict_types=1);

namespace app\repository\plugin;

use app\model\plugin\MobileChannelConfig;
use core\base\Repository;
use think\Model;

class MobileChannelConfigRepository extends Repository
{
    protected function getModel(): Model
    {
        return new MobileChannelConfig();
    }

    public function singleton(): ?array
    {
        $row = $this->getModel()->db()->order('id asc')->find();
        return $row ? (is_array($row) ? $row : $row->toArray()) : null;
    }

    public function upsert(array $data): array
    {
        $row = $this->singleton();
        if ($row) {
            $this->update((int) $row['id'], $data);
            return $this->find((int) $row['id']) ?? [];
        }
        return $this->create($data);
    }
}
