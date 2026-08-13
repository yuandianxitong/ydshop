<?php
declare(strict_types=1);

namespace app\repository\plugin;

use app\model\plugin\Plugin;
use app\model\plugin\PluginInstallLog;
use core\base\Repository;
use think\Model;

class PluginRepository extends Repository
{
    protected function getModel(): Model
    {
        return new Plugin();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAll(): array
    {
        return Plugin::order('category')
            ->order('created_at', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByCode(string $code): ?array
    {
        $row = Plugin::where('code', $code)->find();
        return $row ? $row->toArray() : null;
    }

    /**
     * Paged audit logs, optionally narrowed to one plugin_code.
     *
     * @return array<string, mixed>
     */
    public function logs(?string $code, int $page, int $size): array
    {
        $query = PluginInstallLog::order('created_at', 'desc');
        if ($code !== null && $code !== '') {
            $query->where('plugin_code', $code);
        }
        return $query->paginate(['page' => $page, 'list_rows' => $size])->toArray();
    }
}
