<?php
/**
 * Tests for jerk adjustment calculations.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage Tests
 */

declare(strict_types=1);

namespace CargoSizesModTests;

use Mistralys\X4\Mods\CargoSizesMod\Build\ReductionTier;
use PHPUnit\Framework\TestCase;

/**
 * Tests jerk adjustment logic to ensure jerk decreases (NEVER increases)
 * and that the backwards physics bug is fixed.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage Tests
 */
class JerkAdjustmentTest extends TestCase
{
    /**
     * Test that jerk decreases with tier-based reduction.
     */
    public function testJerkDecreases(): void
    {
        // Verify jerk goes DOWN, not UP
        $originalJerk = 5.8;
        $tier = new ReductionTier(4.0, 0.15); // 15% reduction
        
        $newJerk = $tier->applyReduction($originalJerk);
        
        $this->assertLessThan($originalJerk, $newJerk);
        $this->assertGreaterThan(0, $newJerk);
        $this->assertEqualsWithDelta(4.93, $newJerk, 0.01);
    }

    /**
     * Test that travel jerk has NO extra penalty.
     */
    public function testTravelJerkNoExtraPenalty(): void
    {
        // Travel jerk should use same tier as regular jerk (no 2x penalty)
        $originalJerk = 0.82;
        $jerkTier = new ReductionTier(4.0, 0.15);
        
        $newJerk = $jerkTier->applyReduction($originalJerk);
        
        // Should be 85% of original, not 70% (which would be 2x penalty)
        $this->assertEqualsWithDelta(0.697, $newJerk, 0.01);
        $this->assertGreaterThan(0.7 * $originalJerk, $newJerk); // More than 70%
    }

    /**
     * Test 2x cargo gets 5% jerk reduction.
     */
    public function testTwoXCargoGets5PercentReduction(): void
    {
        $originalJerk = 5.8;
        $tier = new ReductionTier(2.0, 0.05);
        
        $newJerk = $tier->applyReduction($originalJerk);
        
        // Should be 95% of original
        $expected = $originalJerk * 0.95;
        $this->assertEqualsWithDelta($expected, $newJerk, 0.01);
    }

    /**
     * Test 4x cargo gets 15% jerk reduction.
     */
    public function testFourXCargoGets15PercentReduction(): void
    {
        $originalJerk = 5.8;
        $tier = new ReductionTier(4.0, 0.15);
        
        $newJerk = $tier->applyReduction($originalJerk);
        
        // Should be 85% of original
        $expected = $originalJerk * 0.85;
        $this->assertEqualsWithDelta($expected, $newJerk, 0.01);
    }

    /**
     * Test 8x cargo gets 25% jerk reduction.
     */
    public function testEightXCargoGets25PercentReduction(): void
    {
        $originalJerk = 5.8;
        $tier = new ReductionTier(8.0, 0.25);
        
        $newJerk = $tier->applyReduction($originalJerk);
        
        // Should be 75% of original
        $expected = $originalJerk * 0.75;
        $this->assertEqualsWithDelta($expected, $newJerk, 0.01);
    }

    /**
     * Test 10x cargo gets 35% jerk reduction.
     */
    public function testTenXCargoGets35PercentReduction(): void
    {
        $originalJerk = 5.8;
        $tier = new ReductionTier(999, 0.35);
        
        $newJerk = $tier->applyReduction($originalJerk);
        
        // Should be 65% of original
        $expected = $originalJerk * 0.65;
        $this->assertEqualsWithDelta($expected, $newJerk, 0.01);
    }

    /**
     * Test forward jerk (acceleration and deceleration).
     */
    public function testForwardJerkBothDirections(): void
    {
        $originalAccel = 3.9;
        $originalDecel = 5.8;
        $tier = new ReductionTier(4.0, 0.15);
        
        $newAccel = $tier->applyReduction($originalAccel);
        $newDecel = $tier->applyReduction($originalDecel);
        
        // Both should be reduced by 15%
        $this->assertEqualsWithDelta(3.315, $newAccel, 0.01);
        $this->assertEqualsWithDelta(4.93, $newDecel, 0.01);
    }

    /**
     * Test boost jerk reduction.
     */
    public function testBoostJerkReduction(): void
    {
        $originalBoostAccel = 7.8;
        $tier = new ReductionTier(4.0, 0.15);
        
        $newBoostAccel = $tier->applyReduction($originalBoostAccel);
        
        $this->assertEqualsWithDelta(6.63, $newBoostAccel, 0.01);
        $this->assertLessThan($originalBoostAccel, $newBoostAccel);
    }

    /**
     * Test travel jerk (critical for travel mode fix).
     */
    public function testTravelJerkReduction(): void
    {
        $originalTravelAccel = 0.41;
        $originalTravelDecel = 0.82;
        $tier = new ReductionTier(4.0, 0.15);
        
        $newAccel = $tier->applyReduction($originalTravelAccel);
        $newDecel = $tier->applyReduction($originalTravelDecel);
        
        // Should be reduced by 15%, not doubled penalty
        $this->assertEqualsWithDelta(0.3485, $newAccel, 0.01);
        $this->assertEqualsWithDelta(0.697, $newDecel, 0.01);
    }

    /**
     * Test that jerk never becomes zero or negative.
     */
    public function testJerkNeverZeroOrNegative(): void
    {
        $originalJerk = 1.0;
        $tier = new ReductionTier(999, 0.35); // Max jerk reduction
        
        $newJerk = $tier->applyReduction($originalJerk);
        
        $this->assertGreaterThan(0, $newJerk);
    }

    /**
     * Test jerk direction: always decreases, never increases.
     */
    public function testJerkDirectionAlwaysDecreases(): void
    {
        $originalJerk = 10.0;
        
        // Test all tiers
        $tiers = [
            new ReductionTier(2.0, 0.05),
            new ReductionTier(4.0, 0.15),
            new ReductionTier(8.0, 0.25),
            new ReductionTier(999, 0.35)
        ];
        
        foreach ($tiers as $tier) {
            $newJerk = $tier->applyReduction($originalJerk);
            $this->assertLessThan($originalJerk, $newJerk,
                sprintf('Jerk should decrease with tier %s', $tier->format()));
        }
    }

    /**
     * Test strafe jerk reduction.
     */
    public function testStrafeJerkReduction(): void
    {
        $originalStrafeJerk = 5.8;
        $tier = new ReductionTier(4.0, 0.15);
        
        $newJerk = $tier->applyReduction($originalStrafeJerk);
        
        $this->assertEqualsWithDelta(4.93, $newJerk, 0.01);
        $this->assertLessThan($originalStrafeJerk, $newJerk);
    }

    /**
     * Test angular jerk reduction.
     */
    public function testAngularJerkReduction(): void
    {
        $originalAngularJerk = 7.3;
        $tier = new ReductionTier(4.0, 0.15);
        
        $newJerk = $tier->applyReduction($originalAngularJerk);
        
        $this->assertEqualsWithDelta(6.205, $newJerk, 0.01);
        $this->assertLessThan($originalAngularJerk, $newJerk);
    }

    /**
     * Test that all jerk components use same tier logic.
     */
    public function testConsistentJerkReductionAcrossComponents(): void
    {
        $tier = new ReductionTier(4.0, 0.15);
        
        $strafeJerk = 5.8;
        $angularJerk = 7.3;
        $forwardAccel = 3.9;
        $forwardDecel = 5.8;
        $boostAccel = 7.8;
        $travelAccel = 0.41;
        $travelDecel = 0.82;
        
        $newStrafe = $tier->applyReduction($strafeJerk);
        $newAngular = $tier->applyReduction($angularJerk);
        $newFwdAccel = $tier->applyReduction($forwardAccel);
        $newFwdDecel = $tier->applyReduction($forwardDecel);
        $newBoost = $tier->applyReduction($boostAccel);
        $newTravelAccel = $tier->applyReduction($travelAccel);
        $newTravelDecel = $tier->applyReduction($travelDecel);
        
        // All should be reduced by same percentage (15%)
        $this->assertEqualsWithDelta($strafeJerk * 0.85, $newStrafe, 0.01);
        $this->assertEqualsWithDelta($angularJerk * 0.85, $newAngular, 0.01);
        $this->assertEqualsWithDelta($forwardAccel * 0.85, $newFwdAccel, 0.01);
        $this->assertEqualsWithDelta($forwardDecel * 0.85, $newFwdDecel, 0.01);
        $this->assertEqualsWithDelta($boostAccel * 0.85, $newBoost, 0.01);
        $this->assertEqualsWithDelta($travelAccel * 0.85, $newTravelAccel, 0.01);
        $this->assertEqualsWithDelta($travelDecel * 0.85, $newTravelDecel, 0.01);
    }

    /**
     * Test realistic combat ship jerk scenario.
     */
    public function testRealisticCombatShipJerk(): void
    {
        // Combat ship with 4x cargo
        $originalJerk = 5.8;
        $tier = new ReductionTier(4.0, 0.15);
        
        $newJerk = $tier->applyReduction($originalJerk);
        
        $this->assertEqualsWithDelta(4.93, $newJerk, 0.01);
    }

    /**
     * Test realistic cargo ship jerk scenario.
     */
    public function testRealisticCargoShipJerk(): void
    {
        // Large cargo ship with 10x cargo
        $originalJerk = 5.8;
        $tier = new ReductionTier(999, 0.35);
        
        $newJerk = $tier->applyReduction($originalJerk);
        
        $this->assertEqualsWithDelta(3.77, $newJerk, 0.01);
    }

    /**
     * Test incremental reductions (higher tiers = more reduction).
     */
    public function testIncrementalJerkReductions(): void
    {
        $originalJerk = 10.0;
        
        $tier2x = new ReductionTier(2.0, 0.05);
        $tier4x = new ReductionTier(4.0, 0.15);
        $tier8x = new ReductionTier(8.0, 0.25);
        $tier10x = new ReductionTier(999, 0.35);
        
        $jerk2x = $tier2x->applyReduction($originalJerk);
        $jerk4x = $tier4x->applyReduction($originalJerk);
        $jerk8x = $tier8x->applyReduction($originalJerk);
        $jerk10x = $tier10x->applyReduction($originalJerk);
        
        // Each tier should have more reduction (resulting in lower values)
        $this->assertGreaterThan($jerk4x, $jerk2x);
        $this->assertGreaterThan($jerk8x, $jerk4x);
        $this->assertGreaterThan($jerk10x, $jerk8x);
        
        // All should be less than original
        $this->assertLessThan($originalJerk, $jerk2x);
        $this->assertLessThan($originalJerk, $jerk4x);
        $this->assertLessThan($originalJerk, $jerk8x);
        $this->assertLessThan($originalJerk, $jerk10x);
    }

    /**
     * Test that jerk reduction is less aggressive than drag reduction.
     */
    public function testJerkReductionLessAggressiveThanDrag(): void
    {
        $originalValue = 100.0;
        
        // 4x cargo: drag 30%, jerk 15%
        $dragTier = new ReductionTier(4.0, 0.30);
        $jerkTier = new ReductionTier(4.0, 0.15);
        
        $newDrag = $dragTier->applyReduction($originalValue);
        $newJerk = $jerkTier->applyReduction($originalValue);
        
        // Jerk reduction should be less aggressive
        $this->assertLessThan($dragTier->getReductionPercent(), $jerkTier->getReductionPercent());
        $this->assertGreaterThan($newDrag, $newJerk); // Jerk retains more of original value
    }

    /**
     * Test that zero reduction keeps jerk unchanged.
     */
    public function testZeroReductionKeepsJerkUnchanged(): void
    {
        $originalJerk = 5.8;
        $tier = new ReductionTier(1.0, 0.0); // 0% reduction
        
        $newJerk = $tier->applyReduction($originalJerk);
        
        $this->assertEquals($originalJerk, $newJerk);
    }

    /**
     * Test jerk with very small values (travel jerk).
     */
    public function testJerkWithVerySmallValues(): void
    {
        $originalJerk = 0.01;
        $tier = new ReductionTier(4.0, 0.15);
        
        $newJerk = $tier->applyReduction($originalJerk);
        
        $this->assertGreaterThan(0, $newJerk);
        $this->assertLessThan($originalJerk, $newJerk);
        $this->assertEqualsWithDelta(0.0085, $newJerk, 0.0001);
    }
}
