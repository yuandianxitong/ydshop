<?php
declare(strict_types=1);

namespace core\plugin;

/**
 * Copy `_frontend/` from an extracted plugin zip into the Shop project root,
 * and remove those files on uninstall.
 */
class PluginFrontendDeployer
{
    public const ZIP_PREFIX = '_frontend';

    public static function projectRoot(): string
    {
        return dirname(rtrim((string) root_path(), '/\\'));
    }

    /**
     * Deploy `_frontend/` from an extracted zip dir, then delete that folder
     * so it is not moved into plugins/<code>/.
     */
    public static function deployFromExtracted(string $tmpDir): int
    {
        $src = rtrim($tmpDir, '/\\') . DIRECTORY_SEPARATOR . self::ZIP_PREFIX;
        if (!is_dir($src)) {
            return 0;
        }
        $count = self::copyTree($src, self::projectRoot());
        self::removePath($src);
        return $count;
    }

    /**
     * 软链同步插件前端（不再整树拷贝进宿主源码）。
     */
    public static function deployFromPluginDir(string $code): int
    {
        $sync = PluginFrontendSync::sync($code);
        PluginPagesJsonMerger::merge($code);
        return $sync['count'];
    }

    public static function remove(string $code): void
    {
        PluginPagesJsonMerger::unmerge($code);
        $root = self::projectRoot();
        $pluginDir = rtrim(PluginManager::pluginsPath() . $code, '/\\') . DIRECTORY_SEPARATOR;
        $removed = false;
        foreach (['admin', 'pc', 'uniapp'] as $tree) {
            $src = $pluginDir . $tree;
            if (!is_dir($src)) {
                continue;
            }
            $removed = true;
            self::removeCopiedTree($src, $root . DIRECTORY_SEPARATOR . $tree, $tree . '/');
        }
        if ($removed) {
            return;
        }
        foreach (PluginFrontendMap::relativePaths($code) as $rel) {
            if (!PluginFrontendMap::isAllowedRelative($rel)) {
                continue;
            }
            self::removePath($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel));
        }
    }

    private static function deployMapped(string $code): int
    {
        $root = self::projectRoot();
        $count = 0;
        foreach (PluginFrontendMap::relativePaths($code) as $rel) {
            if (!PluginFrontendMap::isAllowedRelative($rel)) {
                continue;
            }
            $abs = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
            if (is_file($abs) || is_dir($abs)) {
                $count++;
            }
        }
        return $count;
    }

    private static function copyTree(string $from, string $to, string $relPrefix = ''): int
    {
        $count = 0;
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($from, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($items as $item) {
            if ($item->isLink()) {
                continue;
            }
            $rel = str_replace('\\', '/', substr($item->getPathname(), strlen($from) + 1));
            $check = $relPrefix . $rel;
            if ($relPrefix !== '' && !PluginFrontendMap::isAllowedRelative($check)) {
                continue;
            }
            if ($relPrefix === '' && !PluginFrontendMap::isAllowedRelative($rel)) {
                continue;
            }
            $dest = $to . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
            if ($item->isDir()) {
                if (!is_dir($dest) && !@mkdir($dest, 0755, true) && !is_dir($dest)) {
                    throw new PluginException("无法创建前端目录：{$dest}", PluginException::ERR_DIR_EXISTS);
                }
                continue;
            }
            if (!$item->isFile()) {
                continue;
            }
            $parent = dirname($dest);
            if (!is_dir($parent) && !@mkdir($parent, 0755, true) && !is_dir($parent)) {
                throw new PluginException("无法创建前端目录：{$parent}", PluginException::ERR_DIR_EXISTS);
            }
            if (!@copy($item->getPathname(), $dest)) {
                throw new PluginException("无法写入前端文件：{$dest}", PluginException::ERR_DIR_EXISTS);
            }
            $count++;
        }
        return $count;
    }

    private static function removeCopiedTree(string $from, string $to, string $relPrefix): void
    {
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($from, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            if ($item->isLink() || $item->isDir()) {
                continue;
            }
            $rel = str_replace('\\', '/', substr($item->getPathname(), strlen($from) + 1));
            if (!PluginFrontendMap::isAllowedRelative($relPrefix . $rel)) {
                continue;
            }
            $dest = $to . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
            if (is_file($dest) || is_link($dest)) {
                @unlink($dest);
            }
        }
    }

    private static function removePath(string $path): void
    {
        if (is_file($path) || is_link($path)) {
            @unlink($path);
            return;
        }
        if (!is_dir($path)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
        @rmdir($path);
    }
}
