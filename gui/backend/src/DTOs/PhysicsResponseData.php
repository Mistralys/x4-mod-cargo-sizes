<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs;

use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\PhysicsCalculator;

/**
 * Parameter object for PhysicsService::buildPhysicsResponse() method.
 *
 * Encapsulates all data needed to construct a PhysicsResponse,
 * reducing method signature from 5 parameters to 1.
 *
 * Follows Parameter Object pattern to improve code readability
 * and make method signatures more maintainable.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend - DTOs
 * @since 1.3.0
 */
readonly class PhysicsResponseData
{
    /**
     * @param PhysicsCalculator $calculator Physics calculator with mass calculations
     * @param PhysicsData $physicsData Original and adjusted physics values (drag, inertia, jerk)
     * @param ReductionTiers $tiers Active reduction tiers for drag and jerk
     * @param PhysicsRequest $request Original request data
     * @param EnginePerformance|null $enginePerformance Engine performance metrics (optional)
     */
    public function __construct(
        public PhysicsCalculator $calculator,
        public PhysicsData $physicsData,
        public ReductionTiers $tiers,
        public PhysicsRequest $request,
        public ?EnginePerformance $enginePerformance
    ) {}
}
