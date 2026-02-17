<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\API;

use RuntimeException;

/**
 * Lightweight dependency injection container.
 *
 * Provides service registration and retrieval with lazy instantiation
 * and singleton pattern. Services are instantiated once and reused
 * across multiple get() calls.
 *
 * NOT PSR-11 compliant (intentionally lightweight for local-only tool).
 *
 * **Usage Example:**
 * ```php
 * $container = new ServiceContainer();
 * $container->register('ship_data', fn() => new ShipDataService());
 * $container->register('physics', fn(ServiceContainer $c) =>
 *     new PhysicsService($c->get('ship_data'))
 * );
 *
 * $service = $container->get('physics'); // Instantiated on first call
 * $same = $container->get('physics');    // Returns same instance
 * ```
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend - API
 * @since 1.3.0
 */
class ServiceContainer
{
    /**
     * Instantiated services (singletons).
     *
     * @var array<string, object>
     */
    private array $services = [];

    /**
     * Service factory functions.
     *
     * @var array<string, callable>
     */
    private array $factories = [];

    /**
     * Register a service factory.
     *
     * Factory is not called until get() is invoked (lazy instantiation).
     * Factory receives container instance as parameter for dependency resolution.
     *
     * **Example:**
     * ```php
     * // Simple service (no dependencies)
     * $container->register('logger', fn() => new Logger());
     *
     * // Service with dependencies
     * $container->register('user_service', fn(ServiceContainer $c) =>
     *     new UserService($c->get('logger'))
     * );
     * ```
     *
     * @param string $id Service identifier (e.g., 'ship_data', 'physics')
     * @param callable $factory Factory function: function(ServiceContainer): object
     * @return void
     */
    public function register(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
    }

    /**
     * Get a service instance (lazy instantiation, singleton).
     *
     * If service not yet instantiated, calls factory and caches result.
     * Subsequent calls return cached instance (singleton pattern).
     *
     * @param string $id Service identifier
     * @return object Service instance
     * @throws RuntimeException if service not registered
     */
    public function get(string $id): object
    {
        // Return cached instance if already instantiated
        if (isset($this->services[$id])) {
            return $this->services[$id];
        }

        // Check if factory registered
        if (!isset($this->factories[$id])) {
            throw new RuntimeException(
                sprintf('Service "%s" not registered in container', $id)
            );
        }

        // Instantiate and cache
        $factory = $this->factories[$id];
        $this->services[$id] = $factory($this);

        return $this->services[$id];
    }

    /**
     * Check if service is registered.
     *
     * Returns true if a factory is registered for the given service ID,
     * regardless of whether the service has been instantiated yet.
     *
     * @param string $id Service identifier
     * @return bool True if service factory registered
     */
    public function has(string $id): bool
    {
        return isset($this->factories[$id]);
    }
}
