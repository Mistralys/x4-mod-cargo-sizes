<?php
/**
 * @package X4 Cargo Sizes Mod
 * @subpackage Macro XML Helpers
 */

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod;

use DOMElement;

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

    public function resolveShipName() : ?string
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

    private string $shipName = '';

    public function setShipName(string $name) : self
    {
        $this->shipName = $name;
        return $this;
    }

    public function getShipName() : string
    {
        return $this->shipName;
    }

    private string $cargoMacro = '';

    public function setCargoMacro(string $cargoConnection) : self
    {
        $this->cargoMacro = $cargoConnection;
        return $this;
    }

    public function getCargoMacro(): string
    {
        return $this->cargoMacro;
    }
}
