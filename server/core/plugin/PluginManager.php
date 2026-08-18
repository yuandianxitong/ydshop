<?php
declare(strict_types=1);

namespace core\plugin;

use app\model\plugin\Plugin;
use app\model\plugin\PluginInstallLog;
use app\model\plugin\PluginMigration;
use app\model\system\Menu;
use app\model\system\Permission;
use core\database\SqlRunner;
use core\license\MarketplaceEntitlement;
use PDO;
use think\facade\Db;

/**
 * Plugin manager — boots plugins whose row in the plugins table has
 * status=installed. Each plugin's routes/events are loaded from its manifest.
 *
 * plugin.json (new format) example:
 * {
 *   "code":        "coupon",
 *   "name":        "优惠券",
 *   "version":     "1.0.0",
 *   "category":    "core",
 *   "parent_menu": "Marketing",
 *   "routes":      { "admin": "route/admin.php", "api": "route/api.php" },
 *   "events":      "event.php",
 *   "hooks":       ["CouponDiscountHook"]
 * }
 *
 * PSR-4 class resolution:
 *   plugins\{code}\listener\{Class}   for event listeners (registered via event.php)
 *   plugins\{code}\hook\{Class}       for hook handlers
 *   plugins\{code}\Plugin             for the main plugin class (optional)
 */
class PluginManager
{
    /** @var array{frontend?: int, mode?: string, admin_pc?: list<array<string, mixed>>, mobile?: list<array<string, mixed>>} */
    public static array $lastFrontend = [];

    /** 入册等批量操作设为 false，只软链不同步入队云编 */
    public static bool $runFrontendQueue = true;

    /** @var array<string, PluginManifest> code => manifest */
    private static array $plugins = [];

    /** @var array<string, string> code => plugin directory */
    private static array $pluginDirs = [];

    /** @var bool Whether boot() has already been called */
    private static bool $booted = false;

    /**
     * Boot all installed plugins. Reads the plugins table to decide which
     * plugins to load; rows with status=disabled are skipped.
     * Safe to call multiple times — plugin discovery/events/hooks run once,
     * HTTP routes are re-registered per request for the current app.
     */
    public static function boot(): void
    {
        if (!self::$booted) {
            self::$booted = true;

            $cached = self::readCache();
            if ($cached !== null) {
                $codes = array_keys($cached);
            } else {
                try {
                    $codes = Plugin::where('status', 'installed')->column('code');
                } catch (\Throwable) {
                    // Installer phase: plugins table doesn't exist yet.
                    return;
                }
            }

            $pluginsDir = self::pluginsPath();
            if (!is_dir($pluginsDir)) {
                return;
            }

            foreach ($codes as $code) {
                try {
                    if (!\core\license\LicenseGuard::canUsePlugin((string) $code)) {
                        continue;
                    }
                } catch (\Throwable) {
                    // 安装期或授权模块未就绪时不阻断 boot
                }
                $dir = $pluginsDir . $code . DIRECTORY_SEPARATOR;
                if (!is_dir($dir)) {
                    continue;
                }

                $manifestFile = $dir . 'plugin.json';
                if (!is_file($manifestFile)) {
                    continue;
                }

                try {
                    $manifest = PluginManifest::fromFile($manifestFile);
                } catch (PluginException) {
                    continue;
                }

                self::$plugins[$code] = $manifest;
                self::$pluginDirs[$code] = $dir;
                self::registerAutoload($code, $dir);

                self::registerEvents($code, $dir, $manifest);
                self::registerHooks($code, $manifest->raw);

                $pluginClass = 'plugins\\' . $code . '\\Plugin';
                if (class_exists($pluginClass)) {
                    /** @var PluginInterface $plugin */
                    $plugin = new $pluginClass();
                    $plugin->boot();
                }
            }
        }

        self::registerLoadedPluginRoutes();
    }

    /**
     * 每个请求都按当前 HTTP 应用注册插件路由（ThinkPHP 每请求新建路由表）。
     */
    private static function registerLoadedPluginRoutes(): void
    {
        foreach (self::$plugins as $code => $manifest) {
            $dir = self::$pluginDirs[$code] ?? (self::pluginsPath() . $code . DIRECTORY_SEPARATOR);
            if (is_dir($dir)) {
                self::registerRoutes($code, $dir, $manifest);
            }
        }
    }

    /**
     * Require route files declared in manifest["routes"], with a fallback to the
     * conventional route/admin.php and route/api.php when manifest omits "routes".
     *
     * 必须按当前 HTTP 应用拆开加载：admin 与 api 常有同名分组（如 article/list），
     * 两套都注册进 /api 时，后台 admin_full 会先命中，C 端带用户 token 就会 401。
     */
    private static function registerRoutes(string $code, string $dir, PluginManifest $manifest): void
    {
        $appName = self::currentHttpApp();

        $loaded = false;
        if (!empty($manifest->routes)) {
            foreach ($manifest->routes as $scope => $rel) {
                if (!is_string($rel) || $rel === '') {
                    continue;
                }
                if (!self::routeScopeMatchesApp(is_string($scope) ? $scope : '', $rel, $appName)) {
                    continue;
                }
                $path = $dir . ltrim($rel, '/\\');
                if (is_file($path)) {
                    require $path;
                    $loaded = true;
                }
            }
            if ($loaded) {
                return;
            }
        }

        $fallbacks = [
            ['admin', 'app/adminapi/route.php'],
            ['admin', 'app/route/admin.php'],
            ['admin', 'route/admin.php'],
            ['api', 'app/api/route.php'],
            ['api', 'app/route/api.php'],
            ['api', 'route/api.php'],
        ];
        foreach ($fallbacks as [$scope, $rel]) {
            if (!self::routeScopeMatchesApp($scope, $rel, $appName)) {
                continue;
            }
            $path = $dir . str_replace('/', DIRECTORY_SEPARATOR, $rel);
            if (is_file($path)) {
                require $path;
            }
        }
    }

    private static function currentHttpApp(): string
    {
        try {
            $name = (string) app()->http->getName();
            if ($name !== '') {
                return $name;
            }
        } catch (\Throwable) {
        }

        $uri = (string) ($_SERVER['PATH_INFO'] ?? $_SERVER['REQUEST_URI'] ?? '');
        if (str_contains($uri, '/adminapi')) {
            return 'adminapi';
        }
        if (preg_match('#(?:^|/)api(?:/|$)#', $uri) === 1) {
            return 'api';
        }
        return '';
    }

    private static function routeScopeMatchesApp(string $scope, string $path, string $appName): bool
    {
        $scope = strtolower($scope);
        $file = strtolower(basename(str_replace('\\', '/', $path)));

        $target = '';
        if (in_array($scope, ['admin', 'adminapi'], true) || $file === 'admin.php') {
            $target = 'adminapi';
        } elseif ($scope === 'api' || $file === 'api.php') {
            $target = 'api';
        }

        // 无法识别的 scope 不装任何应用，避免后台路由误进 /api
        if ($target === '') {
            return false;
        }
        // 应用名未知时只装 C 端路由，宁可不注册后台
        if ($appName === '') {
            return $target === 'api';
        }
        return $target === $appName;
    }

    /**
     * Require the event-listener registration file declared in manifest["events"].
     * The file is expected to call Event::listen(...) for the plugin's listeners.
     */
    private static function registerEvents(string $code, string $dir, PluginManifest $manifest): void
    {
        $candidates = [];
        if ($manifest->events !== '') {
            $candidates[] = $dir . ltrim($manifest->events, '/\\');
        }
        $candidates[] = $dir . 'app' . DIRECTORY_SEPARATOR . 'event.php';
        $candidates[] = $dir . 'event.php';
        foreach ($candidates as $f) {
            if (is_file($f)) {
                require_once $f;
                return;
            }
        }
    }

    /**
     * PSR-4: plugins\{code}\ → plugins/{code}/app/（无 app/ 则回退插件根）。
     */
    private static function registerAutoload(string $code, string $dir): void
    {
        $bases = [];
        $appDir = $dir . 'app' . DIRECTORY_SEPARATOR;
        if (is_dir($appDir)) {
            $bases[] = $appDir;
        }
        $bases[] = $dir;
        $prefix = 'plugins\\' . $code . '\\';

        spl_autoload_register(static function (string $class) use ($prefix, $bases): void {
            if (!str_starts_with($class, $prefix)) {
                return;
            }
            $rel = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix))) . '.php';
            foreach ($bases as $base) {
                $file = $base . $rel;
                if (is_file($file)) {
                    require_once $file;
                    return;
                }
            }
        }, true, true);
    }

    /**
     * 扫描各插件 plugin.json 的 commands，供 console.php 注册。
     *
     * @return list<class-string>
     */
    public static function discoverConsoleCommands(): array
    {
        $out = [];
        $pluginsDir = self::pluginsPath();
        if (!is_dir($pluginsDir)) {
            return $out;
        }
        foreach (scandir($pluginsDir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $manifestFile = $pluginsDir . $entry . DIRECTORY_SEPARATOR . 'plugin.json';
            if (!is_file($manifestFile)) {
                continue;
            }
            $data = json_decode((string) file_get_contents($manifestFile), true);
            if (!is_array($data)) {
                continue;
            }
            foreach ((array) ($data['commands'] ?? []) as $cmd) {
                if (!is_array($cmd)) {
                    continue;
                }
                $class = (string) ($cmd['class'] ?? '');
                if ($class === '') {
                    continue;
                }
                if (!str_contains($class, '\\')) {
                    $class = 'plugins\\' . $entry . '\\' . ltrim($class, '\\');
                } elseif (!str_starts_with($class, 'plugins\\')) {
                    $class = 'plugins\\' . $entry . '\\' . ltrim($class, '\\');
                }
                $rel = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen('plugins\\' . $entry . '\\'))) . '.php';
                $dir = $pluginsDir . $entry . DIRECTORY_SEPARATOR;
                foreach ([$dir . 'app' . DIRECTORY_SEPARATOR . $rel, $dir . $rel] as $file) {
                    if (is_file($file)) {
                        require_once $file;
                        break;
                    }
                }
                if (class_exists($class)) {
                    $out[] = $class;
                }
            }
        }
        return array_values(array_unique($out));
    }

    /**
     * 已装插件声明的可调度命令（name => title）。
     *
     * @return array<string, string>
     */
    public static function cronCommandMap(): array
    {
        $map = [];
        foreach (self::$plugins as $manifest) {
            foreach ((array) ($manifest->raw['commands'] ?? []) as $cmd) {
                if (!is_array($cmd)) {
                    continue;
                }
                $name  = (string) ($cmd['name'] ?? '');
                $title = (string) ($cmd['title'] ?? $name);
                if ($name !== '') {
                    $map[$name] = $title !== '' ? $title : $name;
                }
            }
        }
        return $map;
    }

    /**
     * Register hook handlers declared in manifest["hooks"].
     *
     * Each hook class must expose:
     *   - public string $hook       — the hook name to register on
     *   - public int    $priority   — optional, defaults to 10
     *   - public function handle(array $context, mixed $prev): mixed
     */
    private static function registerHooks(string $code, array $manifest): void
    {
        if (empty($manifest['hooks']) || !is_array($manifest['hooks'])) {
            return;
        }

        foreach ($manifest['hooks'] as $hookShortName) {
            $hookClass = 'plugins\\' . $code . '\\hook\\' . $hookShortName;
            if (!class_exists($hookClass)) {
                continue;
            }

            $instance = new $hookClass();
            $hook     = $instance->hook ?? null;
            $priority = $instance->priority ?? 10;

            if ($hook === null) {
                continue;
            }

            HookManager::register($hook, [$instance, 'handle'], (int) $priority);
        }
    }

    /**
     * Return the loaded manifest for a plugin, or null if not found.
     */
    public static function getPlugin(string $code): ?PluginManifest
    {
        return self::$plugins[$code] ?? null;
    }

    /**
     * Return all loaded plugin manifests keyed by plugin code.
     *
     * @return array<string, PluginManifest>
     */
    public static function getAll(): array
    {
        return self::$plugins;
    }

    /**
     * Codes of plugins that booted in the current process.
     *
     * @return list<string>
     */
    public static function loadedPlugins(): array
    {
        return array_keys(self::$plugins);
    }

    /**
     * Check whether a plugin booted successfully in the current process.
     */
    public static function isInstalled(string $code): bool
    {
        return isset(self::$plugins[$code]);
    }

    /**
     * Reset boot state (useful in tests).
     */
    public static function reset(): void
    {
        self::$plugins = [];
        self::$booted  = false;
    }

    // ------------------------------------------------------------------
    // Lifecycle: install / uninstall / upgrade / enable / disable
    // ------------------------------------------------------------------

    /**
     * Install a plugin: verify requirements, run database/install.sql via
     * SqlRunner, sync menus and permissions, grant to super-admin, write the
     * plugins row + semver baseline in plugin_migrations + audit log.
     * Missing install.sql is a no-op (register only). DDL runs outside the
     * transaction (MySQL auto-commits DDL).
     */
    public static function install(PluginManifest $manifest, string $source = Plugin::SOURCE_BUNDLED): void
    {
        if (Plugin::where('code', $manifest->code)->find()) {
            throw new PluginException(
                "插件已安装：{$manifest->code}",
                PluginException::ERR_CODE_CONFLICT
            );
        }

        if ($manifest->code === 'points_product') {
            self::absorbLegacyPointsOrder();
        }

        self::checkRequires($manifest);
        self::checkMenuConflicts($manifest);
        self::checkPermissionConflicts($manifest);

        try {
            self::runInstallSql($manifest->code);
        } catch (\Throwable $e) {
            self::audit($manifest->code, PluginInstallLog::ACTION_INSTALL, null, $manifest->version, PluginInstallLog::STATUS_FAILED, $e->getMessage());
            throw $e;
        }

        Db::startTrans();
        try {
            PluginRegistry::syncMenus($manifest);
            PluginRegistry::syncPermissions($manifest);
            PluginRegistry::grantToSuperAdmin($manifest);

            $now = date('Y-m-d H:i:s');
            Plugin::insert([
                'code'         => $manifest->code,
                'name'         => $manifest->name,
                'version'      => $manifest->version,
                'category'     => $manifest->category,
                'parent_menu'  => $manifest->parentMenu,
                'description'  => $manifest->description,
                'author'       => $manifest->author,
                'icon'         => $manifest->icon,
                'palette'      => $manifest->palette ? json_encode($manifest->palette) : null,
                'recommended'  => $manifest->recommended ? 1 : 0,
                'source'       => $source,
                'status'       => Plugin::STATUS_INSTALLED,
                'manifest'     => json_encode($manifest->toArray(), JSON_UNESCAPED_UNICODE),
                'installed_at' => $now,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);

            PluginMigration::insert([
                'plugin_code' => $manifest->code,
                'version'     => $manifest->version,
                'executed_at' => $now,
            ]);

            self::audit($manifest->code, PluginInstallLog::ACTION_INSTALL, null, $manifest->version, PluginInstallLog::STATUS_SUCCESS);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            self::audit($manifest->code, PluginInstallLog::ACTION_INSTALL, null, $manifest->version, PluginInstallLog::STATUS_FAILED, $e->getMessage());
            throw $e;
        }

        self::clearCache();
        self::$lastFrontend = self::runFrontendAfter($manifest->code, 'install');
    }

    /**
     * Remove a plugin from the system: refuse if any other installed plugin
     * lists it as a dependency, then clear menus/permissions, plugins row,
     * and plugin_migrations. Business tables are retained unless $purge.
     * With $purge=true, database/uninstall.sql runs first (if present).
     * The plugins/<code>/ directory is intentionally left in place.
     */
    public static function uninstall(string $code, bool $purge = false): void
    {
        $app = Plugin::where('code', $code)->find();
        if (!$app) {
            throw new PluginException("插件未安装：$code", PluginException::ERR_CODE_CONFLICT);
        }

        $dependents = [];
        foreach (Plugin::where('status', Plugin::STATUS_INSTALLED)->select() as $other) {
            if ($other->code === $code) {
                continue;
            }
            $manifestData = is_string($other->manifest) ? json_decode($other->manifest, true) : $other->manifest;
            $deps         = $manifestData['requires']['plugins'] ?? [];
            if (in_array($code, $deps, true)) {
                $dependents[] = $other->code;
            }
        }
        if ($dependents) {
            throw new PluginException(
                '存在依赖此插件的其他插件，请先卸载：' . implode(', ', $dependents),
                PluginException::ERR_DEPENDENCY_MISSING
            );
        }

        if ($purge) {
            try {
                self::runUninstallSql($code);
            } catch (\Throwable $e) {
                self::audit($code, PluginInstallLog::ACTION_UNINSTALL, $app->version, null, PluginInstallLog::STATUS_FAILED, $e->getMessage());
                throw $e;
            }
        }

        Db::startTrans();
        try {
            PluginRegistry::clearByPluginCode($code);
            PluginMigration::where('plugin_code', $code)->delete();
            Plugin::where('code', $code)->delete();
            self::audit($code, PluginInstallLog::ACTION_UNINSTALL, $app->version, null, PluginInstallLog::STATUS_SUCCESS);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            self::audit($code, PluginInstallLog::ACTION_UNINSTALL, $app->version, null, PluginInstallLog::STATUS_FAILED, $e->getMessage());
            throw $e;
        }

        self::clearCache();
        self::$lastFrontend = self::runFrontendAfter($code, 'uninstall');
    }

    /**
     * Upgrade an installed plugin to the version declared in its on-disk
     * plugin.json. Applies database/updates/v*.sql for versions in (from, to]
     * that are not yet recorded in plugin_migrations (semver), then re-syncs
     * menus/permissions and updates the plugins row. Missing updates/ is a no-op.
     */
    public static function upgrade(string $code): void
    {
        $app = Plugin::where('code', $code)->find();
        if (!$app) {
            throw new PluginException("插件未安装：$code", PluginException::ERR_CODE_CONFLICT);
        }

        $manifest = PluginManifest::fromFile(self::pluginsPath() . $code . DIRECTORY_SEPARATOR . 'plugin.json');
        $from = (string) $app->version;
        $to   = $manifest->version;

        if ($code === 'points_product') {
            self::absorbLegacyPointsOrder();
        }

        if (version_compare($to, $from, '<=')) {
            throw new PluginException(
                "无需升级：当前已是 $from",
                PluginException::ERR_VERSION_INCOMPATIBLE
            );
        }

        // Stock adopt: installed plugins without a semver baseline get one
        // before incremental updates run, so (from, to] filtering stays correct.
        self::adoptBaselineIfNeeded($code, $from);

        try {
            self::applyUpdateSql($code, $from, $to);
        } catch (\Throwable $e) {
            self::audit($code, PluginInstallLog::ACTION_UPGRADE, $from, $to, PluginInstallLog::STATUS_FAILED, $e->getMessage());
            throw $e;
        }

        Db::startTrans();
        try {
            PluginRegistry::syncMenus($manifest);
            PluginRegistry::syncPermissions($manifest);
            PluginRegistry::grantToSuperAdmin($manifest);

            $now = date('Y-m-d H:i:s');
            Plugin::where('code', $code)->update([
                'version'     => $to,
                'manifest'    => json_encode($manifest->toArray(), JSON_UNESCAPED_UNICODE),
                'upgraded_at' => $now,
                'updated_at'  => $now,
            ]);

            self::audit($code, PluginInstallLog::ACTION_UPGRADE, $from, $to, PluginInstallLog::STATUS_SUCCESS);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            self::audit($code, PluginInstallLog::ACTION_UPGRADE, $from, $to, PluginInstallLog::STATUS_FAILED, $e->getMessage());
            throw $e;
        }

        self::clearCache();
        self::$lastFrontend = self::runFrontendAfter($code, 'upgrade');
    }

    /**
     * Re-enable a previously disabled plugin. No-op effects on menus/permissions
     * (those weren't deleted on disable); just flips status back to installed.
     */
    public static function enable(string $code): void
    {
        if (!Plugin::where('code', $code)->find()) {
            throw new PluginException("插件未安装：$code", PluginException::ERR_CODE_CONFLICT);
        }
        if (!\core\license\LicenseGuard::canUsePlugin($code)) {
            throw new PluginException('该组件需在官网市场购买后启用：https://www.dev007.cn/market/apps?runtime=shop', PluginException::ERR_CODE_CONFLICT);
        }
        Plugin::where('code', $code)->update([
            'status'     => Plugin::STATUS_INSTALLED,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        self::audit($code, PluginInstallLog::ACTION_ENABLE, null, null, PluginInstallLog::STATUS_SUCCESS);
        self::clearCache();
    }

    /**
     * Mark a plugin disabled so boot() skips it. Menus and permissions stay in
     * the DB — frontend can hide them based on status. clearCache forces the
     * next boot to read fresh state.
     */
    public static function disable(string $code): void
    {
        if (!Plugin::where('code', $code)->find()) {
            throw new PluginException("插件未安装：$code", PluginException::ERR_CODE_CONFLICT);
        }
        Plugin::where('code', $code)->update([
            'status'     => Plugin::STATUS_DISABLED,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        self::audit($code, PluginInstallLog::ACTION_DISABLE, null, null, PluginInstallLog::STATUS_SUCCESS);
        self::clearCache();
    }

    // ------------------------------------------------------------------
    // Lifecycle helpers
    // ------------------------------------------------------------------

    /**
     * points_order 已并入 points_product：接管菜单/权限后删除旧插件行，不 drop 表。
     */
    private static function absorbLegacyPointsOrder(): void
    {
        $legacy = Plugin::where('code', 'points_order')->find();
        if (!$legacy) {
            return;
        }

        Menu::withTrashed()->where('plugin_code', 'points_order')->update(['plugin_code' => 'points_product']);
        Permission::withTrashed()->where('plugin_code', 'points_order')->update(['plugin_code' => 'points_product']);
        PluginMigration::where('plugin_code', 'points_order')->delete();
        Plugin::where('code', 'points_order')->delete();
        self::audit(
            'points_order',
            PluginInstallLog::ACTION_UNINSTALL,
            $legacy->version,
            null,
            PluginInstallLog::STATUS_SUCCESS,
            'absorbed into points_product'
        );

        $rows = MarketplaceEntitlement::all();
        if (is_array($rows) && isset($rows['points_order'])) {
            if (!isset($rows['points_product'])) {
                $rows['points_product'] = $rows['points_order'];
            }
            unset($rows['points_order']);
            MarketplaceEntitlement::save($rows);
        }
    }

    private static function checkRequires(PluginManifest $m): void
    {
        $phpReq = $m->requires['php'] ?? '';
        if ($phpReq !== '') {
            $needed = ltrim($phpReq, '>=');
            if (!version_compare(PHP_VERSION, $needed, '>=')) {
                throw new PluginException(
                    'PHP 版本不满足：需要 ' . $phpReq . '，当前 ' . PHP_VERSION,
                    PluginException::ERR_VERSION_INCOMPATIBLE
                );
            }
        }
        foreach ($m->requires['plugins'] ?? [] as $dep) {
            if (!Plugin::where('code', $dep)->where('status', Plugin::STATUS_INSTALLED)->find()) {
                throw new PluginException(
                    "依赖的插件未安装：$dep",
                    PluginException::ERR_DEPENDENCY_MISSING
                );
            }
        }
    }

    private static function checkMenuConflicts(PluginManifest $m): void
    {
        foreach ($m->menus as $menu) {
            $name = $menu['name'] ?? '';
            if ($name === '') {
                continue;
            }
            $existing = Menu::where('name', $name)->find();
            if ($existing && !empty($existing->plugin_code) && $existing->plugin_code !== $m->code) {
                throw new PluginException(
                    "菜单 name 冲突：$name 已属于插件 {$existing->plugin_code}",
                    PluginException::ERR_MENU_NAME_CONFLICT
                );
            }
        }
    }

    private static function checkPermissionConflicts(PluginManifest $m): void
    {
        foreach ($m->permissions as $perm) {
            $name = $perm['name'] ?? '';
            if ($name === '') {
                continue;
            }
            $existing = Permission::where('name', $name)->find();
            if ($existing && !empty($existing->plugin_code) && $existing->plugin_code !== $m->code) {
                throw new PluginException(
                    "权限 name 冲突：$name 已属于插件 {$existing->plugin_code}",
                    PluginException::ERR_PERMISSION_CONFLICT
                );
            }
        }
    }

    /**
     * Execute plugins/<code>/database/install.sql when present.
     * Missing file is intentional (register-only / still-in-schema plugins).
     */
    private static function runInstallSql(string $code): void
    {
        $path = self::pluginDatabasePath($code) . 'install.sql';
        if (!is_file($path)) {
            return;
        }
        self::sqlRunner()->runFile($path);
    }

    /**
     * Execute plugins/<code>/database/uninstall.sql when present (purge only).
     */
    private static function runUninstallSql(string $code): void
    {
        $path = self::pluginDatabasePath($code) . 'uninstall.sql';
        if (!is_file($path)) {
            return;
        }
        self::sqlRunner()->runFile($path);
    }

    /**
     * Apply database/updates/vX.Y.Z.sql for versions in (from, to] not yet recorded.
     *
     * @return list<string> executed semver versions
     */
    public static function applyUpdateSql(string $code, string $from, string $to): array
    {
        $dir = self::pluginDatabasePath($code) . 'updates' . DIRECTORY_SEPARATOR;
        if (!is_dir($dir)) {
            return [];
        }

        $files = glob($dir . 'v*.sql') ?: [];
        usort($files, static fn($a, $b) => version_compare(
            ltrim(basename($a, '.sql'), 'v'),
            ltrim(basename($b, '.sql'), 'v')
        ));

        $runner   = self::sqlRunner();
        $executed = [];
        foreach ($files as $file) {
            $v = ltrim(basename($file, '.sql'), 'v');
            if (!preg_match('/^\d+\.\d+\.\d+/', $v)) {
                continue;
            }
            if (version_compare($v, $from, '<=')) {
                continue;
            }
            if (version_compare($v, $to, '>')) {
                continue;
            }
            if (PluginMigration::where('plugin_code', $code)->where('version', $v)->find()) {
                continue;
            }

            $runner->runFile($file);
            PluginMigration::insert([
                'plugin_code' => $code,
                'version'     => $v,
                'executed_at' => date('Y-m-d H:i:s'),
            ]);
            $executed[] = $v;
        }

        return $executed;
    }

    /**
     * For stock installs: if plugins row exists but the current version is not
     * recorded as a semver baseline in plugin_migrations, write it without
     * re-running install.sql (CREATE TABLE IF NOT EXISTS covers table adopt).
     */
    public static function adoptBaselineIfNeeded(string $code, string $version): void
    {
        if ($version === '') {
            return;
        }
        if (PluginMigration::where('plugin_code', $code)->where('version', $version)->find()) {
            return;
        }
        PluginMigration::insert([
            'plugin_code' => $code,
            'version'     => $version,
            'executed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private static function pluginDatabasePath(string $code): string
    {
        return self::pluginsPath() . $code . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR;
    }

    private static function sqlRunner(): SqlRunner
    {
        $default = (string) config('database.default');
        $conf    = (array) config("database.connections.{$default}");
        $host    = (string) ($conf['hostname'] ?? '127.0.0.1');
        $port    = (string) ($conf['hostport'] ?? '3306');
        $db      = (string) ($conf['database'] ?? '');
        $user    = (string) ($conf['username'] ?? 'root');
        $pass    = (string) ($conf['password'] ?? '');
        $charset = (string) ($conf['charset'] ?? 'utf8mb4');
        $prefix  = (string) ($conf['prefix'] ?? '');

        $dsn = "mysql:host={$host};dbname={$db};port={$port};charset={$charset}";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        return new SqlRunner($pdo, $prefix);
    }

    private static function audit(string $code, string $action, ?string $from, ?string $to, string $status, ?string $msg = null): void
    {
        PluginInstallLog::insert([
            'plugin_code'  => $code,
            'action'       => $action,
            'version_from' => $from,
            'version_to'   => $to,
            'status'       => $status,
            'message'      => $msg,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Absolute path to the plugins/ root directory (with trailing separator).
     */
    public static function pluginsPath(): string
    {
        return root_path() . 'plugins' . DIRECTORY_SEPARATOR;
    }

    /**
     * Snapshot the installed plugins from the plugins table into a PHP cache
     * file, so subsequent boots can skip the database query.
     */
    public static function buildCache(): void
    {
        try {
            $installed = Plugin::where('status', 'installed')
                ->order('code')
                ->column('version', 'code');
        } catch (\Throwable) {
            return;
        }

        file_put_contents(
            self::cacheFile(),
            '<?php return ' . var_export($installed, true) . ';' . PHP_EOL
        );
    }

    /**
     * Remove the plugin cache file. Called whenever a plugin's installed/disabled
     * state changes so the next boot rebuilds from the database.
     */
    public static function clearCache(): void
    {
        $f = self::cacheFile();
        if (is_file($f)) {
            @unlink($f);
        }
    }

    /**
     * @return array<string, string>|null code => version, or null when no cache.
     */
    private static function readCache(): ?array
    {
        $f = self::cacheFile();
        if (!is_file($f)) {
            return null;
        }
        $data = require $f;
        return is_array($data) ? $data : null;
    }

    private static function cacheFile(): string
    {
        return runtime_path() . 'plugins_cache.php';
    }

    /**
     * @return array{frontend: int, admin_pc: list<array<string, mixed>>, mobile: list<array<string, mixed>>}
     */
    private static function runFrontendAfter(string $code, string $trigger): array
    {
        if (!self::$runFrontendQueue) {
            if ($trigger === 'uninstall') {
                PluginFrontendDeployer::remove($code);
                PluginFrontendSync::remove($code);
                return ['frontend' => 0, 'mode' => 'sync', 'admin_pc' => [], 'mobile' => []];
            }
            $sync = PluginFrontendSync::sync($code);
            PluginPagesJsonMerger::merge($code);
            return ['frontend' => $sync['count'], 'mode' => 'sync', 'admin_pc' => [], 'mobile' => []];
        }
        if ($trigger === 'uninstall') {
            if (class_exists(\app\service\plugin\PluginFrontendOrchestrator::class)) {
                try {
                    return app(\app\service\plugin\PluginFrontendOrchestrator::class)->afterUninstall($code);
                } catch (\Throwable) {
                    PluginFrontendDeployer::remove($code);
                    PluginFrontendSync::remove($code);
                }
            } else {
                PluginFrontendDeployer::remove($code);
                PluginFrontendSync::remove($code);
            }
            return ['frontend' => 0, 'mode' => 'dev', 'admin_pc' => [], 'mobile' => []];
        }
        if (class_exists(\app\service\plugin\PluginFrontendOrchestrator::class)) {
            try {
                return app(\app\service\plugin\PluginFrontendOrchestrator::class)->afterInstall($code, $trigger);
            } catch (\Throwable) {
                // 未跑升级脚本时构建表可能不存在，仍同步软链
            }
        }
        $sync = PluginFrontendSync::sync($code);
        PluginPagesJsonMerger::merge($code);
        return ['frontend' => $sync['count'], 'mode' => 'dev', 'admin_pc' => [], 'mobile' => []];
    }
}
