<?php
/**
 * PHPUnit bootstrap file for X4 Cargo Sizes Mod tests.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage Tests
 */

declare(strict_types=1);

// Require the Composer autoloader
$autoloadFile = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($autoloadFile)) {
    die(
        'ERROR: Composer autoload file not found.' . PHP_EOL .
        'Please run "composer install" before running tests.' . PHP_EOL
    );
}

require_once $autoloadFile;

// Set timezone to avoid warnings
date_default_timezone_set('UTC');

// Display test environment info
echo PHP_EOL;
echo '==================================================' . PHP_EOL;
echo 'X4 Cargo Sizes Mod - Test Suite' . PHP_EOL;
echo '==================================================' . PHP_EOL;
echo 'PHP Version: ' . PHP_VERSION . PHP_EOL;
echo 'PHPUnit Version: ' . PHPUnit\Runner\Version::id() . PHP_EOL;
echo '==================================================' . PHP_EOL;
echo PHP_EOL;
