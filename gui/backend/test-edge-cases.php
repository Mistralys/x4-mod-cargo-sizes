<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Mistralys\X4\Mods\CargoSizesMod\GUI\Services\ShipDataService;

echo "Testing ShipDataService runtime behavior...\n";

try {
    // Test 1: Verify instance can be created
    $service = new ShipDataService(null, null);
    echo "✓ ShipDataService instantiation works\n";
    
    // Test 2: Verify getAllEngines() doesn't cause static property error
    $engines = $service->getAllEngines();
    echo "✓ getAllEngines() executes without static property error\n";
    echo "✓ Found " . count($engines) . " engines\n";
    
    // Test 3: Verify ship types work
    $types = $service->getShipTypes();
    echo "✓ getShipTypes() works, found " . count($types) . " types\n";
    
    echo "\nAll edge-case tests PASSED!\n";
} catch (Throwable $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    echo "  at " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
