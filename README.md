# X4 Cargo Sizes Mod

A mod for X4 that provides options to increase the cargo size of
transports, mining ships, auxiliaries and carriers.

## Mix and match cargo sizes

The mod's files are organized into ZIP files by cargo size multiplier,
and ship type. This allows you to mix it any way you want.
For example, transport ships with 10x cargo size, and mining ships with 
2x cargo size.

## Downloads

### Nexusmods

The easiest way to install the mod is with the Vortex Mod Manager.
See the official [Nexus page for the mod](https://www.nexusmods.com/x4foundations/mods/1713).

### Manual ZIP downloads

See the [Releases](https://github.com/Mistralys/x4-mod-cargo-sizes/releases)
for all available manual downloads.

## Reference of cargo sizes

The exact changes to cargo sizes can be reviewed here:

[Reference of cargo sizes](./docs/cargo-size-reference.md)

## Features and limitations

- The mod affects NPC and player ships.
- Cargo values are increased for both new and existing ships.
- Can be installed and uninstalled at any time.
- After uninstalling, ships may carry more cargo than they
  normally would, but this has no adverse effects.
- One slightly tricky side effect of increasing cargo sizes is 
  that ships actually transport more value and are juicier 
  targets for piracy as a result.

## Compatibility

Because the mod changes flight characteristics of ships to compensate
for the added mass of cargo, it will conflict with any mods that also
change flight characteristics of ships.

**I recommend that you load this mod after conflicting mods** to make the
game use the flight characteristics of this mod instead. It is unlikely 
that any other mod that changes flight characteristics will work with the 
increased cargo sizes.

## Development

### Requirements

1. PHP 8.2 or higher.
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

The internal calculations of the mod automatically scale things like 
the ship's acceleration, inertia and steering curve. These calculations 
are based on a calculated mass multiplier value. 

Example: The Argon Shuyaku Vanguard L-sized transport, cargo size x4.

- Base ship mass: 650
- Base cargo size: 37,000
- Adjusted cargo size: **148,000** = 37,000 * 4 _(cargo size * multiplier)_
- Base full cargo mass: **37,650** = 650 + 37,000 _(mass + cargo)_
- Adjusted full cargo mass: **148,650** = 650 + 148,000 _(mass + adjusted cargo)_
- Mass multiplier: **0.25** = 37,650 / 148,650 _(base full cargo mass / adjusted full cargo mass)_

> NOTE: This assumes that one unit of cargo has a mass of 1. 

Most values can simply be adjusted using the mass multiplier. Some values
like the ship's inertia and steering curve require a lighter touch, however.
The mod solves this by calculating custom multipliers as fractions
of the mass multiplier (this way they scale along with the mass multiplier).

They can be adjusted in the configuration:

```json
{
  "flight-mechanics": {
    "dragReductionFactor": 0.20,
    "steeringIncreaseFactor": 0.24,
    "inertiaIncreaseFactor": 0.40
  }
}
```

Using the Shuyaku example again:

- Inertia multiplier: **0.101** = 0.40 * 0.25 _(inertia factor * mass multiplier)_
- Pitch: **362.940** = 329.329 + 33.365 (= 329.329 * 0.101) _(base pitch + (base pitch * inertia multiplier))_
- Drag multiplier: **0.05** = 0.20 * 0.25 _(drag factor * mass multiplier)_
- Drag: **161.467** = 170.083 - 8.616 (= 170.083 * 0.05) _(base drag - (base drag * drag multiplier))_

**So why those exact values?**

They are based on recommendations from X4 modding resources and actual physics
considerations, seeing that X4's flight model is quite realistic since the 7.5+
flight model update.

Still, the values are adjustable to be easily tweaked as needed.

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
