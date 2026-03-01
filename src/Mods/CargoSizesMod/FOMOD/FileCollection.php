<?php

declare(strict_types=1);

namespace Misc\Mods\CargoSizesMod\FOMOD;

use AppUtils\ZIPHelper;
use Mistralys\X4\Mods\CargoSizesMod\BaseOverrideFile;
use Mistralys\X4\Mods\CargoSizesMod\Build\BuildConfig;
use Mistralys\X4\Mods\CargoSizesMod\CargoSizeExtractor;
use Mistralys\X4\Mods\CargoSizesMod\Console;
use Mistralys\X4\Mods\CargoSizesMod\StorageOverrideFile;

class FileCollection
{
    public private(set) string $shipType;
    public private(set) string $shipSize;
    public private(set) int|float $multiplier;
    public private(set) string $id;

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
        self::$buildConfig = null;
    }

    public static function setConfig(BuildConfig $config): void
    {
        self::$buildConfig = $config;
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

    private static ?BuildConfig $buildConfig = null;

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

        $result = array_filter(
            self::getInstances(),
            fn(FileCollection $instance) => $instance->getShipTypeNormalized() === $shipType
        );

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
        return 'Increase cargo by x'.$this->getMultiplier();
    }

    public function getPluginDescription() : string
    {
        $description = sprintf(
            'Increases the cargo size for %1$s-sized %2$s by x%3$s.',
            strtoupper($this->getShipSize()),
            $this->getShipTypeLabel(),
            $this->getMultiplier()
        );

        $example = $this->getExampleShipDescription();
        if ($example !== '') {
            $description .= "\n\n" . $example;
        }

        return $description;
    }

    /**
     * Returns a description line with an example ship's cargo change for this collection.
     * Picks a random StorageOverrideFile from the collection to use as the example.
     *
     * @return string Example text, or empty string if no suitable file found
     */
    private function getExampleShipDescription(): string
    {
        $storageFiles = array_filter(
            $this->files,
            static fn(BaseOverrideFile $file): bool => $file instanceof StorageOverrideFile && $file->getShipName() !== ''
        );

        // The "Unchanged" plugin option for each FOMOD step contains no StorageOverrideFile
        // entries — it is a no-op that does not modify any game files. This means the
        // empty($storageFiles) guard naturally produces an empty string for those options.
        // This invariant must be preserved: if an "Unchanged" option were ever to gain
        // StorageOverrideFile entries, it would incorrectly show example ship text in the
        // FOMOD installer description.
        if (empty($storageFiles)) {
            return '';
        }

        $storageFiles = array_values($storageFiles);

        // Prefer the deterministically configured ship if available.
        $example = null;
        if (self::$buildConfig !== null) {
            $label = self::$buildConfig->getExampleShip($this->shipType, $this->shipSize);
            if ($label !== '') {
                foreach ($storageFiles as $file) {
                    if ($file->getShipName() === $label) {
                        $example = $file;
                        break;
                    }
                }
            }
        }

        // Fall back to random selection when not configured or ship not found in build data.
        if ($example === null) {
            $example = $storageFiles[array_rand($storageFiles)];
        }

        return sprintf(
            'Example: %s cargo changes from %s m³ to %s m³.',
            $example->getShipName(),
            number_format($example->getCargo(), 0, '.', ','),
            number_format($example->getAdjustedCargo(), 0, '.', ',')
        );
    }

    public function getInputFolderName() : string
    {
        return sprintf(
            '%s_%s_x%s',
            CargoSizeExtractor::normalizeShipType($this->getShipType()),
            $this->getShipSize(),
            $this->getMultiplier()
        );
    }

    public function getShipType(): string
    {
        return $this->shipType;
    }

    public function getShipTypeLabel() : string
    {
        return CargoSizeExtractor::getTypeLabel($this->getShipType());
    }

    public function getShipTypeNormalized() : string
    {
        return CargoSizeExtractor::normalizeShipType($this->getShipType());
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
