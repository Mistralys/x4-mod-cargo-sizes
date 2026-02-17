<?php
declare(strict_types=1);

/**
 * Unit tests for PhysicsCalculationHelper trait.
 *
 * Tests percentage change calculations and average change computations
 * for drag and inertia values. Uses anonymous class pattern to test
 * trait methods in isolation.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend - Tests
 */

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use Mistralys\X4\Mods\CargoSizesMod\GUI\Utils\PhysicsCalculationHelper;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\Drag;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\Inertia;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedDrag;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedInertia;

/**
 * Class PhysicsCalculationHelperTest
 *
 * Demonstrates trait testing pattern using anonymous class.
 * The trait is included in an anonymous class to make private
 * methods accessible for testing.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend - Tests
 */
class PhysicsCalculationHelperTest extends TestCase
{
    /**
     * Helper instance using the trait.
     * 
     * @var object Anonymous class instance with PhysicsCalculationHelper trait
     */
    private object $helper;

    /**
     * Set up test helper before each test.
     *
     * Creates an anonymous class that uses PhysicsCalculationHelper
     * and exposes its private methods for testing.
     */
    protected function setUp(): void
    {
        // Create anonymous class that uses the trait and exposes methods
        $this->helper = new class {
            use PhysicsCalculationHelper {
                calculatePercentChange as public;
                calculateAverageDragChange as public;
                calculateAverageInertiaChange as public;
            }
        };
    }

    /**
     * Test calculatePercentChange() with positive value increase.
     *
     * Verifies that percentage increase is correctly calculated
     * when modified value is greater than original.
     */
    public function testCalculatePercentChangeWithPositiveIncrease(): void
    {
        $result = $this->helper->calculatePercentChange(100.0, 150.0);
        $this->assertEqualsWithDelta(50.0, $result, 0.01, 
            'Expected 50% increase from 100 to 150');
    }

    /**
     * Test calculatePercentChange() with value decrease.
     *
     * Verifies that percentage decrease returns negative value
     * when modified value is less than original.
     */
    public function testCalculatePercentChangeWithDecrease(): void
    {
        $result = $this->helper->calculatePercentChange(100.0, 75.0);
        $this->assertEqualsWithDelta(-25.0, $result, 0.01,
            'Expected -25% decrease from 100 to 75');
    }

    /**
     * Test calculatePercentChange() with zero original value.
     *
     * Verifies edge case handling: division by zero should return 0.0
     * to prevent undefined results.
     */
    public function testCalculatePercentChangeWithZeroOriginal(): void
    {
        $result = $this->helper->calculatePercentChange(0.0, 100.0);
        $this->assertEquals(0.0, $result,
            'Expected 0.0 when original value is zero (edge case handling)');
    }

    /**
     * Test calculatePercentChange() with identical values.
     *
     * Verifies that no change (0%) is calculated when values are equal.
     */
    public function testCalculatePercentChangeWithNoChange(): void
    {
        $result = $this->helper->calculatePercentChange(100.0, 100.0);
        $this->assertEquals(0.0, $result,
            'Expected 0% change when values are identical');
    }

    /**
     * Test calculatePercentChange() with large values.
     *
     * Verifies numerical stability with large floating-point numbers.
     */
    public function testCalculatePercentChangeWithLargeValues(): void
    {
        $result = $this->helper->calculatePercentChange(1000000.0, 1500000.0);
        $this->assertEqualsWithDelta(50.0, $result, 0.01,
            'Expected 50% increase with large values');
    }

    /**
     * Test calculatePercentChange() with negative values.
     *
     * Verifies correct handling of negative numbers (e.g., reversed drag).
     */
    public function testCalculatePercentChangeWithNegativeValues(): void
    {
        $result = $this->helper->calculatePercentChange(-100.0, -150.0);
        $this->assertEqualsWithDelta(50.0, $result, 0.01,
            'Expected 50% increase from -100 to -150');
    }

    /**
     * Test calculateAverageDragChange() with mock drag data.
     *
     * Verifies that average percentage change across all drag axes
     * is correctly calculated.
     */
    public function testCalculateAverageDragChangeWithMockData(): void
    {
        // Create mock drag objects with controlled test data
        $originalDrag = $this->createMock(Drag::class);
        $originalDrag->method('getForward')->willReturn(100.0);
        $originalDrag->method('getReverse')->willReturn(100.0);
        $originalDrag->method('getHorizontal')->willReturn(100.0);
        $originalDrag->method('getVertical')->willReturn(100.0);
        $originalDrag->method('getPitch')->willReturn(100.0);
        $originalDrag->method('getYaw')->willReturn(100.0);
        $originalDrag->method('getRoll')->willReturn(100.0);

        $adjustedDrag = $this->createMock(AdjustedDrag::class);
        $adjustedDrag->method('getForward')->willReturn(150.0);  // +50%
        $adjustedDrag->method('getReverse')->willReturn(150.0);  // +50%
        $adjustedDrag->method('getHorizontal')->willReturn(150.0);  // +50%
        $adjustedDrag->method('getVertical')->willReturn(150.0);  // +50%
        $adjustedDrag->method('getPitch')->willReturn(150.0);  // +50%
        $adjustedDrag->method('getYaw')->willReturn(150.0);  // +50%
        $adjustedDrag->method('getRoll')->willReturn(150.0);  // +50%

        $result = $this->helper->calculateAverageDragChange($originalDrag, $adjustedDrag);
        $this->assertEqualsWithDelta(50.0, $result, 0.01,
            'Expected average of 50% increase across all drag axes');
    }

    /**
     * Test calculateAverageInertiaChange() with mock inertia data.
     *
     * Verifies that average percentage change across all inertia axes
     * is correctly calculated.
     */
    public function testCalculateAverageInertiaChangeWithMockData(): void
    {
        // Create mock inertia objects with controlled test data
        $originalInertia = $this->createMock(Inertia::class);
        $originalInertia->method('getPitch')->willReturn(100.0);
        $originalInertia->method('getYaw')->willReturn(100.0);
        $originalInertia->method('getRoll')->willReturn(100.0);

        $adjustedInertia = $this->createMock(AdjustedInertia::class);
        $adjustedInertia->method('getPitch')->willReturn(120.0);  // +20%
        $adjustedInertia->method('getYaw')->willReturn(130.0);   // +30%
        $adjustedInertia->method('getRoll')->willReturn(140.0);  // +40%

        $result = $this->helper->calculateAverageInertiaChange($originalInertia, $adjustedInertia);
        $this->assertEqualsWithDelta(30.0, $result, 0.01,
            'Expected average of 30% increase across inertia axes (20% + 30% + 40% / 3)');
    }
}
