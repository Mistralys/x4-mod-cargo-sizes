<?php
declare(strict_types=1);

/**
 * PHPUnit Test Bootstrap
 *
 * Initializes autoloader and test environment.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend - Tests
 */

// Load Composer autoloader for backend
require_once __DIR__ . '/../vendor/autoload.php';

// Load main project's autoloader to access X4 Core and mod classes
// This is needed because the backend uses classes from the main project
require_once __DIR__ . '/../../../vendor/autoload.php';

// Load X4 Core dev config to initialize game data
// This is needed for tests that use X4 Core classes (ShipDefs, etc.)
require_once __DIR__ . '/../../../dev-config.php';
