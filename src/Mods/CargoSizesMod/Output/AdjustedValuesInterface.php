<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\Output\Physics;

interface AdjustedValuesInterface
{
    public function isIncrease() : bool;
    public function getPrecision() : int;
    public function getMultiplier() : float;

    /**
     * @return string[]
     */
    public function getComments() : array;
}
