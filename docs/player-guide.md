# Player Guide

Things to know when playing with the X4 Cargo Sizes mod: compatibility
with other mods, how to uninstall it safely, and its known limitations.

Installing the mod does not affect savegame files, and it can be installed
at any time. Installing with the Vortex Mod Manager is recommended.

## Compatibility

Because the mod changes flight characteristics of ships to compensate
for the added mass of cargo, it will conflict with any mods that also
change flight characteristics of ships.

As it is unlikely that any other mod that changes flight characteristics
will work with the increased cargo sizes, I have set the mod to high priority.
This may not work in all cases, so please [report any mod conflicts][] you
may encounter.

### VRO

The mod has not been tested with VRO.

## Uninstalling

The mod can be uninstalled at any time. Ships keep their cargo even if it
exceeds their unmodified cargo capacity, so no cargo is lost. While this
causes no errors in the game, it can cause ships to become slow or
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

[report any mod conflicts]: https://github.com/Mistralys/x4-mod-cargo-sizes/issues
