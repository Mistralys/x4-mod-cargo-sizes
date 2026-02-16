<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs;

use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\Drag;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedDrag;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\Inertia;
use Mistralys\X4\Mods\CargoSizesMod\Output\Physics\AdjustedInertia;
use Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML\Jerk;
use Mistralys\X4\Mods\CargoSizesMod\Output\Jerk\AdjustedJerk;

/**
 * Encapsulates original and adjusted physics values for response building.
 *
 * Groups related physics data (drag, inertia, jerk) with their original
 * and adjusted values to reduce parameter count in response builders.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend
 * @since 1.3.0
 */
final readonly class PhysicsData
{
    /**
     * @param Drag $originalDrag Original drag values from ship definition
     * @param AdjustedDrag $adjustedDrag Adjusted drag values after cargo increase
     * @param Inertia $originalInertia Original inertia values from ship definition
     * @param AdjustedInertia $adjustedInertia Adjusted inertia values after cargo increase
     * @param Jerk $originalJerk Original jerk values from ship definition
     * @param AdjustedJerk $adjustedJerk Adjusted jerk values after cargo increase
     */
    public function __construct(
        public Drag $originalDrag,
        public AdjustedDrag $adjustedDrag,
        public Inertia $originalInertia,
        public AdjustedInertia $adjustedInertia,
        public Jerk $originalJerk,
        public AdjustedJerk $adjustedJerk
    ) {}
}
