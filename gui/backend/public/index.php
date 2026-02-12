<?php
declare(strict_types=1);

// Load parent project's autoloader to access PhysicsCalculator, X4 Core, etc.
require_once __DIR__ . '/../../../vendor/autoload.php';

// Load GUI backend's own autoloader (Slim dependencies)
require_once __DIR__ . '/../vendor/autoload.php';

use Mistralys\X4\Mods\CargoSizesMod\GUI\API\Router;
use Mistralys\X4\Mods\CargoSizesMod\GUI\API\Middleware\CorsMiddleware;
use Slim\Factory\AppFactory;

// Create Slim application
$app = AppFactory::create();

// Add body parsing middleware (for JSON requests)
$app->addBodyParsingMiddleware();

// Add CORS middleware globally
$app->add(new CorsMiddleware());

// Register all API routes
Router::register($app);

// Run the application
$app->run();
