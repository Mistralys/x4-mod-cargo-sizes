<?php
/**
 * Tests for AccelerationOverrideDef and AdjustedAccelerationFactors.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage Tests
 */

declare(strict_types=1);

namespace CargoSizesModTests;

use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AccelerationOverrideDef;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedAccelerationFactors;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\PhysicsCalculator;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\AccelerationFactors;
use PHPUnit\Framework\TestCase;

/**
 * Tests AccelerationOverrideDef XML output structure and AdjustedAccelerationFactors
 * scaling logic to ensure the acceleration-only physics override system is correct.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage Tests
 */
class AccelerationOverrideTest extends TestCase
{
    private const MACRO_NAME = 'ship_arg_l_trans_container_01_a_macro';

    private AccelerationFactors $originalFactors;

    protected function setUp(): void
    {
        // Realistic thruster acceleration factors from an L-class transport
        $this->originalFactors = new AccelerationFactors(
            forward: 2.0,
            reverse: 1.5,
            horizontal: 2.5,
            vertical: 3.0
        );
    }

    /**
     * Verify that AccelerationOverrideDef targets the correct XPath.
     *
     * The XPath must point to properties/thruster/acceleration so the game engine
     * applies the override to the correct XML element.
     */
    public function testAccelerationOverrideXMLTargetsCorrectPath(): void
    {
        $adjusted = new AdjustedAccelerationFactors($this->originalFactors, 2.818);
        $def = new AccelerationOverrideDef(self::MACRO_NAME, $adjusted);

        $path = $def->getPath();

        $this->assertStringContainsString('properties/thruster/acceleration', $path);
        $this->assertStringContainsString(self::MACRO_NAME, $path);
    }

    /**
     * Verify that the rendered XML contains all four acceleration axes.
     *
     * The game file must have forward, reverse, horizontal, and vertical attributes
     * to correctly override all directional thrust values.
     */
    public function testAccelerationOverrideRendersAllFourAxes(): void
    {
        $adjusted = new AdjustedAccelerationFactors($this->originalFactors, 2.818);
        $def = new AccelerationOverrideDef(self::MACRO_NAME, $adjusted);

        $rendered = $def->render();

        $this->assertStringContainsString('forward=', $rendered);
        $this->assertStringContainsString('reverse=', $rendered);
        $this->assertStringContainsString('horizontal=', $rendered);
        $this->assertStringContainsString('vertical=', $rendered);
    }

    /**
     * Verify that newAccel = originalAccel × massRatio × responsiveness.
     *
     * With responsiveness = 1.0, the scaling factor equals the mass ratio.
     * Each adjusted axis must equal its original × scalingFactor.
     */
    public function testAccelerationScalingMatchesMassRatio(): void
    {
        // baseMass 650, cargo 1000 → 4000 (4x): massRatio ≈ 2.818
        $calc = new PhysicsCalculator(650, 1000, 4000, 4.0);
        $responsiveness = 1.0;
        $scalingFactor = $calc->getMassRatio() * $responsiveness;

        $adjusted = new AdjustedAccelerationFactors($this->originalFactors, $scalingFactor);

        $this->assertEqualsWithDelta(
            $this->originalFactors->getForward() * $scalingFactor,
            $adjusted->getForward(),
            0.001
        );
        $this->assertEqualsWithDelta(
            $this->originalFactors->getReverse() * $scalingFactor,
            $adjusted->getReverse(),
            0.001
        );
        $this->assertEqualsWithDelta(
            $this->originalFactors->getHorizontal() * $scalingFactor,
            $adjusted->getHorizontal(),
            0.001
        );
        $this->assertEqualsWithDelta(
            $this->originalFactors->getVertical() * $scalingFactor,
            $adjusted->getVertical(),
            0.001
        );
    }

    /**
     * Verify that responsiveness < 1.0 attenuates acceleration scaling.
     *
     * A factor below 1.0 means the ship gains less acceleration than needed to
     * fully preserve responsiveness — it feels heavier (AccelFactor/Mass ratio decreases).
     */
    public function testResponsivenessAttenuation(): void
    {
        // baseMass 650, cargo 1000 → 4000 (4x): massRatio ≈ 2.818
        $calc = new PhysicsCalculator(650, 1000, 4000, 4.0);
        $massRatio = $calc->getMassRatio();

        $fullScaling = $massRatio * 1.0;
        $attenuatedScaling = $massRatio * 0.7;

        $fullAccel = new AdjustedAccelerationFactors($this->originalFactors, $fullScaling);
        $attenuatedAccel = new AdjustedAccelerationFactors($this->originalFactors, $attenuatedScaling);

        // Attenuated should be less than full-physics scaling on every axis
        $this->assertLessThan($fullAccel->getForward(), $attenuatedAccel->getForward());
        $this->assertLessThan($fullAccel->getReverse(), $attenuatedAccel->getReverse());
        $this->assertLessThan($fullAccel->getHorizontal(), $attenuatedAccel->getHorizontal());
        $this->assertLessThan($fullAccel->getVertical(), $attenuatedAccel->getVertical());

        // Verify the ratio is exactly 0.7 of full scaling
        $this->assertEqualsWithDelta(
            $attenuatedAccel->getForward(),
            $fullAccel->getForward() * 0.7,
            0.001
        );
    }

    /**
     * Verify that responsiveness = 1.0 gives pure mass-ratio scaling.
     *
     * The AccelFactor/Mass ratio must be preserved when responsiveness = 1.0,
     * meaning the ship's time-to-speed is unchanged after the cargo increase.
     */
    public function testResponsivenessDefault(): void
    {
        // baseMass 650, cargo 1000 → 4000 (4x): massRatio ≈ 2.818
        $calc = new PhysicsCalculator(650, 1000, 4000, 4.0);
        $massRatio = $calc->getMassRatio();

        $scalingFactor = $massRatio * 1.0; // responsiveness = 1.0
        $adjusted = new AdjustedAccelerationFactors($this->originalFactors, $scalingFactor);

        // AccelFactor/Mass ratio must equal original: adjusted / massRatio ≈ original
        $originalForwardRatio = $this->originalFactors->getForward() / 1.0; // normalized to mass ratio 1.0
        $newForwardRatio = $adjusted->getForward() / $massRatio;

        $this->assertEqualsWithDelta($originalForwardRatio, $newForwardRatio, 0.001);

        // Scaling factor stored in AdjustedAccelerationFactors must match massRatio exactly
        $this->assertEqualsWithDelta($massRatio, $adjusted->getScalingFactor(), 0.001);
    }
}
