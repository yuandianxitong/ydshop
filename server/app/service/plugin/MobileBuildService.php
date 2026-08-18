<?php
declare(strict_types=1);

namespace app\service\plugin;

use app\job\MobileBuildJob;
use app\model\plugin\MobileBuild;
use app\repository\plugin\MobileBuildRepository;
use core\base\Service;
use core\exception\BusinessException;
use core\mobile\UniBuildRunner;
use core\plugin\PluginPagesJsonMerger;
use core\queue\QueueManager;
use think\facade\App;

class MobileBuildService extends Service
{
    public const QUEUE = 'frontend-builds';

    protected MobileBuildRepository $buildRepo;
    protected UniBuildRunner $runner;

    /**
     * @return list<array<string, mixed>>
     */
    public function enqueueForPlugin(string $code, string $trigger): array
    {
        if (!PluginPagesJsonMerger::wouldAddPages($code)) {
            return [
                $this->insertSkipped($code, MobileBuild::PLATFORM_H5, $trigger),
                $this->insertSkipped($code, MobileBuild::PLATFORM_MP_WEIXIN, $trigger),
            ];
        }
        if (!PluginBuildService::shouldCloudBuild()) {
            return [
                $this->insertSkipped(
                    $code,
                    MobileBuild::PLATFORM_H5,
                    $trigger,
                    '开发机请本机编译 H5；生产关闭 debug 或设 FRONTEND_CLOUD_BUILD=1 后入队'
                ),
                $this->insertSkipped(
                    $code,
                    MobileBuild::PLATFORM_MP_WEIXIN,
                    $trigger,
                    '开发机请本机 pnpm build:mp-weixin 后自行上传'
                ),
            ];
        }
        return [
            $this->enqueue(MobileBuild::PLATFORM_H5, $trigger, $code),
            $this->enqueue(MobileBuild::PLATFORM_MP_WEIXIN, $trigger, $code),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function enqueue(string $platform, string $trigger, ?string $pluginCode = null, ?int $operatorId = null): array
    {
        if (!in_array($platform, [MobileBuild::PLATFORM_H5, MobileBuild::PLATFORM_MP_WEIXIN], true)) {
            throw new BusinessException("invalid platform: {$platform}", 422);
        }
        $row = $this->buildRepo->create([
            'platform'    => $platform,
            'trigger'     => $trigger,
            'plugin_code' => $pluginCode,
            'status'      => MobileBuild::STATUS_QUEUED,
            'log'         => '',
            'operator_id' => $operatorId,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        QueueManager::push(MobileBuildJob::class, ['build_id' => (int) $row['id']], self::QUEUE);
        return $row;
    }

    public function run(int $buildId): void
    {
        $build = $this->buildRepo->find($buildId);
        if (!$build || (int) $build['status'] !== MobileBuild::STATUS_QUEUED) {
            return;
        }
        $this->buildRepo->markRunning($buildId);

        $uniappDir = realpath(App::getRootPath() . '../uniapp') ?: '';
        if ($uniappDir === '' || !is_dir($uniappDir)) {
            $this->buildRepo->markFailed($buildId, '[service] uniapp dir not found');
            return;
        }

        try {
            $result = $this->runner->run($uniappDir, (string) $build['platform']);
            if (!$result['success']) {
                $this->buildRepo->markFailed($buildId, $result['log']);
                return;
            }
            if ((string) $build['platform'] === MobileBuild::PLATFORM_H5) {
                $this->promoteH5($result['artifactPath']);
            }
            $this->buildRepo->markSuccess($buildId, $result['log'], $result['artifactPath']);
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

    private function insertSkipped(string $code, string $platform, string $trigger, ?string $log = null): array
    {
        return $this->buildRepo->create([
            'platform'    => $platform,
            'trigger'     => $trigger,
            'plugin_code' => $code,
            'status'      => MobileBuild::STATUS_SKIPPED,
            'log'         => $log ?? '官方预置页已在发行小程序中，安装不触发 C 端编译',
            'created_at'  => date('Y-m-d H:i:s'),
            'finished_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function promoteH5(string $artifact): void
    {
        $final = rtrim((string) App::getRootPath(), '/\\') . '/public/mobile';
        $tmp = $final . '.build-tmp';
        $this->rrmdir($tmp);
        $this->copyTree($artifact, $tmp);
        $bak = $final . '.bak-' . time();
        if (is_dir($final)) {
            @rename($final, $bak);
        }
        if (!@rename($tmp, $final) && is_dir($bak)) {
            @rename($bak, $final);
        }
        if (is_dir($bak)) {
            $this->rrmdir($bak);
        }
    }

    private function copyTree(string $from, string $to): void
    {
        if (!is_dir($to)) {
            @mkdir($to, 0755, true);
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($from, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($items as $item) {
            $rel = substr($item->getPathname(), strlen($from) + 1);
            $dest = $to . DIRECTORY_SEPARATOR . $rel;
            if ($item->isDir()) {
                if (!is_dir($dest)) {
                    @mkdir($dest, 0755, true);
                }
                continue;
            }
            $parent = dirname($dest);
            if (!is_dir($parent)) {
                @mkdir($parent, 0755, true);
            }
            @copy($item->getPathname(), $dest);
        }
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $f) {
            $p = $dir . DIRECTORY_SEPARATOR . $f;
            is_dir($p) ? $this->rrmdir($p) : @unlink($p);
        }
        @rmdir($dir);
    }
}
