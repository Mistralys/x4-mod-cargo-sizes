# Research Report

## Problem Statement

Can the X4 Cargo Sizes Mod be made compatible with VRO (Variety and Rebalance Overhaul, Steam Workshop ID `ws_1696862840`)? A player suggested that simply adding a `<dependency>` tag to `content.xml` suffices; the mod author is skeptical because VRO extensively modifies ship statistics, and the Cargo Sizes Mod may overwrite VRO's values.

## Problem Decomposition

1. **How does the X4 mod diff system process overlapping patches from multiple mods?** (loading order, priority, replacement semantics)
2. **What does our mod actually modify?** (exact XML paths and attributes)
3. **What does VRO modify on ship macros?** (overlapping parameters)
4. **Does a `<dependency>` tag solve the ordering problem?**
5. **Does VRO add new ships or change storage macros that our mod won't cover?**
6. **What is the correct approach to true VRO compatibility?**

## Context & Constraints

- **X4 uses RFC 5261-based XML diff patching.** Mods place diff XML files in the same relative path as the game's original XML. The game engine applies `<replace>`, `<add>`, and `<remove>` operations using XPath `sel` selectors against the current state of the document.
- **Patches are applied sequentially** in extension loading order. After one mod's patch is applied, the next mod patches the *already-patched* document.
- **Loading order is determined by dependencies and priority.** The `content.xml` `priority` attribute (our mod uses `91`) and `<dependency>` tags influence loading order. A mod that depends on another is loaded *after* that dependency.
- **`<replace>` replaces the entire matched node.** When `sel` targets an element (not just an attribute), the entire element and all its attributes/children are replaced by the new content. When it targets a specific attribute (e.g., `/@max`), only that attribute value is replaced.
- **Our mod is a CLI build tool.** It reads vanilla X4 extracted XML data and generates diff patches based on vanilla values. It does **not** read other mods' data.

## What Our Mod Modifies (v3.0.0)

Per analysis of the built output files:

### Storage Override Files (cargo capacity)
```xml
<!-- Targets a single ATTRIBUTE on storage module macros -->
<replace sel="//macro[@name='storage_arg_l_trans_container_01_a_macro']/properties/cargo/@max">144000</replace>
```
- **XPath target:** `properties/cargo/@max` (attribute replacement)
- **Scope:** Only the `max` attribute on the `<cargo>` element in storage module macros
- **Risk with VRO:** LOW — VRO's focus is combat mechanics, not cargo capacity

### Flight Mechanics Override Files (acceleration)
```xml
<!-- Targets the entire <acceleration> ELEMENT on ship macros -->
<replace sel="//macro[@name='ship_arg_l_trans_container_01_a_macro']/properties/thruster/acceleration">
    <acceleration forward="2.97" reverse="2.97" horizontal="3.37" vertical="3.37" />
</replace>
```
- **XPath target:** `properties/thruster/acceleration` (element replacement — replaces the **entire** `<acceleration>` tag)
- **Scope:** All four acceleration attributes (forward, reverse, horizontal, vertical)
- **Risk with VRO:** **HIGH** — VRO rebalances ship physics including acceleration values

### What is NO LONGER modified (v3.0.0)
- ~~Drag values~~ — Removed
- ~~Jerk values~~ — Removed
- Only `acceleration` under `properties/thruster` is modified

## What VRO Modifies

Based on VRO's description ("changing parameters of every ship, weapon, missile, engine, shield") and its nature as a combat rebalance:

### Confirmed modifications:
- **Ship physics** (mass, drag, inertia, acceleration) — "ship classes and sizes separated from one another"
- **Engine/thruster parameters** — "Engines and thrusters" section on VRO wiki
- **Weapons, shields, missiles** — Core focus of VRO
- **Ship class roles** — e.g., frigates moved to L-size, new ship roles
- **New ships added** — VRO adds custom ships
- **Storage/cargo** — Possibly adjusted to balance economy

### Critical overlap:
VRO almost certainly modifies `properties/thruster/acceleration` and potentially `properties/cargo/@max` on the same ship macros that our mod targets. VRO may also change `properties/physics` (mass, drag, inertia) which affects the physics ratios our mod calculates.

## Prior Art & Known Patterns

### Pattern 1: Dependency Tag for Load Ordering
- **Description:** Adding `<dependency id="ws_1696862840" optional="false" name="Variety and Rebalance Overhaul"/>` to `content.xml` guarantees our mod loads **after** VRO. Our patches then apply to VRO's already-patched ship data.
- **Where used:** This is the standard X4 mechanism for ensuring mod load order. Many mods use this.
- **Strengths:** Trivially easy to implement; ensures our `<replace>` operations apply on top of VRO's values.
- **Weaknesses:** 
  - **Does NOT solve the fundamental problem.** Our mod calculates adjustments from **vanilla** data at build time, not from VRO's modified values. Even if our patches load after VRO, we are replacing VRO's carefully tuned acceleration values with values derived from vanilla baselines.
  - **`optional="false"` forces VRO requirement.** Non-VRO players cannot use the mod.
  - **Storage overrides use absolute values** (e.g., `144000`) calculated from vanilla cargo sizes, not VRO's potentially different cargo sizes.
- **Fit:** Necessary but far from sufficient. Only ensures ordering; does not fix the content problem.

### Pattern 2: Attribute-Level Patching (Surgical Replacement)
- **Description:** Instead of replacing the entire `<acceleration>` element, replace individual attributes only. This avoids overwriting attributes that VRO may have added or modified unexpectedly.
- **Where used:** Common in X4 modding for minimal-footprint changes.
- **Strengths:** Reduces surface area of conflict.
- **Weaknesses:** Still writes absolute values computed from vanilla; doesn't know what VRO set.
- **Fit:** Minor improvement but does not solve the core issue.

### Pattern 3: Separate VRO-Compatible Build Variant
- **Description:** Build two variants of the mod: one for vanilla, one for VRO. The VRO variant would extract ship data from VRO's modified XML (not vanilla) and calculate adjustments based on VRO's baselines.
- **Where used:** Several X4 mods publish separate "VRO-compatible" versions.
- **Strengths:** Correctly calculates physics adjustments based on VRO's actual values. No accidental overwriting.
- **Weaknesses:** Requires access to VRO's extracted data during build; doubles maintenance; must track VRO version updates.
- **Fit:** **Best technical solution** but highest effort. Requires VRO's mod files to be available as build input.

### Pattern 4: Relative/Ratio-Based Patching (Theoretical)
- **Description:** Instead of writing absolute acceleration values, apply a multiplier to whatever the current value is. This would work regardless of what VRO (or any mod) sets as the base.
- **Where used:** Not natively supported by X4's diff system. X4's `<replace>` only supports absolute values.
- **Strengths:** Would be universally compatible with any mod.
- **Weaknesses:** **Not possible** with the X4 diff/patch XML system. There is no `<multiply>` or script expression support in the diff framework. X4 uses RFC 5261 which only supports `<add>`, `<replace>`, and `<remove>` with literal values.
- **Fit:** Not implementable. Would require an MD (Mission Director) script, which fundamentally changes the mod's architecture.

### Pattern 5: Dependency + Accept Value Drift (Pragmatic)
- **Description:** Add VRO dependency (`optional="true"`), keep building from vanilla baselines, and accept that acceleration values will be overwritten. Document this trade-off. Since cargo changes are the mod's primary purpose and acceleration adjustments are secondary quality-of-life tweaks, this may be "good enough."
- **Where used:** Many small mods that overlap with VRO take this "best effort" approach.
- **Strengths:** Zero additional development. Players choose their priority (VRO physics vs cargo-adjusted physics). With `optional="true"`, works with or without VRO.
- **Weaknesses:** VRO users get bigger cargo but with physics that don't account for VRO's rebalanced values; the custom acceleration may feel off.
- **Fit:** Acceptable minimum. Worth documenting clearly.

## Alternative & Creative Approaches

### Approach A: Cargo-Only Mode (Disable Physics Adjustments for VRO)
- **Description:** Offer a build variant (or FOMOD option) that **only** modifies `properties/cargo/@max` and skips all flight mechanics overrides. VRO users get bigger cargo without any physics conflict.
- **Rationale:** Cargo capacity (`@max` attribute on storage modules) is a different XML path than anything VRO cares about for combat balance. VRO may not touch storage macros at all, or only marginally. By not touching acceleration, there is zero overlap with VRO's ship physics tuning.
- **Risk:** Ships with increased cargo will be heavier in practice (the game engine reduces acceleration naturally based on cargo weight), and there's no compensating acceleration adjustment. However, VRO already rebalances physics for different gameplay feel, so overlaying custom acceleration on top of VRO is arguably wrong anyway.
- **Implementation:** Add a build config option like `"skip-flight-mechanics": true` that produces cargo-only diffs.

### Approach B: Extract-from-VRO Build Pipeline
- **Description:** Extend the build tool to accept an alternative data source folder (VRO's modded XML output) in addition to vanilla. Build diff files based on VRO's values as the baseline.
- **Rationale:** VRO's ship data can be extracted from VRO's mod files (which contain diff XMLs). These diffs could be pre-applied to vanilla data to reconstruct VRO's version of each ship, then our mod's calculations would use those as baseline values.
- **Risk:** Complex to implement; requires resolving VRO's diffs against vanilla XML, essentially implementing a partial X4 mod loader. VRO version tracking needed.

### Approach C: MD Script for Runtime Cargo Adjustment
- **Description:** Instead of static XML diffs, use an X4 Mission Director (MD) script that runs at game start to dynamically multiply cargo values. The script would read current ship values (whatever VRO set) and apply multipliers at runtime.
- **Rationale:** Completely sidesteps the XML override conflict since no static XML files compete with VRO.
- **Risk:** Fundamental architecture change. Breaks the entire build-tool paradigm. MD scripts have performance implications and different maintenance requirements. Ships spawned after game start would need handling. This is essentially a different mod.

## Comparative Evaluation

| Criterion | Pattern 1: Dependency Tag | Pattern 3: VRO Build Variant | Pattern 5: Accept Drift | Approach A: Cargo-Only Mode |
|---|---|---|---|---|
| **Complexity** | Trivial | High | Trivial | Low |
| **Correctness** | Poor (vanilla values overwrite VRO) | Excellent | Poor for physics, OK for cargo | Excellent for cargo, N/A for physics |
| **VRO Compatibility** | False sense of compatibility | True compatibility | Partial | True compatibility |
| **Maintenance** | None | High (track VRO versions) | None | Low |
| **User Experience** | Confusing (unexpected physics) | Best | Acceptable with docs | Good (no physics conflict) |
| **Non-VRO Impact** | None (if optional) | None (separate variant) | None | Minor (no physics compensation) |
| **Time to implement** | Minutes | Days to weeks | Minutes | Hours |

## Recommendation

### Recommended approach: **Approach A (Cargo-Only Mode for VRO)** combined with **Pattern 5 (Dependency + Documentation)**

**Rationale:**

1. **Cargo-Only Mode** is the cleanest solution because:
   - The mod's **primary value** is bigger cargo holds. Acceleration adjustments are secondary.
   - Storage module macros (`properties/cargo/@max`) live in **different XML files** than what VRO primarily rebalances. VRO's combat focus targets ship macros for physics/weapons, not storage module capacity.
   - By not emitting `FlightMechanicsOverrideFile` diffs, there is **zero overlap** with VRO's ship physics.
   - VRO players already accept different flight characteristics; they don't expect vanilla physics.
   - Ships will naturally fly slower when fully loaded (the game engine handles mass vs thrust), which is acceptable behavior.

2. **Add VRO dependency as `optional="true"`** to `content.xml` for proper load ordering when both are present.

3. **Document the trade-off** clearly on Nexus/Steam: "When using with VRO, we recommend the cargo-only variant. The standard version includes acceleration adjustments tuned for vanilla physics that may conflict with VRO's rebalanced values."

### Why the player's suggestion is insufficient:

The player's suggestion (just adding `<dependency id="ws_1696862840">`) is **technically correct** about loading order but **misleading about compatibility**:
- It ensures our mod loads after VRO — correct.
- But our `<replace>` on `properties/thruster/acceleration` **completely overwrites** VRO's carefully tuned acceleration values with values calculated from vanilla baselines — this is a silent conflict.
- The mod will "work" (no errors, game loads fine), but VRO's ship balance is partially broken for affected ships.

### Proof-of-Concept Outline

1. Add a `"vro-mode"` or `"skip-flight-mechanics"` boolean option to `config/build-config.json`
2. When enabled, `CargoSizeExtractor` (or the build orchestrator) skips generation of `FlightMechanicsOverrideFile` for each ship
3. Only `StorageOverrideFile` (cargo `@max`) diffs are written
4. Add `<dependency id="ws_1696862840" optional="true" name="Variety and Rebalance Overhaul"/>` to the VRO variant's `content.xml`
5. Generate separate ZIP/FOMOD packages labeled "VRO Compatible"
6. Optionally, add VRO as a FOMOD installer choice: "Are you using VRO?" → cargo-only vs full

### Long-term improvement (Pattern 3):
If demand warrants it, a more sophisticated approach would be to support VRO's extracted data as an alternative input source. This would allow calculating correct acceleration adjustments based on VRO's values. This is architecturally possible (the `CargoSizeExtractor` already takes a data folder path) but requires:
- Access to resolved VRO XML (VRO's diffs applied to vanilla)
- Building a diff-resolution engine or using a pre-resolved data set
- Version tracking for VRO updates

This could be a future enhancement but is not recommended for the initial VRO compatibility implementation.

## Open Questions

- **Does VRO modify storage module macros?** If VRO changes `properties/cargo/@max` on storage modules, even the cargo-only approach would have a silent conflict. This needs verification by inspecting VRO's actual diff files (subscribe to VRO on Steam and examine its `/assets/props/storagemodules/` folder). Based on VRO's stated focus on combat mechanics, this is unlikely but unconfirmed.
- **Does VRO add new transport/miner/auxiliary ships with custom macros?** VRO adds some new ships. These would not be covered by our mod (our mod only targets vanilla + DLC ship macros). This is not a "conflict" but a coverage gap — VRO-exclusive ships won't get cargo increases.
- **What is VRO's priority value in its `content.xml`?** Our mod uses `priority="91"`. If VRO uses a higher priority, load ordering could be unexpected even with a dependency tag. Needs verification.
- **Steam Workshop vs Nexus distribution:** The dependency `id="ws_1696862840"` is VRO's Steam Workshop ID. Nexus/manual installs of VRO may use a different ID. Need to verify VRO's non-Steam extension ID.

## References

- [RFC 5261: XML Patch Operations Framework](https://datatracker.ietf.org/doc/html/rfc5261) — The specification X4 uses for its XML diff patching system
- [VRO Steam Workshop Page](https://steamcommunity.com/sharedfiles/filedetails/?id=1696862840) — VRO mod (Steam ID: `1696862840`)
- [VRO Wiki](https://sites.google.com/view/vrowiki/Home) — VRO documentation
- [Egosoft Modding Support Wiki](https://wiki.egosoft.com/X4%20Foundations%20Wiki/Modding%20Support/) — Official modding resources
- [X4 Modding Wiki (enenra)](https://github.com/enenra/x4modding/wiki) — Community modding wiki
- [XML Patch Guide (theqmann)](https://forum.egosoft.com/viewtopic.php?t=354310) — Community guide on X4 XML diff patching
