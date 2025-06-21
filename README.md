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


[report any mod conflicts]: https://github.com/Mistralys/x4-mod-cargo-sizes/issues
[Nexus page for the mod]: https://www.nexusmods.com/x4foundations/mods/1713
[Reference of cargo sizes]: ./docs/cargo-size-reference.md
[Releases]: https://github.com/Mistralys/x4-mod-cargo-sizes/releases
