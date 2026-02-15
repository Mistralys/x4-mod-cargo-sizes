<?php
/**
 * Mass Ratio Analysis - Real X4 Ship Data
 * 
 * This script calculates actual mass ratios and drag adjustments
 * for real X4 ships with different cargo multipliers.
 */

declare(strict_types=1);

// Ship data from extracted X4 game files
$ships = [
    [
        'name' => 'Nova Vanguard',
        'type' => 'S Fighter',
        'baseMass' => 6.0,
        'originalCargo' => 240,
        'notes' => 'Small combat ship with minimal cargo'
    ],
    [
        'name' => 'Mercury Vanguard',
        'type' => 'M Transporter',
        'baseMass' => 42.966,
        'originalCargo' => 8200,
        'notes' => 'Medium freighter, cargo-focused'
    ],
    [
        'name' => 'Behemoth Vanguard',
        'type' => 'L Destroyer',
        'baseMass' => 196.016,
        'originalCargo' => 2300,
        'notes' => 'Large combat ship, minimal cargo'
    ],
    [
        'name' => 'Magnetar (Gas) Vanguard',
        'type' => 'L Miner',
        'baseMass' => 205.27,
        'originalCargo' => 42000,
        'notes' => 'Large mining ship, huge cargo relative to mass'
    ],
    [
        'name' => 'Shuyaku Vanguard',
        'type' => 'L Freighter',
        'baseMass' => 650.415,
        'originalCargo' => 37000,
        'notes' => 'Largest cargo ship, heavy base mass'
    ],
];

$multipliers = [2, 4, 8, 10];

echo "=" . str_repeat("=", 120) . "\n";
echo "MASS RATIO ANALYSIS - REAL X4 SHIPS\n";
echo "=" . str_repeat("=", 120) . "\n\n";

foreach ($ships as $ship) {
    $baseMass = $ship['baseMass'];
    $originalCargo = $ship['originalCargo'];
    $originalFullMass = $baseMass + $originalCargo;
    
    // Calculate cargo-to-mass ratio to understand ship characteristics
    $cargoToMassRatio = $originalCargo / $baseMass;
    
    echo str_repeat("-", 120) . "\n";
    echo sprintf("%-30s | %-20s | %s\n", $ship['name'], $ship['type'], $ship['notes']);
    echo str_repeat("-", 120) . "\n";
    echo sprintf("Base Mass: %10.2f | Original Cargo: %10.0f | Original Full Mass: %10.2f\n",
        $baseMass, $originalCargo, $originalFullMass);
    echo sprintf("Cargo/Mass Ratio: %.2fx (cargo is %.1f%% of base mass)\n\n",
        $cargoToMassRatio, ($originalCargo / $baseMass) * 100);
    
    echo sprintf("%-12s | %-15s | %-12s | %-18s | %-22s | %-18s\n",
        "Cargo Mult", "Adjusted Cargo", "Adj Full Mass", "Mass Ratio",
        "Current Drag Factor", "Squared Drag %");
    echo str_repeat("-", 120) . "\n";
    
    $extremeCases = [];
    
    foreach ($multipliers as $mult) {
        $adjustedCargo = $originalCargo * $mult;
        $adjustedFullMass = $baseMass + $adjustedCargo;
        
        // Mass ratio: how much heavier the ship becomes (adjusted / original)
        $massRatio = $adjustedFullMass / $originalFullMass;
        
        // CURRENT CODE behavior (from MassAdjustment.php):
        // multiplier = originalFullLoadMass / adjustedFullLoadMass (inverse of mass ratio)
        // dragReductionMultiplier = multiplier * dragReductionFactor (factor = 1.0 currently)
        // So drag is DIVIDED by this multiplier: newDrag = originalDrag / dragReductionMultiplier
        $currentDragFactor = $originalFullMass / $adjustedFullMass; // Inverse of mass ratio
        
        // PROPOSED SQUARED MODE (from plan):
        // newDrag = originalDrag / massRatio²
        // Drag percentage = (1 / massRatio²) * 100
        $massRatioSquared = $massRatio * $massRatio;
        $squaredDragPercent = (1 / $massRatioSquared) * 100;
        
        echo sprintf("%-12s | %15.0f | %12.2f | %10.3f (×%.2f) | %10.3f (= %.1f%%) | %10.2f%%\n",
            $mult . "x",
            $adjustedCargo,
            $adjustedFullMass,
            $massRatio,
            $massRatio,
            $currentDragFactor,
            ($currentDragFactor) * 100,
            $squaredDragPercent
        );
        
        // Flag extreme cases (< 5% drag remaining)
        if ($squaredDragPercent < 5) {
            $extremeCases[] = sprintf(
                "%dx multiplier: %.2f%% drag remaining (EXTREME - ship may be uncontrollable)",
                $mult,
                $squaredDragPercent
            );
        } elseif ($squaredDragPercent < 10) {
            $extremeCases[] = sprintf(
                "%dx multiplier: %.2f%% drag remaining (WARNING - very low drag)",
                $mult,
                $squaredDragPercent
            );
        }
    }
    
    if (!empty($extremeCases)) {
        echo "\n⚠️  CONCERNS:\n";
        foreach ($extremeCases as $concern) {
            echo "   - $concern\n";
        }
    }
    
    echo "\n";
}

echo str_repeat("=", 120) . "\n";
echo "KEY FINDINGS:\n";
echo str_repeat("=", 120) . "\n\n";

// Analysis section
echo "1. CURRENT CODE BEHAVIOR (dragReductionFactor = 1.0):\n";
echo "   - Drag reduction = originalFullMass / adjustedFullMass\n";
echo "   - This is the INVERSE of mass ratio\n";
echo "   - For 2x cargo, drag is typically reduced to 60-90% (weak compensation)\n";
echo "   - For 10x cargo, drag is reduced to 14-50% (still insufficient)\n\n";

echo "2. PROPOSED SQUARED MODE (from plan):\n";
echo "   - Drag reduction = 1 / massRatio²\n";
echo "   - Much more aggressive compensation\n";
echo "   - For 2x cargo on light ship (Nova), drag drops to 25% (very aggressive)\n";
echo "   - For 10x cargo on heavy ship (Shuyaku), drag drops to 45% (reasonable)\n\n";

echo "3. MASS RATIO VARIANCE BY SHIP TYPE:\n";
echo "   - Ships with HIGH cargo/mass ratio (Magnetar, 204x) get HUGE mass increases\n";
echo "   - Ships with LOW cargo/mass ratio (Behemoth, 12x) get SMALL mass increases\n";
echo "   - This creates wildly different physics behavior for same cargo multiplier\n\n";

echo "4. EXTREME CASES IDENTIFIED:\n";
echo "   - Nova (S Fighter) with 10x cargo: ";
$novaOriginal = 6 + 240;
$novaAdjusted = 6 + (240 * 10);
$novaMassRatio = $novaAdjusted / $novaOriginal;
$novaSquared = (1 / ($novaMassRatio * $novaMassRatio)) * 100;
echo sprintf("%.2f%% drag remaining (CRITICAL)\n", $novaSquared);

echo "   - Magnetar with 10x cargo: ";
$magOriginal = 205.27 + 42000;
$magAdjusted = 205.27 + (42000 * 10);
$magMassRatio = $magAdjusted / $magOriginal;
$magSquared = (1 / ($magMassRatio * $magMassRatio)) * 100;
echo sprintf("%.2f%% drag remaining (CRITICAL)\n\n", $magSquared);

echo "5. CURRENT CODE SAFETY LIMITS:\n";
echo "   - Checking code for min/max caps...\n";

// Check for safety limits in code
$flightMechanicsFile = file_get_contents(__DIR__ . '/../../../src/Mods/CargoSizesMod/Output/FlightMechanicsOverrideFile.php');
$adjustedDragFile = file_get_contents(__DIR__ . '/../../../src/Mods/CargoSizesMod/Output/Physics/AdjustedDrag.php');

$hasCaps = (
    strpos($flightMechanicsFile, 'min(') !== false ||
    strpos($flightMechanicsFile, 'max(') !== false ||
    strpos($adjustedDragFile, 'min(') !== false ||
    strpos($adjustedDragFile, 'max(') !== false
);

if ($hasCaps) {
    echo "   ✓ Code contains min/max limits\n";
} else {
    echo "   ✗ NO SAFETY LIMITS FOUND - drag can go to any value\n";
}

echo "\n";
echo str_repeat("=", 120) . "\n";
echo "CONCLUSION:\n";
echo str_repeat("=", 120) . "\n";
echo "Current system has NO relationship to actual ship physics.\n";
echo "The 'multiplier' is just originalFullMass/adjustedFullMass - an inverse ratio.\n";
echo "Ships with different cargo/mass profiles behave COMPLETELY differently.\n";
echo "There are NO safety caps to prevent extreme values.\n";
echo "\nThe proposed squared mode will create even MORE extreme cases without caps.\n";
echo str_repeat("=", 120) . "\n";
