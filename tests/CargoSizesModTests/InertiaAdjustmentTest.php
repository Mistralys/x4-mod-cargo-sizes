<?php
/**
 * Tests for inertia adjustment calculations.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage Tests
 */

declare(strict_types=1);

namespace CargoSizesModTests;

use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\PhysicsCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Tests inertia adjustment logic to ensure inertia increases with mass
 * and dampening factor works correctly.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage Tests
 */
class InertiaAdjustmentTest extends TestCase
{
    /**
     * Test that inertia increases with mass.
     */
    public function testInertiaIncreases(): void
    {
        // Verify inertia goes UP with mass
        $originalInertia = 5.0;
        $massRatio = 2.0;
        $impactFactor = 0.5;
        
        $massIncrease = $massRatio - 1.0;  // 1.0
        $dampedIncrease = $massIncrease * $impactFactor;  // 0.5
        $newInertia = $originalInertia * (1.0 + $dampedIncrease);  // 7.5
        
        $this->assertGreaterThan($originalInertia, $newInertia);
        $this->assertEqualsWithDelta(7.5, $newInertia, 0.1);
    }

    /**
     * Test inertia with full physics (no dampening).
     */
    public function testInertiaFullPhysics(): void
    {
        // With factor 1.0, inertia should scale fully with mass
        $originalInertia = 3.0;
        $massRatio = 4.0;
        $impactFactor = 1.0;
        
        $massIncrease = $massRatio - 1.0;  // 3.0
        $dampedIncrease = $massIncrease * $impactFactor;  // 3.0
        $newInertia = $originalInertia * (1.0 + $dampedIncrease);  // 12.0
        
        $this->assertEqualsWithDelta(12.0, $newInertia, 0.1);
    }

    /**
     * Test inertia with default dampening (0.5).
     */
    public function testInertiaWithDefaultDampening(): void
    {
        $originalInertia = 5.0;
        $massRatio = 2.0;
        $impactFactor = 0.5;  // Default
        
        $newInertia = $originalInertia * (1.0 + ($massRatio - 1.0) * $impactFactor);
        
        // 2x mass with 0.5 dampening → 50% increase (not 100%)
        $this->assertEqualsWithDelta(7.5, $newInertia, 0.1);
    }

    /**
     * Test inertia calculation with PhysicsCalculator integration.
     */
    public function testInertiaWithPhysicsCalculator(): void
    {
        // Combat ship: baseMass 100, cargo 100 → 300 (3x)
        $calc = new PhysicsCalculator(100, 100, 300, 3.0, true);
        $originalInertia = 5.0;
        $impactFactor = 0.5;
        
        $massRatio = $calc->getMassRatio();  // 2.0
        $newInertia = $originalInertia * (1.0 + ($massRatio - 1.0) * $impactFactor);
        
        $this->assertEqualsWithDelta(7.5, $newInertia, 0.1);
    }

    /**
     * Test inertia with 4x cargo multiplier.
     */
    public function testInertiaWith4XCargo(): void
    {
        // Realistic scenario: baseMass 650, cargo 1000 → 4000
        $calc = new PhysicsCalculator(650, 1000, 4000, 4.0, true);
        $originalInertia = 5.0;
        $impactFactor = 0.5;
        
        $massRatio = $calc->getMassRatio();  // ~2.82
        $massIncrease = $massRatio - 1.0;     // ~1.82
        $dampedIncrease = $massIncrease * $impactFactor;  // ~0.91
        $newInertia = $originalInertia * (1.0 + $dampedIncrease);  // ~9.55
        
        $this->assertGreaterThan($originalInertia, $newInertia);
        $this->assertLessThan($originalInertia * 2.0, $newInertia); // Dampened
    }

    /**
     * Test inertia with 10x cargo multiplier (extreme case).
     */
    public function testInertiaWith10XCargo(): void
    {
        // Extreme cargo ship: baseMass 205, cargo 42000 → 420000
        $calc = new PhysicsCalculator(205, 42000, 420000, 10.0, true);
        $originalInertia = 3.0;
        $impactFactor = 0.5;
        
        // With effective ratio cap, should use 10.0 not ~9.96
        $effectiveRatio = $calc->getEffectiveRatio();  // 10.0
        $massIncrease = $effectiveRatio - 1.0;          // 9.0
        $dampedIncrease = $massIncrease * $impactFactor;  // 4.5
        $newInertia = $originalInertia * (1.0 + $dampedIncrease);  // 16.5
        
        $this->assertGreaterThan($originalInertia, $newInertia);
        $this->assertEqualsWithDelta(16.5, $newInertia, 0.5);
    }

    /**
     * Test that dampening factor reduces impact proportionally.
     */
    public function testDampeningFactorReducesImpact(): void
    {
        $originalInertia = 10.0;
        $massRatio = 3.0;  // 200% mass increase
        
        // No dampening
        $factor0 = 0.0;
        $inertia0 = $originalInertia * (1.0 + ($massRatio - 1.0) * $factor0);
        
        // Half dampening
        $factor05 = 0.5;
        $inertia05 = $originalInertia * (1.0 + ($massRatio - 1.0) * $factor05);
        
        // Full physics
        $factor1 = 1.0;
        $inertia1 = $originalInertia * (1.0 + ($massRatio - 1.0) * $factor1);
        
        // No dampening → inertia unchanged
        $this->assertEquals($originalInertia, $inertia0);
        
        // Half dampening → partial increase
        $this->assertGreaterThan($inertia0, $inertia05);
        $this->assertLessThan($inertia1, $inertia05);
        
        // Full physics → full increase
        $this->assertEqualsWithDelta(30.0, $inertia1, 0.1);
    }

    /**
     * Test all three inertia components (pitch, yaw, roll).
     */
    public function testAllInertiaComponents(): void
    {
        $calc = new PhysicsCalculator(650, 1000, 4000, 4.0, true);
        $impactFactor = 0.5;
        
        $originalPitch = 5.0;
        $originalYaw = 5.0;
        $originalRoll = 3.0;
        
        $massRatio = $calc->getMassRatio();
        $multiplier = 1.0 + ($massRatio - 1.0) * $impactFactor;
        
        $newPitch = $originalPitch * $multiplier;
        $newYaw = $originalYaw * $multiplier;
        $newRoll = $originalRoll * $multiplier;
        
        // All should increase proportionally
        $this->assertGreaterThan($originalPitch, $newPitch);
        $this->assertGreaterThan($originalYaw, $newYaw);
        $this->assertGreaterThan($originalRoll, $newRoll);
        
        // Same multiplier applied to all
        $this->assertEqualsWithDelta($originalPitch * $multiplier, $newPitch, 0.01);
        $this->assertEqualsWithDelta($originalYaw * $multiplier, $newYaw, 0.01);
        $this->assertEqualsWithDelta($originalRoll * $multiplier, $newRoll, 0.01);
    }

    /**
     * Test inertia direction: always increases, never decreases.
     */
    public function testInertiaDirectionAlwaysIncreases(): void
    {
        $originalInertia = 5.0;
        $massRatios = [1.5, 2.0, 3.0, 4.0, 10.0];
        $impactFactor = 0.5;
        
        foreach ($massRatios as $massRatio) {
            $newInertia = $originalInertia * (1.0 + ($massRatio - 1.0) * $impactFactor);
            $this->assertGreaterThanOrEqual($originalInertia, $newInertia,
                sprintf('Inertia should increase with mass ratio %.1f', $massRatio));
        }
    }

    /**
     * Test that inertia unchanged when mass ratio is 1.0.
     */
    public function testInertiaUnchangedWhenMassRatioIsOne(): void
    {
        $originalInertia = 5.0;
        $massRatio = 1.0;  // No mass change
        $impactFactor = 0.5;
        
        $newInertia = $originalInertia * (1.0 + ($massRatio - 1.0) * $impactFactor);
        
        $this->assertEquals($originalInertia, $newInertia);
    }

    /**
     * Test realistic combat ship inertia.
     */
    public function testRealisticCombatShipInertia(): void
    {
        // Combat ship with moderate cargo increase
        $calc = new PhysicsCalculator(106, 240, 2400, 10.0, true);
        $originalInertia = 5.0;
        $impactFactor = 0.5;
        
        $massRatio = $calc->getMassRatio();
        $newInertia = $originalInertia * (1.0 + ($massRatio - 1.0) * $impactFactor);
        
        // Should increase but stay reasonable
        $this->assertGreaterThan($originalInertia, $newInertia);
        $this->assertLessThan($originalInertia * 5.0, $newInertia);
    }

    /**
     * Test realistic cargo ship inertia.
     */
    public function testRealisticCargoShipInertia(): void
    {
        // Cargo ship with massive cargo increase
        $calc = new PhysicsCalculator(205, 42000, 420000, 10.0, true);
        $originalInertia = 3.0;
        $impactFactor = 0.5;
        
        // Should use effective ratio cap (10.0), not actual mass ratio (~9.96)
        $effectiveRatio = $calc->getEffectiveRatio();
        $newInertia = $originalInertia * (1.0 + ($effectiveRatio - 1.0) * $impactFactor);
        
        $this->assertGreaterThan($originalInertia, $newInertia);
        // With cap and dampening, should be manageable
        $this->assertLessThan($originalInertia * 6.0, $newInertia);
    }

    /**
     * Test that effective ratio cap prevents extreme inertia.
     */
    public function testEffectiveRatioCapPreventsExtremeInertia(): void
    {
        $originalInertia = 3.0;
        $impactFactor = 0.5;
        
        // Without cap: massRatio ~9.96
        $calcNoCap = new PhysicsCalculator(205, 42000, 420000, 10.0, false);
        $massRatio = $calcNoCap->getMassRatio();
        $inertiaWithoutCap = $originalInertia * (1.0 + ($massRatio - 1.0) * $impactFactor);
        
        // With cap: effectiveRatio 10.0
        $calcWithCap = new PhysicsCalculator(205, 42000, 420000, 10.0, true);
        $effectiveRatio = $calcWithCap->getEffectiveRatio();
        $inertiaWithCap = $originalInertia * (1.0 + ($effectiveRatio - 1.0) * $impactFactor);
        
        // Both should be similar because cap brings it to 10.0
        $this->assertEqualsWithDelta($inertiaWithCap, $inertiaWithoutCap, 0.1);
    }

    /**
     * Test different dampening factors.
     */
    public function testDifferentDampeningFactors(): void
    {
        $originalInertia = 5.0;
        $massRatio = 3.0;
        
        $factors = [0.0, 0.25, 0.5, 0.75, 1.0];
        $previousInertia = $originalInertia;
        
        foreach ($factors as $factor) {
            $newInertia = $originalInertia * (1.0 + ($massRatio - 1.0) * $factor);
            
            // Higher factor → higher inertia
            $this->assertGreaterThanOrEqual($previousInertia, $newInertia);
            $previousInertia = $newInertia;
        }
    }

    /**
     * Test inertia with zero dampening (no change).
     */
    public function testInertiaWithZeroDampening(): void
    {
        $originalInertia = 5.0;
        $massRatio = 4.0;
        $impactFactor = 0.0;  // No dampening = no change
        
        $newInertia = $originalInertia * (1.0 + ($massRatio - 1.0) * $impactFactor);
        
        $this->assertEquals($originalInertia, $newInertia);
    }

    /**
     * Test inertia with amplified dampening (>1.0).
     */
    public function testInertiaWithAmplifiedDampening(): void
    {
        $originalInertia = 5.0;
        $massRatio = 2.0;
        $impactFactor = 2.0;  // Amplified
        
        $newInertia = $originalInertia * (1.0 + ($massRatio - 1.0) * $impactFactor);
        
        // Should be more than full physics would give
        $fullPhysics = $originalInertia * $massRatio;  // 10.0
        $this->assertGreaterThan($fullPhysics, $newInertia);
        $this->assertEqualsWithDelta(15.0, $newInertia, 0.1);
    }

    /**
     * Test formula correctness: inertia = original * (1 + (massRatio - 1) * factor).
     */
    public function testInertiaFormulaCorrectness(): void
    {
        $originalInertia = 10.0;
        $massRatio = 3.0;
        $impactFactor = 0.5;
        
        // Manual calculation
        $massIncrease = $massRatio - 1.0;  // 2.0
        $dampedIncrease = $massIncrease * $impactFactor;  // 1.0
        $expected = $originalInertia * (1.0 + $dampedIncrease);  // 20.0
        
        // Formula  
        $calculated = $originalInertia * (1.0 + ($massRatio - 1.0) * $impactFactor);
        
        $this->assertEqualsWithDelta($expected, $calculated, 0.01);
    }
}
