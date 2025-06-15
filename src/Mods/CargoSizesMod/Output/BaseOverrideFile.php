<?php
/**
 * @package X4 Mods
 * @subpackage Cargo Sizes
 */

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod;

use AppUtils\ConvertHelper\JSONConverter;
use AppUtils\FileHelper\FolderInfo;
use Mistralys\X4\Database\DataSources\DataSourceDefs;
use Mistralys\X4\Database\MacroIndex\MacroFileDefs;
use Mistralys\X4\Mods\CargoSizesMod\Output\OverrideDef;

/**
 * Used to store information and render the XML of the macro file
 * that modifies the cargo size of a single ship.
 *
 * @package X4 Mods
 * @subpackage Cargo Sizes
 */
abstract class BaseOverrideFile
{
    protected FolderInfo $baseFolder;
    protected int|float $multiplier;
    protected string $id;
    protected ShipResult $ship;
    protected ?string $renderedXML = null;

    public function __construct(FolderInfo $baseFolder, int|float $multiplier, ShipResult $ship)
    {
        $this->id = md5(JSONConverter::var2json(array(get_class($this), $ship->getCargoFileName(), $ship->getCargoValue(), $multiplier, $ship->getShipType(), $ship->getSize())));
        $this->baseFolder = $baseFolder;
        $this->multiplier = $multiplier;
        $this->ship = $ship;
    }

    public function getShipType() : string
    {
        return $this->ship->getShipType();
    }

    public function getShipSize() : string
    {
        return $this->ship->getSize();
    }

    public function getName() : string
    {
        return $this->ship->getCargoFileName();
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

    abstract public function getMacroID() : string;

    public function getZipPath(string $rootRelative) : string
    {
        $macroDef = MacroFileDefs::getInstance()->getByID($this->getMacroID());

        $dataSource = DataSourceDefs::getInstance()->getByID($macroDef->getDataFolderID());
        if($dataSource->isExtension()) {
            $rootRelative .= '/extensions/'.$dataSource->getID();
        }

        return $rootRelative.'/'.$macroDef->getFullPath().'.xml';
    }

    public function getShipName() : string
    {
        return $this->ship->getShipLabel();
    }

    public function getTypeLabel() : string
    {
        return $this->ship->getTypeLabel();
    }

    /**
     * @var array<string, array{selector: string, value: string}>
     */
    private array $overrides = array();

    protected function addOverride() : OverrideDef
    {
        $def = new OverrideDef();
        $this->overrides[] = $def;
        return $def;
    }

    private string $template = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<!-- 
    Ship: %1$s
    Cargo multiplier: x%2$s
    Ship Macro File: %5$s
    Ship Storage Macro File: %6$s
%3$s 
-->
<diff>
%4$s
</diff>
XML;

    public function render() : string
    {
        if(isset($this->renderedXML)) {
            return $this->renderedXML;
        }

        $this->preRender();

        $this->renderedXML = sprintf(
            $this->template,
            $this->getShipName(),
            $this->getMultiplier(),
            $this->renderComments(),
            $this->renderOverrides(),
            $this->ship->getShipFileName(),
            $this->ship->getCargoFileName()
        )."\n";

        return $this->renderedXML;
    }

    abstract protected function preRender() : void;

    protected function addComment(string $comment, ...$args) : void
    {
        if(!empty($args)) {
            $comment = vsprintf($comment, $args);
        }

        $this->comments[] = $comment;
    }

    /**
     * @var string[]
     */
    private array $comments = array();

    private function renderComments() : string
    {
        if(empty($this->comments)) {
            return '';
        }

        return "    ".implode("\n    ", $this->comments)."\n";
    }

    private function renderOverrides() : string
    {
        usort($this->overrides, function(OverrideDef $a, OverrideDef $b) {
            return strcmp($a->getPath(), $b->getPath());
        });

        return implode("\n", $this->overrides)."\n";
    }
}
