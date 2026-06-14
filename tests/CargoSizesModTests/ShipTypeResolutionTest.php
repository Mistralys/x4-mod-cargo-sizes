<?php
/**
 * Tests for ship type resolution, including alias mapping for hybrid ship classes.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage Tests
 */

declare(strict_types=1);

namespace CargoSizesModTests;

use Mistralys\X4\Mods\CargoSizesMod\CargoSizeExtractor;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Tests that resolveShipType() correctly resolves standard ship types and
 * aliases hybrid ship classes (scavenger, terraformer) to their output categories.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage Tests
 */
class ShipTypeResolutionTest extends TestCase
{
    private ReflectionClass $reflection;
    private CargoSizeExtractor $extractor;

    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(CargoSizeExtractor::class);
        $this->extractor = $this->reflection->newInstanceWithoutConstructor();
    }

    /**
     * Calls the private resolveShipType() method via Reflection.
     */
    private function resolveShipType(string $macroName): ?string
    {
        $method = $this->reflection->getMethod('resolveShipType');
        $method->setAccessible(true);
        return $method->invoke($this->extractor, $macroName);
    }

    /**
     * Barbarossa (scavenger class) must resolve to the transport output category.
     */
    public function testScavengerResolvesToTransport(): void
    {
        $result = $this->resolveShipType('ship_pir_l_scavenger_01_a_macro');

        $this->assertSame(CargoSizeExtractor::SHIP_TYPE_TRANSPORT, $result);
    }

    /**
     * Xenon H (terraformer class) must resolve to the miner output category.
     */
    public function testTerraformerResolvesToMiner(): void
    {
        $result = $this->resolveShipType('ship_xen_l_terraformer_01_a_macro');

        $this->assertSame(CargoSizeExtractor::SHIP_TYPE_MINER, $result);
    }

    /**
     * Standard transport ships must still resolve to transport.
     */
    public function testTransportResolvesToTransport(): void
    {
        $result = $this->resolveShipType('ship_arg_l_trans_container_01_a_macro');

        $this->assertSame(CargoSizeExtractor::SHIP_TYPE_TRANSPORT, $result);
    }

    /**
     * Standard miner ships must still resolve to miner.
     */
    public function testMinerResolvesToMiner(): void
    {
        $result = $this->resolveShipType('ship_arg_m_miner_liquid_01_a_macro');

        $this->assertSame(CargoSizeExtractor::SHIP_TYPE_MINER, $result);
    }

    /**
     * Resupplier / auxiliary ships must still resolve correctly.
     */
    public function testResupplierResolvesToAuxiliary(): void
    {
        $result = $this->resolveShipType('ship_arg_xl_resupplier_01_a_macro');

        $this->assertSame(CargoSizeExtractor::SHIP_TYPE_AUXILIARY, $result);
    }

    /**
     * Carrier ships must still resolve correctly.
     */
    public function testCarrierResolvesToCarrier(): void
    {
        $result = $this->resolveShipType('ship_arg_xl_carrier_01_a_macro');

        $this->assertSame(CargoSizeExtractor::SHIP_TYPE_CARRIER, $result);
    }

    /**
     * Unknown / unsupported macro names must return null.
     */
    public function testUnknownMacroReturnsNull(): void
    {
        $result = $this->resolveShipType('ship_arg_s_fighter_01_a_macro');

        $this->assertNull($result);
    }
}
