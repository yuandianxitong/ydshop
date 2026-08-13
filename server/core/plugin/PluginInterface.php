<?php
declare(strict_types=1);

namespace core\plugin;

/**
 * Plugin lifecycle interface
 *
 * Every plugin must implement this interface. The methods are called
 * at the corresponding points in the plugin lifecycle:
 *
 *  - boot()       — called on every request (register routes, events, hooks)
 *  - install()    — called once when `php think plugin:install <name>` runs
 *  - uninstall()  — called once when `php think plugin:uninstall <name>` runs
 */
interface PluginInterface
{
    /** Machine-readable identifier, e.g. "coupon" */
    public function getName(): string;

    /** Human-readable display name, e.g. "优惠券插件" */
    public function getTitle(): string;

    /** Semantic version string, e.g. "1.0.0" */
    public function getVersion(): string;

    /** Register routes / events / hooks — runs on every request */
    public function boot(): void;

    /** Run migrations, seed data, etc. — runs once at install time */
    public function install(): void;

    /** Tear-down: drop tables, remove data, etc. — runs once at uninstall time */
    public function uninstall(): void;
}
