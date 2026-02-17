<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\Tests\Unit\DTOs;

use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\PhysicsResponseData;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\PhysicsRequest;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\PhysicsData;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\ReductionTiers;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\EnginePerformance;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\PhysicsCalculator;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\Drag;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\Inertia;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\Jerk;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\JerkForward;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\JerkBoost;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\JerkTravel;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedDrag;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedInertia;
use Mistralys\X4\Mods\CargoSizesMod\Output\Jerk\AdjustedJerk;
use Mistralys\X4\Mods\CargoSizesMod\Build\ReductionTier;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PhysicsResponseData DTO.
 *
 * Verifies the Parameter Object pattern implementation for
 * PhysicsService::buildPhysicsResponse() method.
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
        $calculator = new PhysicsCalculator(1000.0, 500.0, 1000.0, 2.0, false);
        $physicsData = $this->createPhysicsData();
        $tiers = $this->createReductionTiers();
        $request = $this->createPhysicsRequest();
        $enginePerformance = $this->createEnginePerformance();

        $data = new PhysicsResponseData(
            $calculator,
            $physicsData,
            $tiers,
            $request,
            $enginePerformance
        );

        $this->assertInstanceOf(PhysicsResponseData::class, $data);
        $this->assertSame($calculator, $data->calculator);
        $this->assertSame($physicsData, $data->physicsData);
        $this->assertSame($tiers, $data->tiers);
        $this->assertSame($request, $data->request);
        $this->assertSame($enginePerformance, $data->enginePerformance);
    }

    /**
     * Test that PhysicsResponseData can be constructed with null engine performance.
     */
    public function testConstructionWithNullEnginePerformance(): void
    {
        $calculator = new PhysicsCalculator(1000.0, 500.0, 1000.0, 2.0, false);
        $physicsData = $this->createPhysicsData();
        $tiers = $this->createReductionTiers();
        $request = $this->createPhysicsRequest();

        $data = new PhysicsResponseData(
            $calculator,
            $physicsData,
            $tiers,
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
        $calculator = new PhysicsCalculator(1000.0, 500.0, 1000.0, 2.0, false);
        $physicsData = $this->createPhysicsData();
        $tiers = $this->createReductionTiers();
        $request = $this->createPhysicsRequest();

        $data = new PhysicsResponseData(
            $calculator,
            $physicsData,
            $tiers,
            $request,
            null
        );

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Cannot modify readonly property');

        // This should trigger a PHP Error
        $data->calculator = new PhysicsCalculator(2000.0, 1000.0, 2000.0, 2.0, false);
    }

    /**
     * Test that all five properties are accessible.
     */
    public function testAllPropertiesAccessible(): void
    {
        $calculator = new PhysicsCalculator(1000.0, 500.0, 1000.0, 2.0, false);
        $physicsData = $this->createPhysicsData();
        $tiers = $this->createReductionTiers();
        $request = $this->createPhysicsRequest();
        $enginePerformance = $this->createEnginePerformance();

        $data = new PhysicsResponseData(
            $calculator,
            $physicsData,
            $tiers,
            $request,
            $enginePerformance
        );

        // Verify all properties can be read
        $this->assertInstanceOf(PhysicsCalculator::class, $data->calculator);
        $this->assertInstanceOf(PhysicsData::class, $data->physicsData);
        $this->assertInstanceOf(ReductionTiers::class, $data->tiers);
        $this->assertInstanceOf(PhysicsRequest::class, $data->request);
        $this->assertInstanceOf(EnginePerformance::class, $data->enginePerformance);
    }

    // --- Helper Methods ---

    /**
     * Create a mock PhysicsData object for testing.
     */
    private function createPhysicsData(): PhysicsData
    {
        $originalDrag = new Drag(100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0);
        $adjustedDrag = new AdjustedDrag($originalDrag, 0.5);
        
        $originalInertia = new Inertia(10.0, 10.0, 10.0);
        $adjustedInertia = new AdjustedInertia($originalInertia, 1.2);
        
        $originalJerk = new Jerk(
            50.0,
            50.0,
            new JerkForward(50.0, 50.0, 1.0),
            new JerkBoost(100.0, 1.0),
            new JerkTravel(200.0, 200.0, 1.0)
        );
        $adjustedJerk = new AdjustedJerk($originalJerk, 0.8);

        return new PhysicsData(
            $originalDrag,
            $adjustedDrag,
            $originalInertia,
            $adjustedInertia,
            $originalJerk,
            $adjustedJerk
        );
    }

    /**
     * Create a mock ReductionTiers object for testing.
     */
    private function createReductionTiers(): ReductionTiers
    {
        $dragTier = new ReductionTier(2.0, 0.5);
        $jerkTier = new ReductionTier(2.0, 0.3);
        return new ReductionTiers($dragTier, $jerkTier);
    }

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
            useEffectiveRatioCap: false,
            dragReductionFactor: 1.0,
            inertiaImpactFactor: 0.8,
            accelerationResponsiveness: 1.0,
            dragReductionTiers: [['maxMultiplier' => 2.0, 'reductionPercent' => 0.5]],
            jerkReductionTiers: [['maxMultiplier' => 2.0, 'reductionPercent' => 0.3]],
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
