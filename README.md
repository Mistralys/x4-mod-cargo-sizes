# X4 Cargo Sizes Mod

Bigger holds for X4: Foundations ships, without wrecking how they fly.

**LAST VERIFIED: November 2026** | X4 Foundations 9.0, Empire Update.

## Haul more, fly the same

This mod for X4: Foundations increases the cargo size of transports, mining ships, auxiliaries and carriers, including DLC ships. Because extra cargo adds mass, the mod also adjusts ship flight characteristics so that loaded ships stay flyable. Pick a ready-made multiplier, or use the FOMOD installer to choose a multiplier per ship type and size.

## Features

- Choose from cargo multipliers of x2, x4, x6, x8 or x10.
- Mix and match with the FOMOD installer, for example L-sized transports at x10 and M-sized miners at x2.
- Preview the effect before choosing: each installer option shows a real example ship with its vanilla and adjusted cargo values.
- Applies to NPC and player ships, both new and existing.
- Flight characteristics are adjusted to compensate for the added cargo mass.
- Can be installed at any time, and does not affect savegame files.
- Can be uninstalled at any time.
- Browse the exact cargo values in the [cargo size reference](docs/cargo-size-reference.md).
- Build your own multipliers (floats such as `1.5` are accepted) and tune the physics with an included web GUI.

## Requirements

**To play:**

- X4: Foundations
- A mod manager is optional. The [Vortex Mod Manager](https://www.nexusmods.com/x4foundations/mods/1713) route is recommended.

**To build from source:**

- PHP 8.4 or higher
- [Composer](https://getcomposer.org/)
- Game data unpacked with the [X4 Data Extractor](https://github.com/Mistralys/x4-data-extractor)

## Quick Start

**Playing:** download the mod from the [Nexus Mods page](https://www.nexusmods.com/x4foundations/mods/1713) and install it with Vortex, or grab a ZIP from the [GitHub Releases](https://github.com/Mistralys/x4-mod-cargo-sizes/releases). Use the FOMOD ZIP to choose multipliers per ship type and size.

**Building from source:**

```bash
git clone git@github.com:Mistralys/x4-mod-cargo-sizes.git
cd x4-mod-cargo-sizes
cp dev-config.dist.php dev-config.php
composer install
composer build
```

Before `composer build`, edit `dev-config.php` and point it at your X4 game folder and the folder with the extracted game files. See the [building guide](docs/building.md) for the details.

## Learn More

| Resource | Description |
|----------|-------------|
| [Player guide](docs/player-guide.md) | Compatibility, uninstalling and known limitations. |
| [Cargo size reference](docs/cargo-size-reference.md) | The exact cargo values for every multiplier. |
| [Building guide](docs/building.md) | Build the mod from game data and customize build settings. |
| [Physics tuning guide](docs/physics-tuning-guide.md) | Tune how ships handle with extra cargo. |
| [Physics Tuning GUI](gui/README.md) | Setup and usage of the web-based tuning tool. |
| [Changelog](changelog.md) | What changed in each version. |
| [Report an issue](https://github.com/Mistralys/x4-mod-cargo-sizes/issues) | Report bugs and mod conflicts. |

## Related X4 projects

- [X4 Game Notes](https://github.com/Mistralys/x4-game-notes) - Howtos, tips and general information about X4.
- [X4 Core](https://github.com/Mistralys/x4-core) - Library to access X4 game data.
- [X4 Data Extractor](https://github.com/Mistralys/x4-data-extractor) - Tool and library to extract X4 game files.
- [X4 Savegame Parser](https://github.com/Mistralys/x4-savegame-parser) - Tool to parse X4 savegames.

## License

MIT, see [LICENSE](LICENSE).

## Like it? A tea is appreciated!

<a href="https://buymeacoffee.com/aeonoftime"><img src="docs/images/buy-me-a-tea.png" alt="Buy me a tea" width="200"></a>
