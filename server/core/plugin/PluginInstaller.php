<?php
declare(strict_types=1);

namespace core\plugin;

use ZipArchive;

/**
 * Handles the filesystem side of plugin install: unpack a zip into a temp
 * directory, validate the manifest, then move it into plugins/<code>/.
 * The DB side (plugins row, menus/permissions, install logs) is done by
 * PluginManager::install — keep these concerns separated.
 */
class PluginInstaller
{
    /**
     * Extract a plugin zip into $targetDir and return the parsed manifest.
     * Accepts zips that either contain plugin.json at the root, or one top-level
     * directory wrapper that itself contains plugin.json — the latter gets
     * flattened so callers always see the manifest at $targetDir/plugin.json.
     */
    public static function extract(string $zipPath, string $targetDir): PluginManifest
    {
        if (!is_file($zipPath)) {
            throw new PluginException("zip 不存在：$zipPath", PluginException::ERR_ZIP_INVALID);
        }

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $zip    = new ZipArchive();
        $opened = $zip->open($zipPath);
        if ($opened !== true) {
            throw new PluginException("zip 打开失败 (code=$opened)", PluginException::ERR_ZIP_INVALID);
        }

        // Manifest must be present at root OR at one-level depth.
        $hasManifestAtRoot   = $zip->locateName('plugin.json') !== false;
        $hasManifestAnywhere = $hasManifestAtRoot;
        if (!$hasManifestAtRoot) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if ($name !== false && substr_count($name, '/') === 1 && str_ends_with($name, '/plugin.json')) {
                    $hasManifestAnywhere = true;
                    break;
                }
            }
        }
        if (!$hasManifestAnywhere) {
            $zip->close();
            throw new PluginException('zip 缺少 plugin.json', PluginException::ERR_ZIP_INVALID);
        }

        $zip->extractTo($targetDir);
        $zip->close();

        // If the zip wrapped its contents in a single top-level dir, flatten it.
        $manifestPath = $targetDir . '/plugin.json';
        if (!is_file($manifestPath)) {
            $entries = array_values(array_diff(scandir($targetDir), ['.', '..']));
            if (count($entries) === 1 && is_dir($targetDir . '/' . $entries[0])) {
                $sub = $targetDir . '/' . $entries[0];
                foreach (scandir($sub) as $f) {
                    if ($f === '.' || $f === '..') {
                        continue;
                    }
                    rename($sub . '/' . $f, $targetDir . '/' . $f);
                }
                rmdir($sub);
            }
        }

        return PluginManifest::fromFile($targetDir . '/plugin.json');
    }

    /**
     * Move a fully-extracted plugin directory into plugins/<code>/.
     * rename() across filesystems (e.g. /tmp tmpfs → site disk) cannot copy a
     * directory; fall back to recursive copy + delete.
     */
    public static function moveToPluginsDir(string $tmpDir, string $code): string
    {
        $target = rtrim(PluginManager::pluginsPath(), '/\\') . DIRECTORY_SEPARATOR . $code;
        if (is_dir($target)) {
            throw new PluginException("目录已存在：$target", PluginException::ERR_DIR_EXISTS);
        }
        if (self::tryRenameDir($tmpDir, $target)) {
            return $target;
        }
        if (!self::copyDir($tmpDir, $target)) {
            self::removeDir($target);
            throw new PluginException("移动失败：$tmpDir → $target", PluginException::ERR_DIR_EXISTS);
        }
        self::removeDir($tmpDir);
        return $target;
    }

    /**
     * Replace plugins/<code>/ with an extracted directory (upgrade / reinstall).
     */
    public static function replacePluginsDir(string $tmpDir, string $code): string
    {
        $target = rtrim(PluginManager::pluginsPath(), '/\\') . DIRECTORY_SEPARATOR . $code;
        $backup = $target . '.bak-' . uniqid('', true);
        $hadTarget = is_dir($target);
        if ($hadTarget && !self::tryRenameDir($target, $backup)) {
            self::removeDir($target);
            $hadTarget = false;
        }
        try {
            $path = self::moveToPluginsDir($tmpDir, $code);
            if (is_dir($backup)) {
                self::removeDir($backup);
            }
            return $path;
        } catch (\Throwable $e) {
            if ($hadTarget && is_dir($backup) && !is_dir($target)) {
                self::tryRenameDir($backup, $target);
            }
            throw $e;
        }
    }

    private static function tryRenameDir(string $from, string $to): bool
    {
        try {
            return @rename($from, $to);
        } catch (\Throwable) {
            return false;
        }
    }

    private static function copyDir(string $from, string $to): bool
    {
        if (!is_dir($from)) {
            return false;
        }
        if (!is_dir($to) && !@mkdir($to, 0755, true) && !is_dir($to)) {
            return false;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($from, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($items as $item) {
            $rel = substr($item->getPathname(), strlen($from) + 1);
            $dest = $to . DIRECTORY_SEPARATOR . $rel;
            if ($item->isDir()) {
                if (!is_dir($dest) && !@mkdir($dest, 0755, true) && !is_dir($dest)) {
                    return false;
                }
            } elseif ($item->isFile()) {
                if (!@copy($item->getPathname(), $dest)) {
                    return false;
                }
            }
        }
        return true;
    }

    private static function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
        @rmdir($dir);
    }
}
