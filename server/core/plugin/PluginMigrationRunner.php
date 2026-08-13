<?php
declare(strict_types=1);

namespace core\plugin;

use app\model\plugin\Plugin;

/**
 * Apply pending plugin SQL updates (database/updates/v*.sql) via PluginManager.
 *
 * Kept as a thin façade for callers such as plugin:enroll-bundled. Plugin
 * install/upgrade/uninstall themselves go through PluginManager and SqlRunner;
 * PHP/Phinx migrations are no longer executed here.
 *
 * Versions recorded in plugin_migrations are semver strings (e.g. 1.0.0).
 */
class PluginMigrationRunner
{
    /**
     * Apply not-yet-recorded database/updates for an installed plugin up to
     * its on-disk (or DB) version. Missing updates/ is a no-op.
     *
     * Stock installs already at tip: adopt tip semver baseline only, then no-op
     * (do not re-run historical updates — installed = applied to current version).
     *
     * @return list<string> versions executed in this call
     */
    public static function run(string $pluginCode): array
    {
        $app = Plugin::where('code', $pluginCode)->find();
        $from = $app ? (string) $app->version : '0.0.0';

        $manifestPath = PluginManager::pluginsPath() . $pluginCode . DIRECTORY_SEPARATOR . 'plugin.json';
        $to = $from;
        if (is_file($manifestPath)) {
            try {
                $to = PluginManifest::fromFile($manifestPath)->version;
            } catch (\Throwable) {
                // Fall back to DB version when on-disk manifest is unreadable.
            }
        }

        if ($app) {
            // Stock adopt: tip-only baseline when installed but missing semver rows.
            PluginManager::adoptBaselineIfNeeded($pluginCode, $from);
        }

        if (version_compare($to, $from, '<=')) {
            return [];
        }

        return PluginManager::applyUpdateSql($pluginCode, $from, $to);
    }
}
