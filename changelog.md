# Cargo Sizes Mod - Changelog

## v3.0.0 for X4 v7.6 - PHP 8.4 Upgrade (2026-02-10)
- Core: Upgraded project to target PHP 8.4.
- Core: Implemented asymmetric visibility for core properties to reduce boilerplate.
- Core: Added explicit types to all public constants.
- Core: Refactored iteration logic using modern PHP 8.4 array functions (`array_find`, `array_filter`).
- Core: Adopted arrow functions and first-class callables for cleaner logic.
- Core: Marked legacy factor-based flight mechanics constants as deprecated.
- Documentation: Updated manifest and AGENTS documentation to reflect PHP 8.4 standards.

## v2.1.1 for X4 v7.6 - Fomod installer improvements
- Fomod: Added images for each ship type and cargo size multiplier.

## v2.1.0 for X4 v7.6 - Flight models
- Core: Now adjusting the flight models of ships to match their cargo sizes.
- Core: Added x6 and x8 cargo sizes.

## v2.0.0 for X4 v7.6 - Fomod installer
- Fomod: Added the Fomod installer to mix sizes and types.

## v1.1.0 for X4 v7.6 - Missing ships fix
- Ships: Fixed missing ships like the Boa, Raleigh and more.
- XML: Added the ship name to the generated XML.
- Documentation: Much simpler cargo values reference.
- Dependencies: Updated X4 Core to [v0.0.10](https://github.com/Mistralys/x4-core/releases/tag/0.0.10).

## v1.0.1 for X4 v7.6 - Added ship types
- Ship types: Added auxiliaries and carriers.

## v1.0.0 for X4 v7.6 - Initial Release
- Initial release.
