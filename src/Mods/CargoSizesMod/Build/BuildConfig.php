<?php
/**
 * @package Build Tools
 * @subpackage Configuration
 */

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\Build;

use AppUtils\ArrayDataCollection;
use AppUtils\FileHelper\JSONFile;
use Mistralys\X4\Mods\CargoSizesMod\CargoSizeException;

/**
 * Build configuration manager for cargo size mod.
 *
 * Supports both legacy factor-based configuration and new tier-based configuration.
 *
 * @package Build Tools
 * @subpackage Configuration
 */
class BuildConfig
{
    // Legacy keys
    #[\Deprecated]
    public const string KEY_DRAG_REDUCTION_FACTOR = 'dragReductionFactor';
    public const string KEY_STEERING_INCREASE_FACTOR = 'steeringIncreaseFactor';
    #[\Deprecated]
    public const string KEY_INERTIA_INCREASE_FACTOR = 'inertiaIncreaseFactor';
    
    // New tier-based keys
    public const string KEY_DRAG_REDUCTION_TIERS = 'dragReductionTiers';
    public const string KEY_JERK_REDUCTION_TIERS = 'jerkReductionTiers';
    public const string KEY_INERTIA_IMPACT_FACTOR = 'inertiaImpactFactor';
    public const string KEY_USE_EFFECTIVE_RATIO_CAP = 'useEffectiveRatioCap';
    public const string KEY_ACCELERATION_RESPONSIVENESS = 'accelerationResponsiveness';
    
    public const string KEY_MULTIPLIERS = 'cargo-multipliers';
    public const string KEY_FLIGHT_MECHANICS = 'flight-mechanics';
    
    /**
     * @var float[]
     */
    public private(set) array $multipliers = array();

    /**
     * @var array<string,int|float|bool>
     */
    public private(set) array $flightMechanics = array(
        self::KEY_DRAG_REDUCTION_FACTOR => 0.0,
        self::KEY_STEERING_INCREASE_FACTOR => 0.0,
        self::KEY_INERTIA_INCREASE_FACTOR => 0.0,
        self::KEY_INERTIA_IMPACT_FACTOR => 0.5,
        self::KEY_USE_EFFECTIVE_RATIO_CAP => true,
        self::KEY_ACCELERATION_RESPONSIVENESS => 1.0
    );

    /**
     * @var ReductionTier[]
     */
    public private(set) array $dragReductionTiers = [];

    /**
     * @var ReductionTier[]
     */
    public private(set) array $jerkReductionTiers = [];

    /**
     * @throws CargoSizeException
     */
    public function __construct()
    {
        $config = ArrayDataCollection::create(JSONFile::factory(__DIR__.'/../../../../config/build-config.json')->parse());

        // Load cargo multipliers
        foreach($config->getArray(self::KEY_MULTIPLIERS) as $value) {
            if(is_numeric($value)) {
                $this->multipliers[] = (float)$value;
            }
        }

        // Load flight mechanics configuration
        $flightMechanics = $config->getArray(self::KEY_FLIGHT_MECHANICS);
        
        // Load legacy factors
        foreach($flightMechanics as $key => $value) {
            if(is_string($key) && (is_numeric($value) || is_bool($value))) {
                $this->flightMechanics[$key] = $value;
            }
        }

        // Load tier-based configuration if available
        if (isset($flightMechanics[self::KEY_DRAG_REDUCTION_TIERS]) && is_array($flightMechanics[self::KEY_DRAG_REDUCTION_TIERS])) {
            $this->loadDragReductionTiers($flightMechanics[self::KEY_DRAG_REDUCTION_TIERS]);
        }

        if (isset($flightMechanics[self::KEY_JERK_REDUCTION_TIERS]) && is_array($flightMechanics[self::KEY_JERK_REDUCTION_TIERS])) {
            $this->loadJerkReductionTiers($flightMechanics[self::KEY_JERK_REDUCTION_TIERS]);
        }

        $this->validate();
    }

    /**
     * Loads drag reduction tiers from configuration array.
     *
     * @param array<int,array<string,mixed>> $tiersData
     * @throws CargoSizeException
     */
    private function loadDragReductionTiers(array $tiersData): void
    {
        foreach ($tiersData as $tierData) {
            $this->dragReductionTiers[] = ReductionTier::fromArray($tierData);
        }

        $this->validateTierArray($this->dragReductionTiers, 'drag reduction');
    }

    /**
     * Loads jerk reduction tiers from configuration array.
     *
     * @param array<int,array<string,mixed>> $tiersData
     * @throws CargoSizeException
     */
    private function loadJerkReductionTiers(array $tiersData): void
    {
        foreach ($tiersData as $tierData) {
            $this->jerkReductionTiers[] = ReductionTier::fromArray($tierData);
        }

        $this->validateTierArray($this->jerkReductionTiers, 'jerk reduction');
    }

    /**
     * Validates a tier array for consistency.
     *
     * @param ReductionTier[] $tiers
     * @param string $name
     * @throws CargoSizeException
     */
    private function validateTierArray(array $tiers, string $name): void
    {
        if (count($tiers) === 0) {
            throw new CargoSizeException(
                sprintf('At least one %s tier must be defined', $name),
                '',
                CargoSizeException::ERROR_UNHANDLED_SHIP_TYPE
            );
        }

        // Validate tiers are in ascending order
        $previousMax = 0.0;
        foreach ($tiers as $tier) {
            if ($tier->getMaxMultiplier() <= $previousMax) {
                throw new CargoSizeException(
                    sprintf(
                        '%s tiers must be in ascending order. Found %.1f after %.1f',
                        ucfirst($name),
                        $tier->getMaxMultiplier(),
                        $previousMax
                    ),
                    '',
                    CargoSizeException::ERROR_UNHANDLED_SHIP_TYPE
                );
            }
            $previousMax = $tier->getMaxMultiplier();
        }
    }

    /**
     * Validates configuration values.
     *
     * @throws CargoSizeException
     */
    private function validate(): void
    {
        $inertiaImpact = $this->getInertiaImpactFactor();
        if ($inertiaImpact < 0.0 || $inertiaImpact > 2.0) {
            throw new CargoSizeException(
                sprintf('Inertia impact factor must be between 0.0 and 2.0. Received: %f', $inertiaImpact),
                '',
                CargoSizeException::ERROR_UNHANDLED_SHIP_TYPE
            );
        }

        $accelResponsiveness = $this->getAccelerationResponsiveness();
        if ($accelResponsiveness < 0.1 || $accelResponsiveness > 5.0) {
            throw new CargoSizeException(
                sprintf('Acceleration responsiveness must be between 0.1 and 5.0. Received: %f', $accelResponsiveness),
                '',
                CargoSizeException::ERROR_UNHANDLED_SHIP_TYPE
            );
        }
    }

    /**
     * @return float[]
     */
    public function getMultipliers() : array
    {
        return $this->multipliers;
    }

    /**
     * Gets the drag reduction tiers.
     *
     * @return ReductionTier[]
     */
    public function getDragReductionTiers(): array
    {
        return $this->dragReductionTiers;
    }

    /**
     * Gets the jerk reduction tiers.
     *
     * @return ReductionTier[]
     */
    public function getJerkReductionTiers(): array
    {
        return $this->jerkReductionTiers;
    }

    /**
     * Checks if tier-based configuration is being used.
     *
     * @return bool True if tiers are defined
     */
    public function hasTierBasedConfiguration(): bool
    {
        return count($this->dragReductionTiers) > 0 && count($this->jerkReductionTiers) > 0;
    }

    /**
     * Finds the appropriate drag reduction tier for a cargo multiplier.
     *
     * @param float $multiplier Cargo multiplier (e.g., 4.0 for 4x cargo)
     * @return ReductionTier The first tier where maxMultiplier >= multiplier
     * @throws CargoSizeException If no tier applies (should never happen with proper config)
     */
    public function findDragTierForMultiplier(float $multiplier): ReductionTier
    {
        return $this->findTierForMultiplier($this->dragReductionTiers, $multiplier, 'drag');
    }

    /**
     * Finds the appropriate jerk reduction tier for a cargo multiplier.
     *
     * @param float $multiplier Cargo multiplier (e.g., 4.0 for 4x cargo)
     * @return ReductionTier The first tier where maxMultiplier >= multiplier
     * @throws CargoSizeException If no tier applies (should never happen with proper config)
     */
    public function findJerkTierForMultiplier(float $multiplier): ReductionTier
    {
        return $this->findTierForMultiplier($this->jerkReductionTiers, $multiplier, 'jerk');
    }

    /**
     * Generic tier lookup method.
     *
     * @param ReductionTier[] $tiers
     * @param float $multiplier
     * @param string $name
     * @return ReductionTier
     * @throws CargoSizeException
     */
    private function findTierForMultiplier(array $tiers, float $multiplier, string $name): ReductionTier
    {
        $tier = array_find($tiers, fn(ReductionTier $tier) => $tier->appliesToMultiplier($multiplier));

        if ($tier !== null) {
            return $tier;
        }

        throw new CargoSizeException(
            sprintf(
                'No %s tier found for cargo multiplier %.1fx. Check configuration.',
                $name,
                $multiplier
            ),
            '',
            CargoSizeException::ERROR_UNHANDLED_SHIP_TYPE
        );
    }

    /**
     * Gets the inertia impact factor (0.0-2.0).
     *
     * Controls how much mass increase affects inertia.
     * - 0.0 = no impact (inertia stays constant)
     * - 0.5 = dampened impact (recommended)
     * - 1.0 = full impact (inertia scales linearly with mass)
     * - 2.0 = amplified impact
     *
     * @return float Impact factor (default: 0.5)
     */
    public function getInertiaImpactFactor(): float
    {
        return (float)($this->flightMechanics[self::KEY_INERTIA_IMPACT_FACTOR] ?? 0.5);
    }

    /**
     * Gets whether to use effective ratio cap.
     *
     * When true, mass ratio is capped at cargo multiplier to prevent extreme physics
     * for ships with very high cargo-to-mass ratios.
     *
     * @return bool True to cap (default: true)
     */
    public function getUseEffectiveRatioCap(): bool
    {
        return (bool)($this->flightMechanics[self::KEY_USE_EFFECTIVE_RATIO_CAP] ?? true);
    }

    /**
     * Gets the acceleration responsiveness factor (0.1-5.0).
     *
     * Adjusts how acceleration factors scale with mass.
     * - < 1.0 = less responsive (more gradual scaling)
     * - 1.0 = physics-correct (recommended)
     * - > 1.0 = more responsive (aggressive scaling)
     *
     * @return float Responsiveness factor (default: 1.0)
     */
    public function getAccelerationResponsiveness(): float
    {
        return (float)($this->flightMechanics[self::KEY_ACCELERATION_RESPONSIVENESS] ?? 1.0);
    }

    // ===== LEGACY METHODS (for backwards compatibility) =====

    /**
     * @deprecated Use tier-based configuration instead
     */
    public function getDragReductionFactor() : float
    {
        return (float)$this->flightMechanics[self::KEY_DRAG_REDUCTION_FACTOR];
    }

    public function getSteeringIncreaseFactor() : float
    {
        return (float)($this->flightMechanics[self::KEY_STEERING_INCREASE_FACTOR] ?? 1.0);
    }

    /**
     * @deprecated Use tier-based configuration instead
     */
    public function getInertiaIncreaseFactor() : float
    {
        return (float)$this->flightMechanics[self::KEY_INERTIA_INCREASE_FACTOR];
    }
}

