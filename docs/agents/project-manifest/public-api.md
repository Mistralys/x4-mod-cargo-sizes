# Public API Reference

> **Version:** 1.1
> **Last Updated:** February 10, 2026
> **Purpose:** Complete public API signatures (NO implementations)

---

## 🎯 How to Use This Document

This document lists **ONLY** public method signatures, constants, and properties. It does **NOT** include implementation details. Use this to:

- Find method signatures without reading source code
- Understand class contracts
- Discover available public APIs
- Reference method parameters and return types

For implementation details, read the source files located in `src/Mods/CargoSizesMod/`.

---

## 📚 Table of Contents

- [Global Functions](#global-functions)
- [Core Classes](#core-classes)
- [Build System](#build-system)
- [XML Processing](#xml-processing)
- [Output Generation](#output-generation)
- [FOMOD Installer](#fomod-installer)
- [Reference Generators](#reference-generators)

---

## Global Functions

**Namespace:** `Mistralys\X4`  
**File:** [src/functions.php](../../../src/functions.php)

### Helper Functions

```php
function dec(float|int $value, int $decimals): string
```

```php
function dec1(float|int $value): string
```

```php
function dec2(float|int $value): string
```

```php
function dec3(float|int $value): string
```

```php
function calcIncrease(float $value, float $multiplier): float
```

```php
function calcDecrease(float $value, float $multiplier): float
```

---

## Core Classes

### ModInfo

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod`  
**File:** [src/Mods/CargoSizesMod/ModInfo.php](../../../src/Mods/CargoSizesMod/ModInfo.php)

#### Constants

```php
const MOD_NAME = 'Extra Cargo Size for Ships';
const MOD_HOMEPAGE = 'https://github.com/Mistralys/x4-mod-cargo-sizes';
const MOD_NEXUSMODS = 'https://www.nexusmods.com/x4foundations/mods/1713';
const MOD_AUTHOR = 'AeonsOfTime';
const MOD_DESCRIPTION = 'Provides options to increase the cargo size of transports, miners, auxiliaries and carriers.';
```

#### Public Methods

```php
public static function getVersionFile(): FileInfo
```

```php
public static function getVersion(): string
```

---

### CargoSizeException

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod`  
**File:** [src/Mods/CargoSizesMod/CargoSizeException.php](../../../src/Mods/CargoSizesMod/CargoSizeException.php)  
**Extends:** `X4Exception`

#### Constants

```php
const ERROR_UNHANDLED_SHIP_TYPE = 178001;
const ERROR_MISSING_XML_TAG = 178002;
const ERROR_UNRECOGNIZED_SHIP_SIZE = 178003;
const ERROR_MISSING_RELATIVE_PATH = 178004;
const ERROR_MISSING_CHANGELOG = 178005;
const ERROR_CHANGELOG_PARSE = 178006;
const ERROR_FILE_WRITE = 178007;
```

---

## Build System

### CargoSizeBuildTools

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod`  
**File:** [src/Mods/CargoSizesMod/Build/CargoSizeBuildTools.php](../../../src/Mods/CargoSizesMod/Build/CargoSizeBuildTools.php)

#### Public Methods

```php
public static function build(): void
```

```php
public static function getConfig(): BuildConfig
```

---

### BuildConfig

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\Build`
**File:** [src/Mods/CargoSizesMod/Build/BuildConfig.php](../../../src/Mods/CargoSizesMod/Build/BuildConfig.php)

#### Constants

```php
public const string KEY_DRAG_REDUCTION_FACTOR = 'dragReductionFactor';
public const string KEY_STEERING_INCREASE_FACTOR = 'steeringIncreaseFactor';
public const string KEY_INERTIA_INCREASE_FACTOR = 'inertiaIncreaseFactor';
public const string KEY_DRAG_REDUCTION_TIERS = 'dragReductionTiers';
public const string KEY_JERK_REDUCTION_TIERS = 'jerkReductionTiers';
public const string KEY_INERTIA_IMPACT_FACTOR = 'inertiaImpactFactor';
public const string KEY_USE_EFFECTIVE_RATIO_CAP = 'useEffectiveRatioCap';
public const string KEY_ACCELERATION_RESPONSIVENESS = 'accelerationResponsiveness';
public const string KEY_MULTIPLIERS = 'cargo-multipliers';
public const string KEY_FLIGHT_MECHANICS = 'flight-mechanics';
```

#### Properties

```php
public private(set) array $multipliers = array(); // float[]
public private(set) array $flightMechanics = array(...);
public private(set) array $dragReductionTiers = []; // ReductionTier[]
public private(set) array $jerkReductionTiers = []; // ReductionTier[]
```

#### Public Methods

```php
public function __construct()
```

```php
public function getMultipliers(): array // Returns float[]
```

```php
public function getDragReductionFactor(): float // Legacy - still supported
```

```php
public function getSteeringIncreaseFactor(): float
```

```php
public function getInertiaIncreaseFactor(): float // Legacy - still supported
```

```php
public function getDragReductionTiers(): array // Returns ReductionTier[]
```

```php
public function getJerkReductionTiers(): array // Returns ReductionTier[]
```

```php
public function findDragTierForMultiplier(float $multiplier): ReductionTier
```

```php
public function findJerkTierForMultiplier(float $multiplier): ReductionTier
```

```php
public function getInertiaImpactFactor(): float // Default 0.5
```

```php
public function getUseEffectiveRatioCap(): bool // Default true
```

```php
public function getAccelerationResponsiveness(): float // Default 1.0
```

---

### ReductionTier

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\Build`  
**File:** [src/Mods/CargoSizesMod/Build/ReductionTier.php](../../../src/Mods/CargoSizesMod/Build/ReductionTier.php)

#### Public Methods

```php
public function __construct(float $maxMultiplier, float $reductionPercent)
```

```php
public function getMaxMultiplier(): float
```

```php
public function getReductionPercent(): float
```

```php
public function appliesToMultiplier(float $multiplier): bool
```

---

### CargoSizeExtractor

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\Build`  
**File:** [src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php](../../../src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php)

#### Constants

```php
public const string SHIP_TYPE_TRANSPORT = 'trans';
public const string SHIP_TYPE_STORAGE = 'storage';
public const string SHIP_TYPE_MINER = 'miner';
public const string SHIP_TYPE_AUXILIARY = 'resupplier';
public const string SHIP_TYPE_CARRIER = 'carrier';
public const string HOMEPAGE_URL = 'https://github.com/Mistralys/x4-mod-cargo-sizes';
public const string MOD_PREFIX = 'cargo-size';
public const string AUTHOR_NAME = 'AeonsOfTime';
public const string PROPS_FOLDER = 'assets/props/StorageModules/macros';
public const string SHIP_SIZES = array(...);
public const string SHIP_TYPES = array(...);
```

#### Public Methods

```php
public function __construct(FolderInfo $extractedDataFolder, FolderInfo $outputFolder)
```

```php
public static function getTranslations(): TranslationDefs
```

```php
public function extract(array $multipliers): void
```

```php
public static function getShipTypesPretty(): array // Returns string[]
```

---

### Console

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\Build`  
**File:** [src/Mods/CargoSizesMod/Build/Console.php](../../../src/Mods/CargoSizesMod/Build/Console.php)

#### Public Methods

```php
public static function header(string $message, ...$args): void
```

```php
public static function line1(string $message, ...$args): void
```

```php
public static function line2(string $message, ...$args): void
```

```php
public static function line(string $message, ...$args): void
```

```php
public static function nl(): void
```

---

### ContentXMLRenderer

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\Build`  
**File:** [src/Mods/CargoSizesMod/Build/ContentXMLRenderer.php](../../../src/Mods/CargoSizesMod/Build/ContentXMLRenderer.php)

#### Public Methods

```php
public function __construct(
    string $modID, 
    Translation $name, 
    Translation $description, 
    DataFolders $dataFolders
)
```

```php
public function render(): string
```

---

### ShipResult

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\Build`  
**File:** [src/Mods/CargoSizesMod/Build/ShipResult.php](../../../src/Mods/CargoSizesMod/Build/ShipResult.php)

#### Public Methods

```php
public function __construct(
    string $shipLabel, 
    string $shipType, 
    ShipXMLFile $xmlFile, 
    CargoXMLFile $cargoXMLFile
)
```

```php
public function getShipXMLFile(): ShipXMLFile
```

```php
public function getCargoXMLFile(): CargoXMLFile
```

```php
public function getDataFolder(): DataFolder
```

```php
public function getCargoFileName(): string
```

```php
public function getShipFileName(): string
```

```php
public function getCargoValue(): int
```

```php
public function getShipType(): string
```

```php
public function getCargoType(): string
```

```php
public function getSize(): string
```

```php
public function getShipLabel(): string
```

```php
public function getTypeLabel(): string
```

```php
public function calculateCargoValue(float|int $multiplier): int
```

---

### Translation

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\Build`  
**File:** [src/Mods/CargoSizesMod/Build/Translation.php](../../../src/Mods/CargoSizesMod/Build/Translation.php)

#### Constants

```php
public const string TYPE_DESCR_AIO = 'descr-aio';
public const string TYPE_DESCR_TRANSPORT = 'descr-transport';
public const string TYPE_DESCR_MINER = 'descr-miner';
public const string TYPE_NAME_AIO = 'name-aio';
public const string TYPE_NAME_TRANSPORT = 'name-transport';
public const string TYPE_NAME_MINER = 'name-miner';
public const string TYPE_NAME_AUXILIARY = 'name-auxiliary';
public const string TYPE_DESCR_AUXILIARY = 'descr-auxiliary';
public const string TYPE_NAME_CARRIER = 'name-carrier';
public const string TYPE_DESCR_CARRIER = 'descr-carrier';
public const string TYPE_NAME_FOMOD = 'name-fomod';
public const string TYPE_DESCR_FOMOD = 'descr-fomod';
```

#### Public Methods

```php
public function __construct(string $id, array $placeholders = array())
```

```php
public function getInvariant(): string
```

```php
public function getByLanguageID(int $id): string
```

---

### Build Plugin System

#### PluginInterface

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\Build\Plugins`  
**File:** [src/Mods/CargoSizesMod/Build/Plugins/PluginInterface.php](../../../src/Mods/CargoSizesMod/Build/Plugins/PluginInterface.php)

Empty marker interface.

---

#### BasePlugin

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\Build\Plugins`  
**File:** [src/Mods/CargoSizesMod/Build/Plugins/BasePlugin.php](../../../src/Mods/CargoSizesMod/Build/Plugins/BasePlugin.php)  
**Implements:** `PluginInterface`

Abstract base class with no public members.

---

#### PluginLoader

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\Build\Plugins`  
**File:** [src/Mods/CargoSizesMod/Build/Plugins/PluginLoader.php](../../../src/Mods/CargoSizesMod/Build/Plugins/PluginLoader.php)

(Public API not extracted - internal class)

---

## XML Processing

### BaseXMLFile

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\XML`  
**File:** [src/Mods/CargoSizesMod/XML/BaseXMLFile.php](../../../src/Mods/CargoSizesMod/XML/BaseXMLFile.php)

#### Public Methods

```php
public function __construct(FileInfo $xmlFile, DataFolder $dataFolder)
```

```php
public function getRelativePath(): string
```

```php
public function getXML(): string
```

```php
public function getDataFolder(): DataFolder
```

```php
public function getFileName(): string
```

```php
public function xmlContains(string $needle): bool
```

```php
public function getMacroName(): string
```

```php
public function getAliasName(): ?string
```

```php
public function getFirstByTagName(string $tagName): ?DOMElement
```

```php
public function requireFirstByTagName(string $tagName): DOMElement
```

```php
public function getAllByName(string $name): array // Returns DOMElement[]
```

---

### ShipXMLFile

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\XML`  
**File:** [src/Mods/CargoSizesMod/XML/ShipXMLFile.php](../../../src/Mods/CargoSizesMod/XML/ShipXMLFile.php)  
**Extends:** `BaseXMLFile`

#### Properties

```php
public private(set) ?string $size = null;
public private(set) ?AccelerationFactors $accelerationFactors = null;
public private(set) ?Drag $drag = null;
public private(set) ?Inertia $inertia = null;
public private(set) ?Jerk $jerk = null;
public private(set) ?SteeringCurve $steeringCurve = null;
```

#### Public Methods

```php
public function getSize(): string
```

```php
public function resolveShipLabel(): ?string
```

```php
public function getConnections(): array // Returns DOMElement[]
```

```php
public function getMass(): float
```

```php
public function getAccelerationFactors(): AccelerationFactors
```

```php
public function getDrag(): Drag
```

```php
public function getInertia(): Inertia
```

```php
public function getJerk(): Jerk
```

```php
public function getSteeringCurve(): SteeringCurve
```

---

### CargoXMLFile

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\XML`  
**File:** [src/Mods/CargoSizesMod/XML/CargoXMLFile.php](../../../src/Mods/CargoSizesMod/XML/CargoXMLFile.php)  
**Extends:** `BaseXMLFile`

#### Public Methods

```php
public function hasCargoValue(): bool
```

```php
public function isGenericStorage(): bool
```

```php
public function getCargoValue(): int
```

```php
public function getCargoType(): string
```

---

### Ship XML Value Objects

#### AccelerationFactors

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML`  
**File:** [src/Mods/CargoSizesMod/XML/ShipXML/AccelerationFactors.php](../../../src/Mods/CargoSizesMod/XML/ShipXML/AccelerationFactors.php)

```php
public function __construct(
    float $forward, 
    float $reverse, 
    float $horizontal, 
    float $vertical
)
```

```php
public function getForward(): float
```

```php
public function getReverse(): float
```

```php
public function getHorizontal(): float
```

```php
public function getVertical(): float
```

---

#### EmptyAccelerationFactors

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML`  
**File:** [src/Mods/CargoSizesMod/XML/ShipXML/EmptyAccelerationFactors.php](../../../src/Mods/CargoSizesMod/XML/ShipXML/EmptyAccelerationFactors.php)  
**Extends:** `AccelerationFactors`

```php
public function __construct()
```

---

#### Drag

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML`  
**File:** [src/Mods/CargoSizesMod/XML/ShipXML/Drag.php](../../../src/Mods/CargoSizesMod/XML/ShipXML/Drag.php)

```php
public function __construct(
    float $forward, 
    float $reverse, 
    float $horizontal, 
    float $vertical, 
    float $pitch, 
    float $yaw, 
    float $roll
)
```

```php
public function getForward(): float
```

```php
public function formatForward(): string
```

```php
public function getReverse(): float
```

```php
public function getHorizontal(): float
```

```php
public function getVertical(): float
```

```php
public function getPitch(): float
```

```php
public function getYaw(): float
```

```php
public function getRoll(): float
```

---

#### Inertia

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML`  
**File:** [src/Mods/CargoSizesMod/XML/ShipXML/Inertia.php](../../../src/Mods/CargoSizesMod/XML/ShipXML/Inertia.php)

```php
public function __construct(float $pitch, float $yaw, float $roll)
```

```php
public function getPitch(): float
```

```php
public function getYaw(): float
```

```php
public function getRoll(): float
```

---

#### Jerk

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML`  
**File:** [src/Mods/CargoSizesMod/XML/ShipXML/Jerk.php](../../../src/Mods/CargoSizesMod/XML/ShipXML/Jerk.php)

```php
public function __construct(
    float $strafe, 
    float $angular, 
    JerkForward $forward, 
    JerkBoost $boost, 
    JerkTravel $travel
)
```

```php
public function getStrafe(): float
```

```php
public function getAngular(): float
```

```php
public function getForward(): JerkForward
```

```php
public function getBoost(): JerkBoost
```

```php
public function getTravel(): JerkTravel
```

---

#### BaseJerkMovement

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML`  
**File:** [src/Mods/CargoSizesMod/XML/ShipXML/BaseJerkMovement.php](../../../src/Mods/CargoSizesMod/XML/ShipXML/BaseJerkMovement.php)

```php
public function __construct(float $acceleration, float $deceleration, float $ratio)
```

```php
public function getAcceleration(): float
```

```php
public function getDeceleration(): float
```

```php
public function getRatio(): float
```

```php
abstract public function getTagName(): string
```

---

#### JerkForward

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML`  
**File:** [src/Mods/CargoSizesMod/XML/ShipXML/JerkForward.php](../../../src/Mods/CargoSizesMod/XML/ShipXML/JerkForward.php)  
**Extends:** `BaseJerkMovement`

```php
public function getTagName(): string
```

---

#### JerkTravel

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML`  
**File:** [src/Mods/CargoSizesMod/XML/ShipXML/JerkTravel.php](../../../src/Mods/CargoSizesMod/XML/ShipXML/JerkTravel.php)  
**Extends:** `BaseJerkMovement`

```php
public function getTagName(): string
```

---

#### JerkBoost

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML`  
**File:** [src/Mods/CargoSizesMod/XML/ShipXML/JerkBoost.php](../../../src/Mods/CargoSizesMod/XML/ShipXML/JerkBoost.php)

```php
public function __construct(float $acceleration, float $ratio)
```

```php
public function getAcceleration(): float
```

```php
public function getRatio(): float
```

---

#### SteeringCurve

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML`  
**File:** [src/Mods/CargoSizesMod/XML/ShipXML/SteeringCurve.php](../../../src/Mods/CargoSizesMod/XML/ShipXML/SteeringCurve.php)

```php
public function addPosition(string $position, float $value): void
```

```php
public function getPositions(): array // Returns SteeringCurvePosition[]
```

---

#### SteeringCurvePosition

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML`  
**File:** [src/Mods/CargoSizesMod/XML/ShipXML/SteeringCurvePosition.php](../../../src/Mods/CargoSizesMod/XML/ShipXML/SteeringCurvePosition.php)

```php
public function __construct(string $position, float $value)
```

```php
public function getPosition(): string
```

```php
public function getValue(): float
```

---

## Output Generation

### BaseOverrideFile

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\Output`  
**File:** [src/Mods/CargoSizesMod/Output/BaseOverrideFile.php](../../../src/Mods/CargoSizesMod/Output/BaseOverrideFile.php)

#### Public Methods

```php
public function __construct(FolderInfo $baseFolder, int|float $multiplier, ShipResult $ship)
```

```php
public function getShipType(): string
```

```php
public function getShipSize(): string
```

```php
public function getName(): string
```

```php
public function getID(): string
```

```php
public function getMultiplier(): float|int
```

```php
public function getCargo(): int
```

```php
public function getAdjustedCargo(): int
```

```php
public function getSize(): string
```

```php
abstract public function getXMLFile(): BaseXMLFile
```

```php
public function getZipPath(string $rootRelative): string
```

```php
public function getShipName(): string
```

```php
public function getTypeLabel(): string
```

```php
public function render(): string
```

---

### StorageOverrideFile

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\Output`  
**File:** [src/Mods/CargoSizesMod/Output/StorageOverrideFile.php](../../../src/Mods/CargoSizesMod/Output/StorageOverrideFile.php)  
**Extends:** `BaseOverrideFile`

#### Public Methods

```php
public function getXMLFile(): BaseXMLFile
```

---

### FlightMechanicsOverrideFile

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\Output`  
**File:** [src/Mods/CargoSizesMod/Output/FlightMechanicsOverrideFile.php](../../../src/Mods/CargoSizesMod/Output/FlightMechanicsOverrideFile.php)  
**Extends:** `BaseOverrideFile`

#### Public Methods

```php
public function getName(): string
```

```php
public function getXMLFile(): BaseXMLFile
```

---

### MassAdjustment

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\Output`  
**File:** [src/Mods/CargoSizesMod/Output/MassAdjustment.php](../../../src/Mods/CargoSizesMod/Output/MassAdjustment.php)

#### Properties

```php
public private(set) float $mass;
public private(set) int $cargo;
public private(set) int $adjustedCargo;
```

#### Public Methods

```php
public function __construct(float $mass, int $cargo, int $adjustedCargo)
```

```php
public function getMass(): float
```

```php
public function getMultiplier(): float // Legacy - Returns < 1.0 (DEPRECATED)
```

```php
public function getMassRatio(): float // Returns massRatio > 1.0 (physics-correct)
```

```php
public function getInverseMassRatio(): float // 1.0 / massRatio
```

```php
public function getMassRatioSquared(): float // massRatio²
```

```php
public function getMassIncrease(): float // adjustedFullMass - originalFullMass
```

```php
public function getMassIncreasePercent(): float // (massRatio - 1.0) * 100
```

```php
public function formatMultiplier(): string
```

```php
public function getOriginalFullLoadMass(): float
```

```php
public function getAdjustedFullLoadMass(): float
```

---

### OverrideDef

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\Output`  
**File:** [src/Mods/CargoSizesMod/Output/OverrideDef.php](../../../src/Mods/CargoSizesMod/Output/OverrideDef.php)  
**Implements:** `StringableInterface`

#### Public Methods

```php
public function __construct(string $macroName)
```

```php
public function setMacroPath(string $path): self
```

```php
public function setPath(string $path): self
```

```php
public function getPath(): string
```

```php
public function setString(string $value): self
```

```php
public function setInt(int $value): self
```

```php
public function setFloat(float $value, int $precision = 2): self
```

```php
public function setComment(string|StringableInterface $comment, ...$args): self
```

```php
public function render(): string
```

```php
public function __toString(): string
```

---

### TagOverrideDef

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\Output`  
**File:** [src/Mods/CargoSizesMod/Output/TagOverrideDef.php](../../../src/Mods/CargoSizesMod/Output/TagOverrideDef.php)  
**Extends:** `OverrideDef`

#### Public Methods

```php
public function __construct(string $macroName)
```

```php
public function enableAddMode(bool $enable): self
```

```php
public function getPath(): string
```

```php
public function setTagName(string $name): self
```

```php
public function getTagName(): string
```

```php
public function setAttribute(string $name, string $value): self
```

```php
public function addComment(string $comment, ...$args): self
```

```php
public function addComments(array $comments): self
```

---

### Adjusted Values System

#### AdjustedValuesInterface

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\Output\Physics`  
**File:** [src/Mods/CargoSizesMod/Output/Physics/AdjustedValuesInterface.php](../../../src/Mods/CargoSizesMod/Output/Physics/AdjustedValuesInterface.php)

```php
public function isIncrease(): bool
```

```php
public function getPrecision(): int
```

```php
public function getMultiplier(): float
```

```php
public function getComments(): array // Returns string[]
```

---

#### AdjustedValuesTrait

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\Output\Physics`  
**File:** [src/Mods/CargoSizesMod/Output/Physics/AdjustedValuesTrait.php](../../../src/Mods/CargoSizesMod/Output/Physics/AdjustedValuesTrait.php)

```php
public function getMultiplier(): float
```

```php
public function getComments(): array
```

---

### Physics Adjustments

#### PhysicsCalculator

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\Output\Physics`  
**File:** [src/Mods/CargoSizesMod/Output/Physics/PhysicsCalculator.php](../../../src/Mods/CargoSizesMod/Output/Physics/PhysicsCalculator.php)

**Purpose:** Central calculator for all physics-related mass ratios and derived values.

```php
public function __construct(
    float $baseMass,
    float $originalCargo,
    float $adjustedCargo,
    float $cargoMultiplier,
    bool $useEffectiveRatioCap
)
```

##### Core Calculations

```php
public function getMassRatio(): float // adjustedFullMass / originalFullMass (>1.0)
```

```php
public function getCargoMultiplier(): float // User's chosen multiplier (2x, 4x, etc.)
```

```php
public function getEffectiveRatio(): float // min(massRatio, cargoMultiplier) if capped
```

```php
public function getBaseMass(): float // Ship mass without cargo
```

```php
public function getOriginalFullMass(): float // baseMass + originalCargo
```

```php
public function getAdjustedFullMass(): float // baseMass + adjustedCargo
```

```php
public function getMassIncrease(): float // adjustedFullMass - originalFullMass
```

```php
public function getMassIncreasePercent(): float // (massRatio - 1.0) * 100
```

##### Derived Calculations

```php
public function getInverseMassRatio(): float // 1.0 / massRatio (for jerk)
```

```php
public function getMassRatioSquared(): float // massRatio² (for squared drag mode)
```

##### Validation

```php
public function validate(): array // Returns warning strings if any (e.g., extreme ratios)
```

---

#### AdjustedAccelerationFactors

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\Output\Physics`  
**File:** [src/Mods/CargoSizesMod/Output/Physics/AdjustedAccelerationFactors.php](../../../src/Mods/CargoSizesMod/Output/Physics/AdjustedAccelerationFactors.php)  
**Extends:** `AccelerationFactors`  
**Implements:** `AdjustedValuesInterface`  
**Uses:** `AdjustedValuesTrait`

```php
public function __construct(AccelerationFactors $original, float $massMultiplier)
```

```php
public function isIncrease(): bool
```

```php
public function getPrecision(): int
```

---

#### AdjustedDrag

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\Output\Physics`  
**File:** [src/Mods/CargoSizesMod/Output/Physics/AdjustedDrag.php](../../../src/Mods/CargoSizesMod/Output/Physics/AdjustedDrag.php)  
**Extends:** `Drag`  
**Implements:** `AdjustedValuesInterface`  
**Uses:** `AdjustedValuesTrait`

```php
public function __construct(Drag $drag, float $reductionMultiplier)
```

```php
public function isIncrease(): bool
```

```php
public function getPrecision(): int
```

---

#### AdjustedInertia

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\Output\Physics`  
**File:** [src/Mods/CargoSizesMod/Output/Physics/AdjustedInertia.php](../../../src/Mods/CargoSizesMod/Output/Physics/AdjustedInertia.php)  
**Extends:** `Inertia`  
**Implements:** `AdjustedValuesInterface`  
**Uses:** `AdjustedValuesTrait`

```php
public function __construct(Inertia $original, float $multiplier)
```

```php
public function isIncrease(): bool
```

```php
public function getPrecision(): int
```

---

#### PhysicsOverrideDef

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\Output\Physics`  
**File:** [src/Mods/CargoSizesMod/Output/Physics/PhysicsOverrideDef.php](../../../src/Mods/CargoSizesMod/Output/Physics/PhysicsOverrideDef.php)  
**Extends:** `TagOverrideDef`

```php
public function __construct(
    string $macroName, 
    float $mass, 
    AdjustedInertia $inertia, 
    AdjustedDrag $drag, 
    AdjustedAccelerationFactors $accelerationFactors
)
```

---

### Jerk Adjustments

#### AdjustedJerk

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\Output\Jerk`  
**File:** [src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerk.php](../../../src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerk.php)  
**Extends:** `Jerk`  
**Implements:** `AdjustedValuesInterface`  
**Uses:** `AdjustedValuesTrait`

```php
public function __construct(Jerk $original, float $multiplier)
```

```php
public function isIncrease(): bool
```

```php
public function getPrecision(): int
```

```php
public function getBoost(): AdjustedJerkBoost
```

```php
public function getForward(): AdjustedJerkForward
```

```php
public function getTravel(): AdjustedJerkTravel
```

---

#### AdjustedJerkBoost

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\Output\Jerk`  
**File:** [src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerkBoost.php](../../../src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerkBoost.php)  
**Extends:** `JerkBoost`  
**Implements:** `AdjustedValuesInterface`  
**Uses:** `AdjustedValuesTrait`

```php
public function __construct(JerkBoost $original, float $multiplier)
```

```php
public function isIncrease(): bool
```

```php
public function getPrecision(): int
```

---

#### AdjustedJerkForward

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\Output\Jerk`  
**File:** [src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerkForward.php](../../../src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerkForward.php)  
**Extends:** `JerkForward`  
**Implements:** `AdjustedValuesInterface`  
**Uses:** `AdjustedValuesTrait`

```php
public function __construct(JerkForward $original, float $multiplier)
```

```php
public function isIncrease(): bool
```

```php
public function getPrecision(): int
```

---

#### AdjustedJerkTravel

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\Output\Jerk`  
**File:** [src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerkTravel.php](../../../src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerkTravel.php)  
**Extends:** `JerkTravel`  
**Implements:** `AdjustedValuesInterface`  
**Uses:** `AdjustedValuesTrait`

```php
public function __construct(JerkTravel $original, float $multiplier)
```

```php
public function isIncrease(): bool
```

```php
public function getPrecision(): int
```

---

#### JerkOverrideDef

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\Output\Jerk`  
**File:** [src/Mods/CargoSizesMod/Output/Jerk/JerkOverrideDef.php](../../../src/Mods/CargoSizesMod/Output/Jerk/JerkOverrideDef.php)  
**Extends:** `TagOverrideDef`

```php
public function __construct(string $macroName, AdjustedJerk $jerk)
```

---

## FOMOD Installer

### FileCollection

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\FOMOD`  
**File:** [src/Mods/CargoSizesMod/FOMOD/FileCollection.php](../../../src/Mods/CargoSizesMod/FOMOD/FileCollection.php)

#### Properties

```php
public private(set) string $shipType;
public private(set) string $shipSize;
public private(set) int|float $multiplier;
public private(set) string $id;
```

#### Public Methods

```php
public static function reset(): void
```

```php
public static function getInstances(): array // Returns FileCollection[]
```

```php
public static function create(string $shipType, string $size, float|int $multiplier): self
```

```php
public static function getByPrettyShipType(string $shipType): array // Returns FileCollection[]
```

```php
public function getID(): string
```

```php
public function getStepLabel(): string
```

```php
public function getPluginLabel(): string
```

```php
public function getPluginDescription(): string
```

```php
public function getInputFolderName(): string
```

```php
public function getShipType(): string
```

```php
public function getShipTypeLabel(): string
```

```php
public function getShipTypeNormalized(): string
```

```php
public function getShipSize(): string
```

```php
public function getMultiplier(): int|float
```

---

### FomodWriter

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\FOMOD`  
**File:** [src/Mods/CargoSizesMod/FOMOD/FomodWriter.php](../../../src/Mods/CargoSizesMod/FOMOD/FomodWriter.php)

#### Public Methods

```php
public function __construct(array $multipliers, FolderInfo $outputFolder, DataFolders $dataFolders)
```

```php
public function write(): void
```

```php
public function getName(): Translation
```

```php
public function getDescription(): Translation
```

---

### StepPluginImage

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\FOMOD`  
**File:** [src/Mods/CargoSizesMod/FOMOD/StepPluginImage.php](../../../src/Mods/CargoSizesMod/FOMOD/StepPluginImage.php)

#### Public Methods

```php
public function __construct(string $shipType, string $shipSize, float|int|null $multiplier)
```

```php
public function getImageFile(): FileInfo
```

```php
public function exists(): bool
```

```php
public function getFileName(): string
```

```php
public function render(): string
```

```php
public function getZIPPath(): string
```

---

## Reference Generators

### BaseReferenceRenderer

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\References`  
**File:** [src/Mods/CargoSizesMod/References/BaseReferenceRenderer.php](../../../src/Mods/CargoSizesMod/References/BaseReferenceRenderer.php)

#### Public Methods

```php
public function __construct(array $multipliers, array $results)
```

```php
abstract public function getFileName(): string
```

```php
public function write(): void
```

```php
public function generate(): string
```

---

### BBCodeReference

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\References`  
**File:** [src/Mods/CargoSizesMod/References/BBCodeReference.php](../../../src/Mods/CargoSizesMod/References/BBCodeReference.php)  
**Extends:** `BaseReferenceRenderer`

#### Public Methods

```php
public function getFileName(): string
```

---

### MarkdownReference

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\References`  
**File:** [src/Mods/CargoSizesMod/References/MarkdownReference.php](../../../src/Mods/CargoSizesMod/References/MarkdownReference.php)  
**Extends:** `BaseReferenceRenderer`

#### Public Methods

```php
public function getFileName(): string
```

---

### ReleaseNotesGenerator

**Namespace:** `Mistralys\X4\Mods\CargoSizesMod\References`  
**File:** [src/Mods/CargoSizesMod/References/ReleaseNotesGenerator.php](../../../src/Mods/CargoSizesMod/References/ReleaseNotesGenerator.php)

#### Purpose

Generates release notes from changelog files during the build process. Parses `changelog.md` and optionally `changelog-builder.md` to create a formatted release notes file.

#### Public Methods

```php
public function __construct(FolderInfo $buildFolder)
```

```php
public function generate(): void
```

**Throws:**
- `CargoSizeException::ERROR_MISSING_CHANGELOG` - When changelog.md is not found
- `CargoSizeException::ERROR_CHANGELOG_PARSE` - When changelog parsing fails
- `CargoSizeException::ERROR_FILE_WRITE` - When file write operation fails

**Generated Output:**
- File: `build/release-notes-v{VERSION}.md`
- Format: Markdown with main changelog (H1), optional builder changelog (H2), and installation footer
- Content: Latest version changes from both changelogs

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | Feb 9, 2026 | Initial public API documentation |
| 1.1 | Feb 10, 2026 | Updated for PHP 8.4: added asymmetric visibility, typed constants, and refactored iteration logic. |
| 1.2 | Feb 11, 2026 | Added ReleaseNotesGenerator class, three new CargoSizeException error constants (ERROR_MISSING_CHANGELOG, ERROR_CHANGELOG_PARSE, ERROR_FILE_WRITE). |
