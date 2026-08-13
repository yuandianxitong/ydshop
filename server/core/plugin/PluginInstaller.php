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
     * Throws if the target already exists or rename() fails.
     */
    public static function moveToPluginsDir(string $tmpDir, string $code): string
    {
        $target = base_path() . 'plugins' . DIRECTORY_SEPARATOR . $code;
        if (is_dir($target)) {
            throw new PluginException("目录已存在：$target", PluginException::ERR_DIR_EXISTS);
        }
        if (!rename($tmpDir, $target)) {
            throw new PluginException("移动失败：$tmpDir → $target", PluginException::ERR_DIR_EXISTS);
        }
        return $target;
    }
}
