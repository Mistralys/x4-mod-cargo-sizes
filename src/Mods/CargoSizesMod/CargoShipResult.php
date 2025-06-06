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
    private string $fileName;
    private int $cargo;
    private string $shipType;
    private string $storageType;
    private string $size;
    private string $shipName;
    private string $relativePath;
    private DataFolder $dataFolder;

    public function __construct(string $macroName, string $fileName, DataFolder $dataFolder, string $shipName, string $relativePath, int $cargo, string $shipType, string $storageType, string $size)
    {
        $this->dataFolder = $dataFolder;
        $this->macroName = $macroName;
        $this->fileName = $fileName;
        $this->relativePath = $relativePath;
        $this->shipName = $shipName;
        $this->cargo = $cargo;
        $this->shipType = $shipType;
        $this->storageType = $storageType;
        $this->size = $size;
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

    public function getFileName(): string
    {
        return $this->fileName;
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
