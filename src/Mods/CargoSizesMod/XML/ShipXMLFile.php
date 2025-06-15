<?php
/**
 * @package X4 Cargo Sizes Mod
 * @subpackage Macro XML Helpers
 */

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod;

use DOMElement;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\Drag;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\Inertia;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\Jerk;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\JerkBoost;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\JerkForward;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\JerkTravel;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\SteeringCurve;
use Mistralys\X4\XML\DOMExtended;
use Mistralys\X4\XML\ElementExtended;

/**
 * Helper class used to handle a single ship definition XML file.
 * For example, `assets/units/size_m/macros/ship_arg_m_trans_container_01_a_macro.xml`.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage Macro XML Helpers
 */
class ShipXMLFile extends XMLFile
{
    private ?string $size = null;

    public function getSize() : string
    {
        if(isset($this->size)) {
            return $this->size;
        }

        $macro = $this->getMacroName();

        foreach(CargoSizeExtractor::SHIP_SIZES as $size) {
            if(str_contains($macro, '_'.$size.'_')) {
                $this->size = $size;
                return $this->size;
            }
        }

        throw new CargoSizeException(
            'Cannot determine the ship size from the macro name ['.$macro.']. ',
            '',
            CargoSizeException::ERROR_UNRECOGNIZED_SHIP_SIZE
        );
    }

    public function resolveShipLabel() : ?string
    {
        $el = $this->getFirstByTagName('identification');
        if($el !== null) {
            $translationID = $el->getAttribute('name');
            return CargoSizeExtractor::getTranslations()->ts($translationID);
        }

        return null;
    }

    /**
     * @return DOMElement[]
     */
    public function getConnections() : array
    {
        return $this->getAllByName('connection');
    }

    public function getMass() : float
    {
        return (float)$this->requireFirstByTagName('physics')->getAttribute('mass');
    }

    public function getAccelerationFactor() : float
    {
        $el = $this->getFirstByTagName('accfactors');
        if($el === null) {
            return 1.0;
        }

        return (float)$el->getAttribute('forward');
    }

    private ?Drag $drag = null;

    public function getDrag() : Drag
    {
        if(isset($this->drag)) {
            return $this->drag;
        }

        $el = $this->requireFirstByTagName('drag');

        $this->drag = new Drag(
            (float)$el->getAttribute('forward'),
            (float)$el->getAttribute('reverse'),
            (int)$el->getAttribute('horizontal'),
            (int)$el->getAttribute('vertical'),
            (int)$el->getAttribute('pitch'),
            (int)$el->getAttribute('yaw'),
            (float)$el->getAttribute('roll')
        );

        return $this->drag;
    }

    private ?Inertia $inertia = null;

    public function getInertia() : Inertia
    {
        if(isset($this->inertia)) {
            return $this->inertia;
        }

        $el = $this->requireFirstByTagName('inertia');

        $this->inertia = new Inertia(
            (float)$el->getAttribute('pitch'),
            (float)$el->getAttribute('yaw'),
            (float)$el->getAttribute('roll')
        );

        return $this->inertia;
    }

    private ?Jerk $jerk = null;

    public function getJerk() : Jerk
    {
        if(isset($this->jerk)) {
            return $this->jerk;
        }

        $dom = new DOMExtended($this->dom);
        $jerk = $dom->bySelector('jerk')->requireFirst();

        $forward = $jerk->findChildren()->byTagName('forward')->requireFirst();
        $boost = $jerk->findChildren()->byTagName('forward_boost')->requireFirst();
        $travel = $jerk->findChildren()->byTagName('forward_travel')->requireFirst();

        $this->jerk = new Jerk(
            (float)$jerk->findChildren()->byTagName('strafe')->requireFirst()->getAttribute('value'),
            (float)$jerk->findChildren()->byTagName('angular')->requireFirst()->getAttribute('value'),
            new JerkForward(
                (float)$forward->getAttribute('accel'),
                (float)$forward->getAttribute('decel'),
                (float)$forward->getAttribute('ratio')
            ),
            new JerkBoost(
                (float)$boost->getAttribute('accel'),
                (float)$boost->getAttribute('ratio')
            ),
            new JerkTravel(
                (float)$travel->getAttribute('accel'),
                (float)$travel->getAttribute('decel'),
                (float)$travel->getAttribute('ratio')
            )
        );

        return $this->jerk;
    }

    private ?SteeringCurve $steeringCurve = null;

    public function getSteeringCurve() : SteeringCurve
    {
        if(isset($this->steeringCurve)) {
            return $this->steeringCurve;
        }

        $this->steeringCurve = new SteeringCurve();

        $dom = new DOMExtended($this->dom);
        $curve = $dom->byTagName('steeringcurve')->requireFirst();

        foreach($curve->findChildren()->byTagName('point')->getAll() as $point) {
            $this->steeringCurve->addPosition(
                $point->getAttribute('position'),
                (float)$point->getAttribute('value')
            );
        }

        return $this->steeringCurve;
    }
}
