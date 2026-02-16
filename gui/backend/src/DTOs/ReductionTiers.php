<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs;

use Mistralys\X4\Mods\CargoSizesMod\Build\ReductionTier;

/**
 * Encapsulates drag and jerk reduction tier configuration.
 *
 * Groups reduction tier values to reduce parameter count in response
 * builders and provide convenient tier-level operations.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend
 * @since 1.3.0
 */
final readonly class ReductionTiers
{
    /**
     * @param ReductionTier $drag Drag reduction tier configuration
     * @param ReductionTier $jerk Jerk reduction tier configuration
     */
    public function __construct(
        public ReductionTier $drag,
        public ReductionTier $jerk
    ) {}

    /**
     * Get formatted active tier label for display.
     *
     * Generates a human-readable label showing the reduction percentages
     * for both drag and jerk tiers (e.g., "Drag: 25% reduction | Jerk: 33% reduction").
     *
     * @return string Formatted tier label with reduction percentages
     */
    public function getActiveTierLabel(): string
    {
        return sprintf(
            'Drag: %.0f%% reduction | Jerk: %.0f%% reduction',
            $this->drag->getReductionPercent() * 100,
            $this->jerk->getReductionPercent() * 100
        );
    }
}
