<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\Build;

use AppUtils\ArrayDataCollection;
use AppUtils\FileHelper\JSONFile;

class BuildConfig
{
    public const KEY_DRAG_REDUCTION_FACTOR = 'dragReductionFactor';
    public const KEY_STEERING_INCREASE_FACTOR = 'steeringIncreaseFactor';
    public const KEY_INERTIA_INCREASE_FACTOR = 'inertiaIncreaseFactor';
    public const KEY_MULTIPLIERS = 'cargo-multipliers';
    public const KEY_FLIGHT_MECHANICS = 'flight-mechanics';
    
    /**
     * @var float[]
     */
    private array $multipliers = array();

    /**
     * @var array<string,int|float>
     */
    private array $flightMechanics = array(
        self::KEY_DRAG_REDUCTION_FACTOR => 0.0,
        self::KEY_STEERING_INCREASE_FACTOR => 0.0,
        self::KEY_INERTIA_INCREASE_FACTOR => 0.0
    );

    public function __construct()
    {
        $config = ArrayDataCollection::create(JSONFile::factory(__DIR__.'/../../../../config/build-config.json')->parse());

        foreach($config->getArray(self::KEY_MULTIPLIERS) as $value) {
            if(is_numeric($value)) {
                $this->multipliers[] = (float)$value;
            }
        }

        foreach($config->getArray(self::KEY_FLIGHT_MECHANICS) as $key => $value) {
            if(is_numeric($value) && is_string($key)) {
                $this->flightMechanics[$key] = (float)$value;
            }
        }
    }

    /**
     * @return float[]
     */
    public function getMultipliers() : array
    {
        return $this->multipliers;
    }

    public function getDragReductionFactor() : float
    {
        return (float)$this->flightMechanics[self::KEY_DRAG_REDUCTION_FACTOR];
    }

    public function getSteeringIncreaseFactor() : float
    {
        return (float)$this->flightMechanics[self::KEY_STEERING_INCREASE_FACTOR];
    }

    public function getInertiaIncreaseFactor() : float
    {
        return (float)$this->flightMechanics[self::KEY_INERTIA_INCREASE_FACTOR];
    }
}

