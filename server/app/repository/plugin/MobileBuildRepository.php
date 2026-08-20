<?php
declare(strict_types=1);

namespace app\repository\plugin;

use app\model\plugin\MobileBuild;
use core\base\Repository;
use think\Model;

class MobileBuildRepository extends Repository
{
    protected function getModel(): Model
    {
        return new MobileBuild();
    }

    /**
     * @return array{list: list<array<string, mixed>>, total: int}
     */
    public function listPaginated(int $page, int $limit, ?string $pluginCode = null): array
    {
        $total = (int) $this->getModel()->db()
            ->when($pluginCode, fn ($q) => $q->where('plugin_code', $pluginCode))
            ->count();
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

    public function hasActive(): bool
    {
        return $this->getModel()->db()
            ->whereIn('status', [MobileBuild::STATUS_QUEUED, MobileBuild::STATUS_RUNNING])
            ->count() > 0;
    }

    public function markRunning(int $id): bool
    {
        return $this->updateIfStatus($id, [MobileBuild::STATUS_QUEUED], [
            'status'     => MobileBuild::STATUS_RUNNING,
            'started_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function markSuccess(int $id, string $log, string $artifactPath): bool
    {
        return $this->updateIfStatus($id, [MobileBuild::STATUS_RUNNING], [
            'status'        => MobileBuild::STATUS_SUCCESS,
            'log'           => $log,
            'artifact_path' => $artifactPath,
            'finished_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    public function markFailed(int $id, string $log): bool
    {
        return $this->updateIfStatus($id, [MobileBuild::STATUS_QUEUED, MobileBuild::STATUS_RUNNING], [
            'status'      => MobileBuild::STATUS_FAILED,
            'log'         => $log,
            'finished_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function markUploaded(int $id, string $resultJson): bool
    {
        return $this->update($id, [
            'status'             => MobileBuild::STATUS_UPLOADED,
            'upload_result_json' => $resultJson,
            'finished_at'        => date('Y-m-d H:i:s'),
        ]);
    }

    public function markCancelled(int $id, string $log): bool
    {
        return $this->updateIfStatus($id, [MobileBuild::STATUS_QUEUED, MobileBuild::STATUS_RUNNING], [
            'status'      => MobileBuild::STATUS_CANCELLED,
            'log'         => $log,
            'finished_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param list<int> $fromStatuses
     * @param array<string, mixed> $data
     */
    private function updateIfStatus(int $id, array $fromStatuses, array $data): bool
    {
        return $this->getModel()->db()
            ->where('id', $id)
            ->whereIn('status', $fromStatuses)
            ->update($data) > 0;
    }
}
