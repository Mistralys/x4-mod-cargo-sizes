<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs;

/**
 * Ship information for API responses.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend
 */
class ShipDetails
{
    /**
     * @param string $id Ship identifier
     * @param string $name Ship name
     * @param string $type Ship type (transport, mining, auxiliary, carrier)
     * @param string $size Ship size (s, m, l, xl)
     * @param float $mass Ship base mass
     * @param float $cargo Ship cargo capacity
     * @param array<string> $engines List of compatible engine IDs
     * @param int $engineCount Number of engine slots
     * @param string $cargoType Cargo type (container, liquid, solid, none)
     * @param array{forward: float, reverse: float, horizontal: float, vertical: float, pitch: float, yaw: float, roll: float} $dragOriginal Real drag values from ship
     * @param array{pitch: float, yaw: float, roll: float} $inertiaOriginal Real inertia values from ship
     * @param array{strafe: float, angular: float, forwardAccel: float, forwardDecel: float, forwardRatio: float, boostAccel: float, boostRatio: float, travelAccel: float, travelDecel: float, travelRatio: float} $jerkOriginal Real jerk values from ship
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $type,
        public string $size,
        public float $mass,
        public float $cargo,
        public array $engines = [],
        public readonly int $engineCount = 0,
        public readonly string $cargoType = 'none',
        public readonly array $dragOriginal = ['forward' => 0.0, 'reverse' => 0.0, 'horizontal' => 0.0, 'vertical' => 0.0, 'pitch' => 0.0, 'yaw' => 0.0, 'roll' => 0.0],
        public readonly array $inertiaOriginal = ['pitch' => 0.0, 'yaw' => 0.0, 'roll' => 0.0],
        public readonly array $jerkOriginal = ['strafe' => 0.0, 'angular' => 0.0, 'forwardAccel' => 0.0, 'forwardDecel' => 0.0, 'forwardRatio' => 0.0, 'boostAccel' => 0.0, 'boostRatio' => 0.0, 'travelAccel' => 0.0, 'travelDecel' => 0.0, 'travelRatio' => 0.0]
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'size' => $this->size,
            'mass' => $this->mass,
            'cargo' => $this->cargo,
            'engines' => $this->engines,
            'engineCount' => $this->engineCount,
            'cargoType' => $this->cargoType,
            'dragOriginal' => $this->dragOriginal,
            'inertiaOriginal' => $this->inertiaOriginal,
            'jerkOriginal' => $this->jerkOriginal
        ];
    }
}