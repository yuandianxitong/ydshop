<?php
declare(strict_types=1);

namespace app\service\plugin;

use app\repository\plugin\MobileBuildRepository;
use app\repository\plugin\PluginBuildRepository;
use core\base\Service;
use core\exception\BusinessException;
use think\facade\App;

/**
 * 同机 4G 上同时只允许一个前端构建（admin/PC 或 H5/小程序）。
 */
class FrontendBuildCoordinator extends Service
{
    public const LOCK_NAME = 'frontend-build.lock';

    protected MobileBuildRepository $mobileBuildRepository;
    protected PluginBuildRepository $pluginBuildRepository;

    public function hasActive(): bool
    {
        return $this->mobileBuildRepository->hasActive() || $this->pluginBuildRepository->hasActive();
    }

    public function assertIdle(): void
    {
        if ($this->hasActive()) {
            throw new BusinessException('已有编译任务进行中，请等待完成或取消后再试', 422);
        }
    }

    /**
     * @template T
     * @param callable(): T $fn
     * @return T
     */
    public function withLock(callable $fn): mixed
    {
        $dir = rtrim((string) App::getRuntimePath(), '/\\');
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('cannot create runtime dir for frontend-build.lock');
        }
        $path = $dir . DIRECTORY_SEPARATOR . self::LOCK_NAME;
        $fp = fopen($path, 'c');
        if ($fp === false) {
            throw new \RuntimeException('cannot open frontend-build.lock');
        }
        try {
            if (!flock($fp, LOCK_EX)) {
                throw new \RuntimeException('cannot lock frontend-build.lock');
            }
            return $fn();
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }
}
