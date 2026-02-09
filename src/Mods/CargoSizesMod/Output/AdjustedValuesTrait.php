<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\Output\Physics;

use function Mistralys\X4\dec;
use function Mistralys\X4\dec3;

trait AdjustedValuesTrait
{
    /**
     * @var string[]
     */
    private array $comments = array();
    private float $multiplier = 1.0;

    protected function setMultiplier(float $multiplier) : void
    {
        $this->multiplier = $multiplier;
    }

    public function getMultiplier() : float
    {
        return $this->multiplier;
    }

    protected function addValue(string $label, float $originalValue, float $newValue) : void
    {
        if($originalValue === $newValue) {
            $this->addComment(sprintf(
                '%s: %s (no change)',
                $label,
                dec($newValue, $this->getPrecision())
            ));
            return;
        }

        $sign = '+';
        if(!$this->isIncrease()) {
            $sign = '-';
        }

        $precision = $this->getPrecision();

        $this->addComment(sprintf(
            '%s: %s (= %s %s (%s * %s))',
            $label,
            dec($newValue, $precision),
            dec($originalValue, $precision),
            $sign,
            dec($originalValue, $precision),
            dec3($this->getMultiplier())
        ));
    }

    protected function addComment(string $comment, ...$args) : void
    {
        $this->comments[] = vsprintf($comment, $args);
    }

    public function getComments() : array
    {
        return $this->comments;
    }
}
