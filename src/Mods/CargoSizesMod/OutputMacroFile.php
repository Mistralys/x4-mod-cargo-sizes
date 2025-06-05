<?php
/**
 * @package X4 Mods
 * @subpackage Cargo Sizes
 */

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod;

use AppUtils\ConvertHelper\JSONConverter;
use AppUtils\FileHelper\FileInfo;
use AppUtils\FileHelper\FolderInfo;

/**
 * Used to store information and render the XML of the macro file
 * that modifies the cargo size of a single ship.
 *
 * @package X4 Mods
 * @subpackage Cargo Sizes
 */
class OutputMacroFile
{
    private FolderInfo $baseFolder;
    private int|float $multiplier;
    private string $id;
    private CargoShipResult $ship;

    public function __construct(FolderInfo $baseFolder, int|float $multiplier, CargoShipResult $ship)
    {
        $this->id = md5(JSONConverter::var2json(array($ship->getFileName(), $ship->getCargoValue(), $multiplier, $ship->getShipType(), $ship->getSize())));
        $this->baseFolder = $baseFolder;
        $this->multiplier = $multiplier;
        $this->ship = $ship;
    }

    public function getRelativePath() : string
    {
        return $this->ship->getRelativePath();
    }

    public function getName() : string
    {
        return $this->ship->getFileName();
    }

    public function getID(): string
    {
        return $this->id;
    }

    public function getMultiplier(): float|int
    {
        return $this->multiplier;
    }

    public function getCargo(): int
    {
        return $this->ship->getCargoValue();
    }

    public function getAdjustedCargo(): int
    {
        return $this->ship->calculateCargoValue($this->getMultiplier());
    }

    /**
     * @return string Ship size, e.g. `s`, `m`, `l`, `xl`
     */
    public function getSize(): string
    {
        return $this->ship->getSize();
    }

    private string $template = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<!-- 
    Ship: %4$s
    Original cargo value: %1$s
    Multiplier: %3$s 
-->
<diff>
	<replace sel="/macros/macro/properties/cargo/@max">%2$s</replace>
</diff>
XML;

    public function write() : string
    {
        $xml = sprintf(
            $this->template,
            $this->getCargo(),
            $this->getAdjustedCargo(),
            $this->getMultiplier(),
            $this->getShipName()
        );

        $path = sprintf(
            '%s/%s-%sx-%s-%s/%s',
            $this->baseFolder,
            CargoSizeExtractor::MOD_PREFIX,
            $this->getMultiplier(),
            CargoSizeExtractor::prettifyShipType($this->ship->getShipType()),
            $this->getSize(),
            $this->getName()
        );

        return FileInfo::factory($path)
            ->putContents($xml."\n")
            ->getPath();
    }

    public function getShipName() : string
    {
        return $this->ship->getShipName();
    }

    public function getTypeLabel() : string
    {
        return $this->ship->getTypeLabel();
    }
}
