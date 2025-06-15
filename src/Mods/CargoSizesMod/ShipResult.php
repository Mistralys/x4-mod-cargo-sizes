<?php
/**
 * @package X4 Cargo Sizes Mod
 * @subpackage Extractor
 */

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod;

use Mistralys\X4\ExtractedData\DataFolder;
use Mistralys\X4\Mods\CargoSizesMod\XML\CargoXMLFile;

/**
 * @package X4 Cargo Sizes Mod
 * @subpackage Extractor
 */
class ShipResult
{
    private string $shipType;
    private string $shipLabel;
    private ShipXMLFile $shipXMLFile;
    private CargoXMLFile $cargoXMLFile;

    public function __construct(string $shipLabel, string $shipType, ShipXMLFile $xmlFile, CargoXMLFile $cargoXMLFile)
    {
        $this->shipLabel = $shipLabel;
        $this->shipType = $shipType;
        $this->shipXMLFile = $xmlFile;
        $this->cargoXMLFile = $cargoXMLFile;
    }

    public function getShipXMLFile(): ShipXMLFile
    {
        return $this->shipXMLFile;
    }

    public function getCargoXMLFile(): CargoXMLFile
    {
        return $this->cargoXMLFile;
    }

    public function getDataFolder() : DataFolder
    {
        return $this->shipXMLFile->getDataFolder();
    }

    public function getCargoFileName(): string
    {
        return $this->cargoXMLFile->getFileName();
    }

    public function getShipFileName(): string
    {
        return $this->shipXMLFile->getFileName();
    }

    public function getCargoValue(): int
    {
        return $this->cargoXMLFile->getCargoValue();
    }

    public function getShipType(): string
    {
        return $this->shipType;
    }

    public function getCargoType(): string
    {
        return $this->cargoXMLFile->getCargoType();
    }

    public function getSize(): string
    {
        return $this->shipXMLFile->getSize();
    }

    public function getShipLabel(): string
    {
        return $this->shipLabel;
    }

    public function getTypeLabel() : string
    {
        return CargoSizeExtractor::SHIP_TYPES[$this->getShipType()]['label'] ?? 'Unknown';
    }

    public function calculateCargoValue(float|int $multiplier) : int
    {
        return (int)ceil($this->getCargoValue() * $multiplier);
    }
}
