<?php
declare(strict_types=1);

namespace core\plugin;

use ZipArchive;

/**
 * Pack plugins/<code>/ into runtime/plugin-packages/<code>-<version>.zip.
 * Zip root contains plugin.json so PluginInstaller::extract can install it.
 */
class PluginPacker
{
    private const SKIP_NAMES = ['.DS_Store', '.git', '.gitignore', 'node_modules', 'tests', '__tests__'];

    public static function packagesPath(): string
    {
        return rtrim(runtime_path(), '/\\') . DIRECTORY_SEPARATOR . 'plugin-packages' . DIRECTORY_SEPARATOR;
    }

    /**
     * @return array{path: string, frontend_files: int}
     */
    public static function pack(string $code, ?string $outputDir = null, bool $force = false): array
    {
        $code = trim($code);
        if ($code === '' || !preg_match('/^[a-z][a-z0-9_]*$/', $code)) {
            throw new PluginException("无效的插件 code：{$code}", PluginException::ERR_MANIFEST_INVALID);
        }

        $dir = rtrim(PluginManager::pluginsPath() . $code, '/\\');
        $manifestPath = $dir . DIRECTORY_SEPARATOR . 'plugin.json';
        if (!is_dir($dir) || !is_file($manifestPath)) {
            throw new PluginException("插件不存在或缺少 plugin.json：{$dir}", PluginException::ERR_MANIFEST_NOT_FOUND);
        }

        $manifest = PluginManifest::fromFile($manifestPath);
        if ($manifest->code !== $code) {
            throw new PluginException(
                "目录名 [{$code}] 与 plugin.json 的 code [{$manifest->code}] 不一致",
                PluginException::ERR_MANIFEST_INVALID
            );
        }

        $outDir = $outputDir !== null && $outputDir !== ''
            ? rtrim($outputDir, '/\\') . DIRECTORY_SEPARATOR
            : self::packagesPath();
        if (!is_dir($outDir) && !mkdir($outDir, 0775, true) && !is_dir($outDir)) {
            throw new PluginException("无法创建打包目录：{$outDir}", PluginException::ERR_DIR_EXISTS);
        }

        $finalPath = $outDir . $manifest->code . '-' . $manifest->version . '.zip';
        if (is_file($finalPath) && !$force) {
            throw new PluginException("目标已存在：{$finalPath}（用 --force 覆盖）", PluginException::ERR_DIR_EXISTS);
        }

        $tmpZip = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'plugin-pack-' . $code . '-' . uniqid('', true) . '.zip';
        $frontendFiles = self::buildZip($dir, $tmpZip, $code);

        if (is_file($finalPath)) {
            unlink($finalPath);
        }
        if (!rename($tmpZip, $finalPath) && !copy($tmpZip, $finalPath)) {
            @unlink($tmpZip);
            throw new PluginException("无法写入打包文件：{$finalPath}", PluginException::ERR_ZIP_INVALID);
        }
        @unlink($tmpZip);

        return ['path' => $finalPath, 'frontend_files' => $frontendFiles];
    }

    private static function buildZip(string $dir, string $zipPath, string $code): int
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new PluginException('无法创建临时 zip', PluginException::ERR_ZIP_INVALID);
        }

        try {
            self::addDir($zip, $dir);
            $frontendFiles = self::bundledFrontendCount($dir);
            if ($frontendFiles === 0) {
                $frontendFiles = self::addFrontend($zip, $code);
            }
        } catch (\Throwable $e) {
            $zip->close();
            @unlink($zipPath);
            throw $e;
        }
        $zip->close();

        if (!self::zipHasRootManifest($zipPath)) {
            @unlink($zipPath);
            throw new PluginException('打包结果缺少 plugin.json', PluginException::ERR_ZIP_INVALID);
        }

        return $frontendFiles;
    }

    private static function zipHasRootManifest(string $zipPath): bool
    {
        $check = new ZipArchive();
        if ($check->open($zipPath) !== true) {
            return false;
        }
        $ok = $check->locateName('plugin.json') !== false;
        $check->close();
        return $ok;
    }

    private static function addDir(ZipArchive $zip, string $absDir): void
    {
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($absDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($items as $item) {
            if ($item->isLink()) {
                continue;
            }
            $name = $item->getFilename();
            if (in_array($name, self::SKIP_NAMES, true) || str_ends_with($name, '.log')) {
                continue;
            }
            $rel = str_replace('\\', '/', substr($item->getPathname(), strlen($absDir) + 1));
            if ($rel === PluginFrontendDeployer::ZIP_PREFIX || str_starts_with($rel, PluginFrontendDeployer::ZIP_PREFIX . '/')) {
                continue;
            }
            foreach (explode('/', $rel) as $part) {
                if (in_array($part, self::SKIP_NAMES, true)) {
                    continue 2;
                }
            }
            if ($item->isDir()) {
                $zip->addEmptyDir($rel);
            } elseif ($item->isFile()) {
                $zip->addFile($item->getPathname(), $rel);
            }
        }
    }

    private static function bundledFrontendCount(string $dir): int
    {
        $count = 0;
        foreach (['admin', 'pc', 'uniapp'] as $tree) {
            $src = $dir . DIRECTORY_SEPARATOR . $tree;
            if (!is_dir($src)) {
                continue;
            }
            $items = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($items as $item) {
                if ($item->isFile() && !$item->isLink()) {
                    $count++;
                }
            }
        }
        return $count;
    }

    private static function addFrontend(ZipArchive $zip, string $code): int
    {
        $root = PluginFrontendDeployer::projectRoot();
        $count = 0;
        foreach (PluginFrontendMap::relativePaths($code) as $rel) {
            if (!PluginFrontendMap::isAllowedRelative($rel)) {
                continue;
            }
            $abs = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
            if (is_file($abs)) {
                $zip->addFile($abs, PluginFrontendDeployer::ZIP_PREFIX . '/' . $rel);
                $count++;
                continue;
            }
            if (!is_dir($abs)) {
                continue;
            }
            $items = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($abs, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($items as $item) {
                if ($item->isLink()) {
                    continue;
                }
                $name = $item->getFilename();
                if (in_array($name, self::SKIP_NAMES, true) || str_ends_with($name, '.log')) {
                    continue;
                }
                $inner = str_replace('\\', '/', substr($item->getPathname(), strlen($abs) + 1));
                $zipRel = PluginFrontendDeployer::ZIP_PREFIX . '/' . $rel . ($inner !== '' ? '/' . $inner : '');
                if ($item->isDir()) {
                    $zip->addEmptyDir($zipRel);
                } elseif ($item->isFile()) {
                    $zip->addFile($item->getPathname(), $zipRel);
                    $count++;
                }
            }
        }
        return $count;
    }
}
