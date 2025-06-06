<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod;

use Mistralys\X4\Database\Translations\TranslationExtractor;
use Mistralys\X4\ExtractedData\DataFolders;

class ContentXMLRenderer
{
    private const XML_TEMPLATE = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<content id="%1$s" name="%2$s" description="%3$s" author="%4$s" version="%5$s" date="%6$s" save="0" enabled="1">
    %8$s
    %7$s
</content>
XML;
    private string $modID;
    private Translation $name;
    private Translation $description;
    private DataFolders $dataFolders;

    public function __construct(string $modID, Translation $name, Translation $description, DataFolders $dataFolders)
    {
        $this->modID = $modID;
        $this->name = $name;
        $this->description = $description;
        $this->dataFolders = $dataFolders;
    }

    /**
     * Renders the content for the `content.xml` file that is
     * used by X4 to display information on the mod in the
     * "Extensions" UI.
     *
     * @return string
     */
    public function render() : string
    {
        return sprintf(
            self::XML_TEMPLATE,
            $this->modID,
            $this->name->getInvariant(),
            $this->description->getInvariant(),
            ModInfo::MOD_AUTHOR,
            str_replace('.', '', ModInfo::getVersion()),
            date('Y-m-d'),
            $this->renderTranslations(),
            $this->renderDependencies()
        );
    }

    private function renderTranslations() : string
    {
        $translations = array();

        foreach(array_keys(TranslationExtractor::LANGUAGES) as $langID)
        {
            $translations[] = sprintf(
                '<text language="%d" name="%s" description="%s" author="%s" />',
                $langID,
                $this->name->getByLanguageID($langID),
                $this->description->getByLanguageID($langID),
                ModInfo::MOD_AUTHOR
            );
        }

        return implode("\n    ", $translations)."\n";
    }

    private function renderDependencies() : string
    {
        $dependencies = array();

        foreach($this->dataFolders->getAll() as $dataFolder)
        {
            if(!$dataFolder->isExtension()) {
                continue;
            }

            $dependencies[] = sprintf(
                '<dependency id="%s" optional="true" name="%s"/>',
                $dataFolder->getID(),
                $dataFolder->getLabel()
            );
        }

        return implode("\n    ", $dependencies)."\n";
    }
}