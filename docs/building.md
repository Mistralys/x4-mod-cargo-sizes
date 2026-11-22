# Building the Mod

The mod is designed to be built directly from the game's data files,
to make sure it is always up to date with the latest game version.

## Requirements

1. PHP 8.4 or higher.
2. [Composer](https://getcomposer.org/).
3. The game's data files, unpacked with the [X4 Data Extractor][].

## Unpacking game data files

The mod requires the game's data files to be unpacked using the
[X4 Data Extractor][] tool. The tool acts as a library to access the
extracted information. This includes the DLC metadata necessary to
generate the correct mod file structure.

Please refer to the tool's instructions to unpack the game data files.

## Building the mod

1. Clone this repository.
2. Copy `dev-config.dist.php` to `dev-config.php`.
3. Edit the file to set the correct paths.
4. Run `composer install` to install the dependencies.
5. Run `composer build` to build the mod.

## Automatic release notes generation

The build process automatically generates release notes from the project changelogs:

- **Main Changelog:** `changelog.md` - Contains mod version changes
- **Builder Changelog:** `changelog-builder.md` - Contains build system changes

During each build, a `release-notes-v{VERSION}.md` file is generated in the `build/` directory containing:

- Release heading with version and title from the main changelog
- All changes from the latest mod version
- Builder section with build system changes (if builder changelog exists)
- **Cargo Multiplier Comparison** table - a randomly selected transport ship shown across all supported multipliers (e.g., 2x through 10x), with vanilla and adjusted cargo values side-by-side
- Installation instructions footer (AIO vs FOMOD)

The generated file is ready for use in GitHub Releases and mod platform updates.

## Customizing build settings

All configuration settings for the build process are located
in the `config/build-config.json` file.

### Cargo size multipliers

Multipliers can be added and removed from the `cargo-multipliers`
list. During the build process, the mod will automatically generate
all the listed multipliers.

> NOTE: Multiplier values can be floats, so you can choose to
> use a multiplier of `1.5` for example.

### Flight model settings

Because the amount of cargo a ship carries in its hold affects how
it flies, the mod will automatically adjust the flight model to
compensate for the increased cargo size.

## How It Works - Acceleration Scaling

### The Core Problem

When cargo capacity increases, the ship mass increases. Heavier ships accelerate more slowly - they feel like freight trains. The mod compensates for this by scaling the ship's acceleration factors proportionally to the mass increase.

### Configuration

The `flight-mechanics` section in `config/build-config.json` has a single tuning parameter:

```json
"flight-mechanics": {
  "accelerationResponsiveness": 1.0
}
```

### What Gets Adjusted

1. **Mass** - Directly increased by the added cargo weight
2. **Acceleration factors** - Scaled to maintain the original `AccelFactor/Mass` ratio

### Physics Formula

```
accelerationScalingFactor = massRatio × accelerationResponsiveness
newAccel = originalAccel × accelerationScalingFactor
```

- `massRatio` - how many times heavier the ship became (e.g. `2.0` for 2x cargo)
- `accelerationResponsiveness` - tuning multiplier (default `1.0` = vanilla feel)
- `1.0` = ship accelerates at the same rate as vanilla despite the extra mass
- Values below `1.0` make ships feel heavier; above `1.0` make them feel snappier

### Tuning Your Experience

See the [Physics Tuning Guide](physics-tuning-guide.md) for:

- Detailed parameter explanations
- Common tuning scenarios (too sluggish, AI flight issues, etc.)
- Testing workflow
- Value ranges

## Physics Tuning GUI

An interactive web-based GUI is available for tuning the physics
parameters in real time, using ships and engines from the extracted
game data. Tuned values can be saved to `config/build-config.json`.

```bash
# Install GUI dependencies (from project root)
composer gui:install

# Run the development server (Linux/Mac)
composer gui:start

# Or on Windows
composer gui:start-win
```

See the [GUI README](../gui/README.md) for setup, usage and troubleshooting.

## Checking that a refactor does not change the build output

Refactors that must not change the mod output can be verified with `composer build:baseline` (before the change) and `composer build:parity` (after a `composer build`). The workflow is documented for agents in [AGENTS.md](../AGENTS.md) under "Output-parity check for refactors".

[X4 Data Extractor]: https://github.com/Mistralys/x4-data-extractor
