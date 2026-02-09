# Tech Stack & Architectural Patterns

> **Version:** 1.0  
> **Last Updated:** February 9, 2026  
> **Purpose:** Defines runtime environment, dependencies, and architectural patterns

---

## Runtime Environment

### PHP Requirements
- **PHP Version:** 8.2 or higher
- **Required Extensions:**
  - `ext-dom` - XML manipulation

### Composer Dependencies

#### Production Dependencies
- **mistralys/x4-core** (dev-main) - Core library for X4 game data access (OOP interface to game data)
- **PHP 8.2+** - Modern PHP features (typed properties, constructor property promotion, etc.)
- **ext-dom** - DOM manipulation for XML files

#### Development Dependencies
- **phpunit/phpunit** (>=9.5.20) - Unit testing framework
- **phpstan/phpstan** (>=1.6.1) - Static analysis tool
- **roave/security-advisories** (dev-latest) - Security vulnerability database

### External Tools
- **X4 Data Extractor** - Required for building; extracts game files
- **X4 Foundations Game** - Source of data files

---

## Project Type

**Build Tool / Mod Generator**

This is NOT a web application or service. It's a **command-line build tool** that:
1. Reads extracted X4 game data files
2. Processes XML ship definitions
3. Calculates adjusted flight mechanics
4. Generates mod files (XML overrides) for the X4 game
5. Packages mod files into distributable formats (ZIP, FOMOD installer)

---

## Architectural Patterns

### 1. **Extractor-Builder Pattern**

The core workflow follows an extraction-and-building pattern:

```
Game Data (XML) → Extractor → Builder → Mod Output (XML)
```

**Key Classes:**
- `CargoSizeExtractor` - Reads game XML files
- `CargoSizeBuildTools` - Orchestrates the build process
- `ContentXMLRenderer` - Generates mod content.xml files

**Example Flow:**
```php
// Extract and process game data
$extractor = new CargoSizeExtractor($extractedFolder, $outputFolder);
$extractor->extract([2, 4, 8, 10]); // multipliers

// Results are automatically written to output folder
```

### 2. **XML File Representation Pattern**

XML files are wrapped in OOP classes for easy manipulation:

```
BaseXMLFile (abstract)
├── ShipXMLFile (ship definitions)
└── CargoXMLFile (storage definitions)
```

**Philosophy:** Each XML file type has a dedicated class that provides accessors for relevant data without exposing DOM details.

**Example:**
```php
$shipXML = new ShipXMLFile($fileInfo, $dataFolder);
$mass = $shipXML->getMass();
$drag = $shipXML->getDrag();
$inertia = $shipXML->getInertia();
```

### 3. **Override Definition Pattern**

Mod files are created using override definitions that represent changes to game values:

```
OverrideDef - Base override (macro, path, value)
├── TagOverrideDef - XML tag-based overrides
│   ├── PhysicsOverrideDef - Physics value overrides
│   └── JerkOverrideDef - Jerk movement overrides
└── ...
```

**Philosophy:** Each override knows how to render itself to XML format.

**Example:**
```php
$override = new OverrideDef('ship_argon_l_trans_container_01_a_macro');
$override->setPath('macros/ship_argon_l_trans_container_01_a_macro');
$override->setInt(148000);
$override->setComment('Adjusted cargo: %d → %d', 37000, 148000);
echo $override->render(); // Generates <replace> XML
```

### 4. **Adjusted Values Pattern**

Flight mechanics adjustments use a decorator-like pattern:

```
Original Value → Adjusted Value (with metadata)
```

All adjusted values implement `AdjustedValuesInterface`:
- `getMultiplier()` - Adjustment multiplier used
- `isIncrease()` - Whether it's an increase or decrease
- `getPrecision()` - Decimal precision for rendering
- `getComments()` - Explanatory comments for XML

**Example:**
```php
$originalDrag = $shipXML->getDrag();
$adjustedDrag = new AdjustedDrag($originalDrag, $reductionMultiplier);

// AdjustedDrag knows it's a decrease
if ($adjustedDrag->isIncrease()) {
    // Won't execute - drag is reduced
}
```

### 5. **Ship Result Aggregation Pattern**

Each processed ship generates a `ShipResult` that aggregates:
- Ship XML file
- Cargo XML file
- Calculated values
- Metadata (type, size, label)

**Purpose:** Single object containing all information needed to generate override files.

```php
$result = new ShipResult($label, $type, $shipXML, $cargoXML);
$result->getCargoValue(); // Original
$result->calculateCargoValue(4); // Adjusted by multiplier
```

### 6. **Static Factory Pattern**

Builder and configuration classes use static factories:

```php
CargoSizeBuildTools::build(); // Entry point from composer.json
CargoSizeBuildTools::getConfig(); // Static config accessor
```

### 7. **Output File Generation Pattern**

Two types of override files:
- **StorageOverrideFile** - Cargo capacity changes
- **FlightMechanicsOverrideFile** - Physics adjustments

Both extend `BaseOverrideFile` and know how to:
- Generate their XML content
- Calculate their ZIP path
- Render comments and metadata

### 8. **Translation System**

Multi-language support via `Translation` class:
- Defines translation keys (name, description for each ship type)
- Loads from x4-core's `TranslationDefs`
- Supports placeholder substitution

**Example:**
```php
$translation = new Translation(Translation::TYPE_NAME_TRANSPORT, ['multiplier' => 4]);
$english = $translation->getByLanguageID(44); // English
$german = $translation->getByLanguageID(49);  // German
```

### 9. **FOMOD Installer Generation**

FOMOD (Fallout Mod Manager format) installer generation:
- `FomodWriter` - Main FOMOD generator
- `FileCollection` - Groups files by ship type/size/multiplier
- `StepPluginImage` - Manages installer images

**Purpose:** Allows users to selectively install specific ship types and multipliers.

### 10. **Build Plugin System**

Extensible plugin system for build-time generators:
- `PluginInterface` - Marker interface
- `BasePlugin` - Base implementation
- `PluginLoader` - Discovers and executes plugins
- `BBCodeReferencePlugin` - Generates reference documentation

**Philosophy:** Plugins can hook into the build process to generate additional content.

### 11. **Console Output Helper**

`Console` class provides consistent terminal output:
```php
Console::header('Building mod files...');
Console::line1('Processing ships: %d', $count);
Console::line2('  - Ship: %s', $name);
Console::nl();
```

---

## Architectural Constraints

### Synchronous File I/O
All file operations are synchronous. No async/await patterns are used.

### XML-Centric
Everything revolves around reading and writing XML files. No database, no web API.

### Single-Execution Model
Build runs once and exits. Not a long-running process or daemon.

### Dependency on X4 Core
Heavy reliance on `mistralys/x4-core` for:
- Translation definitions
- Data folder structures
- XML utilities
- File helper utilities (from `apputils` dependency)

---

## Data Storage

### Input Data
- **Extracted Game Files** - XML files from X4 game (via X4 Data Extractor)
  - Ship definitions: `assets/units/size_*/macros/*.xml`
  - Storage modules: `assets/props/StorageModules/macros/*.xml`

### Output Data
- **Mod Files** - XML override files organized by:
  - Ship type (transport, miner, auxiliary, carrier)
  - Ship size (xs, s, m, l, xl)
  - Cargo multiplier (2x, 4x, 8x, 10x, etc.)

### Configuration Files
- **build-config.json** - Build configuration
  - Cargo multipliers to generate
  - Flight mechanics adjustment factors
- **translations.json** - Translation overrides
- **Custom build configs** - Per-user build configurations (config/custom-builds/)

---

## Build Process

### Entry Point
```json
// composer.json
{
  "scripts": {
    "build": "Mistralys\\X4\\Mods\\CargoSizesMod\\CargoSizeBuildTools::build"
  }
}
```

### Build Steps
1. **Load Configuration** - `BuildConfig` reads build-config.json
2. **Initialize Extractor** - `CargoSizeExtractor` with input/output folders
3. **Extract Ships** - Process all ship types and sizes
4. **Calculate Adjustments** - Physics calculations for each multiplier
5. **Generate Override Files** - Create XML files for mod
6. **Generate FOMOD Installer** - Create installer package
7. **Generate Reference Docs** - Markdown and BBCode cargo size references
8. **Package Distribution** - Create ZIP files

---

## Physics Calculations

The mod adjusts ship flight mechanics to compensate for increased cargo mass.

### Core Concept: Mass Multiplier
```
mass_multiplier = original_full_mass / adjusted_full_mass
```

Where:
- `original_full_mass = ship_mass + original_cargo_capacity`
- `adjusted_full_mass = ship_mass + (original_cargo_capacity * cargo_multiplier)`

### Applied Adjustments

1. **Drag Reduction** - Ships need less drag with more mass
   ```
   drag_multiplier = mass_multiplier * config.dragReductionFactor
   new_drag = original_drag - (original_drag * drag_multiplier)
   ```

2. **Inertia Increase** - More mass = more rotational inertia
   ```
   inertia_multiplier = mass_multiplier * config.inertiaIncreaseFactor
   new_inertia = original_inertia + (original_inertia * inertia_multiplier)
   ```

3. **Steering Adjustment** - Compensate for increased inertia
   ```
   steering_multiplier = mass_multiplier * config.steeringIncreaseFactor
   ```

4. **Acceleration Factors** - Adjusted by mass multiplier

### Configuration Factors (from build-config.json)
- `dragReductionFactor: 0.20` - 20% of mass multiplier
- `steeringIncreaseFactor: 0.24` - 24% of mass multiplier
- `inertiaIncreaseFactor: 0.40` - 40% of mass multiplier

**Philosophy:** These factors scale the adjustments based on the mass multiplier, ensuring physics changes are proportional to cargo increase.

---

## Code Style & Conventions

### Naming Conventions
- **Classes:** PascalCase (e.g., `CargoSizeExtractor`)
- **Methods:** camelCase (e.g., `getCargoValue()`)
- **Constants:** SCREAMING_SNAKE_CASE (e.g., `SHIP_TYPE_TRANSPORT`)
- **Variables:** camelCase (e.g., `$multiplier`)

### Type Declarations
- All method parameters have type declarations
- All return types are declared
- Strict types enabled: `declare(strict_types=1);`

### Documentation
- DocBlocks for all public methods
- Type hints in PHPDoc when arrays contain specific types
- `@package` and `@subpackage` annotations

### Exception Handling
- Custom exception: `CargoSizeException`
- Error codes defined as constants (e.g., `ERROR_UNHANDLED_SHIP_TYPE = 178001`)

---

## Key Design Decisions

### Why PHP?
- X4 Core library is PHP-based
- Strong XML manipulation with ext-dom
- Excellent file system utilities
- Composer for dependency management

### Why No Database?
- All data comes from extracted XML files
- Output is XML files (no need to store state)
- Build process is stateless

### Why Static Factories?
- Build tools need to be invoked from Composer scripts
- Configuration needs to be accessible globally
- No application state to maintain

### Why Separate Adjusted Value Classes?
- Clear separation between original and modified values
- Each adjustment knows its metadata (multiplier, precision, comments)
- Enables automatic XML comment generation

---

## Testing Strategy

### Unit Tests
- PHPUnit for testing
- Test coverage for calculation logic
- Mock game data files for testing

### Static Analysis
- PHPStan for type safety
- Level 5+ analysis (strict checking)

### Manual Testing
- Build mod files
- Load in X4 game
- Verify cargo values and flight mechanics

---

## Distribution Formats

### ZIP Files
- **All-In-One ZIPs** - All ship types with single multiplier
- **FOMOD Installer** - Custom selection of ship types and multipliers

### File Organization
```
mod-name-vX.X.X-for-vX.X/
├── content.xml (mod metadata)
└── extensions/
    └── mod-prefix-shiptype-size-Nx/
        ├── content.xml
        └── assets/
            ├── props/
            └── units/
```

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | Feb 9, 2026 | Initial tech stack documentation |
