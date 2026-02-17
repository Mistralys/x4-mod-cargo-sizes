<?php
declare(strict_types=1);

/**
 * Unit tests for ServiceContainer.
 *
 * Tests service registration, lazy instantiation, singleton behavior,
 * exception handling, and the has() method.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend - Tests
 */

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\Tests\Unit\API;

use PHPUnit\Framework\TestCase;
use Mistralys\X4\Mods\CargoSizesMod\GUI\API\ServiceContainer;
use RuntimeException;
use stdClass;

/**
 * Class ServiceContainerTest
 *
 * Comprehensive tests for the lightweight DI container,
 * verifying lazy instantiation, singleton pattern, and error handling.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend - Tests
 */
class ServiceContainerTest extends TestCase
{
    /**
     * Test basic service registration and retrieval.
     *
     * Verifies that a registered service can be retrieved and
     * is of the expected type.
     */
    public function testRegisterAndGetService(): void
    {
        $container = new ServiceContainer();
        $container->register('test', fn() => new stdClass());

        $service = $container->get('test');

        $this->assertInstanceOf(stdClass::class, $service,
            'Retrieved service should be instance of registered class');
    }

    /**
     * Test singleton behavior (same instance returned).
     *
     * Verifies that multiple calls to get() return the exact same
     * instance (singleton pattern), not new instances.
     */
    public function testSingletonBehavior(): void
    {
        $container = new ServiceContainer();
        $container->register('test', fn() => new stdClass());

        $service1 = $container->get('test');
        $service2 = $container->get('test');

        $this->assertSame($service1, $service2,
            'Container should return same instance on multiple get() calls (singleton)');
    }

    /**
     * Test lazy instantiation (factory not called until get()).
     *
     * Verifies that the factory function is NOT called during register(),
     * but IS called during the first get() (lazy instantiation).
     */
    public function testLazyInstantiation(): void
    {
        $container = new ServiceContainer();
        $factoryCalled = false;

        $container->register('test', function() use (&$factoryCalled) {
            $factoryCalled = true;
            return new stdClass();
        });

        $this->assertFalse($factoryCalled,
            'Factory should not be called during register() (lazy instantiation)');

        $container->get('test');

        $this->assertTrue($factoryCalled,
            'Factory should be called on first get()');
    }

    /**
     * Test that factory is called only once (lazy + singleton).
     *
     * Verifies that the factory is called exactly once even when
     * get() is called multiple times (combination of lazy + singleton).
     */
    public function testFactoryCalledOnlyOnce(): void
    {
        $container = new ServiceContainer();
        $callCount = 0;

        $container->register('test', function() use (&$callCount) {
            $callCount++;
            return new stdClass();
        });

        $container->get('test');
        $container->get('test');
        $container->get('test');

        $this->assertEquals(1, $callCount,
            'Factory should be called exactly once despite multiple get() calls');
    }

    /**
     * Test exception when requesting unregistered service.
     *
     * Verifies that attempting to get() an unregistered service
     * throws RuntimeException with appropriate error message.
     */
    public function testExceptionOnMissingService(): void
    {
        $container = new ServiceContainer();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Service "missing" not registered in container');

        $container->get('missing');
    }

    /**
     * Test has() method for checking service existence.
     *
     * Verifies that has() correctly reports whether a service
     * factory is registered, regardless of instantiation status.
     */
    public function testHasMethod(): void
    {
        $container = new ServiceContainer();
        $container->register('test', fn() => new stdClass());

        $this->assertTrue($container->has('test'),
            'has() should return true for registered service');
        $this->assertFalse($container->has('missing'),
            'has() should return false for unregistered service');
    }

    /**
     * Test has() returns true even before instantiation.
     *
     * Verifies that has() checks factory registration, not instantiation status.
     */
    public function testHasMethodBeforeInstantiation(): void
    {
        $container = new ServiceContainer();
        $container->register('test', fn() => new stdClass());

        // Check has() before calling get()
        $this->assertTrue($container->has('test'),
            'has() should return true even before service is instantiated');

        // Now instantiate
        $container->get('test');

        // Should still return true
        $this->assertTrue($container->has('test'),
            'has() should still return true after instantiation');
    }

    /**
     * Test service with dependencies (nested get()).
     *
     * Verifies that factories can call $container->get() to resolve
     * dependencies (dependency injection pattern).
     */
    public function testServiceWithDependencies(): void
    {
        $container = new ServiceContainer();

        // Register dependency
        $logger = new class {
            public string $name = 'Logger';
        };
        $container->register('logger', fn() => $logger);

        // Register service that depends on logger
        $container->register('service', fn(ServiceContainer $c) => new class($c->get('logger')) {
            public function __construct(
                public readonly object $logger
            ) {}
        });

        $service = $container->get('service');

        $this->assertIsObject($service->logger,
            'Service should receive dependency from container');
        $this->assertEquals('Logger', $service->logger->name,
            'Dependency should be correctly resolved');
    }

    /**
     * Test that dependencies are also singletons.
     *
     * Verifies that when serviceA and serviceB both depend on serviceC,
     * they receive the same instance of serviceC (shared singleton).
     */
    public function testSharedDependencySingleton(): void
    {
        $container = new ServiceContainer();

        // Register shared dependency
        $shared = new stdClass();
        $container->register('shared', fn() => $shared);

        // Register two services that depend on 'shared'
        $container->register('serviceA', fn(ServiceContainer $c) => new class($c->get('shared')) {
            public function __construct(
                public readonly object $shared
            ) {}
        });
        $container->register('serviceB', fn(ServiceContainer $c) => new class($c->get('shared')) {
            public function __construct(
                public readonly object $shared
            ) {}
        });

        $serviceA = $container->get('serviceA');
        $serviceB = $container->get('serviceB');

        $this->assertSame($serviceA->shared, $serviceB->shared,
            'Both services should receive the same instance of shared dependency');
    }
}
