<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\References;

use Mistralys\X4\Mods\CargoSizesMod\ShipResult;

class BBCodeReference extends BaseReferenceRenderer
{
    protected function generateDocumentHeader(array &$lines): void
    {
    }

    public function getFileName(): string
    {
        return 'cargo-size-reference.bbcode';
    }

    protected function generateMultiplierHeader(array &$lines, float|int $multiplier): void
    {
        $lines[] = '[size=2][b]Cargo Size x'.$multiplier.'[/b][/size]';
        $lines[] = '[spoiler]';
    }

    protected function generateMultiplierEnd(array &$lines): void
    {
        $lines[] = '[/spoiler]';
    }

    protected function generateListStart(array &$lines): void
    {
        $lines[] = '[list]';
    }

    protected function generateListEnd(array &$lines): void
    {
        $lines[] = '[/list]';
    }

    protected function generateTypeHeader(array &$lines, string $typeLabel): void
    {
        $lines[] = '[size=2][b]'.$typeLabel.'[/b][/size]';
    }

    protected function generateShipLine(array &$lines, ShipResult $file, float|int $multiplier): void
    {
        $lines[] = sprintf(
            '[*] [i]%s[/i]: %s m3 > [b]%s m3[/b]',
            $file->getShipLabel(),
            number_format($file->getCargoValue(), 0, '.', ','),
            number_format($file->calculateCargoValue($multiplier), 0, '.', ','),
        );
    }
}
