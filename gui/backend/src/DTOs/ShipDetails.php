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
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $type,
        public string $size,
        public float $mass,
        public float $cargo,
        public array $engines = []
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
            'engines' => $this->engines
        ];
    }
}