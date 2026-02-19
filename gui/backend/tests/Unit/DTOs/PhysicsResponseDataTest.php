<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\Tests\Unit\DTOs;

use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\PhysicsResponseData;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\PhysicsRequest;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\EnginePerformance;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\PhysicsCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PhysicsResponseData DTO.
 *
 * Verifies the Parameter Object pattern implementation for
 * PhysicsService::calculatePhysics() method.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend - Tests
 * @covers \Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\PhysicsResponseData
 */
class PhysicsResponseDataTest extends TestCase
{
    /**
     * Test that PhysicsResponseData can be constructed with all required parameters.
     */
    public function testConstruction(): void
    {
        $calculator = new PhysicsCalculator(1000.0, 500.0, 1000.0, 2.0);
        $request = $this->createPhysicsRequest();
        $enginePerformance = $this->createEnginePerformance();

        $data = new PhysicsResponseData(
            $calculator,
            $request,
            $enginePerformance
        );

        $this->assertInstanceOf(PhysicsResponseData::class, $data);
        $this->assertSame($calculator, $data->calculator);
        $this->assertSame($request, $data->request);
        $this->assertSame($enginePerformance, $data->enginePerformance);
    }

    /**
     * Test that PhysicsResponseData can be constructed with null engine performance.
     */
    public function testConstructionWithNullEnginePerformance(): void
    {
        $calculator = new PhysicsCalculator(1000.0, 500.0, 1000.0, 2.0);
        $request = $this->createPhysicsRequest();

        $data = new PhysicsResponseData(
            $calculator,
            $request,
            null
        );

        $this->assertNull($data->enginePerformance);
    }

    /**
     * Test that PhysicsResponseData properties are readonly.
     *
     * PHP 8.1+ readonly class properties cannot be modified after construction.
     * This test verifies the readonly constraint by attempting to assign,
     * which should trigger a PHP Error.
     */
    public function testReadonlyPropertiesCannotBeModified(): void
    {
        $calculator = new PhysicsCalculator(1000.0, 500.0, 1000.0, 2.0);
        $request = $this->createPhysicsRequest();

        $data = new PhysicsResponseData(
            $calculator,
            $request,
            null
        );

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Cannot modify readonly property');

        // This should trigger a PHP Error
        $data->calculator = new PhysicsCalculator(2000.0, 1000.0, 2000.0, 2.0);
    }

    /**
     * Test that all three properties are accessible.
     */
    public function testAllPropertiesAccessible(): void
    {
        $calculator = new PhysicsCalculator(1000.0, 500.0, 1000.0, 2.0);
        $request = $this->createPhysicsRequest();
        $enginePerformance = $this->createEnginePerformance();

        $data = new PhysicsResponseData(
            $calculator,
            $request,
            $enginePerformance
        );

        // Verify all properties can be read
        $this->assertInstanceOf(PhysicsCalculator::class, $data->calculator);
        $this->assertInstanceOf(PhysicsRequest::class, $data->request);
        $this->assertInstanceOf(EnginePerformance::class, $data->enginePerformance);
    }

    // --- Helper Methods ---

    /**
     * Create a mock PhysicsRequest object for testing.
     */
    private function createPhysicsRequest(): PhysicsRequest
    {
        return new PhysicsRequest(
            baseMass: 1000.0,
            originalCargo: 500.0,
            adjustedCargo: 1000.0,
            cargoMultiplier: 2.0,
            accelerationResponsiveness: 1.0,
            engineId: 'engine_arg_s_combat_01_mk1',
            shipId: 'ship_arg_s_fighter_01_a_macro'
        );
    }

    /**
     * Create a mock EnginePerformance object for testing.
     */
    private function createEnginePerformance(): EnginePerformance
    {
        return new EnginePerformance(
            engineId: 'engine_arg_s_combat_01_mk1',
            thrustForward: 1000.0,
            originalTWR: 2.5,
            adjustedTWR: 2.0,
            twrReductionPercent: 20.0,
            originalAcceleration: 25.0,
            adjustedAcceleration: 20.0,
            engineCount: 1,
            topSpeed: 500.0,
            topSpeedAdjusted: 500.0,
            topSpeedReverse: 250.0,
            topSpeedBoost: 1000.0,
            topSpeedTravel: 2000.0
        );
    }
}
