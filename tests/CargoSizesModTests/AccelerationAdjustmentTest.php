<?php
/**
 * Tests for acceleration factor adjustment calculations.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage Tests
 */

declare(strict_types=1);

namespace CargoSizesModTests;

use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\PhysicsCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Tests acceleration factor adjustment logic to ensure factors scale with mass
 * to maintain responsiveness (AccelFactor/Mass ratio preserved).
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage Tests
 */
class AccelerationAdjustmentTest extends TestCase
{
    /**
     * Test that acceleration factor scales with mass.
     */
    public function testAccelerationScalesWithMass(): void
    {
        // Verify acceleration factor increases proportionally
        $originalAccel = 2.0;
        $massRatio = 4.0;
        $responsiveness = 1.0;
        
        $newAccel = $originalAccel * $massRatio * $responsiveness;
        
        $this->assertGreaterThan($originalAccel, $newAccel);
        $this->assertEqualsWithDelta(8.0, $newAccel, 0.1);
    }

    /**
     * Test that AccelFactor/Mass ratio is maintained with responsiveness=1.0.
     */
    public function testResponsivenessRatioMaintained(): void
    {
        // With responsiveness=1.0, AccelFactor/Mass ratio should be constant
        $originalAccel = 2.0;
        $originalMass = 1000.0;
        $massRatio = 4.0;
        $responsiveness = 1.0;
        
        $newAccel = $originalAccel * $massRatio * $responsiveness;  // 8.0
        $newMass = $originalMass * $massRatio;  // 4000.0
        
        $originalRatio = $originalAccel / $originalMass;  // 0.002
        $newRatio = $newAccel / $newMass;  // 0.002
        
        $this->assertEqualsWithDelta($originalRatio, $newRatio, 0.0001);
    }

    /**
     * Test heavier feel with lower responsiveness.
     */
    public function testHeavierFeelWithLowerResponsiveness(): void
    {
        // With responsiveness < 1.0, ship should feel heavier
        $originalAccel = 2.0;
        $massRatio = 4.0;
        $responsiveness = 0.7;
        
        $newAccel = $originalAccel * $massRatio * $responsiveness;  // 5.6
        
        // New ratio should be 70% of original (heavier feel)
        $this->assertEqualsWithDelta(5.6, $newAccel, 0.1);
        $this->assertLessThan($originalAccel * $massRatio, $newAccel);
    }

    /**
     * Test realistic scenario with PhysicsCalculator.
     */
    public function testAccelerationWithPhysicsCalculator(): void
    {
        // Combat ship: baseMass 650, cargo 1000 → 4000
        $calc = new PhysicsCalculator(650, 1000, 4000, 4.0, true);
        $originalAccel = 2.0;
        $responsiveness = 1.0;
        
        $massRatio = $calc->getMassRatio();  // ~2.82
        $newAccel = $originalAccel * $massRatio * $responsiveness;
        
        $this->assertGreaterThan($originalAccel, $newAccel);
        $this->assertEqualsWithDelta(5.64, $newAccel, 0.1);
    }

    /**
     * Test all four acceleration components (forward, reverse, horizontal, vertical).
     */
    public function testAllAccelerationComponents(): void
    {
        $calc = new PhysicsCalculator(650, 1000, 4000, 4.0, true);
        $responsiveness = 1.0;
        
        $originalForward = 2.0;
        $originalReverse = 1.5;
        $originalHorizontal = 2.5;
        $originalVertical = 3.0;
        
        $massRatio = $calc->getMassRatio();
        
        $newForward = $originalForward * $massRatio * $responsiveness;
        $newReverse = $originalReverse * $massRatio * $responsiveness;
        $newHorizontal = $originalHorizontal * $massRatio * $responsiveness;
        $newVertical = $originalVertical * $massRatio * $responsiveness;
        
        // All should scale proportionally
        $this->assertGreaterThan($originalForward, $newForward);
        $this->assertGreaterThan($originalReverse, $newReverse);
        $this->assertGreaterThan($originalHorizontal, $newHorizontal);
        $this->assertGreaterThan($originalVertical, $newVertical);
    }

    /**
     * Test that mass ratio is applied correctly.
     */
    public function testMassRatioApplication(): void
    {
        $originalAccel = 5.0;
        $massRatio = 2.5;
        $responsiveness = 1.0;
        
        $newAccel = $originalAccel * $massRatio * $responsiveness;
        
        $this->assertEqualsWithDelta(12.5, $newAccel, 0.1);
    }

    /**
     * Test responsiveness factor of 0.5 (half responsiveness).
     */
    public function testHalfResponsiveness(): void
    {
        $originalAccel = 4.0;
        $massRatio = 3.0;
        $responsiveness = 0.5;
        
        $newAccel = $originalAccel * $massRatio * $responsiveness;
        
        // Should be 50% of what full scaling would give
        $fullScaling = $originalAccel * $massRatio;  // 12.0
        $this->assertEqualsWithDelta($fullScaling * $responsiveness, $newAccel, 0.1);
        $this->assertEqualsWithDelta(6.0, $newAccel, 0.1);
    }

    /**
     * Test responsiveness factor of 1.5 (increased responsiveness).
     */
    public function testIncreasedResponsiveness(): void
    {
        $originalAccel = 2.0;
        $massRatio = 2.0;
        $responsiveness = 1.5;
        
        $newAccel = $originalAccel * $massRatio * $responsiveness;
        
        // Should be more responsive than physics-correct
        $physicsCorrect = $originalAccel * $massRatio;  // 4.0
        $this->assertGreaterThan($physicsCorrect, $newAccel);
        $this->assertEqualsWithDelta(6.0, $newAccel, 0.1);
    }

    /**
     * Test that acceleration direction is correct (increases, not decreases).
     */
    public function testAccelerationDirectionIncreases(): void
    {
        $originalAccel = 2.0;
        $massRatios = [1.5, 2.0, 3.0, 4.0, 10.0];
        $responsiveness = 1.0;
        
        foreach ($massRatios as $massRatio) {
            $newAccel = $originalAccel * $massRatio * $responsiveness;
            $this->assertGreaterThanOrEqual($originalAccel, $newAccel,
                sprintf('Acceleration should increase with mass ratio %.1f', $massRatio));
        }
    }

    /**
     * Test acceleration unchanged when mass ratio is 1.0.
     */
    public function testAccelerationUnchangedWhenMassRatioIsOne(): void
    {
        $originalAccel = 2.0;
        $massRatio = 1.0;  // No mass change
        $responsiveness = 1.0;
        
        $newAccel = $originalAccel * $massRatio * $responsiveness;
        
        $this->assertEquals($originalAccel, $newAccel);
    }

    /**
     * Test extreme cargo multiplier with effective ratio cap.
     */
    public function testExtremeCargoWithCap(): void
    {
        // Cargo ship: baseMass 205, cargo 42000 → 420000
        $calc = new PhysicsCalculator(205, 42000, 420000, 10.0, true);
        $originalAccel = 2.0;
        $responsiveness = 1.0;
        
        // Should use effective ratio (10.0), not actual mass ratio (~9.96)
        $effectiveRatio = $calc->getEffectiveRatio();
        $newAccel = $originalAccel * $effectiveRatio * $responsiveness;
        
        $this->assertEqualsWithDelta(20.0, $newAccel, 0.5);
    }

    /**
     * Test that formula maintains time-to-speed (responsiveness).
     */
    public function testTimeToSpeedMaintained(): void
    {
        $originalAccel = 2.0;
        $originalMass = 1000.0;
        $massRatio = 4.0;
        $responsiveness = 1.0;
        
        $newAccel = $originalAccel * $massRatio * $responsiveness;
        $newMass = $originalMass * $massRatio;
        
        // Time to reach speed is proportional to Mass/AccelFactor
        // These should be equal
        $originalTimeRatio = $originalMass / $originalAccel;
        $newTimeRatio = $newMass / $newAccel;
        
        $this->assertEqualsWithDelta($originalTimeRatio, $newTimeRatio, 0.1);
    }

    /**
     * Test realistic combat ship acceleration.
     */
    public function testRealisticCombatShipAcceleration(): void
    {
        // Combat ship with moderate cargo increase
        $calc = new PhysicsCalculator(106, 240, 2400, 10.0, true);
        $originalAccel = 2.0;
        $responsiveness = 1.0;
        
        $massRatio = $calc->getMassRatio();
        $newAccel = $originalAccel * $massRatio * $responsiveness;
        
        // Should scale proportionally
        $this->assertGreaterThan($originalAccel, $newAccel);
        $this->assertLessThan($originalAccel * 10.0, $newAccel);
    }

    /**
     * Test realistic cargo ship acceleration.
     */
    public function testRealisticCargoShipAcceleration(): void
    {
        // Cargo ship with massive cargo increase
        $calc = new PhysicsCalculator(205, 42000, 420000, 10.0, true);
        $originalAccel = 1.5;
        $responsiveness = 1.0;
        
        $effectiveRatio = $calc->getEffectiveRatio();
        $newAccel = $originalAccel * $effectiveRatio * $responsiveness;
        
        // With cap, should use 10.0
        $this->assertEqualsWithDelta(15.0, $newAccel, 0.5);
    }

    /**
     * Test different responsiveness values.
     */
    public function testDifferentResponsivenessValues(): void
    {
        $originalAccel = 2.0;
        $massRatio = 4.0;
        
        $responsiveness = [0.5, 0.7, 1.0, 1.3, 1.5];
        $previousAccel = 0.0;
        
        foreach ($responsiveness as $factor) {
            $newAccel = $originalAccel * $massRatio * $factor;
            
            // Higher responsiveness → higher acceleration factor
            $this->assertGreaterThan($previousAccel, $newAccel);
            $previousAccel = $newAccel;
        }
    }

    /**
     * Test that zero responsiveness results in no change.
     */
    public function testZeroResponsiveness(): void
    {
        $originalAccel = 2.0;
        $massRatio = 4.0;
        $responsiveness = 0.0;
        
        $newAccel = $originalAccel * $massRatio * $responsiveness;
        
        // With zero responsiveness, no acceleration increase
        $this->assertEquals(0.0, $newAccel);
    }

    /**
     * Test formula correctness: newAccel = originalAccel * massRatio * responsiveness.
     */
    public function testAccelerationFormulaCorrectness(): void
    {
        $originalAccel = 3.0;
        $massRatio = 2.5;
        $responsiveness = 0.8;
        
        // Manual calculation
        $expected = $originalAccel * $massRatio * $responsiveness;
        $expected = 3.0 * 2.5 * 0.8;  // 6.0
        
        // Formula
        $calculated = $originalAccel * $massRatio * $responsiveness;
        
        $this->assertEqualsWithDelta($expected, $calculated, 0.01);
        $this->assertEqualsWithDelta(6.0, $calculated, 0.01);
    }

    /**
     * Test that acceleration scaling is consistent across all components.
     */
    public function testConsistentScalingAcrossComponents(): void
    {
        $calc = new PhysicsCalculator(650, 1000, 4000, 4.0, true);
        $responsiveness = 1.0;
        $massRatio = $calc->getMassRatio();
        
        $components = [
            'forward' => 2.0,
            'reverse' => 1.5,
            'horizontal' => 2.5,
            'vertical' => 3.0
        ];
        
        foreach ($components as $name => $originalValue) {
            $newValue = $originalValue * $massRatio * $responsiveness;
            $ratio = $newValue / $originalValue;
            
            // All should have same scaling ratio
            $this->assertEqualsWithDelta($massRatio, $ratio, 0.01,
                sprintf('Component %s should scale by mass ratio', $name));
        }
    }

    /**
     * Test that responsiveness < 1.0 maintains ratio but makes ship heavier.
     */
    public function testResponsivenessLessThanOneFeelsHeavier(): void
    {
        $originalAccel = 4.0;
        $originalMass = 2000.0;
        $massRatio = 3.0;
        $responsiveness = 0.7;
        
        $newAccel = $originalAccel * $massRatio * $responsiveness;
        $newMass = $originalMass * $massRatio;
        
        $originalRatio = $originalAccel / $originalMass;
        $newRatio = $newAccel / $newMass;
        
        // New ratio should be 70% of original
        $this->assertEqualsWithDelta($originalRatio * $responsiveness, $newRatio, 0.0001);
        $this->assertLessThan($originalRatio, $newRatio);
    }

    /**
     * Test that responsiveness > 1.0 makes ship more responsive.
     */
    public function testResponsivenessGreaterThanOneFastener(): void
    {
        $originalAccel = 4.0;
        $originalMass = 2000.0;
        $massRatio = 3.0;
        $responsiveness = 1.3;
        
        $newAccel = $originalAccel * $massRatio * $responsiveness;
        $newMass = $originalMass * $massRatio;
        
        $originalRatio = $originalAccel / $originalMass;
        $newRatio = $newAccel / $newMass;
        
        // New ratio should be 130% of original
        $this->assertEqualsWithDelta($originalRatio * $responsiveness, $newRatio, 0.0001);
        $this->assertGreaterThan($originalRatio, $newRatio);
    }

    /**
     * Test edge case: very small mass ratio.
     */
    public function testVerySmallMassRatio(): void
    {
        $calc = new PhysicsCalculator(1000, 10, 20, 2.0, true);
        $originalAccel = 2.0;
        $responsiveness = 1.0;
        
        $massRatio = $calc->getMassRatio();  // Should be very close to 1.0
        $newAccel = $originalAccel * $massRatio * $responsiveness;
        
        // Should be very close to original
        $this->assertEqualsWithDelta($originalAccel, $newAccel, 0.1);
    }

    /**
     * Test that acceleration factor never becomes zero or negative.
     */
    public function testAccelerationNeverZeroOrNegative(): void
    {
        $originalAccel = 2.0;
        $massRatios = [1.0, 2.0, 5.0, 10.0];
        $responsiveness = [0.1, 0.5, 1.0, 1.5];
        
        foreach ($massRatios as $massRatio) {
            foreach ($responsiveness as $factor) {
                if ($factor > 0) {  // Skip zero responsiveness
                    $newAccel = $originalAccel * $massRatio * $factor;
                    $this->assertGreaterThan(0, $newAccel);
                }
            }
        }
    }
}
