<?php
declare(strict_types=1);

namespace core\plugin;

/**
 * PHP 侧软链同步（安装路径，不 exec Node）。与 scripts/sync-plugins.mjs 同构。
 */
class PluginFrontendSync
{
    /**
     * @return array{count: int, links: list<string>}
     */
    public static function sync(string $code): array
    {
        $code = trim($code);
        if ($code === '' || !preg_match('/^[a-z][a-z0-9_]*$/', $code)) {
            return ['count' => 0, 'links' => []];
        }
        $links = [];
        $links = array_merge($links, self::syncAdmin($code));
        $links = array_merge($links, self::syncPc($code));
        $links = array_merge($links, self::syncUniapp($code));
        return ['count' => count($links), 'links' => $links];
    }

    public static function remove(string $code): void
    {
        $root = PluginFrontendDeployer::projectRoot();
        $adminSrc = $root . '/admin/src';
        self::removePath($adminSrc . '/views/plugin-apps/' . $code);
        foreach (['api', 'locales', 'components'] as $mod) {
            self::removePath($adminSrc . '/' . $mod . '/plugins/' . $code);
        }
        self::removePath($root . '/pc/plugins/' . $code);
        self::removeAdminCompat($code);
        self::removePcCompat($code);
    }

    private static function removeAdminCompat(string $code): void
    {
        $pluginSrc = rtrim(PluginManager::pluginsPath() . $code, '/\\') . '/admin/src';
        $adminSrc = PluginFrontendDeployer::projectRoot() . '/admin/src';
        foreach (['api', 'components'] as $mod) {
            $from = $pluginSrc . '/' . $mod;
            if (!is_dir($from)) {
                continue;
            }
            foreach (self::walkFiles($from) as $rel) {
                $dest = $adminSrc . '/' . $mod . '/' . $rel;
                if (is_link($dest)) {
                    self::removePath($dest);
                }
            }
        }
    }

    private static function removePcCompat(string $code): void
    {
        $pcDir = rtrim(PluginManager::pluginsPath() . $code, '/\\') . '/pc';
        $pcRoot = PluginFrontendDeployer::projectRoot() . '/pc';
        if (!is_dir($pcDir)) {
            return;
        }
        foreach (['pages', 'api', 'components'] as $mod) {
            $from = $pcDir . '/' . $mod;
            if (!is_dir($from)) {
                continue;
            }
            foreach (self::walkFiles($from) as $rel) {
                $dest = $pcRoot . '/' . $mod . '/' . $rel;
                if (is_link($dest)) {
                    self::removePath($dest);
                }
            }
        }
    }

    /** @return list<string> */
    private static function syncAdmin(string $code): array
    {
        $pluginSrc = rtrim(PluginManager::pluginsPath() . $code, '/\\') . '/admin/src';
        $adminSrc = PluginFrontendDeployer::projectRoot() . '/admin/src';
        if (!is_dir($pluginSrc) || !is_dir($adminSrc)) {
            return [];
        }
        $links = [];
        foreach (['views', 'api', 'locales', 'components'] as $mod) {
            $from = $pluginSrc . '/' . $mod;
            if (!is_dir($from)) {
                continue;
            }
            $bucket = $mod === 'views' ? 'plugin-apps' : 'plugins';
            $to = $adminSrc . '/' . $mod . '/' . $bucket . '/' . $code;
            if (self::linkOrCopy($from, $to)) {
                $links[] = $to;
            }
            if ($mod === 'api' || $mod === 'components') {
                foreach (self::walkFiles($from) as $rel) {
                    $dest = $adminSrc . '/' . $mod . '/' . $rel;
                    if (is_file($dest) && !is_link($dest)) {
                        continue;
                    }
                    if (self::linkOrCopy($from . '/' . $rel, $dest)) {
                        $links[] = $dest;
                    }
                }
            }
        }
        return $links;
    }

    /** @return list<string> */
    private static function syncPc(string $code): array
    {
        $pcDir = rtrim(PluginManager::pluginsPath() . $code, '/\\') . '/pc';
        $pcRoot = PluginFrontendDeployer::projectRoot() . '/pc';
        if (!is_dir($pcDir) || !is_dir($pcRoot)) {
            return [];
        }
        $links = [];
        $bundle = $pcRoot . '/plugins/' . $code;
        if (self::linkOrCopy($pcDir, $bundle)) {
            $links[] = $bundle;
        }
        foreach (['pages', 'api', 'components'] as $mod) {
            $from = $pcDir . '/' . $mod;
            if (!is_dir($from)) {
                continue;
            }
            foreach (self::walkFiles($from) as $rel) {
                $dest = $pcRoot . '/' . $mod . '/' . $rel;
                if ((is_file($dest) || is_dir($dest)) && !is_link($dest)) {
                    continue;
                }
                if (self::linkOrCopy($from . '/' . $rel, $dest)) {
                    $links[] = $dest;
                }
            }
        }
        return $links;
    }

    /** @return list<string> */
    private static function syncUniapp(string $code): array
    {
        $fromRoot = rtrim(PluginManager::pluginsPath() . $code, '/\\') . '/uniapp';
        $toRoot = PluginFrontendDeployer::projectRoot() . '/uniapp';
        if (!is_dir($fromRoot) || !is_dir($toRoot)) {
            return [];
        }
        $links = [];
        foreach (self::walkFiles($fromRoot) as $rel) {
            $check = str_replace('\\', '/', $rel);
            if (!str_starts_with($check, 'src/')) {
                continue;
            }
            $dest = $toRoot . '/' . $rel;
            if ((is_file($dest) || is_dir($dest)) && !is_link($dest)) {
                continue;
            }
            if (self::linkOrCopy($fromRoot . '/' . $rel, $dest)) {
                $links[] = $dest;
            }
        }
        return $links;
    }

    private static function linkOrCopy(string $from, string $to): bool
    {
        $parent = dirname($to);
        if (!is_dir($parent) && !@mkdir($parent, 0755, true) && !is_dir($parent)) {
            return false;
        }
        self::removePath($to);
        $rel = self::relative($parent, $from);
        if (\function_exists('symlink')) {
            try {
                if (@\symlink($rel, $to)) {
                    return true;
                }
            } catch (\Throwable) {
                // disable_functions 含 symlink 时是 Error，@ 压不住
            }
        }
        if (is_dir($from)) {
            return self::copyDir($from, $to);
        }
        return @copy($from, $to);
    }

    private static function copyDir(string $from, string $to): bool
    {
        if (!is_dir($to) && !@mkdir($to, 0755, true) && !is_dir($to)) {
            return false;
        }
        $ok = true;
        foreach (self::walkFiles($from) as $rel) {
            $dest = $to . '/' . $rel;
            $parent = dirname($dest);
            if (!is_dir($parent)) {
                @mkdir($parent, 0755, true);
            }
            $ok = @copy($from . '/' . $rel, $dest) && $ok;
        }
        return $ok;
    }

    /** @return list<string> */
    private static function walkFiles(string $dir): array
    {
        $out = [];
        if (!is_dir($dir)) {
            return $out;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );
        $base = strlen($dir) + 1;
        foreach ($items as $item) {
            if ($item->isFile() || $item->isLink()) {
                $out[] = str_replace('\\', '/', substr($item->getPathname(), $base));
            }
        }
        return $out;
    }

    private static function relative(string $from, string $to): string
    {
        $from = explode('/', str_replace('\\', '/', realpath($from) ?: $from));
        $to   = explode('/', str_replace('\\', '/', realpath($to) ?: $to));
        while ($from && $to && $from[0] === $to[0]) {
            array_shift($from);
            array_shift($to);
        }
        return str_repeat('../', count($from)) . implode('/', $to);
    }

    private static function removePath(string $path): void
    {
        if (is_link($path) || is_file($path)) {
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
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($path);
    }
}
