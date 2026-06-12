<?php
/**
 * @package Output
 * @subpackage Physics
 */

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\Output\Physics;

use Mistralys\X4\Mods\CargoSizesMod\Output\TagOverrideDef;
use function Mistralys\X4\dec2;

/**
 * Override definition for the thruster acceleration factors.
 *
 * Targets only `properties/thruster/acceleration` and outputs the four
 * acceleration axes (forward, reverse, horizontal, vertical) with XML
 * comments showing the scaling factor and original values.
 *
 * @package Output
 * @subpackage Physics
 */
class AccelerationOverrideDef extends TagOverrideDef
{
    private AdjustedAccelerationFactors $accelerationFactors;

    public function __construct(string $macroName, AdjustedAccelerationFactors $accelerationFactors)
    {
        parent::__construct($macroName);

        $this
            ->setMacroPath('properties/physics/accfactors')
            ->setTagName('accfactors');

        // Ships that have no explicit <accfactors> in their macro XML need an <add>
        // operation (to append the element under <physics>). Ships that already have
        // <accfactors> use a <replace> operation on the existing element.
        $this->enableAddMode($accelerationFactors->getOriginal()->isEmpty());

        $this->accelerationFactors = $accelerationFactors;
    }

    private const TAG_TEMPLATE = <<<'XML'
        <accfactors forward="$ACC_FORWARD" reverse="$ACC_REVERSE" horizontal="$ACC_HORIZONTAL" vertical="$ACC_VERTICAL" />
        <!-- AccelerationFactor scaled by $SCALING_FACTOR -->
        <!-- Original: forward=$ACC_FORWARD_ORIG, reverse=$ACC_REVERSE_ORIG, horizontal=$ACC_HORIZONTAL_ORIG, vertical=$ACC_VERTICAL_ORIG -->
XML;

    protected function renderTag(): string
    {
        return str_replace(
            array_keys($this->getValues()),
            array_values($this->getValues()),
            self::TAG_TEMPLATE
        );
    }

    private function getValues(): array
    {
        $orig = $this->accelerationFactors->getOriginal();

        // IMPORTANT: _ORIG keys must come before their shorter counterparts
        // (e.g. $ACC_FORWARD_ORIG before $ACC_FORWARD) to prevent str_replace
        // from mangling the _ORIG tokens during substitution.
        return array(
            '$ACC_FORWARD_ORIG' => dec2($orig->getForward()),
            '$ACC_REVERSE_ORIG' => dec2($orig->getReverse()),
            '$ACC_HORIZONTAL_ORIG' => dec2($orig->getHorizontal()),
            '$ACC_VERTICAL_ORIG' => dec2($orig->getVertical()),
            '$ACC_FORWARD' => dec2($this->accelerationFactors->getForward()),
            '$ACC_REVERSE' => dec2($this->accelerationFactors->getReverse()),
            '$ACC_HORIZONTAL' => dec2($this->accelerationFactors->getHorizontal()),
            '$ACC_VERTICAL' => dec2($this->accelerationFactors->getVertical()),
            '$SCALING_FACTOR' => dec2($this->accelerationFactors->getScalingFactor()) . 'x',
        );
    }
}
