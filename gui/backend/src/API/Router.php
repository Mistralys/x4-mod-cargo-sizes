<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\API;

use Mistralys\X4\Mods\CargoSizesMod\GUI\API\Endpoints\PhysicsEndpoint;
use Mistralys\X4\Mods\CargoSizesMod\GUI\API\Endpoints\ShipsEndpoint;
use Mistralys\X4\Mods\CargoSizesMod\GUI\API\Endpoints\ConfigEndpoint;
use Mistralys\X4\Mods\CargoSizesMod\GUI\API\Endpoints\ClassRangeEndpoint;
use Mistralys\X4\Mods\CargoSizesMod\GUI\Services\ShipDataService;
use Mistralys\X4\Mods\CargoSizesMod\GUI\Services\PhysicsService;
use Mistralys\X4\Mods\CargoSizesMod\GUI\Services\ClassRangeService;
use Mistralys\X4\Mods\CargoSizesMod\GUI\Services\ConfigService;
use Slim\App;

/**
 * API Router - Defines all REST API routes.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend
 */
class Router
{
    /**
     * Register all API routes with the Slim app.
     *
     * @param App $app
     * @return void
     */
    public static function register(App $app): void
    {
        // Create and configure dependency injection container
        $container = new ServiceContainer();

        // Register services with factory functions
        $container->register('config_service', fn() => new ConfigService());
        $container->register('ship_data', fn() => new ShipDataService());
        $container->register('physics', fn(ServiceContainer $c) => 
            new PhysicsService($c->get('ship_data'))
        );
        $container->register('class_range', fn(ServiceContainer $c) =>
            new ClassRangeService($c->get('ship_data'))
        );

        // Instantiate endpoints with container-managed services
        $physicsEndpoint = new PhysicsEndpoint($container->get('physics'));
        $classRangeEndpoint = new ClassRangeEndpoint(
            $container->get('class_range')
        );
        $shipsEndpoint = new ShipsEndpoint($container->get('ship_data'));
        $configEndpoint = new ConfigEndpoint($container->get('config_service'));

        // Physics calculation endpoints
        $app->post('/api/calculate/physics', [$physicsEndpoint, 'calculate']);
        $app->post('/api/calculate/batch', [$physicsEndpoint, 'calculateBatch']);

        // Class-range calculation endpoint
        $app->post('/api/calculate/class-range', [$classRangeEndpoint, 'calculate']);

        // Ship data endpoints
        $app->get('/api/ships/types', [$shipsEndpoint, 'getTypes']);
        $app->get('/api/ships/{type}', [$shipsEndpoint, 'getShipsByType']);
        $app->get('/api/ships/details/{shipId}', [$shipsEndpoint, 'getDetails']);
        $app->get('/api/ships/{shipId}/engines', [$shipsEndpoint, 'getEnginesForShip']);
        $app->get('/api/engines', [$shipsEndpoint, 'getAllEngines']);

        // Configuration endpoints
        $app->get('/api/config', [$configEndpoint, 'get']);
        $app->post('/api/config', [$configEndpoint, 'update']);
        $app->post('/api/config/validate', [$configEndpoint, 'validate']);
    }
}
