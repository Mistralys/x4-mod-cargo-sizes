<?php
/**
 * @package X4 Cargo Sizes Mod
 * @subpackage Macro XML Helpers
 */

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\XML;

use DOMElement;
use Mistralys\X4\Mods\CargoSizesMod\CargoSizeException;
use Mistralys\X4\Mods\CargoSizesMod\CargoSizeExtractor;
use Mistralys\X4\Mods\CargoSizesMod\XMLFile;

/**
 * Helper class used to handle a single cargo definition XML file.
 * For example, `assets/units/size_m/macros/storage_arg_m_trans_container_01_a_macro.xml`.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage Macro XML Helpers
 */
class CargoXMLFile extends XMLFile
{
    public function getRelativePath() : string
    {
        $folder = $this->xmlFile->getRuntimeProperty(CargoSizeExtractor::FILE_PROP_FOLDER_RELATIVE);
        if(!empty($folder) && is_string($folder)) {
            return $folder;
        }

        throw new CargoSizeException(
            sprintf(
                'Invalid or missing relative path for the cargo XML file [%s].',
                $this->getFileName()
            ),
            '',
            CargoSizeException::ERROR_MISSING_RELATIVE_PATH
        );
    }

    public function hasCargoValue() : bool
    {
        return $this->getFirstByTagName('cargo') !== null;
    }

    public function isGenericStorage() : bool
    {
        return $this->xmlContains('generic_storage');
    }

    public function getCargoValue() : int
    {
        return (int)$this->getCargoElement()->getAttribute('max');
    }

    public function getCargoType() : string
    {
        return $this->getCargoElement()->getAttribute('tags');
    }

    private function getCargoElement() : DOMElement
    {
        return $this->requireFirstByTagName('cargo');
    }
}
