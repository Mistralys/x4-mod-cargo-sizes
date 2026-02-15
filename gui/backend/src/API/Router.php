<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\API;

use Mistralys\X4\Mods\CargoSizesMod\GUI\API\Endpoints\PhysicsEndpoint;
use Mistralys\X4\Mods\CargoSizesMod\GUI\API\Endpoints\ShipsEndpoint;
use Mistralys\X4\Mods\CargoSizesMod\GUI\API\Endpoints\ConfigEndpoint;
use Mistralys\X4\Mods\CargoSizesMod\GUI\API\Endpoints\ClassRangeEndpoint;
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
        // Physics calculation endpoints
        $physicsEndpoint = new PhysicsEndpoint();
        $app->post('/api/calculate/physics', [$physicsEndpoint, 'calculate']);
        $app->post('/api/calculate/batch', [$physicsEndpoint, 'calculateBatch']);
        
        // Class-range calculation endpoint
        $classRangeEndpoint = new ClassRangeEndpoint();
        $app->post('/api/calculate/class-range', [$classRangeEndpoint, 'calculate']);

        // Ship data endpoints
        $shipsEndpoint = new ShipsEndpoint();
        $app->get('/api/ships/types', [$shipsEndpoint, 'getTypes']);
        $app->get('/api/ships/{type}', [$shipsEndpoint, 'getShipsByType']);
        $app->get('/api/ships/details/{shipId}', [$shipsEndpoint, 'getDetails']);
        $app->get('/api/ships/{shipId}/engines', [$shipsEndpoint, 'getEnginesForShip']);
        $app->get('/api/engines', [$shipsEndpoint, 'getAllEngines']);

        // Configuration endpoints
        $configEndpoint = new ConfigEndpoint();
        $app->get('/api/config', [$configEndpoint, 'get']);
        $app->post('/api/config', [$configEndpoint, 'update']);
        $app->post('/api/config/validate', [$configEndpoint, 'validate']);
    }
}
