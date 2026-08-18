<?php
declare(strict_types=1);

namespace app\repository\plugin;

use app\model\plugin\PluginBuild;
use core\base\Repository;
use think\Model;

class PluginBuildRepository extends Repository
{
    protected function getModel(): Model
    {
        return new PluginBuild();
    }

    /**
     * @return array{list: list<array<string, mixed>>, total: int}
     */
    public function listPaginated(int $page, int $limit, ?string $pluginCode = null): array
    {
        $query = $this->getModel()->db();
        if ($pluginCode) {
            $query->where('plugin_code', $pluginCode);
        }
        $total = (int) $query->count();
        $list = $this->getModel()->db()
            ->when($pluginCode, fn ($q) => $q->where('plugin_code', $pluginCode))
            ->order('id desc')
            ->page($page, $limit)
            ->select()
            ->toArray();
        return ['list' => $list, 'total' => $total];
    }

    /**
     * @param list<int> $ids
     * @return list<array<string, mixed>>
     */
    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        return $this->getModel()->db()->whereIn('id', $ids)->order('id desc')->select()->toArray();
    }

    public function latestFor(string $pluginCode, string $target): ?array
    {
        $row = $this->getModel()->db()
            ->where('plugin_code', $pluginCode)
            ->where('target', $target)
            ->order('id desc')
            ->find();
        return $row ? (is_array($row) ? $row : $row->toArray()) : null;
    }

    public function markRunning(int $id): bool
    {
        return $this->update($id, [
            'status'     => PluginBuild::STATUS_RUNNING,
            'started_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function markSuccess(int $id, string $log, string $artifactPath): bool
    {
        return $this->update($id, [
            'status'        => PluginBuild::STATUS_SUCCESS,
            'log'           => $log,
            'artifact_path' => $artifactPath,
            'finished_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    public function markFailed(int $id, string $log): bool
    {
        return $this->update($id, [
            'status'      => PluginBuild::STATUS_FAILED,
            'log'         => $log,
            'finished_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
