# Data Flows & Interactions

> **Version:** 1.0  
> **Last Updated:** February 9, 2026  
> **Purpose:** Describes how data flows through the system from input to output

---

## 🎯 Overview

This is a **build tool**, not a web application. Data flows in a **single direction** from source to output:

```
Game Data (XML) → Extract → Calculate → Generate → Package → Distribution (ZIP)
```

There are no bidirectional flows, no database queries, no user interactions during runtime. The build runs once and exits.

---

## 📊 High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     COMPOSER BUILD COMMAND                   │
│                composer build (entry point)                  │
└────────────────────────┬────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────────┐
│             CargoSizeBuildTools::build()                     │
│  • Loads BuildConfig                                         │
│  • Initializes CargoSizeExtractor                            │
│  • Orchestrates entire build process                         │
└────────────────────────┬────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────────┐
│                    BUILD PROCESS                             │
│  1. Extract ship data from game files                        │
│  2. Calculate physics adjustments                            │
│  3. Generate XML override files                              │
│  4. Generate FOMOD installer                                 │
│  5. Generate reference documentation                         │
│  6. Package into distributable ZIPs                          │
└────────────────────────┬────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────────┐
│                  OUTPUT ARTIFACTS                            │
│  • build/ - ZIP files for distribution                       │
│  • output/ - Intermediate XML files                          │
│  • docs/ - Generated documentation                           │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔄 Complete Build Flow (Detailed)

### Step 1: Entry Point

```
composer build
    ↓
composer.json: "scripts" → "build"
    ↓
CargoSizeBuildTools::build() (static method)
```

**What happens:**
- Composer invokes the static `build()` method
- Build method initializes the entire build process

---

### Step 2: Configuration Loading

```
CargoSizeBuildTools::build()
    ↓
new BuildConfig()
    ↓
Read config/build-config.json
    ↓
Parse:
  • cargo-multipliers: [2, 4, 8, 10]
  • flight-mechanics:
      - dragReductionFactor: 0.20
      - steeringIncreaseFactor: 0.24
      - inertiaIncreaseFactor: 0.40
```

**Outputs:**
- `BuildConfig` instance with multipliers and factors
- Available globally via `CargoSizeBuildTools::getConfig()`

---

### Step 3: Extractor Initialization

```
CargoSizeBuildTools::build()
    ↓
new CargoSizeExtractor($extractedDataFolder, $outputFolder)
    ↓
Resolves paths:
  • Input: Extracted game data (from X4 Data Extractor)
  • Output: output/vX.X/ (build output directory)
```

**Key data:**
- `$extractedDataFolder` - Contains unpacked X4 XML files
- `$outputFolder` - Where mod files will be written

---

### Step 4: Ship Data Extraction

```
CargoSizeExtractor::extract($multipliers)
    ↓
For each SHIP_TYPE (transport, miner, auxiliary, carrier):
    ↓
    For each SHIP_SIZE (xs, s, m, l, xl):
        ↓
        Find ship XML files in:
          assets/units/size_<size>/macros/*.xml
        ↓
        For each ship XML file:
            ↓
            Parse ship XML → ShipXMLFile
            ↓
            Find cargo definition in ship XML
            ↓
            Locate cargo XML file:
              assets/props/StorageModules/macros/<cargo_macro>.xml
            ↓
            Parse cargo XML → CargoXMLFile
            ↓
            Create ShipResult:
              • Ship metadata (label, type, size)
              • ShipXMLFile (physics data)
              • CargoXMLFile (storage capacity)
```

**Flow diagram:**

```
Ship XML (ship macro)
    ↓
Parse with ShipXMLFile
    ↓ Extract:
    • Mass
    • Drag
    • Inertia
    • Jerk
    • Acceleration Factors
    • Steering Curve
    ↓
Find <connection> tag with "connection.storage"
    ↓
Extract cargo macro reference
    ↓
Locate Cargo XML file
    ↓
Parse with CargoXMLFile
    ↓ Extract:
    • Cargo capacity (storage amount)
    • Cargo type (liquid, solid, container)
    ↓
Create ShipResult
```

**Output:** Array of `ShipResult` objects containing all ship data

---

### Step 5: Physics Calculations

For each `ShipResult` and each `multiplier`:

```
ShipResult
    ↓
Extract original values:
  • mass = ShipXMLFile::getMass()
  • cargo = CargoXMLFile::getCargoValue()
  • drag = ShipXMLFile::getDrag()
  • inertia = ShipXMLFile::getInertia()
  • accelerationFactors = ShipXMLFile::getAccelerationFactors()
  • jerk = ShipXMLFile::getJerk()
    ↓
Calculate adjusted cargo:
  adjustedCargo = cargo * multiplier
    ↓
Calculate mass adjustment:
  new MassAdjustment(mass, cargo, adjustedCargo)
    ↓
MassAdjustment calculates mass multiplier:
  originalFullMass = mass + cargo
  adjustedFullMass = mass + adjustedCargo
  massMultiplier = originalFullMass / adjustedFullMass
    ↓
Calculate physics adjustments using massMultiplier:
  
  • AdjustedDrag:
      dragMultiplier = massMultiplier * config.dragReductionFactor
      newDrag = originalDrag * (1 - dragMultiplier)
  
  • AdjustedInertia:
      inertiaMultiplier = massMultiplier * config.inertiaIncreaseFactor
      newInertia = originalInertia * (1 + inertiaMultiplier)
  
  • AdjustedAccelerationFactors:
      newAccel = originalAccel * massMultiplier
  
  • AdjustedJerk:
      newJerk = originalJerk * (1 + (massMultiplier * steeringFactor))
```

**Key concept:** All adjustments are proportional to the `massMultiplier`, which represents how much heavier the ship becomes with full cargo.

---

### Step 6: Override File Generation

For each ship and multiplier, generate two XML override files:

#### Storage Override

```
new StorageOverrideFile($outputFolder, $multiplier, $shipResult)
    ↓
Create OverrideDef:
  • Macro: cargo_macro_name
  • Path: macros/cargo_macro_name
  • Value: adjustedCargo (integer)
  • Comment: "Adjusted: 37000 → 148000"
    ↓
Render to XML:
  <replace sel="..." value="148000">
    <!-- Adjusted: 37000 → 148000 -->
  </replace>
    ↓
Write to file:
  output/v7.6/trans/l/4x/storage/ship_macro.xml
```

#### Flight Mechanics Override

```
new FlightMechanicsOverrideFile($outputFolder, $multiplier, $shipResult)
    ↓
Create PhysicsOverrideDef:
  • Macro: ship_macro_name
  • Mass: adjustedFullMass
  • AdjustedInertia (pitch, yaw, roll)
  • AdjustedDrag (forward, reverse, horizontal, vertical, pitch, yaw, roll)
  • AdjustedAccelerationFactors
    ↓
Create JerkOverrideDef:
  • AdjustedJerk (strafe, angular)
  • AdjustedJerkForward
  • AdjustedJerkBoost
  • AdjustedJerkTravel
    ↓
Render all overrides to XML with comments
    ↓
Write to file:
  output/v7.6/trans/l/4x/flight-mechanics/ship_macro.xml
```

**File organization:**

```
output/vX.X/
  <ship-type>/         # trans, miner, aux, carrier
    <size>/            # xs, s, m, l, xl
      <multiplier>/    # 2x, 4x, 8x, 10x
        storage/
          ship_macro_1.xml
          ship_macro_2.xml
          ...
        flight-mechanics/
          ship_macro_1.xml
          ship_macro_2.xml
          ...
```

---

### Step 7: Content.xml Generation

For each multiplier configuration:

```
ContentXMLRenderer
    ↓
Create content.xml metadata:
  • Mod ID: cargo-size-all-4x
  • Mod name: Translation (multi-language)
  • Mod description: Translation (multi-language)
  • Data folders: All DLC folders that have ship files
    ↓
Render XML:
  <?xml version="1.0" encoding="utf-8"?>
  <content>
    <id>cargo-size-all-4x</id>
    <name>{Translation}</name>
    <description>{Translation}</description>
    <author>AeonsOfTime</author>
    ...
  </content>
    ↓
Write to file:
  output/vX.X/content-all-4x.xml
```

**Purpose:** Tells X4 game about the mod's metadata and which folders to load.

---

### Step 8: FOMOD Installer Generation

```
FomodWriter::write()
    ↓
Group files by ship type and size:
  FileCollection::create('trans', 'l', 4)
    ↓
For each ship type (Transport, Miner, Auxiliary, Carrier):
    ↓
    Create installer step with options:
      • None (default)
      • XS ships
      • S ships
      • M ships
      • L ships
      • XL ships
      • All sizes
    ↓
    For each size option, create plugins for multipliers:
      • 2x cargo
      • 4x cargo
      • 8x cargo
      • 10x cargo
    ↓
Generate ModuleConfig.xml with:
  • Installation steps (one per ship type)
  • Plugin options (size + multiplier combinations)
  • File patterns (which files to install for each selection)
  • Dependencies (conditional installation)
    ↓
Generate content.xml for each combination:
  extensions/cargo-size-trans-l-4x/content.xml
    ↓
Copy XML files to FOMOD structure:
  output/vX.X/fomod/extensions/cargo-size-trans-l-4x/
    ├── content.xml
    └── assets/
        ├── props/StorageModules/macros/
        └── units/size_l/macros/
    ↓
Generate installer images:
  StepPluginImage::render()
    ↓
Write to:
  output/vX.X/fomod/images/trans-l-4x.jpg
```

**Result:** Complete FOMOD installer package that allows users to selectively install specific ship types and multipliers.

---

### Step 9: Reference Documentation Generation

```
Build Plugins System
    ↓
PluginLoader discovers plugins:
  • BBCodeReferencePlugin
    ↓
Execute each plugin:
    ↓
BBCodeReferencePlugin::run()
    ↓
Create BBCodeReference with ship results
    ↓
Generate reference table:

Ship Name | Type | Size | Original Cargo | 2x | 4x | 8x | 10x
----------|------|------|----------------|----|----|----|----|
Shuyaku   | Trans| L    | 37,000         | 74k| 148k| 296k| 370k
...
    ↓
Write to:
  docs/cargo-size-reference.md
  docs/nexus-description.bbcode
```

**Purpose:** Provides human-readable documentation of all cargo size changes.

---

### Step 10: ZIP Packaging

```
For each multiplier:
    ↓
Create ZIP file:
  build/vX-X-X-for-vX-X/cargo-size-all-4x-vX.X.X-for-vX.X.zip
    ↓
Add to ZIP:
  • content.xml (from output/vX.X/content-all-4x.xml)
  • extensions/
      └── cargo-size-all-4x/
          ├── content.xml
          └── assets/
              ├── props/StorageModules/macros/*.xml
              └── units/size_*/macros/*.xml
```

**FOMOD ZIP:**

```
Create FOMOD ZIP:
  build/vX-X-X-for-vX-X/cargo-size-fomod-vX.X.X-for-vX.X.zip
    ↓
Add to ZIP:
  • fomod/ModuleConfig.xml
  • fomod/images/*.jpg
  • content.xml (main)
  • extensions/ (all combinations)
      ├── cargo-size-trans-l-4x/
      ├── cargo-size-miner-m-2x/
      └── ...
```

---

## 🔍 Key Data Transformations

### 1. XML → Value Objects

```
XML File
    ↓ (read with ext-dom)
DOMDocument
    ↓ (parse with ShipXMLFile or CargoXMLFile)
Value Objects:
  • Drag (7 float values)
  • Inertia (3 float values)
  • Jerk (complex nested structure)
  • AccelerationFactors (4 float values)
```

**Purpose:** Provides type-safe, OOP access to XML data.

---

### 2. Original → Adjusted Values

```
Original Value Object (e.g., Drag)
    ↓ + MassMultiplier + Config Factor
AdjustedDrag (extends Drag, implements AdjustedValuesInterface)
    ↓ Includes:
    • New calculated values
    • Multiplier used
    • Whether it's an increase or decrease
    • Precision for rendering
    • Comments explaining the adjustment
```

**Purpose:** Encapsulates both the adjusted value AND the metadata about the adjustment.

---

### 3. Adjusted Values → XML Overrides

```
AdjustedDrag
    ↓
TagOverrideDef or PhysicsOverrideDef
    ↓ Includes:
    • XPath selector
    • XML tag name and attributes
    • New value
    • XML comments
    ↓
Render to XML string:
<replace sel="...[@id='ship_macro']/physics/drag" value="161.467">
  <!-- Drag reduction: 170.083 → 161.467 (0.05 multiplier) -->
  <!-- Original: 170.083 -->
  <!-- Adjusted: 161.467 -->
</replace>
```

**Purpose:** Generates well-documented XML that X4 game engine can process.

---

## 📦 Data Dependencies

### Input Dependencies

```
1. Extracted Game Data (XML files)
   ↓ Provided by X4 Data Extractor
   
2. build-config.json
   ↓ Defines multipliers and factors
   
3. translations.json
   ↓ Translation overrides
   
4. X4 Core library
   ↓ Provides:
   • TranslationDefs (multi-language support)
   • DataFolders (DLC structure)
   • File utilities
```

### Output Dependencies

```
1. X4 Game Engine
   ↓ Consumes:
   • content.xml (mod metadata)
   • XML override files (diff files)
   
2. FOMOD-compatible Mod Managers
   ↓ Consumes:
   • ModuleConfig.xml (installer definition)
   
3. End Users
   ↓ Consumes:
   • ZIP files (manual installation)
   • Reference documentation (information)
```

---

## 🔄 Translation Flow

```
Translation Key (e.g., "name-transport")
    ↓
new Translation(Translation::TYPE_NAME_TRANSPORT, ['multiplier' => 4])
    ↓
Look up in TranslationDefs (from X4 Core)
    ↓
Load from config/translations.json overrides
    ↓
Substitute placeholders:
  "Cargo size x{multiplier} for transports" → "Cargo size x4 for transports"
    ↓
Get by language:
  • English (ID 44): "Cargo size x4 for transports"
  • German (ID 49): "Frachtgröße x4 für Transporter"
  • French (ID 33): "Taille cargo x4 pour transporteurs"
  • etc.
    ↓
Embed in content.xml:
<name>
  <en>Cargo size x4 for transports</en>
  <de>Frachtgröße x4 für Transporter</de>
  <fr>Taille cargo x4 pour transporteurs</fr>
  ...
</name>
```

**Supported Languages:** 7 (English, German, French, Spanish, Italian, Russian, Korean)

---

## 🚫 What This System Does NOT Do

### No Runtime State
- No long-running processes
- No daemon or server
- Runs once and exits

### No Bidirectional Communication
- Build output is never read back
- No feedback loop from game to build tool

### No Database
- All data from XML files
- No persistent storage layer
- No queries or transactions

### No User Interface
- Command-line only (via Composer)
- Console output for progress
- No GUI, no web interface

### No Network Calls
- All data is local
- No API requests
- No remote resources

### No Async Operations
- All file I/O is synchronous
- Sequential processing
- Blocking operations

---

## ⚙️ Build Configuration Impact

Changes to `build-config.json` affect the entire flow:

### Change: Add Multiplier (e.g., 16x)

```
build-config.json:
  "cargo-multipliers": [2, 4, 8, 10, 16]  ← Add 16
    ↓
BuildConfig::getMultipliers() returns [2, 4, 8, 10, 16]
    ↓
CargoSizeExtractor generates 16x variants for all ships
    ↓
Output files created:
  • output/vX.X/<type>/<size>/16x/...
  • build/.../cargo-size-all-16x-vX.X.X-for-vX.X.zip
    ↓
FOMOD installer includes 16x options
```

### Change: Adjust Flight Mechanics Factor

```
build-config.json:
  "dragReductionFactor": 0.30  ← Change from 0.20
    ↓
BuildConfig::getDragReductionFactor() returns 0.30
    ↓
AdjustedDrag calculations use new factor:
  dragMultiplier = massMultiplier * 0.30  ← Larger reduction
  newDrag = originalDrag * (1 - dragMultiplier)
    ↓
All flight mechanics files regenerated with new values
```

---

## 🎯 Critical Path Analysis

**Longest dependency chain:**

```
1. Extract Game Files (X4 Data Extractor) ← EXTERNAL
   ↓ 
2. composer build
   ↓
3. Load BuildConfig
   ↓
4. Initialize CargoSizeExtractor
   ↓
5. For each ship type/size/multiplier:
   ↓
   a. Parse ship XML
   ↓
   b. Parse cargo XML
   ↓
   c. Calculate physics adjustments
   ↓
   d. Generate storage override
   ↓
   e. Generate flight mechanics override
   ↓
6. Generate FOMOD installer
   ↓
7. Generate reference docs
   ↓
8. Package ZIPs
   ↓
9. COMPLETE
```

**Bottleneck:** Ship XML parsing (I/O intensive, hundreds of files)

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | Feb 9, 2026 | Initial data flows documentation |
