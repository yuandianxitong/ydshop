<?php
declare(strict_types=1);

namespace app\service\plugin;

use app\job\PluginBuildJob;
use app\model\plugin\PluginBuild;
use app\repository\plugin\PluginBuildRepository;
use core\base\Service;
use core\exception\BusinessException;
use core\plugin\PluginBuilder;
use core\plugin\PluginManager;
use core\queue\QueueManager;
use think\facade\App;

class PluginBuildService extends Service
{
    public const QUEUE = 'frontend-builds';

    protected PluginBuildRepository $buildRepo;
    protected PluginBuilder $builder;

    /**
     * 生产默认入队；开发机（app_debug）只软链，除非 FRONTEND_CLOUD_BUILD=1。
     */
    public static function shouldCloudBuild(): bool
    {
        $forced = env('FRONTEND_CLOUD_BUILD');
        if ($forced !== null && $forced !== '') {
            return filter_var($forced, FILTER_VALIDATE_BOOLEAN);
        }
        try {
            return !app()->isDebug();
        } catch (\Throwable) {
            return true;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function enqueueForPlugin(string $code, string $trigger): array
    {
        $rows = [];
        if (!self::shouldCloudBuild()) {
            $rows[] = $this->insertSkipped(PluginBuild::TARGET_ADMIN, $trigger, $code);
            $pcDir = rtrim(PluginManager::pluginsPath() . $code, '/\\') . '/pc';
            if (is_dir($pcDir)) {
                $rows[] = $this->insertSkipped(PluginBuild::TARGET_PC, $trigger, $code);
            }
            return $rows;
        }
        $rows[] = $this->enqueue(PluginBuild::TARGET_ADMIN, $trigger, $code);
        $pcDir = rtrim(PluginManager::pluginsPath() . $code, '/\\') . '/pc';
        if (is_dir($pcDir)) {
            $rows[] = $this->enqueue(PluginBuild::TARGET_PC, $trigger, $code);
        }
        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    public function enqueue(string $target, string $trigger, ?string $pluginCode = null, ?int $operatorId = null): array
    {
        if (!in_array($target, [PluginBuild::TARGET_ADMIN, PluginBuild::TARGET_PC], true)) {
            throw new BusinessException("invalid target: {$target}", 422);
        }
        $row = $this->buildRepo->create([
            'target'      => $target,
            'trigger'     => $trigger,
            'plugin_code' => $pluginCode,
            'status'      => PluginBuild::STATUS_QUEUED,
            'log'         => '',
            'operator_id' => $operatorId,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        QueueManager::push(PluginBuildJob::class, ['build_id' => (int) $row['id']], self::QUEUE);
        return $row;
    }

    public function run(int $buildId): void
    {
        $build = $this->buildRepo->find($buildId);
        if (!$build || (int) $build['status'] !== PluginBuild::STATUS_QUEUED) {
            return;
        }
        $this->buildRepo->markRunning($buildId);

        $target = (string) $build['target'];
        $rootPath = App::getRootPath();
        $sourceDir = realpath($rootPath . '../' . $target) ?: '';
        $publicDir = $rootPath . 'public';
        if ($sourceDir === '' || !is_dir($sourceDir)) {
            $this->buildRepo->markFailed($buildId, "[service] source dir not found: {$rootPath}../{$target}");
            return;
        }

        try {
            $result = $this->builder->build($target, $sourceDir, $publicDir);
            if ($result['exitCode'] === 0) {
                $this->buildRepo->markSuccess($buildId, $result['log'], $result['artifactPath']);
            } else {
                $this->buildRepo->markFailed($buildId, $result['log']);
            }
        } catch (\Throwable $e) {
            $this->buildRepo->markFailed($buildId, '[exception] ' . $e->getMessage());
        }
    }

    /**
     * @return array{list: list<array<string, mixed>>, total: int}
     */
    public function list(int $page, int $limit, ?string $pluginCode = null): array
    {
        return $this->buildRepo->listPaginated($page, $limit, $pluginCode);
    }

    /**
     * @param list<int> $ids
     * @return list<array<string, mixed>>
     */
    public function findByIds(array $ids): array
    {
        return $this->buildRepo->findByIds($ids);
    }

    public function rebuild(string $target, ?int $operatorId = null): array
    {
        return $this->enqueue($target, 'manual', null, $operatorId);
    }

    /**
     * @return array<string, mixed>
     */
    private function insertSkipped(string $target, string $trigger, string $pluginCode): array
    {
        return $this->buildRepo->create([
            'target'      => $target,
            'trigger'     => $trigger,
            'plugin_code' => $pluginCode,
            'status'      => PluginBuild::STATUS_SKIPPED,
            'log'         => '开发环境已软链，Vite 现场编译；生产关闭 debug 或设 FRONTEND_CLOUD_BUILD=1 后入队',
            'created_at'  => date('Y-m-d H:i:s'),
            'finished_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
