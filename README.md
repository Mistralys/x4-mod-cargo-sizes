# X4 Cargo Sizes Mod

A mod for X4 that provides options to increase the cargo size of
transport and mining ships.

## Mix and match cargo sizes

The mod's files are organized into ZIP files by cargo size multiplier,
and ship type. This allows you to mix it any way you want.
For example, transport ships with 10x cargo size, and mining ships with 
2x cargo size.

### Limiting to ship sizes

It is possible to apply the cargo size changes to only specific 
ship sizes:

1. Install the mod as usual, with Vortex or manually.
2. Open the mod's staging folder
3. Remove the folders you don't want to use.

For example, the `cargo-size-transport-10x` ZIP file contains the
following folders:

- `cargo-size-10x-transport-l` - L-sized ships
- `cargo-size-10x-transport-m` - M-sized ships
- `cargo-size-10x-transport-s` - S-sized ships

Remove the folders you don't want to use.

## Limitations

The mod affects NPC and player ships. 

One slightly tricky side effect of increasing cargo sizes is that
by being able to transport more, ships actually transport more value
and will potentially be juicier targets for piracy as a result.

## Building from game sources

The mod is designed to be built directly from the game's data files,
to make sure it is always up to date with the latest game version.

1. Unpack the game's data files ([howto](https://github.com/Mistralys/x4-game-notes/blob/main/unpacking-game-files.md)).
2. Clone this repository.
3. Copy `dev-config.php.dist` to `dev-config.php`.
4. Edit the file to set the correct paths.
5. Run `composer install` to install the dependencies.
6. Run `composer build-mod` to build the mod.

### Customizing multiplier values

Edit the file `config/build-config.php` to set the desired multiplier 
values, then build the mod again.
