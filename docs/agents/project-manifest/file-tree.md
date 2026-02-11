# Project File Tree

> **Version:** 1.1  
> **Last Updated:** February 11, 2026  
> **Purpose:** Complete directory structure for navigation and file location

---

## 📁 Root Directory Structure

```
x4-mod-cargo-sizes/
├── batch/                          # Windows batch scripts for game data extraction
├── build/                          # Built mod files (generated)
├── config/                         # Build configuration files
├── docs/                           # Documentation
│   └── agents/                     # AI agent documentation (this manifest)
├── output/                         # Build output (generated)
├── src/                            # Source code
│   ├── functions.php               # Global helper functions
│   └── Mods/                       # Mod namespace
│       └── CargoSizesMod/          # Main mod code
├── vendor/                         # Composer dependencies
├── changelog-builder.md            # Changelog building guide
├── changelog.md                    # Version history
├── composer.json                   # Composer configuration
├── dev-config.dist.php             # Development config template
├── dev-config.php                  # Development config (local, gitignored)
├── LICENSE                         # MIT License
├── mod-version.txt                 # Current mod version
└── README.md                       # Project documentation
```

---

## 📦 Source Code Structure (src/)

### Root Level
```
src/
├── functions.php                   # Global helper functions (dec(), calcIncrease(), etc.)
└── Mods/
    └── CargoSizesMod/              # Main namespace root
```

### Main Mod Directory (src/Mods/CargoSizesMod/)

```
CargoSizesMod/
├── Build/                          # Build system
│   ├── Plugins/                    # Build plugin system
│   │   ├── Plugin/                 # Concrete plugin implementations
│   │   │   └── BBCodeReferencePlugin.php
│   │   ├── BasePlugin.php          # Abstract plugin base
│   │   ├── PluginInterface.php     # Plugin contract
│   │   └── PluginLoader.php        # Plugin discovery/execution
│   ├── BuildConfig.php             # Build configuration loader
│   ├── CargoSizeBuildTools.php     # Main build orchestrator (Composer entry point)
│   ├── CargoSizeExtractor.php      # Game data extractor
│   ├── Console.php                 # Terminal output helper
│   ├── ContentXMLRenderer.php      # content.xml generator
│   ├── ShipResult.php              # Processed ship data container
│   └── Translation.php             # Translation key manager
│
├── FOMOD/                          # FOMOD installer generation
│   ├── FileCollection.php          # Groups files by type/size/multiplier
│   ├── FomodWriter.php             # Main FOMOD generator
│   └── StepPluginImage.php         # Installer image manager
│
├── Output/                         # Output file generation
│   ├── Jerk/                       # Jerk movement adjustments
│   │   ├── AdjustedJerk.php        # Main jerk adjustment
│   │   ├── AdjustedJerkBoost.php   # Boost mode jerk
│   │   ├── AdjustedJerkForward.php # Forward jerk
│   │   ├── AdjustedJerkTravel.php  # Travel mode jerk
│   │   └── JerkOverrideDef.php     # Jerk XML override definition
│   │
│   ├── Physics/                    # Physics value adjustments
│   │   ├── AdjustedAccelerationFactors.php
│   │   ├── AdjustedDrag.php        # Drag reduction
│   │   ├── AdjustedInertia.php     # Inertia increase
│   │   ├── AdjustedValuesInterface.php  # Adjustment contract
│   │   ├── AdjustedValuesTrait.php      # Shared adjustment behavior
│   │   └── PhysicsOverrideDef.php       # Physics XML override definition
│   │
│   ├── BaseOverrideFile.php        # Abstract override file base
│   ├── FlightMechanicsOverrideFile.php  # Flight mechanics override generator
│   ├── MassAdjustment.php          # Mass multiplier calculator
│   ├── OverrideDef.php             # Base XML override definition
│   ├── StorageOverrideFile.php     # Cargo storage override generator
│   └── TagOverrideDef.php          # Tag-based XML override definition
│
├── References/                     # Reference documentation generators
│   ├── BaseReferenceRenderer.php   # Abstract reference base
│   ├── BBCodeReference.php         # BBCode format (for forums)
│   ├── MarkdownReference.php       # Markdown format (for GitHub)
│   └── ReleaseNotesGenerator.php   # Auto-generates release notes from changelogs
│
├── XML/                            # XML file processing
│   ├── ShipXML/                    # Ship XML value objects
│   │   ├── AccelerationFactors.php # Acceleration data
│   │   ├── BaseJerkMovement.php    # Abstract jerk movement base
│   │   ├── Drag.php                # Drag values
│   │   ├── EmptyAccelerationFactors.php  # Null object pattern
│   │   ├── Inertia.php             # Inertia values (pitch, yaw, roll)
│   │   ├── Jerk.php                # Jerk movement data
│   │   ├── JerkBoost.php           # Boost mode jerk
│   │   ├── JerkForward.php         # Forward jerk
│   │   ├── JerkTravel.php          # Travel mode jerk
│   │   ├── SteeringCurve.php       # Steering curve collection
│   │   └── SteeringCurvePosition.php  # Single steering curve point
│   │
│   ├── BaseXMLFile.php             # Abstract XML file wrapper
│   ├── CargoXMLFile.php            # Storage module XML wrapper
│   └── ShipXMLFile.php             # Ship definition XML wrapper
│
├── CargoSizeException.php          # Custom exception class
└── ModInfo.php                     # Mod metadata (name, version, URLs)
```

---

## 📋 Configuration Files (config/)

```
config/
├── custom-builds/                  # Custom build configurations
│   └── irukandji.json              # Example custom build
├── build-config.json               # Main build configuration
└── translations.json               # Translation overrides
```

---

## 📖 Documentation (docs/)

```
docs/
├── agents/                         # AI agent documentation
│   └── project-manifest/           # THIS MANIFEST
│       ├── README.md               # Entry point and navigation
│       ├── constraints.md          # Coding rules and constraints
│       ├── data-flows.md           # System data flows
│       ├── file-tree.md            # THIS FILE
│       ├── public-api.md           # Public API signatures
│       └── tech-stack.md           # Tech stack and patterns
│
├── cargo-size-reference.md         # Generated cargo size reference
├── nexus-description.bbcode        # Generated Nexus mod description
└── nexus-description.bbcode.tpl    # Template for Nexus description
```

---

## 🔨 Build Output (build/ - Generated)

```
build/
└── vX-X-X-for-vX-X/                # Version-specific builds
    ├── cargo-size-all-2x-vX.X.X-for-vX.X.zip
    ├── cargo-size-all-4x-vX.X.X-for-vX.X.zip
    ├── cargo-size-all-8x-vX.X.X-for-vX.X.zip
    ├── cargo-size-all-10x-vX.X.X-for-vX.X.zip
    └── cargo-size-fomod-vX.X.X-for-vX.X.zip
```

---

## 📂 Output Directory (output/ - Generated)

```
output/
└── vX.X/                           # Game version
    ├── aux/                        # Auxiliary ships
    │   ├── xs/                     # Size subdivisions
    │   │   ├── 2x/                 # Multiplier subdivisions
    │   │   │   ├── flight-mechanics/
    │   │   │   │   └── [ship_macro].xml
    │   │   │   └── storage/
    │   │   │       └── [ship_macro].xml
    │   │   ├── 4x/
    │   │   └── ...
    │   ├── s/
    │   ├── m/
    │   ├── l/
    │   └── xl/
    │
    ├── carrier/                    # Carrier ships (same structure as aux)
    ├── miner/                      # Mining ships (same structure as aux)
    ├── trans/                      # Transport ships (same structure as aux)
    │
    ├── content-all-2x.xml          # All-in-one content.xml for 2x
    ├── content-all-4x.xml          # All-in-one content.xml for 4x
    ├── content-all-8x.xml          # All-in-one content.xml for 8x
    ├── content-all-10x.xml         # All-in-one content.xml for 10x
    │
    └── fomod/                      # FOMOD installer files
        ├── fomod/
        │   └── ModuleConfig.xml
        ├── images/                 # Installer images
        │   ├── carrier-l-4x.jpg
        │   ├── miner-m-2x.jpg
        │   └── ...
        └── extensions/             # Extension folders (grouped by selection)
            ├── cargo-size-carrier-l-4x/
            │   ├── content.xml
            │   └── assets/
            └── ...
```

---

## 🧪 Batch Scripts (batch/)

```
batch/
├── unpack-all.bat                  # Unpack all game data
├── unpack-ego_dlc_boron.bat        # Unpack Boron DLC
├── unpack-ego_dlc_mini_01.bat      # Unpack Mini DLC 1
├── unpack-ego_dlc_mini_02.bat      # Unpack Mini DLC 2
├── unpack-ego_dlc_pirate.bat       # Unpack Pirate DLC
├── unpack-ego_dlc_split.bat        # Unpack Split DLC
├── unpack-ego_dlc_terran.bat       # Unpack Terran DLC
├── unpack-ego_dlc_timelines.bat    # Unpack Timelines DLC
└── unpack-vanilla.bat              # Unpack vanilla game data
```

---

## 📦 Dependencies (vendor/ - Managed by Composer)

Key dependencies:
- **mistralys/x4-core** - X4 game data library
- **phpunit/phpunit** - Testing framework
- **phpstan/phpstan** - Static analysis

See [tech-stack.md](tech-stack.md) for complete dependency list.

---

## 🔍 Quick File Finder

### Need to...

**Add a new ship type?**
→ Modify `src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php`

**Change cargo multipliers?**
→ Edit `config/build-config.json`

**Adjust flight mechanics calculations?**
→ Edit `config/build-config.json` (factors)
→ See `src/Mods/CargoSizesMod/Output/Physics/*.php` (implementation)

**Change build behavior?**
→ See `src/Mods/CargoSizesMod/Build/CargoSizeBuildTools.php`

**Add translation?**
→ See `src/Mods/CargoSizesMod/Build/Translation.php`
→ Edit `config/translations.json`

**Add build plugin?**
→ Create class in `src/Mods/CargoSizesMod/Build/Plugins/Plugin/`
→ Implement `PluginInterface`

**Change FOMOD installer?**
→ See `src/Mods/CargoSizesMod/FOMOD/FomodWriter.php`

**Change XML override generation?**
→ See `src/Mods/CargoSizesMod/Output/OverrideDef.php` (base)
→ See `src/Mods/CargoSizesMod/Output/*OverrideDef.php` (specific types)

**Read ship XML data?**
→ See `src/Mods/CargoSizesMod/XML/ShipXMLFile.php`
→ See `src/Mods/CargoSizesMod/XML/ShipXML/*.php` (value objects)

**Read cargo XML data?**
→ See `src/Mods/CargoSizesMod/XML/CargoXMLFile.php`

---

## 📊 File Count Summary

| Category | Count | Notes |
|----------|-------|-------|
| **Source Files** | 49 | All PHP files in src/ |
| **Build System** | 8 | Main build orchestration |
| **XML Processing** | 14 | XML file wrappers and value objects |
| **Output Generation** | 13 | Override files and definitions |
| **References** | 3 | Documentation generators |
| **FOMOD** | 3 | Installer generation |
| **Plugins** | 4 | Build plugin system |
| **Config Files** | 3 | JSON configuration |
| **Batch Scripts** | 9 | Game data extraction |
| **Documentation** | 6+ | Markdown docs + this manifest |

**Total Source Classes:** 49 PHP files

---

## 🗂️ Namespace Organization

```
Mistralys\X4\                       # Global namespace
└── Mods\
    └── CargoSizesMod\              # Main mod namespace
        ├── Build\                  # Build system
        │   └── Plugins\            # Plugin system
        │       └── Plugin\         # Concrete plugins
        ├── FOMOD\                  # FOMOD generation
        ├── Output\                 # Output file generation
        │   ├── Jerk\               # Jerk adjustments
        │   └── Physics\            # Physics adjustments
        ├── References\             # Reference generators
        └── XML\                    # XML processing
            └── ShipXML\            # Ship XML value objects
```

---

## 📝 Special Files

### Version Control
- `.gitignore` - Excludes vendor/, build/, output/, dev-config.php

### Configuration
- `dev-config.dist.php` - Template for local development config
- `dev-config.php` - Local config (path to extracted game data, output folder)

### Metadata
- `mod-version.txt` - Single source of truth for mod version
- `LICENSE` - MIT License
- `composer.json` - Composer configuration and autoloading

### Build Artifacts (Generated)
- `build/` - Final distributable mod files (.zip)
- `output/` - Intermediate build output (XML files)
- `docs/cargo-size-reference.md` - Generated documentation
- `docs/nexus-description.bbcode` - Generated mod page content

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | Feb 9, 2026 | Initial file tree documentation |
