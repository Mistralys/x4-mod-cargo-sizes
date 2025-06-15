<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\References;

use AppUtils\FileHelper\FileInfo;
use Mistralys\X4\Mods\CargoSizesMod\ShipResult;

abstract class BaseReferenceRenderer
{
    private array $multipliers;
    private array $results;

    public function __construct(array $multipliers, array $results)
    {
        $this->multipliers = $multipliers;
        $this->results = $results;
    }

    abstract public function getFileName() : string;

    public function write() : void
    {
        FileInfo::factory(__DIR__.'/../../../../docs/'.$this->getFileName())
            ->putContents($this->generate());
    }

    public function generate() : string
    {
        $lines = array();

        $this->generateDocumentHeader($lines);

        foreach($this->multipliers as $multiplier)
        {
            $this->generateMultiplierHeader($lines, $multiplier);

            foreach($this->results as $type => $results)
            {
                $this->generateMultiplierLines($lines, $type, $results, $multiplier);
            }

            $this->generateMultiplierEnd($lines);
        }

        return implode("\n", $lines)."\n";
    }

    abstract protected function generateMultiplierEnd(array &$lines) : void;

    abstract protected function generateDocumentHeader(array &$lines) : void;

    /**
     * @param string[] $lines
     * @param int|float $multiplier
     * @return void
     */
    abstract protected function generateMultiplierHeader(array &$lines, int|float $multiplier) : void;

    /**
     * @param string[] $lines
     * @param string $typeLabel
     * @param ShipResult[] $files
     * @return void
     */
    private function generateMultiplierLines(array &$lines, string $typeLabel, array $files, int|float $multiplier) : void
    {
        $this->generateTypeHeader($lines, $typeLabel);

        usort($files, static function(ShipResult $a, ShipResult $b) : int {
            return strnatcasecmp($a->getShipLabel(), $b->getShipLabel());
        });

        $this->generateListStart($lines);

        foreach($files as $file) {
            $this->generateShipLine($lines, $file, $multiplier);
        }

        $this->generateListEnd($lines);
    }

    /**
     * @param string[] $lines
     * @return void
     */
    abstract protected function generateListStart(array  &$lines) : void;

    /**
     * @param string[] $lines
     * @return void
     */
    abstract protected function generateListEnd(array  &$lines) : void;

    /**
     * @param string[] $lines
     * @param string $typeLabel
     * @return void
     */
    abstract protected function generateTypeHeader(array &$lines, string $typeLabel) : void;

    /**
     * @param string[] $lines
     * @param ShipResult $file
     * @param int|float $multiplier
     * @return void
     */
    abstract protected function generateShipLine(array &$lines, ShipResult $file, int|float $multiplier) : void;
}
