<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\References;

use Mistralys\X4\Mods\CargoSizesMod\ShipResult;

class MarkdownReference extends BaseReferenceRenderer
{
    protected function generateDocumentHeader(array &$lines): void
    {
        $lines[] = '# Cargo Sizes Mod - Cargo Values Reference';
        $lines[] = '';
    }

    public function getFileName(): string
    {
        return 'cargo-size-reference.md';
    }

    protected function generateMultiplierHeader(array &$lines, float|int $multiplier): void
    {
        $lines[] = '## Cargo Size x'.$multiplier;
        $lines[] = '';
    }

    protected function generateMultiplierEnd(array &$lines): void
    {
    }

    protected function generateTypeHeader(array &$lines, string $typeLabel): void
    {
        $lines[] = '### '.$typeLabel;
        $lines[] = '';
    }

    protected function generateShipLine(array &$lines, ShipResult $file, float|int $multiplier): void
    {
        $lines[] = sprintf(
            '- _%s_: %s m3 > **%s m3**',
            $file->getShipLabel(),
            number_format($file->getCargoValue(), 0, '.', ','),
            number_format($file->calculateCargoValue($multiplier), 0, '.', ','),
        );
    }

    protected function generateListEnd(array &$lines): void
    {
        $lines[] = '';
    }

    protected function generateListStart(array &$lines): void
    {
    }
}
