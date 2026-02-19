# X4 Cargo Sizes Mod

A mod for X4 that provides options to increase the cargo size of
transports, mining ships, auxiliaries and carriers.

## Mix and match cargo sizes

The mod's files are organized into ZIP files by cargo size multiplier
and change the cargo size of all ship types by default.

A separate FOMOD installer ZIP lets you choose cargo increases by ship
type and size. For example, L-sized transport ships with 10x cargo size, 
and M-sized mining ships with 2x cargo size.

## Downloads

### Nexusmods

The easiest way to install the mod is with the Vortex Mod Manager.
See the official [Nexus page for the mod][].

### Manual ZIP downloads

See the [Releases][]
for all available manual downloads.

## Features

- Automatic flight model adjustments to compensate for increased cargo sizes.
- Affects NPC and player ships.
- Cargo values are increased for both new and existing ships.
- Can be installed and uninstalled at any time.
- **Interactive Physics Tuning GUI** - Real-time visual tool for testing and configuring physics adjustments.
- _Haul away, me mateys!_

## Reference of cargo sizes

The exact changes to cargo sizes can be reviewed here:

[Reference of cargo sizes][]

## Compatibility

Because the mod changes flight characteristics of ships to compensate
for the added mass of cargo, it will conflict with any mods that also
change flight characteristics of ships.

As it is unlikely that any other mod that changes flight characteristics
will work with the increased cargo sizes, I have set the mod to high priority.
This may not work in all cases, so please [report any mod conflicts][] you
may encounter. 

## Uninstalling

The mod can be uninstalled at any time. However, ships may end up
carrying way more cargo than their unmodified storage allows. While
this causes no errors in the game, it can cause ships to become
unresponsive as their engines cannot move that much mass.

There are several ways you can deal with this:

- Drop the excess cargo from the ship's hold.
- Drop off the cargo before uninstalling the mod.

## Limitations

### Ship flight handling changes

To compensate for the increased cargo sizes, the mod
adjusts the flight characteristics of ships. Ideally, they should
perform roughly the same way as before. However, due to how
physics works, changes in flight behavior are unavoidable, especially
for larger ships and higher cargo multipliers.

### Increased piracy (unverified)

Theoretically, a tricky side effect of increasing cargo sizes is
that ships actually transport more value and are juicier targets for 
piracy as a result.

In my games, I have not been able to verify this. It seems to me that
the piracy happens just as often as before. On the contrary, in the few
piracy cases I was able to observe, my traders only dropped a fraction
of their cargo. 

> In one case, the pirates did not even bother to pick up the loot:
> I was able to send my trader to pick up the dropped cargo again
> when the pirates left.

### Mining delays (unverified)

Theoretically, miners should take a lot longer to fill their cargo
holds, as the mod does not increase the mining yields. 

In my games, however, that did not seem to be the case. The miners working 
for my metal refinery, for example, will not wait until the hold is full to 
drop off their ore. I think this is because the station manager recalls them 
whenever raw resources are needed.

## Development

### Physics Tuning GUI

An interactive web-based GUI is available for real-time physics tuning and visualization. The GUI allows you to:

- **Adjust physics parameters** (acceleration responsiveness) and see results instantly
- **Select specific ships and engines** from extracted game data
- **Compare original vs. adjusted values** with visual color-coded changes
- **Save configurations** directly to `build-config.json` for use with `composer build`
- **Test different cargo multipliers** with real ship/engine combinations

**Quick Start:**

```bash
# Install GUI dependencies (from project root)
composer gui:install

# Run the development server (Linux/Mac)
composer gui:start

# Or on Windows
composer gui:start-win
```

The GUI will open automatically at `http://localhost:5173` with the backend API on port 8080.

**Documentation:**
- [GUI README](gui/README.md) - Complete setup, usage, and troubleshooting guide
- [API Documentation](gui/docs/API.md) - REST API endpoint specifications
- [Architecture Overview](gui/docs/ARCHITECTURE.md) - System design and data flows
- [Development Guide](gui/docs/DEVELOPMENT.md) - Contributing and code standards

### Requirements

1. PHP 8.4 or higher.
2. [Composer](https://getcomposer.org/).

### Building from game sources

The mod is designed to be built directly from the game's data files,
to make sure it is always up to date with the latest game version.

### Unpacking game data files

The mod requires the game's data files to be unpacked using the
[X4 Data Extractor][] tool. The tool acts as a library to access the 
extracted information. This includes the DLC metadata necessary to
generate the correct mod file structure.

Please refer to the tool's instructions to unpack the game data files.

### Building the mod

1. Clone this repository.
2. Copy `dev-config.php.dist` to `dev-config.php`.
3. Edit the file to set the correct paths.
4. Run `composer install` to install the dependencies.
5. Run `composer build-mod` to build the mod.

### Automatic Release Notes Generation

The build process automatically generates release notes from the project changelogs:

- **Main Changelog:** `changelog.md` - Contains mod version changes
- **Builder Changelog:** `changelog-builder.md` - Contains build system changes

During each build, a `release-notes-v{VERSION}.md` file is generated in the `build/` directory containing:
- Release heading with version and title from the main changelog
- All changes from the latest mod version
- Builder section with build system changes (if builder changelog exists)
- Installation instructions footer (AIO vs FOMOD)

The generated file is ready for use in GitHub Releases and mod platform updates.

### Customizing build settings

All configuration settings for the build process are located
in the `config/build-config.json` file.

#### Cargo size multipliers

Multipliers can be added and removed from the `cargo-multipliers`
list. During the build process, the mod will automatically generate
all the listed multipliers.

> NOTE: Multiplier values can be floats, so you can choose to
> use a multiplier of `1.5` for example.

#### Flight model settings

Because the amount of cargo a ship carries in its hold affects how
it flies, the mod will automatically adjust the flight model to 
compensate for the increased cargo size.

### How It Works - Acceleration Scaling

#### The Core Problem

When cargo capacity increases, the ship mass increases. Heavier ships accelerate more slowly — they feel like freight trains. The mod compensates for this by scaling the ship's acceleration factors proportionally to the mass increase.

#### Configuration

The flight mechanics section in `config/build-config.json` has a single tuning parameter:

```json
"flight-mechanics": {
  "accelerationResponsiveness": 1.0
}
```

#### What Gets Adjusted

1. **Mass** - Directly increased by the added cargo weight
2. **Acceleration factors** - Scaled to maintain the original `AccelFactor/Mass` ratio

#### Physics Formula

```
accelerationScalingFactor = massRatio × accelerationResponsiveness
newAccel = originalAccel × accelerationScalingFactor
```

- `massRatio` — how many times heavier the ship became (e.g. `2.0` for 2x cargo)
- `accelerationResponsiveness` — tuning multiplier (default `1.0` = vanilla feel)
- `1.0` = ship accelerates at the same rate as vanilla despite the extra mass
- Values below `1.0` make ships feel heavier; above `1.0` make them feel snappier

#### Tuning Your Experience

See [Physics Tuning Guide](docs/physics-tuning-guide.md) for:
- Detailed parameter explanations
- Common tuning scenarios (too sluggish, AI flight issues, etc.)
- Testing workflow
- Value ranges

## X4 Tools and libraries

- [X4 Game Notes][] - _Docs_ - Howto, tips and general information about X4.
- [X4 Core][] - _Library_ - Access X4 game data in an OOP way.
- [X4 Data Extractor][] - _Tool & Library_ - Extract X4 game files.
- [X4 Savegame Parser][] - _Tool_ - Parse X4 savegames to extract information.
- [X4 Cargo Size Mod][] - _Mod_ - Mod to increase ship cargo sizes.

[X4 Data Extractor]: https://github.com/Mistralys/x4-data-extractor
[X4 Game Notes]: https://github.com/Mistralys/x4-game-notes
[X4 Core]: https://github.com/Mistralys/x4-core
[X4 Savegame Parser]: https://github.com/Mistralys/x4-savegame-parser
[X4 Cargo Size Mod]: https://github.com/Mistralys/x4-mod-cargo-sizes


[report any mod conflicts]: https://github.com/Mistralys/x4-mod-cargo-sizes/issues
[Nexus page for the mod]: https://www.nexusmods.com/x4foundations/mods/1713
[Reference of cargo sizes]: ./docs/cargo-size-reference.md
[Releases]: https://github.com/Mistralys/x4-mod-cargo-sizes/releases
