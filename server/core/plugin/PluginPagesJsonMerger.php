<?php
declare(strict_types=1);

namespace core\plugin;

/**
 * 将 plugin.json uniapp.subPackages 合并进宿主 pages.json；卸载时按声明撤回。
 * 已存在的 path 保持不动（幂等）。撤回时若对应 vue 仍在宿主则跳过（公开仓 stub）。
 */
class PluginPagesJsonMerger
{
    public static function pagesJsonPath(): string
    {
        return PluginFrontendDeployer::projectRoot()
            . DIRECTORY_SEPARATOR . 'uniapp'
            . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . 'pages.json';
    }

    /**
     * 声明的分包里是否有宿主 pages.json 尚未收录的页（装完需要重发小程序）。
     */
    public static function wouldAddPages(string $code): bool
    {
        $packages = self::declaredSubPackages($code);
        if ($packages === []) {
            return self::hasUnregisteredVue($code);
        }
        $registered = self::registeredPaths();
        foreach ($packages as $pkg) {
            $root = trim((string) $pkg['root'], '/');
            foreach ($pkg['pages'] as $page) {
                $path = (string) ($page['path'] ?? '');
                if ($path === '') {
                    continue;
                }
                $full = $root . '/' . $path;
                if (!isset($registered[$full])) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function merge(string $code): bool
    {
        $packages = self::declaredSubPackages($code);
        if ($packages === []) {
            return false;
        }
        return self::writePages(function (array $data) use ($packages): array {
            $data['subPackages'] = $data['subPackages'] ?? [];
            foreach ($packages as $pkg) {
                $data['subPackages'] = self::mergePackage($data['subPackages'], $pkg);
            }
            return $data;
        });
    }

    public static function unmerge(string $code): bool
    {
        $packages = self::declaredSubPackages($code);
        if ($packages === []) {
            return false;
        }
        $root = PluginFrontendDeployer::projectRoot() . DIRECTORY_SEPARATOR . 'uniapp'
            . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
        return self::writePages(function (array $data) use ($packages, $root): array {
            $subs = $data['subPackages'] ?? [];
            foreach ($packages as $pkg) {
                $subs = self::unmergePackage($subs, $pkg, $root);
            }
            $data['subPackages'] = array_values($subs);
            return $data;
        });
    }

    /** @return array<string, true> */
    private static function registeredPaths(): array
    {
        $path = self::pagesJsonPath();
        if (!is_file($path)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($path), true);
        if (!is_array($data)) {
            return [];
        }
        $out = [];
        foreach ((array) ($data['pages'] ?? []) as $page) {
            $p = (string) ($page['path'] ?? '');
            if ($p !== '') {
                $out[$p] = true;
            }
        }
        foreach ((array) ($data['subPackages'] ?? []) as $pkg) {
            $root = trim((string) ($pkg['root'] ?? ''), '/');
            foreach ((array) ($pkg['pages'] ?? []) as $page) {
                $p = (string) ($page['path'] ?? '');
                if ($p === '') {
                    continue;
                }
                $out[$root . '/' . $p] = true;
            }
        }
        return $out;
    }

    private static function hasUnregisteredVue(string $code): bool
    {
        $dir = rtrim(PluginManager::pluginsPath() . $code, '/\\') . '/uniapp/src';
        if (!is_dir($dir)) {
            return false;
        }
        $registered = self::registeredPaths();
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );
        $base = strlen($dir) + 1;
        foreach ($items as $item) {
            if (!$item->isFile() || !str_ends_with($item->getFilename(), '.vue')) {
                continue;
            }
            $rel = str_replace('\\', '/', substr($item->getPathname(), $base));
            $full = preg_replace('/\.vue$/', '', $rel) ?: '';
            if ($full !== '' && !isset($registered[$full])) {
                return true;
            }
        }
        return false;
    }

    /** @return list<array{root: string, pages: list<array>}> */
    private static function declaredSubPackages(string $code): array
    {
        $dir = rtrim(PluginManager::pluginsPath() . $code, '/\\') . DIRECTORY_SEPARATOR;
        $file = $dir . 'plugin.json';
        if (!is_file($file)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($file), true);
        $uniapp = is_array($data['uniapp'] ?? null) ? $data['uniapp'] : [];
        $out = [];
        foreach ((array) ($uniapp['subPackages'] ?? []) as $pkg) {
            if (!is_array($pkg) || empty($pkg['root']) || empty($pkg['pages']) || !is_array($pkg['pages'])) {
                continue;
            }
            $out[] = [
                'root'  => (string) $pkg['root'],
                'pages' => array_values($pkg['pages']),
            ];
        }
        return $out;
    }

    /**
     * @param list<array<string, mixed>> $subs
     * @param array{root: string, pages: list<array>} $pkg
     * @return list<array<string, mixed>>
     */
    private static function mergePackage(array $subs, array $pkg): array
    {
        foreach ($subs as $i => $existing) {
            if (($existing['root'] ?? '') !== $pkg['root']) {
                continue;
            }
            $pages = is_array($existing['pages'] ?? null) ? $existing['pages'] : [];
            $have  = [];
            foreach ($pages as $page) {
                $have[(string) ($page['path'] ?? '')] = true;
            }
            foreach ($pkg['pages'] as $page) {
                $path = (string) ($page['path'] ?? '');
                if ($path === '' || isset($have[$path])) {
                    continue;
                }
                $pages[] = $page;
                $have[$path] = true;
            }
            $subs[$i]['pages'] = $pages;
            return $subs;
        }
        $subs[] = $pkg;
        return $subs;
    }

    /**
     * @param list<array<string, mixed>> $subs
     * @param array{root: string, pages: list<array>} $pkg
     * @return list<array<string, mixed>>
     */
    private static function unmergePackage(array $subs, array $pkg, string $uniappSrc): array
    {
        $remove = [];
        foreach ($pkg['pages'] as $page) {
            $path = (string) ($page['path'] ?? '');
            if ($path === '') {
                continue;
            }
            $vue = $uniappSrc . str_replace('/', DIRECTORY_SEPARATOR, $pkg['root'] . '/' . $path) . '.vue';
            if (is_file($vue)) {
                continue;
            }
            $remove[$path] = true;
        }
        if ($remove === []) {
            return $subs;
        }
        foreach ($subs as $i => $existing) {
            if (($existing['root'] ?? '') !== $pkg['root']) {
                continue;
            }
            $pages = [];
            foreach ((array) ($existing['pages'] ?? []) as $page) {
                $path = (string) ($page['path'] ?? '');
                if ($path !== '' && isset($remove[$path])) {
                    continue;
                }
                $pages[] = $page;
            }
            if ($pages === []) {
                unset($subs[$i]);
            } else {
                $subs[$i]['pages'] = $pages;
            }
        }
        return $subs;
    }

    /** @param callable(array): array $mutator */
    private static function writePages(callable $mutator): bool
    {
        $path = self::pagesJsonPath();
        if (!is_file($path)) {
            return false;
        }
        $raw = (string) file_get_contents($path);
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new PluginException('pages.json 不是合法 JSON', PluginException::ERR_MANIFEST_INVALID);
        }
        $next = $mutator($data);
        $json = json_encode($next, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($json === false) {
            throw new PluginException('无法编码 pages.json', PluginException::ERR_MANIFEST_INVALID);
        }
        $json .= "\n";
        if ($json === $raw) {
            return false;
        }
        if (file_put_contents($path, $json) === false) {
            throw new PluginException('无法写入 pages.json', PluginException::ERR_DIR_EXISTS);
        }
        return true;
    }
}
