<?php
declare(strict_types=1);

/**
 * Comprehensive tests for PhysicsService.
 *
 * Testing Strategy:
 * ----------------
 * This test suite validates PhysicsService's physics calculation logic.
 * PhysicsService wraps the PhysicsCalculator from the main project and
 * applies ship-specific physics data from X4 Core.
 *
 * Key aspects:
 * 1. **Integration with X4 Core:** Tests use real ship and engine data loaded
 *    via bootstrap.php. This validates that physics calculations work with
 *    actual game data.
 *
 * 2. **Dependency Injection:** PhysicsService accepts ShipDataService via DI,
 *    enabling controlled testing when needed.
 *
 * 3. **Determinism:** Physics calculations must be deterministic - same inputs
 *    always produce the same outputs. This is critical for GUI reliability.
 *
 * 4. **Optional Parameters:** Tests verify behavior with and without optional
 *    parameters (shipId, engineId), ensuring backward compatibility.
 *
 * 5. **Complex Calculations:** Tests verify that PhysicsCalculator integration,
 *    ship data loading, tier selection, and response building all work correctly.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend - Tests
 */

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Mistralys\X4\Mods\CargoSizesMod\GUI\Services\PhysicsService;
use Mistralys\X4\Mods\CargoSizesMod\GUI\Services\ShipDataService;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\PhysicsRequest;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\PhysicsResponse;
use Mistralys\X4\Mods\CargoSizesMod\GUI\Exceptions\GUIException;

/**
 * Test suite for PhysicsService.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend - Tests
 */
class PhysicsServiceTest extends TestCase
{
    private PhysicsService $service;
    private ShipDataService $shipDataService;

    protected function setUp(): void
    {
        // Use real X4 Core data via bootstrap
        $this->shipDataService = new ShipDataService();
        $this->service = new PhysicsService($this->shipDataService);
    }

    /**
     * Helper to create a valid test PhysicsRequest.
     *
     * @param string|null $shipId Optional ship ID
     * @param string|null $engineId Optional engine ID
     * @return PhysicsRequest
     */
    private function createValidRequest(?string $shipId = null, ?string $engineId = null): PhysicsRequest
    {
        return new PhysicsRequest(
            baseMass: 5000.0,
            originalCargo: 10000.0,
            adjustedCargo: 20000.0,
            cargoMultiplier: 2.0,
            useEffectiveRatioCap: true,
            dragReductionFactor: 1.0,
            inertiaImpactFactor: 0.5,
            accelerationResponsiveness: 1.0,
            dragReductionTiers: [
                ['maxMultiplier' => 2.0, 'reductionPercent' => 0.10],
                ['maxMultiplier' => 4.0, 'reductionPercent' => 0.30],
                ['maxMultiplier' => 8.0, 'reductionPercent' => 0.50]
            ],
            jerkReductionTiers: [
                ['maxMultiplier' => 2.0, 'reductionPercent' => 0.05],
                ['maxMultiplier' => 4.0, 'reductionPercent' => 0.15]
            ],
            engineId: $engineId,
            shipId: $shipId
        );
    }

    /**
     * Test calculatePhysics with valid request returns PhysicsResponse.
     *
     * Verifies that the service can perform a complete physics calculation
     * and return a properly structured response.
     */
    public function testCalculatePhysicsWithValidRequest(): void
    {
        // Arrange: Create valid request without optional parameters
        $request = $this->createValidRequest();

        // Act: Calculate physics
        $response = $this->service->calculatePhysics($request);

        // Assert: Response is valid
        $this->assertInstanceOf(PhysicsResponse::class, $response);
        
        // Verify basic response structure
        $this->assertIsFloat($response->massRatio);
        $this->assertGreaterThan(0, $response->massRatio, 'Mass ratio should be positive');
        
        $this->assertIsFloat($response->effectiveRatio);
        $this->assertGreaterThan(0, $response->effectiveRatio, 'Effective ratio should be positive');
        
        $this->assertIsFloat($response->originalFullMass);
        $this->assertIsFloat($response->adjustedFullMass);
        $this->assertGreaterThan($response->originalFullMass, $response->adjustedFullMass, 
            'Adjusted mass should be greater than original');
        
        // Verify drag arrays have correct structure
        $this->assertIsArray($response->dragOriginal);
        $this->assertArrayHasKey('forward', $response->dragOriginal);
        $this->assertArrayHasKey('reverse', $response->dragOriginal);
        
        $this->assertIsArray($response->dragAdjusted);
        $this->assertArrayHasKey('forward', $response->dragAdjusted);
        
        // Verify inertia arrays
        $this->assertIsArray($response->inertiaOriginal);
        $this->assertArrayHasKey('pitch', $response->inertiaOriginal);
        $this->assertArrayHasKey('yaw', $response->inertiaOriginal);
        $this->assertArrayHasKey('roll', $response->inertiaOriginal);
        
        // Verify jerk arrays
        $this->assertIsArray($response->jerkOriginal);
        $this->assertArrayHasKey('forward', $response->jerkOriginal);
        $this->assertArrayHasKey('boost', $response->jerkOriginal);
        $this->assertArrayHasKey('travel', $response->jerkOriginal);
    }

    /**
     * Test calculatePhysics without shipId uses default physics values.
     *
     * Verifies backward compatibility - when no shipId is provided,
     * the service uses hardcoded default physics values.
     */
    public function testCalculatePhysicsWithoutShipId(): void
    {
        // Arrange: Create request without shipId
        $request = $this->createValidRequest(shipId: null, engineId: null);

        // Act: Calculate physics
        $response = $this->service->calculatePhysics($request);

        // Assert: Response uses default values
        $this->assertInstanceOf(PhysicsResponse::class, $response);
        
        // Default drag values are 100.0 for all axes
        $this->assertEquals(100.0, $response->dragOriginal['forward'], 
            'Without shipId, should use default drag forward value of 100.0');
        $this->assertEquals(100.0, $response->dragOriginal['reverse']);
        $this->assertEquals(100.0, $response->dragOriginal['horizontal']);
        
        // Default inertia values are 10.0 for all axes
        $this->assertEquals(10.0, $response->inertiaOriginal['pitch'],
            'Without shipId, should use default inertia pitch value of 10.0');
        $this->assertEquals(10.0, $response->inertiaOriginal['yaw']);
        $this->assertEquals(10.0, $response->inertiaOriginal['roll']);
    }

    /**
     * Test calculatePhysics with specific shipId loads real ship data.
     *
     * Integration test verifying that ship-specific physics data is loaded
     * from X4 Core and used in calculations.
     */
    public function testCalculatePhysicsWithSpecificShipId(): void
    {
        // Arrange: Get a real transport ship for testing
        $ships = $this->shipDataService->getShipsByType('transport');
        $this->assertNotEmpty($ships, 'Need at least one transport ship for test');
        
        $testShipId = $ships[0]['id'];
        $request = $this->createValidRequest(shipId: $testShipId, engineId: null);

        // Act: Calculate physics with specific ship
        $response = $this->service->calculatePhysics($request);

        // Assert: Response uses ship-specific values (not defaults)
        $this->assertInstanceOf(PhysicsResponse::class, $response);
        
        // Real ship drag values should differ from defaults (100.0)
        // Note: We can't assert specific values since they vary by ship,
        // but we can verify they're loaded and positive
        $this->assertIsFloat($response->dragOriginal['forward']);
        $this->assertGreaterThan(0, $response->dragOriginal['forward']);
        
        // If the ship has non-default drag, verify it's not the default value
        // (Some ships might coincidentally have drag=100.0, so this is a loose check)
        $this->assertIsFloat($response->dragAdjusted['forward']);
        $this->assertGreaterThan(0, $response->dragAdjusted['forward']);
    }

    /**
     * Test calculatePhysics with engineId includes engine performance.
     *
     * Verifies that when an engine ID is provided, the response includes
     * engine performance calculations.
     */
    public function testCalculatePhysicsWithEngineId(): void
    {
        // Arrange: Get a real ship and engine
        $ships = $this->shipDataService->getShipsByType('transport');
        $this->assertNotEmpty($ships, 'Need at least one transport ship for test');
        
        $testShipId = $ships[0]['id'];
        $engines = $this->shipDataService->getEnginesForShip($testShipId);
        $this->assertNotEmpty($engines, 'Need at least one engine for test');
        
        $testEngineId = $engines[0]['id'];
        $request = $this->createValidRequest(shipId: $testShipId, engineId: $testEngineId);

        // Act: Calculate physics with engine
        $response = $this->service->calculatePhysics($request);

        // Assert: Response includes engine performance
        $this->assertInstanceOf(PhysicsResponse::class, $response);
        $this->assertNotNull($response->enginePerformance, 
            'Response should include engine performance when engineId provided');
        
        $this->assertEquals($testEngineId, $response->enginePerformance->engineId);
        
        // Verify engine performance has expected metrics
        $this->assertIsFloat($response->enginePerformance->originalTWR);
        $this->assertGreaterThan(0, $response->enginePerformance->originalTWR);
        
        $this->assertIsFloat($response->enginePerformance->adjustedTWR);
        $this->assertGreaterThan(0, $response->enginePerformance->adjustedTWR);
        
        // TWR should decrease with increased mass (assuming compensation < 1.0)
        // With accelerationResponsiveness=1.0, TWR is fully compensated back to original
        // So we just verify it's positive and not exceeding original
        $this->assertLessThanOrEqual(
            $response->enginePerformance->originalTWR + 0.01,
            $response->enginePerformance->adjustedTWR,
            'Adjusted TWR should not exceed original TWR');
        
        // Verify top speed metrics are populated
        $this->assertNotNull($response->topSpeedOriginal);
        $this->assertNotNull($response->topSpeedAdjusted);
        $this->assertIsFloat($response->topSpeedOriginal);
        $this->assertIsFloat($response->topSpeedAdjusted);
    }

    /**
     * Test calculatePhysics without engineId omits engine performance.
     *
     * Verifies that when no engine ID is provided, the response
     * does not include engine performance calculations.
     */
    public function testCalculatePhysicsWithoutEngineId(): void
    {
        // Arrange: Create request without engineId
        $request = $this->createValidRequest(shipId: null, engineId: null);

        // Act: Calculate physics
        $response = $this->service->calculatePhysics($request);

        // Assert: Response omits engine performance
        $this->assertInstanceOf(PhysicsResponse::class, $response);
        $this->assertNull($response->enginePerformance, 
            'Response should not include engine performance when engineId omitted');
        
        $this->assertNull($response->topSpeedOriginal);
        $this->assertNull($response->topSpeedAdjusted);
        $this->assertNull($response->accelerationOriginal);
        $this->assertNull($response->accelerationAdjusted);
    }

    /**
     * Test calculatePhysics determinism - same input produces same output.
     *
     * Critical test verifying that physics calculations are deterministic.
     * This is essential for GUI reliability and testing.
     */
    public function testCalculatePhysicsDeterminism(): void
    {
        // Arrange: Create identical requests
        $request1 = $this->createValidRequest();
        $request2 = $this->createValidRequest();

        // Act: Calculate physics twice
        $response1 = $this->service->calculatePhysics($request1);
        $response2 = $this->service->calculatePhysics($request2);

        // Assert: Responses are identical
        $this->assertEquals($response1->massRatio, $response2->massRatio, 
            'Mass ratio should be deterministic');
        $this->assertEquals($response1->effectiveRatio, $response2->effectiveRatio,
            'Effective ratio should be deterministic');
        $this->assertEquals($response1->originalFullMass, $response2->originalFullMass,
            'Original full mass should be deterministic');
        $this->assertEquals($response1->adjustedFullMass, $response2->adjustedFullMass,
            'Adjusted full mass should be deterministic');
        
        // Verify drag values are identical
        $this->assertEquals($response1->dragAdjusted['forward'], $response2->dragAdjusted['forward'],
            'Adjusted drag forward should be deterministic');
        $this->assertEquals($response1->dragAdjusted['reverse'], $response2->dragAdjusted['reverse'],
            'Adjusted drag reverse should be deterministic');
        
        // Verify inertia values are identical
        $this->assertEquals($response1->inertiaAdjusted['pitch'], $response2->inertiaAdjusted['pitch'],
            'Adjusted inertia pitch should be deterministic');
        
        // Verify jerk values are identical
        $this->assertEquals($response1->jerkAdjusted['forward']['accel'], 
            $response2->jerkAdjusted['forward']['accel'],
            'Adjusted jerk forward accel should be deterministic');
    }

    /**
     * Test calculatePhysics with different cargo multipliers.
     *
     * Verifies that higher cargo multipliers produce proportionally
     * greater mass changes and physics adjustments.
     */
    public function testCalculatePhysicsWithDifferentMultipliers(): void
    {
        // Arrange: Create requests with 2x and 4x multipliers
        $request2x = new PhysicsRequest(
            baseMass: 5000.0,
            originalCargo: 10000.0,
            adjustedCargo: 20000.0,
            cargoMultiplier: 2.0,
            useEffectiveRatioCap: true,
            dragReductionFactor: 1.0,
            inertiaImpactFactor: 0.5,
            accelerationResponsiveness: 1.0,
            dragReductionTiers: [
                ['maxMultiplier' => 2.0, 'reductionPercent' => 0.10],
                ['maxMultiplier' => 4.0, 'reductionPercent' => 0.30]
            ],
            jerkReductionTiers: [
                ['maxMultiplier' => 2.0, 'reductionPercent' => 0.05],
                ['maxMultiplier' => 4.0, 'reductionPercent' => 0.15]
            ]
        );

        $request4x = new PhysicsRequest(
            baseMass: 5000.0,
            originalCargo: 10000.0,
            adjustedCargo: 40000.0,
            cargoMultiplier: 4.0,
            useEffectiveRatioCap: true,
            dragReductionFactor: 1.0,
            inertiaImpactFactor: 0.5,
            accelerationResponsiveness: 1.0,
            dragReductionTiers: [
                ['maxMultiplier' => 2.0, 'reductionPercent' => 0.10],
                ['maxMultiplier' => 4.0, 'reductionPercent' => 0.30]
            ],
            jerkReductionTiers: [
                ['maxMultiplier' => 2.0, 'reductionPercent' => 0.05],
                ['maxMultiplier' => 4.0, 'reductionPercent' => 0.15]
            ]
        );

        // Act: Calculate physics for both
        $response2x = $this->service->calculatePhysics($request2x);
        $response4x = $this->service->calculatePhysics($request4x);

        // Assert: 4x multiplier produces greater changes
        $this->assertGreaterThan($response2x->massRatio, $response4x->massRatio,
            '4x multiplier should produce greater mass ratio than 2x');
        $this->assertGreaterThan($response2x->massIncrease, $response4x->massIncrease,
            '4x multiplier should produce greater mass increase than 2x');
    }

    /**
     * Test calculatePhysics verifies activeTier is populated.
     *
     * Verifies that the service correctly identifies and reports
     * which reduction tier is active for the given multiplier.
     */
    public function testCalculatePhysicsPopulatesActiveTier(): void
    {
        // Arrange: Create request with 2x multiplier
        $request = $this->createValidRequest();

        // Act: Calculate physics
        $response = $this->service->calculatePhysics($request);

        // Assert: activeTier is populated
        $this->assertIsString($response->activeTier);
        $this->assertNotEmpty($response->activeTier, 'Active tier should be populated');
        
        // Should reference the reduction percentages from 2x tier (10% drag, 5% jerk)
        $this->assertStringContainsString('10%', $response->activeTier,
            'Active tier should reference the 10% drag reduction from 2x tier');
        $this->assertStringContainsString('5%', $response->activeTier,
            'Active tier should reference the 5% jerk reduction from 2x tier');
    }
}
