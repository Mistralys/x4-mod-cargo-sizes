<?php
/**
 * Tests for drag adjustment calculations.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage Tests
 */

declare(strict_types=1);

namespace CargoSizesModTests;

use Mistralys\X4\Mods\CargoSizesMod\Build\ReductionTier;
use PHPUnit\Framework\TestCase;

/**
 * Tests drag adjustment logic to ensure drag decreases (never increases)
 * and tier-based reductions work correctly.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage Tests
 */
class DragAdjustmentTest extends TestCase
{
    /**
     * Test that drag decreases with tier-based reduction.
     */
    public function testDragDecreasesWithTierSystem(): void
    {
        // Original drag: 15.0, 4x cargo → 30% reduction
        $originalDrag = 15.0;
        $tier = new ReductionTier(4.0, 0.30);
        
        $newDrag = $tier->applyReduction($originalDrag);
        
        $this->assertLessThan($originalDrag, $newDrag);
        $this->assertEqualsWithDelta(10.5, $newDrag, 0.1); // 70% of 15.0
    }

    /**
     * Test that drag never becomes negative.
     */
    public function testDragNeverNegative(): void
    {
        // Even with extreme reduction, drag should stay positive
        $originalDrag = 5.0;
        $tier = new ReductionTier(999, 0.70); // 70% reduction (safety cap)
        
        $newDrag = $tier->applyReduction($originalDrag);
        
        $this->assertGreaterThan(0, $newDrag);
        $this->assertEqualsWithDelta(1.5, $newDrag, 0.1); // 30% of 5.0
    }

    /**
     * Test safety cap (max 70% reduction).
     */
    public function testSafetyCap(): void
    {
        // Max reduction is 70% (30% remains)
        $originalDrag = 20.0;
        $tier = new ReductionTier(999, 0.70); // Safety cap
        
        $newDrag = $tier->applyReduction($originalDrag);
        
        $this->assertEqualsWithDelta(6.0, $newDrag, 0.1); // 30% of 20.0
        $this->assertGreaterThan(0.25 * $originalDrag, $newDrag); // Never below 25%
    }

    /**
     * Test 2x cargo gets 10% drag reduction.
     */
    public function testTwoXCargoGets10PercentReduction(): void
    {
        $originalDrag = 17.9;
        $tier = new ReductionTier(2.0, 0.10);
        
        $newDrag = $tier->applyReduction($originalDrag);
        
        // Should be 90% of original
        $expected = $originalDrag * 0.90;
        $this->assertEqualsWithDelta($expected, $newDrag, 0.01);
    }

    /**
     * Test 4x cargo gets 30% drag reduction.
     */
    public function testFourXCargoGets30PercentReduction(): void
    {
        $originalDrag = 17.9;
        $tier = new ReductionTier(4.0, 0.30);
        
        $newDrag = $tier->applyReduction($originalDrag);
        
        // Should be 70% of original
        $expected = $originalDrag * 0.70;
        $this->assertEqualsWithDelta($expected, $newDrag, 0.01);
    }

    /**
     * Test 8x cargo gets 50% drag reduction.
     */
    public function testEightXCargoGets50PercentReduction(): void
    {
        $originalDrag = 20.0;
        $tier = new ReductionTier(8.0, 0.50);
        
        $newDrag = $tier->applyReduction($originalDrag);
        
        // Should be 50% of original
        $expected = $originalDrag * 0.50;
        $this->assertEqualsWithDelta($expected, $newDrag, 0.01);
    }

    /**
     * Test 10x cargo gets 70% drag reduction (safety cap).
     */
    public function testTenXCargoGets70PercentReduction(): void
    {
        $originalDrag = 100.0;
        $tier = new ReductionTier(999, 0.70);
        
        $newDrag = $tier->applyReduction($originalDrag);
        
        // Should be 30% of original (70% reduction)
        $expected = $originalDrag * 0.30;
        $this->assertEqualsWithDelta($expected, $newDrag, 0.01);
    }

    /**
     * Test drag reduction with very small original values.
     */
    public function testDragReductionWithSmallValues(): void
    {
        $originalDrag = 1.0;
        $tier = new ReductionTier(4.0, 0.30);
        
        $newDrag = $tier->applyReduction($originalDrag);
        
        $this->assertGreaterThan(0, $newDrag);
        $this->assertLessThan($originalDrag, $newDrag);
        $this->assertEqualsWithDelta(0.7, $newDrag, 0.01);
    }

    /**
     * Test drag reduction with large original values.
     */
    public function testDragReductionWithLargeValues(): void
    {
        $originalDrag = 1000.0;
        $tier = new ReductionTier(4.0, 0.30);
        
        $newDrag = $tier->applyReduction($originalDrag);
        
        $this->assertGreaterThan(0, $newDrag);
        $this->assertLessThan($originalDrag, $newDrag);
        $this->assertEqualsWithDelta(700.0, $newDrag, 0.1);
    }

    /**
     * Test that zero reduction percentkeeps drag unchanged.
     */
    public function testZeroReductionKeepsDragUnchanged(): void
    {
        $originalDrag = 15.0;
        $tier = new ReductionTier(1.0, 0.0); // 0% reduction
        
        $newDrag = $tier->applyReduction($originalDrag);
        
        $this->assertEquals($originalDrag, $newDrag);
    }

    /**
     * Test consistent reduction across all drag components.
     */
    public function testConsistentReductionAcrossComponents(): void
    {
        $tier = new ReductionTier(4.0, 0.30);
        
        // All 7 drag components
        $forward = 17.9;
        $reverse = 26.7;
        $horizontal = 35.2;
        $vertical = 35.1;
        $pitch = 10.5;
        $yaw = 10.5;
        $roll = 7.8;
        
        $newForward = $tier->applyReduction($forward);
        $newReverse = $tier->applyReduction($reverse);
        $newHorizontal = $tier->applyReduction($horizontal);
        $newVertical = $tier->applyReduction($vertical);
        $newPitch = $tier->applyReduction($pitch);
        $newYaw = $tier->applyReduction($yaw);
        $newRoll = $tier->applyReduction($roll);
        
        // All should be reduced by same percentage
        $this->assertEqualsWithDelta($forward * 0.70, $newForward, 0.01);
        $this->assertEqualsWithDelta($reverse * 0.70, $newReverse, 0.01);
        $this->assertEqualsWithDelta($horizontal * 0.70, $newHorizontal, 0.01);
        $this->assertEqualsWithDelta($vertical * 0.70, $newVertical, 0.01);
        $this->assertEqualsWithDelta($pitch * 0.70, $newPitch, 0.01);
        $this->assertEqualsWithDelta($yaw * 0.70, $newYaw, 0.01);
        $this->assertEqualsWithDelta($roll * 0.70, $newRoll, 0.01);
    }

    /**
     * Test direction: drag always decreases, never increases.
     */
    public function testDragDirectionAlwaysDecreases(): void
    {
        $originalDrag = 50.0;
        
        // Test all tiers
        $tiers = [
            new ReductionTier(2.0, 0.10),
            new ReductionTier(4.0, 0.30),
            new ReductionTier(8.0, 0.50),
            new ReductionTier(999, 0.70)
        ];
        
        foreach ($tiers as $tier) {
            $newDrag = $tier->applyReduction($originalDrag);
            $this->assertLessThan($originalDrag, $newDrag, 
                sprintf('Drag should decrease with tier %s', $tier->format()));
        }
    }

    /**
     * Test realistic combat ship drag scenario.
     */
    public function testRealisticCombatShipDrag(): void
    {
        // Combat ship with 4x cargo
        $originalForwardDrag = 17.9;
        $tier = new ReductionTier(4.0, 0.30);
        
        $newDrag = $tier->applyReduction($originalForwardDrag);
        
        $this->assertEqualsWithDelta(12.53, $newDrag, 0.01);
    }

    /**
     * Test realistic cargo ship drag scenario.
     */
    public function testRealisticCargoShipDrag(): void
    {
        // Large cargo ship with 10x cargo
        $originalForwardDrag = 20.0;
        $tier = new ReductionTier(999, 0.70); // Safety cap
        
        $newDrag = $tier->applyReduction($originalForwardDrag);
        
        $this->assertEqualsWithDelta(6.0, $newDrag, 0.01);
    }

    /**
     * Test that reduction multiplier produces same result.
     */
    public function testReductionMultiplierEquivalence(): void
    {
        $originalDrag = 100.0;
        $tier = new ReductionTier(4.0, 0.30);
        
        // Two ways to calculate should give same result
        $method1 = $tier->applyReduction($originalDrag);
        $method2 = $originalDrag * $tier->getReductionMultiplier();
        
        $this->assertEqualsWithDelta($method1, $method2, 0.01);
    }

    /**
     * Test incremental reductions (higher tiers = more reduction).
     */
    public function testIncrementalReductions(): void
    {
        $originalDrag = 100.0;
        
        $tier2x = new ReductionTier(2.0, 0.10);
        $tier4x = new ReductionTier(4.0, 0.30);
        $tier8x = new ReductionTier(8.0, 0.50);
        $tier10x = new ReductionTier(999, 0.70);
        
        $drag2x = $tier2x->applyReduction($originalDrag);
        $drag4x = $tier4x->applyReduction($originalDrag);
        $drag8x = $tier8x->applyReduction($originalDrag);
        $drag10x = $tier10x->applyReduction($originalDrag);
        
        // Each tier should have more reduction
        $this->assertGreaterThan($drag4x, $drag2x);
        $this->assertGreaterThan($drag8x, $drag4x);
        $this->assertGreaterThan($drag10x, $drag8x);
        
        // All should be less than original
        $this->assertLessThan($originalDrag, $drag2x);
        $this->assertLessThan($originalDrag, $drag4x);
        $this->assertLessThan($originalDrag, $drag8x);
        $this->assertLessThan($originalDrag, $drag10x);
    }
}
