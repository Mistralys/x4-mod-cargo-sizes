<?php
/**
 * Tests for tier-based reduction system.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage Tests
 */

declare(strict_types=1);

namespace CargoSizesModTests;

use Mistralys\X4\Mods\CargoSizesMod\Build\BuildConfig;
use Mistralys\X4\Mods\CargoSizesMod\Build\ReductionTier;
use Mistralys\X4\Mods\CargoSizesMod\CargoSizeException;
use PHPUnit\Framework\TestCase;

/**
 * Tests the tier-based reduction system including ReductionTier class
 * and BuildConfig tier lookup methods.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage Tests
 */
class TierSystemTest extends TestCase
{
    /**
     * Test ReductionTier creation and getters.
     */
    public function testReductionTierCreation(): void
    {
        $tier = new ReductionTier(4.0, 0.30);
        
        $this->assertEquals(4.0, $tier->getMaxMultiplier());
        $this->assertEquals(0.30, $tier->getReductionPercent());
    }

    /**
     * Test that tier applies to correct multipliers.
     */
    public function testTierAppliesToMultiplier(): void
    {
        $tier = new ReductionTier(4.0, 0.30);
        
        $this->assertTrue($tier->appliesToMultiplier(2.0));
        $this->assertTrue($tier->appliesToMultiplier(4.0));
        $this->assertFalse($tier->appliesToMultiplier(5.0));
    }

    /**
     * Test tier reduction application.
     */
    public function testTierApplyReduction(): void
    {
        $tier = new ReductionTier(4.0, 0.30); // 30% reduction
        
        $result = $tier->applyReduction(100.0);
        $this->assertEqualsWithDelta(70.0, $result, 0.01);
    }

    /**
     * Test reduction multiplier calculation.
     */
    public function testReductionMultiplier(): void
    {
        $tier = new ReductionTier(4.0, 0.30); // 30% reduction
        
        $multiplier = $tier->getReductionMultiplier();
        $this->assertEqualsWithDelta(0.70, $multiplier, 0.01);
    }

    /**
     * Test tier format string.
     */
    public function testTierFormat(): void
    {
        $tier = new ReductionTier(4.0, 0.30);
        
        $formatted = $tier->format();
        $this->assertStringContainsString('4.0', $formatted);
        $this->assertStringContainsString('30%', $formatted);
    }

    /**
     * Test tier validation rejects negative max multiplier.
     */
    public function testTierValidationRejectsNegativeMaxMultiplier(): void
    {
        $this->expectException(CargoSizeException::class);
        new ReductionTier(-1.0, 0.30);
    }

    /**
     * Test tier validation rejects zero max multiplier.
     */
    public function testTierValidationRejectsZeroMaxMultiplier(): void
    {
        $this->expectException(CargoSizeException::class);
        new ReductionTier(0.0, 0.30);
    }

    /**
     * Test tier validation rejects reduction percent > 1.0.
     */
    public function testTierValidationRejectsInvalidPercent(): void
    {
        $this->expectException(CargoSizeException::class);
        new ReductionTier(4.0, 1.5); // > 1.0 should fail
    }

    /**
     * Test tier validation rejects negative reduction percent.
     */
    public function testTierValidationRejectsNegativePercent(): void
    {
        $this->expectException(CargoSizeException::class);
        new ReductionTier(4.0, -0.1);
    }

    /**
     * Test creating tier from array.
     */
    public function testTierFromArray(): void
    {
        $data = [
            'maxMultiplier' => 4.0,
            'reductionPercent' => 0.30
        ];
        
        $tier = ReductionTier::fromArray($data);
        
        $this->assertEquals(4.0, $tier->getMaxMultiplier());
        $this->assertEquals(0.30, $tier->getReductionPercent());
    }

    /**
     * Test fromArray throws exception for missing maxMultiplier.
     */
    public function testTierFromArrayThrowsForMissingMaxMultiplier(): void
    {
        $this->expectException(CargoSizeException::class);
        $this->expectExceptionMessage('maxMultiplier');
        
        ReductionTier::fromArray(['reductionPercent' => 0.30]);
    }

    /**
     * Test fromArray throws exception for missing reductionPercent.
     */
    public function testTierFromArrayThrowsForMissingReductionPercent(): void
    {
        $this->expectException(CargoSizeException::class);
        $this->expectExceptionMessage('reductionPercent');
        
        ReductionTier::fromArray(['maxMultiplier' => 4.0]);
    }

    /**
     * Test finding drag tier for 4x cargo multiplier.
     */
    public function testFindDragTierForMultiplier(): void
    {
        $config = new BuildConfig();
        
        // Should find tier with maxMultiplier=4.0, reductionPercent=0.30
        $tier = $config->findDragTierForMultiplier(4.0);
        $this->assertEquals(0.30, $tier->getReductionPercent());
    }

    /**
     * Test finding tier for multiplier between tier boundaries.
     */
    public function testFindTierForBetweenValues(): void
    {
        // 3x cargo should find first tier >= 3.0 (which is 4.0 tier)
        $config = new BuildConfig();
        $tier = $config->findDragTierForMultiplier(3.0);
        $this->assertEquals(4.0, $tier->getMaxMultiplier());
    }

    /**
     * Test finding tier for extreme multiplier uses catchall tier.
     */
    public function testFindTierForExtreme(): void
    {
        // 10x cargo should find safety cap tier (999)
        $config = new BuildConfig();
        $tier = $config->findDragTierForMultiplier(10.0);
        $this->assertEquals(0.70, $tier->getReductionPercent());
    }

    /**
     * Test finding jerk tier works correctly.
     */
    public function testFindJerkTierForMultiplier(): void
    {
        $config = new BuildConfig();
        
        // 4x cargo should get 15% jerk reduction
        $tier = $config->findJerkTierForMultiplier(4.0);
        $this->assertEquals(0.15, $tier->getReductionPercent());
    }

    /**
     * Test that 2x cargo gets correct drag tier.
     */
    public function testTwoXCargoGetsCorrectDragTier(): void
    {
        $config = new BuildConfig();
        $tier = $config->findDragTierForMultiplier(2.0);
        
        // 2x should get 10% reduction
        $this->assertEquals(0.10, $tier->getReductionPercent());
    }

    /**
     * Test that 2x cargo gets correct jerk tier.
     */
    public function testTwoXCargoGetsCorrectJerkTier(): void
    {
        $config = new BuildConfig();
        $tier = $config->findJerkTierForMultiplier(2.0);
        
        // 2x should get 5% reduction
        $this->assertEquals(0.05, $tier->getReductionPercent());
    }

    /**
     * Test that 8x cargo gets correct drag tier.
     */
    public function testEightXCargoGetsCorrectDragTier(): void
    {
        $config = new BuildConfig();
        $tier = $config->findDragTierForMultiplier(8.0);
        
        // 8x should get 50% reduction
        $this->assertEquals(0.50, $tier->getReductionPercent());
    }

    /**
     * Test that 8x cargo gets correct jerk tier.
     */
    public function testEightXCargoGetsCorrectJerkTier(): void
    {
        $config = new BuildConfig();
        $tier = $config->findJerkTierForMultiplier(8.0);
        
        // 8x should get 25% reduction
        $this->assertEquals(0.25, $tier->getReductionPercent());
    }

    /**
     * Test that extreme multiplier (10x+) gets safety cap.
     */
    public function testExtremeMultiplierGetsSafetyCap(): void
    {
        $config = new BuildConfig();
        
        // Both 10x and 20x should get the same safety cap tier
        $tier10 = $config->findDragTierForMultiplier(10.0);
        $tier20 = $config->findDragTierForMultiplier(20.0);
        
        $this->assertEquals(0.70, $tier10->getReductionPercent());
        $this->assertEquals(0.70, $tier20->getReductionPercent());
    }

    /**
     * Test that config has tier-based configuration.
     */
    public function testConfigHasTierBasedConfiguration(): void
    {
        $config = new BuildConfig();
        $this->assertTrue($config->hasTierBasedConfiguration());
    }

    /**
     * Test that drag reduction tiers are loaded.
     */
    public function testDragReductionTiersLoaded(): void
    {
        $config = new BuildConfig();
        $tiers = $config->getDragReductionTiers();
        
        $this->assertNotEmpty($tiers);
        $this->assertGreaterThanOrEqual(4, count($tiers)); // Should have 4 tiers
    }

    /**
     * Test that jerk reduction tiers are loaded.
     */
    public function testJerkReductionTiersLoaded(): void
    {
        $config = new BuildConfig();
        $tiers = $config->getJerkReductionTiers();
        
        $this->assertNotEmpty($tiers);
        $this->assertGreaterThanOrEqual(4, count($tiers)); // Should have 4 tiers
    }

    /**
     * Test inertia impact factor is within valid range.
     */
    public function testInertiaImpactFactorValid(): void
    {
        $config = new BuildConfig();
        $factor = $config->getInertiaImpactFactor();
        
        $this->assertGreaterThanOrEqual(0.0, $factor);
        $this->assertLessThanOrEqual(2.0, $factor);
    }

    /**
     * Test acceleration responsiveness is within valid range.
     */
    public function testAccelerationResponsivenessValid(): void
    {
        $config = new BuildConfig();
        $factor = $config->getAccelerationResponsiveness();
        
        $this->assertGreaterThanOrEqual(0.1, $factor);
        $this->assertLessThanOrEqual(5.0, $factor);
    }

    /**
     * Test use effective ratio cap setting.
     */
    public function testUseEffectiveRatioCapSetting(): void
    {
        $config = new BuildConfig();
        $useCap = $config->getUseEffectiveRatioCap();
        
        $this->assertIsBool($useCap);
    }

    /**
     * Test default inertia impact factor.
     */
    public function testDefaultInertiaImpactFactor(): void
    {
        $config = new BuildConfig();
        $factor = $config->getInertiaImpactFactor();
        
        // Default should be 0.5
        $this->assertEqualsWithDelta(0.5, $factor, 0.01);
    }

    /**
     * Test default acceleration responsiveness.
     */
    public function testDefaultAccelerationResponsiveness(): void
    {
        $config = new BuildConfig();
        $factor = $config->getAccelerationResponsiveness();
        
        // Default should be 1.0
        $this->assertEqualsWithDelta(1.0, $factor, 0.01);
    }

    /**
     * Test default effective ratio cap setting.
     */
    public function testDefaultEffectiveRatioCapEnabled(): void
    {
        $config = new BuildConfig();
        $useCap = $config->getUseEffectiveRatioCap();
        
        // Default should be true (enabled)
        $this->assertTrue($useCap);
    }

    /**
     * Test that all configured cargo multipliers are loaded.
     */
    public function testCargoMultipliersLoaded(): void
    {
        $config = new BuildConfig();
        $multipliers = $config->getMultipliers();
        
        $this->assertNotEmpty($multipliers);
        $this->assertContains(2.0, $multipliers);
        $this->assertContains(4.0, $multipliers);
    }

    /**
     * Test that steering increase factor exists (for compatibility).
     */
    public function testSteeringIncreaseFactorExists(): void
    {
        $config = new BuildConfig();
        $factor = $config->getSteeringIncreaseFactor();
        
        $this->assertIsFloat($factor);
    }
}
