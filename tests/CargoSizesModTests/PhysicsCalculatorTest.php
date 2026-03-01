<?php
/**
 * Tests for PhysicsCalculator class.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage Tests
 */

declare(strict_types=1);

namespace CargoSizesModTests;

use Mistralys\X4\Mods\CargoSizesMod\CargoSizeException;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\PhysicsCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Tests the PhysicsCalculator class to ensure mass ratio calculations
 * are correct and physics-based.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage Tests
 */
class PhysicsCalculatorTest extends TestCase
{
    /**
     * Test that mass ratio is always greater than 1.0 when cargo increases.
     */
    public function testMassRatioIsGreaterThanOne(): void
    {
        // When cargo increases, mass ratio should be > 1.0
        $calc = new PhysicsCalculator(650, 1000, 4000, 4.0);
        $this->assertGreaterThan(1.0, $calc->getMassRatio());
    }

    /**
     * Test precise mass ratio calculation for a known case.
     */
    public function testMassRatioCalculation(): void
    {
        // baseMass: 650, originalCargo: 1000, adjustedCargo: 4000
        // originalFullMass: 1650, adjustedFullMass: 4650
        // massRatio: 4650 / 1650 = 2.818...
        $calc = new PhysicsCalculator(650, 1000, 4000, 4.0);
        $this->assertEqualsWithDelta(2.818, $calc->getMassRatio(), 0.001);
    }

    /**
     * Test inverse mass ratio calculation (used for jerk).
     */
    public function testInverseMassRatio(): void
    {
        // Mass ratio 2.0 → inverse should be 0.5
        $calc = new PhysicsCalculator(100, 100, 300, 3.0);
        // originalFull: 200, adjustedFull: 400, ratio: 2.0
        $this->assertEqualsWithDelta(0.5, $calc->getInverseMassRatio(), 0.01);
    }

    /**
     * Test squared mass ratio calculation (used for drag).
     */
    public function testMassRatioSquared(): void
    {
        // Mass ratio 2.0 → squared should be 4.0
        $calc = new PhysicsCalculator(100, 100, 300, 3.0);
        $this->assertEqualsWithDelta(4.0, $calc->getMassRatioSquared(), 0.01);
    }

    /**
     * Test mass increase percentage calculation.
     */
    public function testMassIncreasePercent(): void
    {
        // Mass ratio 2.0 → 100% increase
        $calc = new PhysicsCalculator(100, 100, 300, 3.0);
        $this->assertEqualsWithDelta(100.0, $calc->getMassIncreasePercent(), 1.0);
    }

    /**
     * Test that validation throws exception for zero base mass.
     */
    public function testValidationThrowsForZeroBaseMass(): void
    {
        $this->expectException(CargoSizeException::class);
        $this->expectExceptionMessage('Base mass must be greater than zero');
        new PhysicsCalculator(0, 1000, 4000, 4.0);
    }

    /**
     * Test that validation throws exception for negative base mass.
     */
    public function testValidationThrowsForNegativeBaseMass(): void
    {
        $this->expectException(CargoSizeException::class);
        new PhysicsCalculator(-100, 1000, 4000, 4.0);
    }

    /**
     * Test that validation throws exception for negative cargo.
     */
    public function testValidationThrowsForNegativeCargo(): void
    {
        $this->expectException(CargoSizeException::class);
        new PhysicsCalculator(100, -1000, 4000, 4.0);
    }

    /**
     * Test that validation throws exception for negative cargo multiplier.
     */
    public function testValidationThrowsForNegativeMultiplier(): void
    {
        $this->expectException(CargoSizeException::class);
        new PhysicsCalculator(100, 1000, 4000, -4.0);
    }

    /**
     * Test full mass calculation: base mass + cargo.
     */
    public function testOriginalFullMassCalculation(): void
    {
        $calc = new PhysicsCalculator(650, 1000, 4000, 4.0);
        $this->assertEquals(1650, $calc->getOriginalFullMass());
    }

    /**
     * Test adjusted full mass calculation.
     */
    public function testAdjustedFullMassCalculation(): void
    {
        $calc = new PhysicsCalculator(650, 1000, 4000, 4.0);
        $this->assertEquals(4650, $calc->getAdjustedFullMass());
    }

    /**
     * Test mass increase calculation (difference in full masses).
     */
    public function testMassIncrease(): void
    {
        $calc = new PhysicsCalculator(650, 1000, 4000, 4.0);
        $this->assertEquals(3000, $calc->getMassIncrease());
    }

    /**
     * Test cargo multiplier getter.
     */
    public function testCargoMultiplierGetter(): void
    {
        $calc = new PhysicsCalculator(650, 1000, 4000, 4.0);
        $this->assertEquals(4.0, $calc->getCargoMultiplier());
    }

    /**
     * Test base mass getter.
     */
    public function testBaseMassGetter(): void
    {
        $calc = new PhysicsCalculator(650, 1000, 4000, 4.0);
        $this->assertEquals(650, $calc->getBaseMass());
    }

    /**
     * Test validation warnings for extreme mass ratio.
     */
    public function testValidationWarningsForExtremeMassRatio(): void
    {
        // Extreme case: massRatio > 10.0
        $calc = new PhysicsCalculator(10, 1000, 12000, 12.0);
        $warnings = $calc->getValidationWarnings();
        
        $this->assertNotEmpty($warnings);
        $this->assertStringContainsString('Extreme mass ratio', $warnings[0]);
    }

    /**
     * Test formatMassRatio returns expected decimal string format.
     */
    public function testFormatMassRatio(): void
    {
        $calc = new PhysicsCalculator(650, 1000, 4000, 4.0);
        
        $formattedRatio = $calc->formatMassRatio(2);
        $this->assertMatchesRegularExpression('/^\d+\.\d{2}$/', $formattedRatio);
    }

    /**
     * Test debug info contains all expected information.
     */
    public function testDebugInfo(): void
    {
        $calc = new PhysicsCalculator(650, 1000, 4000, 4.0);
        $debugInfo = $calc->getDebugInfo();
        
        $this->assertStringContainsString('Base Mass: 650', $debugInfo);
        $this->assertStringContainsString('Original Cargo: 1000', $debugInfo);
        $this->assertStringContainsString('Adjusted Cargo: 4000', $debugInfo);
        $this->assertStringContainsString('Mass Ratio:', $debugInfo);
        $this->assertStringContainsString('Cargo Multiplier: 4.00', $debugInfo);
    }

    /**
     * Test edge case: ship with zero original cargo.
     */
    public function testZeroOriginalCargo(): void
    {
        $calc = new PhysicsCalculator(100, 0, 1000, 10.0);
        
        // Mass ratio should be calculable (baseMass + 0) / (baseMass + 1000)
        $this->assertGreaterThan(1.0, $calc->getMassRatio());
        
        // Should generate warning
        $warnings = $calc->getValidationWarnings();
        $hasZeroCargoWarning = false;
        foreach ($warnings as $warning) {
            if (str_contains($warning, 'zero cargo capacity')) {
                $hasZeroCargoWarning = true;
                break;
            }
        }
        $this->assertTrue($hasZeroCargoWarning);
    }

    /**
     * Test combat ship with low cargo (realistic scenario).
     */
    public function testCombatShipLowCargo(): void
    {
        // Combat ship: high base mass, low cargo
        // baseMass: 106, originalCargo: 240, adjustedCargo: 2400 (10x)
        $calc = new PhysicsCalculator(106, 240, 2400, 10.0);
        
        // Mass ratio should be close to 7.2x  
        // originalFull: 346, adjustedFull: 2506, ratio: ~7.24
        $this->assertLessThan(8.0, $calc->getMassRatio());
        $this->assertGreaterThan(7.0, $calc->getMassRatio());
    }

    /**
     * Test cargo ship with high cargo (realistic scenario).
     */
    public function testCargoShipHighCargo(): void
    {
        // Cargo ship: low base mass, massive cargo
        // baseMass: 205, originalCargo: 42000, adjustedCargo: 420000 (10x)
        $calc = new PhysicsCalculator(205, 42000, 420000, 10.0);
        
        // Actual mass ratio: (205 + 420000) / (205 + 42000) = 420205 / 42205 = ~9.956
        $this->assertEqualsWithDelta(9.956, $calc->getMassRatio(), 0.01);
        
        $this->assertGreaterThan(9.0, $calc->getMassRatio());
        $this->assertLessThan(10.0, $calc->getMassRatio());
    }
}
