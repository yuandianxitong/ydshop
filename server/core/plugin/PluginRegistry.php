<?php
declare(strict_types=1);

namespace core\plugin;

use app\model\system\Menu;
use app\model\system\Permission;
use app\model\system\Role;
use think\facade\Db;

/**
 * Syncs a plugin's declared menus and permissions into the system tables,
 * grants them to the super admin, and removes them on uninstall. Idempotent:
 * running sync* repeatedly with the same manifest leaves the DB unchanged.
 */
class PluginRegistry
{
    private const SUPER_ADMIN_ROLE_NAME = 'super_admin';

    /**
     * Upsert all menus declared in the manifest under the resolved parent menu,
     * then delete any leftover rows tagged with this plugin_code that the new
     * manifest no longer mentions (orphan cleanup).
     */
    public static function syncMenus(PluginManifest $manifest): void
    {
        $parent = self::resolveParentMenu($manifest->parentMenu);
        $isWorkspace = $manifest->displayMode === PluginManifest::DISPLAY_WORKSPACE;
        $now = date('Y-m-d H:i:s');

        foreach ($manifest->menus as $m) {
            if (empty($m['name'])) {
                throw new PluginException(
                    'menus[].name 必填',
                    PluginException::ERR_MANIFEST_INVALID
                );
            }

            $name = $m['name'];
            // Adopt any row matching by name — either previously tagged with this
            // plugin_code (idempotent re-sync) or a legacy untagged system row
            // (pre-vNext bundled install). Cross-plugin conflicts are guarded by
            // the post-loop owner check.
            $existing = Menu::withTrashed()
                ->where('name', $name)
                ->where(function ($q) use ($manifest) {
                    $q->whereNull('plugin_code')
                      ->whereOr('plugin_code', $manifest->code);
                })
                ->find();

            $payload = [
                'parent_id'   => $parent->id,
                'type'        => 2,
                'title'       => $m['title'] ?? $name,
                'path'        => $m['path'] ?? null,
                'component'   => $m['component'] ?? 'LAYOUT',
                'redirect'    => $m['redirect'] ?? null,
                'icon'        => $m['icon'] ?? null,
                'permission'  => $m['permission'] ?? null,
                'is_hidden'   => $isWorkspace ? 1 : 0,
                'is_cache'    => 1,
                'is_affix'    => 0,
                'is_iframe'   => 0,
                'status'      => 1,
                'sort'        => $m['sort'] ?? 0,
                'plugin_code' => $manifest->code,
                'updated_at'  => $now,
                'deleted_at'  => null,
            ];

            if ($existing) {
                Menu::withTrashed()->where('id', $existing->id)->update($payload);
            } else {
                $payload['name']       = $name;
                $payload['created_at'] = $now;
                Menu::insert($payload);
            }
        }

        $keepNames = array_column($manifest->menus, 'name');
        $orphanQuery = Menu::where('plugin_code', $manifest->code);
        if ($keepNames) {
            $orphanQuery->whereNotIn('name', $keepNames);
        }
        $orphanIds = $orphanQuery->column('id');
        if ($orphanIds) {
            Db::name('role_menus')->whereIn('menu_id', $orphanIds)->delete();
            Db::name('menus')->whereIn('id', $orphanIds)->delete();
        }
    }

    /**
     * Upsert permissions declared in the manifest; fail if a name is already
     * owned by another plugin. Permissions formerly belonging to this plugin
     * that are no longer declared get removed (orphan cleanup).
     */
    public static function syncPermissions(PluginManifest $manifest): void
    {
        $now = date('Y-m-d H:i:s');

        foreach ($manifest->permissions as $p) {
            if (empty($p['name'])) {
                throw new PluginException(
                    'permissions[].name 必填',
                    PluginException::ERR_MANIFEST_INVALID
                );
            }

            $name = $p['name'];
            $existing = Permission::withTrashed()->where('name', $name)->find();

            $payload = [
                'title'       => $p['title'] ?? $name,
                'group'       => $p['group'] ?? 'plugin',
                'description' => $p['description'] ?? null,
                'guard_name'  => 'admin',
                'status'      => 1,
                'sort'        => $p['sort'] ?? 0,
                'plugin_code' => $manifest->code,
                'updated_at'  => $now,
                'deleted_at'  => null,
            ];

            if ($existing) {
                if (
                    !empty($existing->plugin_code)
                    && $existing->plugin_code !== $manifest->code
                ) {
                    throw new PluginException(
                        "permission 冲突：$name 已属于插件 {$existing->plugin_code}",
                        PluginException::ERR_PERMISSION_CONFLICT
                    );
                }
                Permission::withTrashed()->where('id', $existing->id)->update($payload);
            } else {
                $payload['name']       = $name;
                $payload['created_at'] = $now;
                Permission::insert($payload);
            }
        }

        $keepNames = array_column($manifest->permissions, 'name');
        $orphanQuery = Permission::where('plugin_code', $manifest->code);
        if ($keepNames) {
            $orphanQuery->whereNotIn('name', $keepNames);
        }
        $orphanIds = $orphanQuery->column('id');
        if ($orphanIds) {
            Db::name('role_permissions')->whereIn('permission_id', $orphanIds)->delete();
            Db::name('permissions')->whereIn('id', $orphanIds)->delete();
        }
    }

    /**
     * Grant every permission owned by this plugin to the super-admin role,
     * idempotently. Silent no-op if super-admin role or permissions missing.
     */
    public static function grantToSuperAdmin(PluginManifest $manifest): void
    {
        $superAdmin = Role::where('name', self::SUPER_ADMIN_ROLE_NAME)->find();
        if (!$superAdmin) {
            return;
        }
        $now = date('Y-m-d H:i:s');

        // Permissions
        $permIds = Permission::where('plugin_code', $manifest->code)->column('id');
        if ($permIds) {
            $existing = Db::name('role_permissions')
                ->where('role_id', $superAdmin->id)
                ->whereIn('permission_id', $permIds)
                ->column('permission_id');
            $missing = array_diff($permIds, $existing);
            if ($missing) {
                Db::name('role_permissions')->insertAll(array_map(static fn($pid) => [
                    'role_id'       => $superAdmin->id,
                    'permission_id' => $pid,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ], array_values($missing)));
            }
        }

        // Menus — without this the plugin's sidebar entries never appear and
        // the dynamic router never sees the plugin's routes.
        $menuIds = Menu::where('plugin_code', $manifest->code)->column('id');
        if ($menuIds) {
            $existing = Db::name('role_menus')
                ->where('role_id', $superAdmin->id)
                ->whereIn('menu_id', $menuIds)
                ->column('menu_id');
            $missing = array_diff($menuIds, $existing);
            if ($missing) {
                Db::name('role_menus')->insertAll(array_map(static fn($mid) => [
                    'role_id'    => $superAdmin->id,
                    'menu_id'    => $mid,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], array_values($missing)));
            }
        }
    }

    /**
     * Remove every menu and permission row tagged with this plugin_code, plus
     * any role pivot rows pointing to them. Called on uninstall.
     */
    public static function clearByPluginCode(string $code): void
    {
        // Hard-delete so unique name indexes do not block reinstall after uninstall.
        $menuIds = Menu::withTrashed()->where('plugin_code', $code)->column('id');
        if ($menuIds) {
            Db::name('role_menus')->whereIn('menu_id', $menuIds)->delete();
            Db::name('menus')->whereIn('id', $menuIds)->delete();
        }

        $permIds = Permission::withTrashed()->where('plugin_code', $code)->column('id');
        if ($permIds) {
            Db::name('role_permissions')->whereIn('permission_id', $permIds)->delete();
            Db::name('permissions')->whereIn('id', $permIds)->delete();
        }
    }

    /**
     * Walk a "/"-delimited parent menu path (e.g. "Marketing" or "App/Sub") and
     * return the matching Menu row. Throws if any segment is missing.
     */
    private static function resolveParentMenu(string $name): Menu
    {
        $parts   = explode('/', $name);
        $current = Menu::where('parent_id', 0)->where('name', $parts[0])->find();
        if (!$current) {
            throw new PluginException(
                "父级菜单不存在：{$parts[0]}",
                PluginException::ERR_PARENT_MENU_MISSING
            );
        }
        for ($i = 1, $n = count($parts); $i < $n; $i++) {
            $current = Menu::where('parent_id', $current->id)->where('name', $parts[$i])->find();
            if (!$current) {
                throw new PluginException(
                    "父级菜单不存在：$name",
                    PluginException::ERR_PARENT_MENU_MISSING
                );
            }
        }
        return $current;
    }
}
