<?php
/**
 * @package X4 Cargo Sizes Mod
 * @subpackage Extractor
 */

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod;

use Mistralys\X4\ExtractedData\DataFolder;

/**
 * @package X4 Cargo Sizes Mod
 * @subpackage Extractor
 */
class CargoShipResult
{
    private string $macroName;
    private string $cargoFileName;
    private int $cargo;
    private string $shipType;
    private string $storageType;
    private string $size;
    private string $shipName;
    private string $relativePath;
    private DataFolder $dataFolder;
    private ShipXMLFile $xmlFile;
    private string $shipFileName;

    public function __construct(string $macroName, string $shipFileName, string $cargoFileName, DataFolder $dataFolder, string $shipName, string $relativePath, int $cargo, string $shipType, string $storageType, string $size, ShipXMLFile $xmlFile)
    {
        $this->dataFolder = $dataFolder;
        $this->macroName = $macroName;
        $this->shipFileName = $shipFileName;
        $this->cargoFileName = $cargoFileName;
        $this->relativePath = $relativePath;
        $this->shipName = $shipName;
        $this->cargo = $cargo;
        $this->shipType = $shipType;
        $this->storageType = $storageType;
        $this->size = $size;
        $this->xmlFile = $xmlFile;
    }

    public function getXMLFile(): ShipXMLFile
    {
        return $this->xmlFile;
    }

    public function getRelativePath(): string
    {
        if($this->dataFolder->isExtension()) {
            return sprintf(
                'extensions/%s/%s',
                $this->dataFolder->getID(),
                $this->relativePath
            );
        }

        return $this->relativePath;
    }

    public function getMacroName(): string
    {
        return $this->macroName;
    }

    public function getCargoFileName(): string
    {
        return $this->cargoFileName;
    }

    public function getShipFileName(): string
    {
        return $this->shipFileName;
    }

    public function getCargoValue(): int
    {
        return $this->cargo;
    }

    public function getShipType(): string
    {
        return $this->shipType;
    }

    public function getStorageType(): string
    {
        return $this->storageType;
    }

    public function getSize(): string
    {
        return $this->size;
    }

    public function getShipName(): string
    {
        return $this->shipName;
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
