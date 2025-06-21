<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\FOMOD;

use AppUtils\FileHelper\FileInfo;
use AppUtils\FileHelper\FolderInfo;
use AppUtils\ZIPHelper;
use Misc\Mods\CargoSizesMod\FOMOD\FileCollection;
use Mistralys\X4\ExtractedData\DataFolders;
use Mistralys\X4\Mods\CargoSizesMod\CargoSizeExtractor;
use Mistralys\X4\Mods\CargoSizesMod\Console;
use Mistralys\X4\Mods\CargoSizesMod\ContentXMLRenderer;
use Mistralys\X4\Mods\CargoSizesMod\ModInfo;
use Mistralys\X4\Mods\CargoSizesMod\Translation;

class FomodWriter
{
    private FolderInfo $outputFolder;
    /**
     * @var float[]|int[]
     */
    private array $multipliers;
    private ZIPHelper $zip;
    private DataFolders $dataFolders;

    /**
     * @param array<int,int|float> $multipliers
     */
    public function __construct(array $multipliers, FolderInfo $outputFolder, DataFolders $dataFolders)
    {
        $this->outputFolder = $outputFolder;
        $this->multipliers = $multipliers;
        $this->dataFolders = $dataFolders;
    }

    public function write() : void
    {
        Console::header('Create FOMOD archive');

        $this->zip = new ZIPHelper(sprintf(
            '%s/cargo-size-fomod-v%s.zip',
            $this->outputFolder,
            ModInfo::getVersion()
        ));

        $this->writeInfoFile();
        $this->writeContentXML();
        $this->writeModuleConfig();
        $this->writeFiles();
        $this->writeImages();

        $this->zip->save();

        Console::line1('DONE.');
        Console::nl();
    }

    private function writeFiles() : void
    {
        Console::line1('Writing mod files...');

        foreach(FileCollection::getInstances() as $collection) {
            $collection->writeFiles($this->zip);
        }
    }

    private function writeInfoFile() : void
    {
        Console::line1('Writing info.xml');

        $this->zip->addString($this->generateInfoXML(), 'fomod/info.xml');
    }

    private const INFO_XML_TEMPLATE = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<fomod>
    <Name>%1$s</Name>
    <Author>%2$s</Author>
    <Version MachineVersion="%3$s">%3$s</Version>
    <Website>%4$s</Website>
    <Description>%5$s</Description>
</fomod>

XML;

    private function generateInfoXML() : string
    {
        return sprintf(
            self::INFO_XML_TEMPLATE,
            ModInfo::MOD_NAME,
            ModInfo::MOD_AUTHOR,
            ModInfo::getVersion(),
            ModInfo::MOD_HOMEPAGE,
            ModInfo::MOD_DESCRIPTION
        );
    }

    private function writeContentXML() : void
    {
        Console::line1('Writing content.xml');

        $this->zip->addString($this->generateContentXML(), 'cargo-size-fomod/content.xml');
    }

    public function getName() : Translation
    {
        return new Translation(Translation::TYPE_NAME_FOMOD);
    }

    public function getDescription() : Translation
    {
        return new Translation(Translation::TYPE_DESCR_FOMOD);
    }

    private function generateContentXML() : string
    {
        return (new ContentXMLRenderer(
            sprintf('cargo-size-fomod-v%s', ModInfo::getVersion()),
            $this->getName(),
            $this->getDescription(),
            $this->dataFolders
        ))->render();
    }

    private function writeModuleConfig() : void
    {
        Console::line1('Writing ModuleConfig.xml');

        $this->zip->addString($this->generateConfigXML(), 'fomod/ModuleConfig.xml');
    }

    private function writeImages() : void
    {
        foreach($this->images as $image)
        {
            $this->zip->addFile($image->getImageFile()->getPath(), $image->getZIPPath());
        }
    }

    private const CONFIG_XML_TEMPLATE = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="http://qconsulting.ca/fo3/ModConfig5.0.xsd">
    <moduleName>%1$s</moduleName>

    <requiredInstallFiles>
        <file source="cargo-size-fomod/content.xml"/>
    </requiredInstallFiles>

    <installSteps order="Explicit">

%2$s
        
    </installSteps>
</config>

XML;


    private function generateConfigXML() : string
    {
        return sprintf(
            self::CONFIG_XML_TEMPLATE,
            ModInfo::MOD_NAME,
            $this->generateConfigSteps()
        );
    }

    private function generateConfigSteps() : string
    {
        $steps = array();

        foreach(CargoSizeExtractor::getShipTypesPretty() as $shipType)
        {
            $ships = FileCollection::getByPrettyShipType($shipType);

            foreach (CargoSizeExtractor::SHIP_SIZES as $shipSize)
            {
                $sizeShips = array_filter($ships, static function(FileCollection $collection) use ($shipSize) : bool {
                    return $collection->getShipSize() === $shipSize;
                });

                if(empty($sizeShips)) {
                    continue;
                }

                $steps[] = array(
                    'shipType' => $shipType,
                    'shipSize' => $shipSize,
                    'sizeShips' => $sizeShips
                );
            }
        }

        $totalSteps = count($steps);
        $stepXML = array();
        $stepNumber = 1;
        foreach($steps as $step) {
            $stepXML[] = $this->generateConfigStep(
                $stepNumber,
                $totalSteps,
                CargoSizeExtractor::getTypeLabel($step['shipType']).' ('.strtoupper($step['shipSize']).')',
                $step['sizeShips'],
                $step['shipType'],
                $step['shipSize']
            );

            $stepNumber++;
        }

        return implode(PHP_EOL, $stepXML);
    }

    private const CONFIG_STEP_TEMPLATE = <<<'XML'
        <installStep name="%1$s %2$s (%3$s/%4$s)">
            <optionalFileGroups>
                <group name="Cargo increase for %2$s-sized %1$s" type="SelectExactlyOne">
                    <plugins order="Explicit">
%5$s
                    </plugins>
                </group>
            </optionalFileGroups>
        </installStep>        

XML;

    /**
     * @param int $stepNumber
     * @param int $totalSteps
     * @param string $label
     * @param FileCollection[] $ships
     * @return string
     */
    private function generateConfigStep(int $stepNumber, int $totalSteps, string $label, array $ships, string $shipType, string $shipSize) : string
    {
        Console::line2('Install step [%s] %s/%s', $label, $stepNumber, $totalSteps);

        return sprintf(
            self::CONFIG_STEP_TEMPLATE,
            CargoSizeExtractor::getTypeLabel($shipType),
            strtoupper($shipSize),
            $stepNumber,
            $totalSteps,
            $this->generateConfigPlugins($ships, $shipType, $shipSize)
        );
    }

    private function registerPluginImage(string $shipType, string $shipSize, float|int|null $multiplier) : StepPluginImage
    {
        $image = new StepPluginImage($shipType, $shipSize, $multiplier);
        $this->images[] = $image;
        return $image;
    }

    /**
     * @var StepPluginImage[]
     */
    private array $images = array();

    /**
     * @param FileCollection[] $fileCollections
     * @return string
     */
    private function generateConfigPlugins(array $fileCollections, string $shipType, string $shipSize) : string
    {
        $plugins = array();

        // Add the default plugin for unchanged ships.
        $plugins[] = sprintf(
            self::CONFIG_PLUGIN_DEFAULT_TEMPLATE,
            $this->registerPluginImage($shipType, $shipSize, null)->render()
        );

        foreach($this->multipliers as $multiplier) {
            foreach($fileCollections as $fileCollection) {
                if($fileCollection->getMultiplier() !== $multiplier) {
                    continue;
                }

                $plugins[] = $this->generateConfigPlugin(
                    $fileCollection,
                    $this->registerPluginImage($shipType, $shipSize, $multiplier)
                );
            }
        }

        return implode(PHP_EOL, $plugins);
    }

    private const CONFIG_PLUGIN_TEMPLATE = <<<'XML'
                        <plugin name="%1$s">
                            <description>%2$s</description>
                            %5$s
                            <files>
                                <folder source="%3$s" destination="%4$s"/>
                            </files>
                            <typeDescriptor><type name="Optional" /></typeDescriptor>
                        </plugin>

XML;

    private function generateConfigPlugin(FileCollection $collection, StepPluginImage $image) : string
    {
        return sprintf(
            self::CONFIG_PLUGIN_TEMPLATE,
            $collection->getPluginLabel(),
            $collection->getPluginDescription(),
            $collection->getInputFolderName(),
            $collection->getOutputFolderName(),
            $image->render()
        );
    }

    private const CONFIG_PLUGIN_DEFAULT_TEMPLATE = <<<'XML'
                        <plugin name="Unchanged">
                            <description>Do not change this ship category.</description>
                            %1$s
                            <typeDescriptor><type name="Recommended" /></typeDescriptor>
                        </plugin>

XML;
}
