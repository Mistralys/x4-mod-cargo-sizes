<?php
/**
 * @package Build Tools
 * @subpackage Configuration
 */

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\Build;

use Mistralys\X4\Mods\CargoSizesMod\CargoSizeException;

/**
 * Represents a reduction tier for tier-based physics adjustments.
 *
 * Each tier defines a maximum cargo multiplier and the reduction percentage
 * to apply to ships with that multiplier.
 *
 * **Example:**
 * ReductionTier(maxMultiplier: 4.0, reductionPercent: 0.30)
 * - Applies to all ships with cargo multiplier <= 4.0
 * - Reduces the value by 30%
 *
 * @package Build Tools
 * @subpackage Configuration
 */
class ReductionTier
{
    /**
     * @param float $maxMultiplier Maximum cargo multiplier this tier applies to
     * @param float $reductionPercent Reduction percentage (0.0-1.0, where 0.30 = 30% reduction)
     * @throws CargoSizeException
     */
    public function __construct(
        private float $maxMultiplier,
        private float $reductionPercent
    )
    {
        $this->validate();
    }

    /**
     * Gets the maximum cargo multiplier this tier applies to.
     *
     * @return float Maximum multiplier (e.g., 4.0 for 4x cargo)
     */
    public function getMaxMultiplier(): float
    {
        return $this->maxMultiplier;
    }

    /**
     * Gets the reduction percentage as a decimal.
     *
     * @return float Reduction percentage (0.0-1.0)
     */
    public function getReductionPercent(): float
    {
        return $this->reductionPercent;
    }

    /**
     * Checks if this tier applies to the given cargo multiplier.
     *
     * @param float $multiplier Cargo multiplier to check (e.g., 4.0)
     * @return bool True if this tier applies (multiplier <= maxMultiplier)
     */
    public function appliesToMultiplier(float $multiplier): bool
    {
        return $multiplier <= $this->maxMultiplier;
    }

    /**
     * Applies the reduction to a value.
     *
     * **Example:**
     * - Original value: 100.0
     * - Reduction percent: 0.30 (30%)
     * - Result: 70.0 (reduced by 30%)
     *
     * @param float $value Original value
     * @return float Reduced value
     */
    public function applyReduction(float $value): float
    {
        return $value * (1.0 - $this->reductionPercent);
    }

    /**
     * Gets the multiplier to apply to a value (1.0 - reductionPercent).
     *
     * @return float Multiplier (e.g., 0.70 for 30% reduction)
     */
    public function getReductionMultiplier(): float
    {
        return 1.0 - $this->reductionPercent;
    }

    /**
     * Validates tier parameters.
     *
     * @throws CargoSizeException
     */
    private function validate(): void
    {
        if ($this->maxMultiplier <= 0) {
            throw new CargoSizeException(
                sprintf('Max multiplier must be greater than zero. Received: %f', $this->maxMultiplier),
                '',
                CargoSizeException::ERROR_UNHANDLED_SHIP_TYPE
            );
        }

        if ($this->reductionPercent < 0.0 || $this->reductionPercent > 1.0) {
            throw new CargoSizeException(
                sprintf(
                    'Reduction percent must be between 0.0 and 1.0. Received: %f',
                    $this->reductionPercent
                ),
                '',
                CargoSizeException::ERROR_UNHANDLED_SHIP_TYPE
            );
        }
    }

    /**
     * Formats the tier for display.
     *
     * @return string Formatted tier (e.g., "<=4.0x: 30% reduction")
     */
    public function format(): string
    {
        return sprintf(
            '<=%s: %d%% reduction',
            number_format($this->maxMultiplier, 1),
            (int)($this->reductionPercent * 100)
        );
    }

    /**
     * Creates a tier from an associative array.
     *
     * @param array{maxMultiplier: float|int, reductionPercent: float} $data
     * @return self
     * @throws CargoSizeException
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['maxMultiplier']) || !is_numeric($data['maxMultiplier'])) {
            throw new CargoSizeException(
                'Tier data must contain numeric "maxMultiplier" key',
                '',
                CargoSizeException::ERROR_UNHANDLED_SHIP_TYPE
            );
        }

        if (!isset($data['reductionPercent']) || !is_numeric($data['reductionPercent'])) {
            throw new CargoSizeException(
                'Tier data must contain numeric "reductionPercent" key',
                '',
                CargoSizeException::ERROR_UNHANDLED_SHIP_TYPE
            );
        }

        return new self(
            (float)$data['maxMultiplier'],
            (float)$data['reductionPercent']
        );
    }
}
