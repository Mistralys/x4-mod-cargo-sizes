<?php

declare(strict_types=1);

namespace Misc\Mods\CargoSizesMod\FOMOD;

use AppUtils\ZIPHelper;
use Mistralys\X4\Mods\CargoSizesMod\BaseOverrideFile;
use Mistralys\X4\Mods\CargoSizesMod\CargoSizeExtractor;
use Mistralys\X4\Mods\CargoSizesMod\Console;
use Mistralys\X4\Mods\CargoSizesMod\StorageOverrideFile;

class FileCollection
{
    private string $shipType;
    private string $shipSize;
    private int|float $multiplier;
    private string $id;

    private function __construct(string $id, string $shipType, string $shipSize, int|float $multiplier)
    {
        $this->id = $id;
        $this->shipType = $shipType;
        $this->shipSize = $shipSize;
        $this->multiplier = $multiplier;
    }

    public static function reset() : void
    {
        self::$instances = array();
    }

    /**
     * @return FileCollection[]
     */
    public static function getInstances(): array
    {
        uasort(self::$instances, static function(FileCollection $a, FileCollection $b) : int {
            return strnatcasecmp($a->getStepLabel(), $b->getStepLabel());
        });

        return array_values(self::$instances);
    }

    /**
     * @var array<string,FileCollection>
     */
    private static array $instances = array();

    public static function create(string $shipType, string $size, float|int $multiplier) : self
    {
        $id = sprintf(
            '%s-%s-%s',
            $shipType,
            $size,
            str_replace('.', '', (string)$multiplier)
        );

        if(!isset(self::$instances[$id])) {
            self::$instances[$id] = new self($id, $shipType, $size, $multiplier);
        }

        return self::$instances[$id];
    }

    /**
     * @param string $shipType
     * @return FileCollection[]
     */
    public static function getByPrettyShipType(string $shipType) : array
    {
        $result = array();

        foreach(self::getInstances() as $instance) {
            if($instance->getShipTypePretty() === $shipType) {
                $result[] = $instance;
            }
        }

        return $result;
    }

    public function getID() : string
    {
        return $this->id;
    }

    public function getStepLabel() : string
    {
        return sprintf(
            '%s (%s)',
            CargoSizeExtractor::getTypeLabel($this->getShipType()),
            strtoupper($this->getShipSize())
        );
    }

    public function getPluginLabel() : string
    {
        return 'x'.$this->getMultiplier();
    }

    public function getPluginDescription() : string
    {
        return sprintf(
            'Increase the cargo size by x%s',
            $this->getMultiplier()
        );
    }

    public function getInputFolderName() : string
    {
        return sprintf(
            '%s_%s_x%s',
            CargoSizeExtractor::prettifyShipType($this->getShipType()),
            $this->getShipSize(),
            $this->getMultiplier()
        );
    }

    public function getShipType(): string
    {
        return $this->shipType;
    }

    public function getShipTypePretty() : string
    {
        return CargoSizeExtractor::prettifyShipType($this->getShipType());
    }

    public function getShipSize(): string
    {
        return $this->shipSize;
    }

    public function getMultiplier(): int|float
    {
        return $this->multiplier;
    }

    /**
     * @var BaseOverrideFile[]
     */
    private array $files = array();

    public function addFile(BaseOverrideFile $macroFile) : self
    {
        $this->files[] = $macroFile;
        return $this;
    }

    public function getOutputFolderName() : string
    {
        return 'cargo-size-fomod';
    }

    public function writeFiles(ZIPHelper $zip) : void
    {
        $relativeName = $this->getInputFolderName();

        foreach($this->files as $file) {
            $path = $file->getZipPath($relativeName);
            Console::line2('Writing file [%s].', $path);
            $zip->addString($file->render(), $path);
        }
    }
}
