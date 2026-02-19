<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs;

use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\PhysicsCalculator;

/**
 * Parameter object: holds all data needed to build a PhysicsResponse.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend - DTOs
 * @since 1.3.0
 */
readonly class PhysicsResponseData
{
    /**
     * @param PhysicsCalculator $calculator Physics calculator with mass calculations
     * @param PhysicsRequest $request Original request data
     * @param EnginePerformance|null $enginePerformance Engine performance metrics (optional)
     */
    public function __construct(
        public PhysicsCalculator $calculator,
        public PhysicsRequest $request,
        public ?EnginePerformance $enginePerformance
    ) {}
}
