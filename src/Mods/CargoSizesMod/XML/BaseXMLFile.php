<?php
/**
 * @package X4 Cargo Sizes Mod
 * @subpackage Macro XML Helpers
 */

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod;

use AppUtils\FileHelper\FileInfo;
use DOMDocument;
use DOMElement;
use Mistralys\X4\ExtractedData\DataFolder;

/**
 * Helper class used to handle a generic macro XML file.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage Macro XML Helpers
 */
abstract class BaseXMLFile
{
    protected FileInfo $xmlFile;
    protected string $xml;
    protected DOMDocument $dom;
    protected DataFolder $dataFolder;

    public function __construct(FileInfo $xmlFile, DataFolder $dataFolder)
    {
        $this->xmlFile = $xmlFile;
        $this->xml = $xmlFile->getContents();
        $this->dom = new DOMDocument();
        $this->dom->loadXML($this->xml);
        $this->dataFolder = $dataFolder;
    }

    public function getXML(): string
    {
        return $this->xml;
    }

    public function getDataFolder() : DataFolder
    {
        return $this->dataFolder;
    }

    public function getFileName() : string
    {
        return $this->xmlFile->getName();
    }

    public function xmlContains(string $needle) : bool
    {
        return str_contains($this->xml, $needle);
    }

    private ?string $macroName = null;

    public function getMacroName() : string
    {
        if(!isset($this->macroName))
        {
            $this->macroName = $this
                ->requireFirstByTagName('macro')
                ->getAttribute('name');
        }

        return $this->macroName;
    }

    public function getAliasName() : ?string
    {
        $alias = $this->requireFirstByTagName('macro')
            ->getAttribute('alias');

        if(!empty($alias)) {
            return $alias;
        }

        return null;
    }

    public function getFirstByTagName(string $tagName) : ?DOMElement
    {
        $elements = $this->dom->getElementsByTagName($tagName);
        return $elements->length > 0 ? $elements->item(0) : null;
    }

    public function requireFirstByTagName(string $tagName) : DOMElement
    {
        $element = $this->getFirstByTagName($tagName);

        if ($element !== null) {
            return $element;
        }

        throw new CargoSizeException(
            "The XML file does not contain a <{$tagName}> element.",
            $this->xml,
            CargoSizeException::ERROR_MISSING_XML_TAG
        );
    }

    /**
     * @param string $name
     * @return DOMElement[]
     */
    public function getAllByName(string $name) : array
    {
        return iterator_to_array($this->dom->getElementsByTagName($name)->getIterator());
    }
}
