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
            accelerationResponsiveness: 1.0,
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
        $request = $this->createValidRequest();

        $response = $this->service->calculatePhysics($request);

        $this->assertInstanceOf(PhysicsResponse::class, $response);

        $this->assertIsFloat($response->massRatio);
        $this->assertGreaterThan(0, $response->massRatio, 'Mass ratio should be positive');

        $this->assertIsFloat($response->originalFullMass);
        $this->assertIsFloat($response->adjustedFullMass);
        $this->assertGreaterThan($response->originalFullMass, $response->adjustedFullMass,
            'Adjusted mass should be greater than original');

        $this->assertIsFloat($response->accelerationScalingFactor);
        $this->assertGreaterThan(0, $response->accelerationScalingFactor,
            'Acceleration scaling factor should be positive');

        $this->assertEquals(1.0, $response->accelerationResponsiveness,
            'Responsiveness should match the request value');
    }

    /**
     * Test calculatePhysics without shipId uses default physics values.
     *
     * Verifies backward compatibility - when no shipId is provided,
     * the service uses hardcoded default physics values.
     */
    public function testCalculatePhysicsWithoutShipId(): void
    {
        $request = $this->createValidRequest(shipId: null, engineId: null);

        $response = $this->service->calculatePhysics($request);

        $this->assertInstanceOf(PhysicsResponse::class, $response);
        $this->assertNull($response->enginePerformance,
            'Without engineId, engine performance should be null');
        $this->assertNull($response->topSpeedOriginal);
        $this->assertNull($response->topSpeedAdjusted);
    }

    /**
     * Test calculatePhysics with specific shipId loads real ship data.
     *
     * Integration test verifying that ship-specific physics data is loaded
     * from X4 Core and used in calculations.
     */
    public function testCalculatePhysicsWithSpecificShipId(): void
    {
        $ships = $this->shipDataService->getShipsByType('transport');
        $this->assertNotEmpty($ships, 'Need at least one transport ship for test');

        $testShipId = $ships[0]['id'];
        $request = $this->createValidRequest(shipId: $testShipId, engineId: null);

        $response = $this->service->calculatePhysics($request);

        $this->assertInstanceOf(PhysicsResponse::class, $response);
        $this->assertIsFloat($response->massRatio);
        $this->assertGreaterThan(0, $response->massRatio);

        // Acceleration scaling factor = massRatio × responsiveness(1.0)
        $this->assertEqualsWithDelta(
            $response->massRatio,
            $response->accelerationScalingFactor,
            0.0001,
            'Scaling factor should equal massRatio when responsiveness=1.0'
        );
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
        $request1 = $this->createValidRequest();
        $request2 = $this->createValidRequest();

        $response1 = $this->service->calculatePhysics($request1);
        $response2 = $this->service->calculatePhysics($request2);

        $this->assertEquals($response1->massRatio, $response2->massRatio,
            'Mass ratio should be deterministic');
        $this->assertEquals($response1->originalFullMass, $response2->originalFullMass,
            'Original full mass should be deterministic');
        $this->assertEquals($response1->adjustedFullMass, $response2->adjustedFullMass,
            'Adjusted full mass should be deterministic');
        $this->assertEquals($response1->accelerationScalingFactor, $response2->accelerationScalingFactor,
            'Acceleration scaling factor should be deterministic');
    }

    /**
     * Test calculatePhysics with different cargo multipliers.
     *
     * Verifies that higher cargo multipliers produce proportionally
     * greater mass changes and physics adjustments.
     */
    public function testCalculatePhysicsWithDifferentMultipliers(): void
    {
        $request2x = new PhysicsRequest(
            baseMass: 5000.0,
            originalCargo: 10000.0,
            adjustedCargo: 20000.0,
            cargoMultiplier: 2.0,
            accelerationResponsiveness: 1.0
        );

        $request4x = new PhysicsRequest(
            baseMass: 5000.0,
            originalCargo: 10000.0,
            adjustedCargo: 40000.0,
            cargoMultiplier: 4.0,
            accelerationResponsiveness: 1.0
        );

        $response2x = $this->service->calculatePhysics($request2x);
        $response4x = $this->service->calculatePhysics($request4x);

        $this->assertGreaterThan($response2x->massRatio, $response4x->massRatio,
            '4x multiplier should produce greater mass ratio than 2x');
        $this->assertGreaterThan($response2x->massIncrease, $response4x->massIncrease,
            '4x multiplier should produce greater mass increase than 2x');
        $this->assertGreaterThan($response2x->accelerationScalingFactor, $response4x->accelerationScalingFactor,
            '4x multiplier should produce larger acceleration scaling factor');
    }

}
