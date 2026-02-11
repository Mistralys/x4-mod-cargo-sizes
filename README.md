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

### How It Works - Tier-Based Physics

#### Why Tier-Based?

Ships vary wildly in cargo-to-mass ratios:
- **Combat ships**: Small cargo (100-2000) vs heavy hull (100-600 mass) → Low impact
- **Cargo ships**: Massive cargo (15,000-50,000) vs light hull (200-650 mass) → **Extreme impact**

Formula-based adjustments would make cargo ships undriveable (99% drag reduction). Tier-based system treats all ships with same cargo multiplier equally (predictable, safe, tunable).

#### Configuration

Adjustments organized into **tiers** by cargo multiplier:

```json
"dragReductionTiers": [
  { "maxMultiplier": 2.0, "reductionPercent": 0.10 },  // 2x cargo: 10% reduction
  { "maxMultiplier": 4.0, "reductionPercent": 0.30 },  // 4x cargo: 30% reduction
  { "maxMultiplier": 8.0, "reductionPercent": 0.50 },  // 8x cargo: 50% reduction
  { "maxMultiplier": 999, "reductionPercent": 0.70 }   // 10x+: 70% reduction (safety cap)
]
```

All ships with 4x cargo get **30% drag reduction** regardless of their mass ratio.

#### What Gets Adjusted

1. **Mass** - Directly increased by cargo difference
2. **Drag** (tier-based) - Reduced to compensate for fixed engine thrust
3. **Jerk** (tier-based) - Reduced for heavier feel
4. **Inertia** (dampened) - Increased proportionally to mass
5. **Acceleration** (scaled) - Maintains responsiveness despite mass increase

#### Physics Formulas

- **Drag reduction:** `newDrag = originalDrag × (1 - tierPercent)`
- **Jerk reduction:** `newJerk = originalJerk × (1 - tierPercent)`
- **Inertia increase:** `newInertia = originalInertia × (1 + (massRatio-1) × dampFactor)`
- **Accel scaling:** `newAccel = originalAccel × massRatio × responsiveness`

#### Tuning Your Experience

See [Physics Tuning Guide](docs/physics-tuning-guide.md) for:
- Detailed parameter explanations
- Common tuning scenarios (travel mode issues, too sluggish, etc.)
- Testing workflow
- Value ranges and safety limits

#### Travel Mode

Travel mode works by:
1. **Aggressive drag reduction** (70% for high-tier cargo) enables reaching speed
2. **Jerk reduction** (35% for high-tier) smooths acceleration ramp
3. **Acceleration scaling** maintains responsiveness

**Note:** Travel speed depends on engine thrust (player-chosen equipment). Ships with weak engines may need upgrades for high cargo multipliers.

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
