<?php
/**
 * @package X4 Mods
 * @subpackage Cargo Sizes
 */

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod;

use AppUtils\FileHelper;

/**
 * Used to store information and render the XML of the macro file
 * that modifies the cargo size of a single ship.
 *
 * @package X4 Mods
 * @subpackage Cargo Sizes
 */
class StorageOverrideFile extends BaseOverrideFile
{
    protected function preRender() : void
    {
        $this->addOverride()
            ->setMacroPath('properties/cargo/@max')
            ->setInt($this->getAdjustedCargo())
            ->setComment('Original cargo value: %s', $this->getCargo());
    }

    public function getMacroID() : string
    {
        return FileHelper::removeExtension($this->ship->getCargoFileName());
    }
}
