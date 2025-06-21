<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\FOMOD;

use AppUtils\FileHelper\FileInfo;

class StepPluginImage
{
    private string $shipType;
    private string $shipSize;
    private float|int|null $multiplier;

    public function __construct(string $shipType, string $shipSize, float|int|null $multiplier)
    {
        $this->shipType = $shipType;
        $this->shipSize = $shipSize;
        $this->multiplier = $multiplier;
    }

    public function getImageFile() : FileInfo
    {
        return FileInfo::factory(sprintf(
            __DIR__.'/../../../../docs/fomod/%s',
            $this->getFileName()
        ));
    }

    public function exists() : bool
    {
        return $this->getImageFile()->exists();
    }

    public function getFileName() : string
    {
        if($this->multiplier !== null) {
            return sprintf(
                '%s-%s-x%s.jpg',
                $this->shipSize,
                $this->shipType,
                $this->multiplier
            );
        }

        return sprintf(
            '%s-%s.jpg',
            $this->shipSize,
            $this->shipType
        );
    }

    public function render() : string
    {
        $image = $this->getImageFile();

        if($image->exists()) {
            return sprintf(
                '<image path="fomod/images/%s" />',
                $image->getName()
            );
        }

        return '';
    }

    public function getZIPPath() : string
    {
        return sprintf('fomod/images/%s', $this->getFileName());
    }
}
