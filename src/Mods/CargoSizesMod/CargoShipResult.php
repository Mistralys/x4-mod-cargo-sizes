<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod;

class CargoShipResult
{
    private string $macroName;
    private string $fileName;
    private int $cargo;
    private string $shipType;
    private string $storageType;
    private string $size;
    private string $shipName = '';

    public function __construct(string $macroName, string $fileName, int $cargo, string $shipType, string $storageType, string $size)
    {
        $this->macroName = $macroName;
        $this->fileName = $fileName;
        $this->cargo = $cargo;
        $this->shipType = $shipType;
        $this->storageType = $storageType;
        $this->size = $size;
    }

    public function getMacroName(): string
    {
        return $this->macroName;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getCargo(): int
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

    public function setShipName(string $shipName) : self
    {
        $this->shipName = $shipName;
        return $this;
    }

    public function getShipName(): string
    {
        return $this->shipName;
    }

    public function getTypeLabel() : string
    {
        return CargoSizeExtractor::SHIP_TYPES[$this->getShipType()]['label'] ?? 'Unknown';
    }
}
