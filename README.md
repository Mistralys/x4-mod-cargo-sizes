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

### Customizing multiplier values

Edit the file `config/build-config.php` to set the desired multiplier 
values, then build the mod again.

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
