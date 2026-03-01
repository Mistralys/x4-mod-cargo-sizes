<?php
/**
 * @package X4 Cargo Sizes Mod
 * @subpackage Physics Calculations
 */

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\Output\Physics;

use Mistralys\X4\Mods\CargoSizesMod\CargoSizeException;

/**
 * Physics calculator for mass-based adjustments.
 *
 * Provides clear, physics-based calculations to replace confusing "multiplier" terminology.
 * All ratios are > 1.0 when cargo increases, making the math intuitive.
 *
 * **Key Concepts:**
 * - **massRatio**: adjustedFullMass / originalFullMass (>1.0 when cargo increases)
 * - **cargoMultiplier**: user's chosen cargo multiplier (2x, 4x, 10x)
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage Physics Calculations
 */
class PhysicsCalculator
{
    private float $originalFullMass;
    private float $adjustedFullMass;
    private float $massIncrease;
    private float $massRatio;

    /**
     * @param float $baseMass Ship mass without cargo
     * @param float $originalCargo Original cargo capacity
     * @param float $adjustedCargo Adjusted cargo capacity (after multiplier)
     * @param float $cargoMultiplier User's chosen multiplier (2x, 4x, 10x, etc.)
     * @throws CargoSizeException
     */
    public function __construct(
        private float $baseMass,
        private float $originalCargo,
        private float $adjustedCargo,
        private float $cargoMultiplier
    )
    {
        $this->validate();
        $this->calculate();
    }

    /**
     * Validates input values.
     *
     * @throws CargoSizeException
     */
    private function validate(): void
    {
        if ($this->baseMass <= 0) {
            throw new CargoSizeException(
                sprintf('Base mass must be greater than zero. Received: %f', $this->baseMass),
                '',
                CargoSizeException::ERROR_INVALID_CALCULATOR_PARAMS
            );
        }

        if ($this->originalCargo < 0) {
            throw new CargoSizeException(
                sprintf('Original cargo cannot be negative. Received: %f', $this->originalCargo),
                '',
                CargoSizeException::ERROR_INVALID_CALCULATOR_PARAMS
            );
        }

        if ($this->adjustedCargo < 0) {
            throw new CargoSizeException(
                sprintf('Adjusted cargo cannot be negative. Received: %f', $this->adjustedCargo),
                '',
                CargoSizeException::ERROR_INVALID_CALCULATOR_PARAMS
            );
        }

        if ($this->cargoMultiplier <= 0) {
            throw new CargoSizeException(
                sprintf('Cargo multiplier must be greater than zero. Received: %f', $this->cargoMultiplier),
                '',
                CargoSizeException::ERROR_INVALID_CALCULATOR_PARAMS
            );
        }
    }

    /**
     * Performs all calculations.
     */
    private function calculate(): void
    {
        $this->originalFullMass = $this->baseMass + $this->originalCargo;
        $this->adjustedFullMass = $this->baseMass + $this->adjustedCargo;
        $this->massIncrease = $this->adjustedFullMass - $this->originalFullMass;

        // Prevent division by zero
        if ($this->originalFullMass <= 0) {
            $this->massRatio = 1.0;
        } else {
            $this->massRatio = $this->adjustedFullMass / $this->originalFullMass;
        }
    }

    /**
     * Gets the mass ratio (adjustedFullMass / originalFullMass).
     *
     * This is always >= 1.0 for ships with increased cargo.
     * Values > 10.0 are considered extreme.
     *
     * @return float Mass ratio (typically 1.0-10.0)
     */
    public function getMassRatio(): float
    {
        return $this->massRatio;
    }

    /**
     * Gets the user's chosen cargo multiplier (2x, 4x, 10x, etc.).
     *
     * @return float Cargo multiplier
     */
    public function getCargoMultiplier(): float
    {
        return $this->cargoMultiplier;
    }

    /**
     * Gets the ship's base mass (without cargo).
     *
     * @return float Base mass
     */
    public function getBaseMass(): float
    {
        return $this->baseMass;
    }

    /**
     * Gets the original full load mass (baseMass + originalCargo).
     *
     * @return float Original full mass
     */
    public function getOriginalFullMass(): float
    {
        return $this->originalFullMass;
    }

    /**
     * Gets the adjusted full load mass (baseMass + adjustedCargo).
     *
     * @return float Adjusted full mass
     */
    public function getAdjustedFullMass(): float
    {
        return $this->adjustedFullMass;
    }

    /**
     * Gets the absolute mass increase (adjustedFullMass - originalFullMass).
     *
     * @return float Mass increase in kg
     */
    public function getMassIncrease(): float
    {
        return $this->massIncrease;
    }

    /**
     * Gets the mass increase as a percentage.
     *
     * @return float Mass increase percentage (0.0 = no change, 100.0 = doubled)
     */
    public function getMassIncreasePercent(): float
    {
        return ($this->massRatio - 1.0) * 100.0;
    }

    /**
     * Gets the inverse mass ratio (1.0 / massRatio).
     *
     * Used for jerk calculations where higher mass should reduce jerk.
     * Always <= 1.0 for ships with increased cargo.
     *
     * @return float Inverse mass ratio
     */
    public function getInverseMassRatio(): float
    {
        return 1.0 / $this->massRatio;
    }

    /**
     * Gets the squared mass ratio (massRatio²).
     *
     * Used for squared drag reduction calculations.
     *
     * @return float Mass ratio squared
     */
    public function getMassRatioSquared(): float
    {
        return $this->massRatio * $this->massRatio;
    }

    /**
     * Validates the calculator and returns any warnings.
     *
     * @return string[] Array of warning messages
     */
    public function getValidationWarnings(): array
    {
        $warnings = [];

        if ($this->massRatio > 10.0) {
            $warnings[] = sprintf(
                'Extreme mass ratio detected: %.2fx (cargo-to-mass ratio is very high)',
                $this->massRatio
            );
        }

        if ($this->originalCargo === 0.0 && $this->adjustedCargo > 0.0) {
            $warnings[] = 'Ship originally had zero cargo capacity but now has cargo';
        }

        return $warnings;
    }

    /**
     * Formats the mass ratio for display.
     *
     * @param int $decimals Number of decimal places
     * @return string Formatted ratio (e.g., "2.45")
     */
    public function formatMassRatio(int $decimals = 2): string
    {
        return number_format($this->massRatio, $decimals, '.', '');
    }

    /**
     * Gets a debug string representation of the calculator.
     *
     * @return string Debug information
     */
    public function getDebugInfo(): string
    {
        return sprintf(
            "PhysicsCalculator:\n" .
            "  Base Mass: %.2f kg\n" .
            "  Original Cargo: %.2f kg\n" .
            "  Adjusted Cargo: %.2f kg\n" .
            "  Original Full Mass: %.2f kg\n" .
            "  Adjusted Full Mass: %.2f kg\n" .
            "  Mass Increase: %.2f kg (%.1f%%)\n" .
            "  Mass Ratio: %.2fx\n" .
            "  Cargo Multiplier: %.2fx",
            $this->baseMass,
            $this->originalCargo,
            $this->adjustedCargo,
            $this->originalFullMass,
            $this->adjustedFullMass,
            $this->massIncrease,
            $this->getMassIncreasePercent(),
            $this->massRatio,
            $this->cargoMultiplier
        );
    }
}
