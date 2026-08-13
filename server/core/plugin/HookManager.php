<?php
declare(strict_types=1);

namespace core\plugin;

/**
 * Synchronous hook registry (WordPress-style filters).
 *
 * Usage:
 *   // Register a handler (lower priority number = runs first)
 *   HookManager::register('cart.total', function(array $ctx, $prev) {
 *       return $prev - $ctx['discount'];
 *   }, 10);
 *
 *   // Apply all registered handlers and get the final value
 *   $total = HookManager::apply('cart.total', ['discount' => 50], $rawTotal);
 */
class HookManager
{
    /**
     * Registry: hook => [['handler' => callable, 'priority' => int], ...]
     * Entries are kept sorted by priority (ascending) at insertion time.
     *
     * @var array<string, array<int, array{handler: callable, priority: int}>>
     */
    private static array $hooks = [];

    /**
     * Register a handler for a hook.
     *
     * @param string   $hook     Hook identifier
     * @param callable $handler  Signature: (array $context, mixed $previousValue): mixed
     * @param int      $priority Lower value = higher precedence (default 10)
     */
    public static function register(string $hook, callable $handler, int $priority = 10): void
    {
        self::$hooks[$hook][] = ['handler' => $handler, 'priority' => $priority];

        // Keep the list sorted by priority so apply() can iterate linearly
        usort(self::$hooks[$hook], static fn($a, $b) => $a['priority'] <=> $b['priority']);
    }

    /**
     * Chain-execute all handlers registered for $hook.
     *
     * Each handler receives ($context, $previousValue) and its return value
     * becomes the $previousValue for the next handler.
     *
     * @param string $hook         Hook identifier
     * @param array  $context      Immutable context data passed to every handler
     * @param mixed  $initialValue Seed value for the chain
     * @return mixed               Accumulated value after all handlers have run
     */
    public static function apply(string $hook, array $context, mixed $initialValue = null): mixed
    {
        if (!isset(self::$hooks[$hook])) {
            return $initialValue;
        }

        $value = $initialValue;
        foreach (self::$hooks[$hook] as $entry) {
            $value = ($entry['handler'])($context, $value);
        }

        return $value;
    }

    /**
     * Check whether any handlers are registered for a hook.
     */
    public static function has(string $hook): bool
    {
        return !empty(self::$hooks[$hook]);
    }

    /**
     * Remove all registered handlers.
     * Mainly useful in unit tests to isolate hook state between test cases.
     */
    public static function clear(): void
    {
        self::$hooks = [];
    }
}
