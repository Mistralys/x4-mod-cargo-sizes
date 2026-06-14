# Project Constraints & Rules

> **Version:** 1.4
> **Last Updated:** March 1, 2026
> **Purpose:** Non-negotiable rules, conventions, and coding standards

---

## 🎯 Philosophy

This project follows **strict conventions** to maintain consistency and reliability. These constraints are based on:

1. **X4 Game Compatibility** - Must work with X4 game engine requirements
2. **X4 Core Library Patterns** - Inherits patterns from parent library
3. **PHP Best Practices** - Modern PHP 8.4+ standards
4. **Maintainability** - Code must be readable and extensible

**When in doubt, follow the existing pattern in the codebase.**

---

## 🚫 Absolute Constraints (NEVER Violate)

### 1. File I/O - Synchronous Only

**RULE:** All file operations MUST be synchronous.

❌ **Forbidden:**
```php
// NO async/await
async function readFile() { ... }

// NO promises
$promise = readFileAsync();

// NO event loops
$loop = React\EventLoop\Factory::create();
```

✅ **Required:**
```php
// Synchronous file operations
$content = file_get_contents($path);
file_put_contents($path, $content);

// Or use AppUtils FileHelper
$fileInfo = FileInfo::factory($path);
$content = $fileInfo->getContents();
```

**Reason:** Build tools are single-execution scripts. Async adds complexity with no benefit.

---

### 2. No Database Connections

**RULE:** This project does NOT use databases.

❌ **Forbidden:**
```php
// NO database connections
$pdo = new PDO(...);
$mysqli = new mysqli(...);

// NO query builders
$query = DB::table('ships')->where(...);

// NO ORMs
Model::find($id);
```

✅ **Required:**
```php
// All data from XML files
$xmlFile = new ShipXMLFile($fileInfo, $dataFolder);
$cargo = $xmlFile->getCargoValue();

// All configuration from JSON files
$config = JSONFile::factory('config/build-config.json')->parse();
```

**Reason:** All data comes from extracted game XML files. No need for database layer.

---

### 3. Strict Type Declarations

**RULE:** ALL PHP files MUST have strict types enabled.

✅ **Required:**
```php
<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod;

class MyClass
{
    public function process(int $value): string
    {
        return (string)$value;
    }
}
```

**Reason:** Prevents type coercion bugs. Enforces type safety.

---

### 4. Type Hints Everywhere

**RULE:** All method parameters and return types MUST have type declarations.

❌ **Forbidden:**
```php
public function calculate($value)  // No type hint
{
    return $value * 2;  // No return type
}
```

✅ **Required:**
```php
public function calculate(float $value): float
{
    return $value * 2;
}
```

**Exception:** Constructors promoted properties (allowed by PHP 8.0+):
```php
public function __construct(
    private readonly float $mass,
    private readonly int $cargo
) {
    // Types declared in promotion
}
```

---

### 5. Asymmetric Visibility

**RULE:** Use asymmetric visibility for properties that need to be read-only from the outside but modifiable within the class or hierarchy.

**Required:**
```php
public private(set) string $name;
```

**Note:** Always keep existing getter methods for backward compatibility with the public API.

---

### 6. Typed Constants

**RULE:** All class constants MUST have explicit type declarations.

**Required:**
```php
public const string MY_CONSTANT = 'value';
```

---

### 7. Exception Hierarchy

**RULE:** All exceptions MUST extend `CargoSizeException`.

```php
CargoSizeException (extends X4Exception)
```

❌ **Forbidden:**
```php
throw new \Exception('Error');
throw new \RuntimeException('Error');
throw new CustomException('Error'); // Doesn't extend CargoSizeException
```

✅ **Required:**
```php
throw new CargoSizeException(
    'Unhandled ship type',
    CargoSizeException::ERROR_UNHANDLED_SHIP_TYPE
);
```

**Error Code Ranges:**
- `178001-178099` - CargoSizesMod errors
- Codes MUST be constants in CargoSizeException

---

### 8. No eval() or Dynamic Code Execution

**RULE:** NEVER use `eval()`, `create_function()`, or similar.

❌ **Absolutely Forbidden:**
```php
eval($code);
create_function('$a', 'return $a * 2;');
assert($code); // with callback
```

**Reason:** Security risk, impossible to debug, no legitimate use case.

---

### 9. Namespace Structure

**RULE:** All code MUST be in `Mistralys\X4\Mods\CargoSizesMod` namespace.

✅ **Required:**
```php
<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod;
// or
namespace Mistralys\X4\Mods\CargoSizesMod\Build;
// or
namespace Mistralys\X4\Mods\CargoSizesMod\Output\Physics;
```

**File Location MUST match namespace:**
- `Mistralys\X4\Mods\CargoSizesMod\Build\Console` → `src/Mods/CargoSizesMod/Build/Console.php`

---

## 📋 Naming Conventions

### Classes

**Format:** PascalCase

```php
class CargoSizeExtractor { }
class ShipXMLFile { }
class AdjustedAccelerationFactors { }
```

**Suffixes:**
- `*Exception` - Exception classes
- `*Interface` - Interfaces
- `*Trait` - Traits
- `*Def` - Definition/configuration classes (e.g., `OverrideDef`)
- `*File` - File wrapper classes (e.g., `BaseXMLFile`)

---

### Methods

**Format:** camelCase

```php
public function getCargoValue(): int { }
public function calculateCargoValue(float $multiplier): int { }
public function isIncrease(): bool { }
```

**Prefixes:**
- `get*()` - Returns a value (no side effects)
- `set*()` - Sets a value (fluent interface should return `$this`)
- `is*()` / `has*()` - Boolean checks
- `create*()` - Factory methods
- `render*()` / `generate*()` - Creates output
- `calculate*()` - Performs calculation

---

### Constants

**Format:** SCREAMING_SNAKE_CASE

```php
const SHIP_TYPE_TRANSPORT = 'trans';
const ERROR_UNHANDLED_SHIP_TYPE = 178001;
const KEY_MULTIPLIERS = 'cargo-multipliers';
```

---

### Variables

**Format:** camelCase

```php
$shipResult = ...;
$multiplier = 4;
$adjustedCargo = $cargo * $multiplier;
```

**Avoid single-letter variables except:**
- Loop counters: `$i`, `$j`, `$k`
- Coordinates: `$x`, `$y`, `$z`

---

### File Names

**Format:** PascalCase.php (matching class name)

```
CargoSizeExtractor.php
ShipXMLFile.php
AdjustedAccelerationFactors.php
```

**Exception:** `functions.php` (global functions file)

---

## 🏗️ Architectural Constraints

### 1. Immutability for Value Objects

**RULE:** Value objects (physics data) SHOULD be immutable.

✅ **Good:**
```php
class Drag
{
    public function __construct(
        private readonly float $forward,
        private readonly float $reverse,
        // ...
    ) {}
    
    public function getForward(): float
    {
        return $this->forward;
    }
}
```

❌ **Avoid:**
```php
class Drag
{
    private float $forward;
    
    public function setForward(float $value): void  // Mutator on value object
    {
        $this->forward = $value;
    }
}
```

**Reason:** Physics values shouldn't change after creation. Prevents bugs.

---

### 2. Fluent Interfaces for Builders

**RULE:** Builder/definition classes SHOULD use fluent interfaces.

✅ **Required:**
```php
$override = new TagOverrideDef('ship_macro');
$override
    ->setTagName('physics')
    ->setAttribute('type', 'inertia')
    ->setFloat(362.940, 3)
    ->addComment('Adjusted pitch inertia');
```

**Pattern:** All `set*()` and `add*()` methods return `self` / `$this`.

---

### 3. Static Factories Only Where Needed

**RULE:** Use static factories for:
- Singleton-like access (e.g., `CargoSizeBuildTools::getConfig()`)
- Alternative constructors (e.g., `FileCollection::create()`)

❌ **Avoid:**
```php
class ShipResult
{
    public static function create(...): self  // Unnecessary static factory
    {
        return new self(...);
    }
}
```

✅ **Use constructor directly:**
```php
$result = new ShipResult($label, $type, $shipXML, $cargoXML);
```

---

### 4. Inheritance vs Composition

**RULE:** Prefer composition over inheritance except for:
- Abstract base classes with template methods (e.g., `BaseXMLFile`)
- Value object hierarchies (e.g., `AdjustedAccelerationFactors extends AccelerationFactors`)
- Interface implementations

✅ **Good use of inheritance:**
```php
abstract class BaseXMLFile
{
    abstract public function getFileName(): string;
    
    public function getRelativePath(): string  // Template method
    {
        // Common implementation
    }
}

class ShipXMLFile extends BaseXMLFile { }
```

---

### 5. Adjusted Values Pattern

**RULE:** All flight mechanics adjustments MUST implement `AdjustedValuesInterface`.

✅ **Required:**
```php
class AdjustedAccelerationFactors extends AccelerationFactors implements AdjustedValuesInterface
{
    use AdjustedValuesTrait;
    
    public function isIncrease(): bool
    {
        return true;  // Acceleration increases proportionally with mass ratio
    }
    
    public function getPrecision(): int
    {
        return 6;  // 6 decimal places
    }
}  
```

**Required methods:**
- `getMultiplier(): float` - Adjustment multiplier
- `isIncrease(): bool` - Direction of adjustment
- `getPrecision(): int` - Decimal places for rendering
- `getComments(): array` - Explanatory comments

---

## 📝 Documentation Standards

### Class-Level DocBlocks

**RULE:** All classes MUST have a DocBlock.

✅ **Required:**
```php
/**
 * Extracts ship data from X4 game files and generates mod files.
 * 
 * @package Build Tools
 * @subpackage Extraction
 */
class CargoSizeExtractor
{
    // ...
}
```

---

### Method-Level DocBlocks

**RULE:** Public methods SHOULD have DocBlocks.

✅ **Good:**
```php
/**
 * Calculates the adjusted cargo value for a given multiplier.
 *
 * @param float|int $multiplier The cargo size multiplier (e.g., 2, 4, 8)
 * @return int The adjusted cargo capacity
 */
public function calculateCargoValue(float|int $multiplier): int
{
    return (int)($this->getCargoValue() * $multiplier);
}
```

**Optional when:**
- Method is self-explanatory
- Type hints make it obvious

---

### Array Type Hints

**RULE:** When returning arrays of specific types, document in PHPDoc.

✅ **Required:**
```php
/**
 * Gets all steering curve positions.
 *
 * @return SteeringCurvePosition[]
 */
public function getPositions(): array
{
    return $this->positions;
}
```

---

## 🧪 Testing Standards

### Test Organization

**RULE:** Tests SHOULD be organized in `tests/` directory.

```
tests/
├── bootstrap.php
└── CargoSizesModTests/
    ├── AccelerationAdjustmentTest.php
    ├── AccelerationOverrideTest.php
    └── PhysicsCalculatorTest.php
```

---

### Test Naming

**Format:** `test*` methods or `*Test` classes

```php
class CargoSizeCalculationTest extends TestCase
{
    public function testMultiplierCalculation(): void
    {
        // ...
    }
}
```

---

### No Tests in Production Code

**RULE:** Test code MUST NOT be in `src/`.

❌ **Forbidden:**
```php
// In src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php
public function __testMode(): void  // NO!
{
    // Testing code in production class
}
```

---

## 🔒 Security Constraints

### 1. No User Input Execution

**RULE:** Never execute user-provided code or file paths without validation.

❌ **Dangerous:**
```php
$userPath = $_GET['path'];  // NO!
$file = file_get_contents($userPath);
```

**Note:** This is a build tool, no HTTP input. But principle applies to config files.

---

### 2. Validate Configuration Files

**RULE:** Configuration values MUST be validated.

✅ **Required:**
```php
public function __construct()
{
    $config = ArrayDataCollection::create(
        JSONFile::factory(__DIR__.'/../config/build-config.json')->parse()
    );
    
    foreach($config->getArray(self::KEY_MULTIPLIERS) as $value) {
        if(is_numeric($value) && $value > 0) {  // Validate!
            $this->multipliers[] = (float)$value;
        }
    }
}
```

---

### 3. No Credentials in Code

**RULE:** No API keys, passwords, or credentials in source code.

✅ **Use configuration files:**
- `dev-config.php` (local, gitignored)
- Environment variables

---

## 📦 Dependency Constraints

### 1. Adding Dependencies

**RULE:** New Composer dependencies require justification.

**Questions to ask:**
1. Can this be done without a dependency?
2. Is the dependency actively maintained?
3. Does it have security vulnerabilities?
4. Is the license compatible (MIT preferred)?

**Process:**
```bash
# Check security
composer require <package>

# Run security audit
composer audit

# Update documentation
# → Update tech-stack.md
```

---

### 2. No Unused Dependencies

**RULE:** Remove unused dependencies.

```bash
# Periodically check
composer show --installed

# Remove unused packages
composer remove <package>
```

---

### 3. Version Constraints

**RULE:** Use sensible version constraints.

✅ **Good:**
```json
{
  "require": {
    "mistralys/x4-core": "dev-main",
    "php": ">=8.2"
  }
}
```

❌ **Avoid:**
```json
{
  "require": {
    "some/package": "*"  // Too loose
    "some/package": "1.2.3"  // Too strict
  }
}
```

---

## 🎨 Code Style

### 1. Indentation

**RULE:** 4 spaces, no tabs.

✅ **Required:**
```php
class MyClass
{
    public function myMethod(): void
    {
        if ($condition) {
            // 4 spaces
            echo 'test';
        }
    }
}
```

---

### 2. Braces

**RULE:** Opening brace on same line for control structures, new line for classes/methods.

✅ **Required:**
```php
// Control structures: same line
if ($condition) {
    // ...
}

foreach ($items as $item) {
    // ...
}

// Classes and methods: new line
class MyClass
{
    public function myMethod(): void
    {
        // ...
    }
}
```

---

### 3. Line Length

**GUIDELINE:** Aim for 120 characters max.

**Exceptions:** Long strings, URLs, file paths.

---

### 4. Blank Lines

**RULE:** Use blank lines to separate logical blocks.

✅ **Good:**
```php
public function process(): void
{
    $shipXML = $this->loadShipXML();
    $cargoXML = $this->loadCargoXML();
    
    $adjustment = $this->calculateAdjustment($shipXML, $cargoXML);
    
    $this->generateOverrideFile($adjustment);
}
```

---

## 🔧 Build System Constraints

### 1. Composer Scripts Only

**RULE:** Build MUST be invokable via `composer build`.

```json
{
  "scripts": {
    "build": "Mistralys\\X4\\Mods\\CargoSizesMod\\CargoSizeBuildTools::build"
  }
}
```

**No external build tools** (Make, Grunt, Webpack, etc.)

---

### 2. Self-Contained Build

**RULE:** Build process MUST NOT require internet access.

**Exception:** Initial `composer install` to download dependencies.

---

### 3. Idempotent Builds

**RULE:** Running build multiple times SHOULD produce identical output (given same input).

```bash
composer build  # Run 1
composer build  # Run 2 - same output
```

---

### 4. Build Configuration Keys

The file `config/build-config.json` contains the following top-level keys:

| Key | Type | Required | Description |
|-----|------|----------|-------------|
| `"cargo-multipliers"` | `number[]` | Yes | List of cargo multipliers to build (e.g. `[2, 4, 8, 10]`). |
| `"flight-mechanics"` | `object` | No | Per-key acceleration responsiveness overrides. |
| `"example-ships"` | `object` | No | Maps `{shipType}-{size}` keys to ship label strings for deterministic example selection. |

**`"example-ships"` details:**

- **Optional key.** If absent, `FileCollection::getExampleShipDescription()` and `ReleaseNotesGenerator::formatComparisonTable()` fall back to `array_rand()` (random selection).
- **Key format:** `{normalizedShipType}-{size}` (lowercase). Examples: `"trans-s"`, `"trans-m"`, `"trans-l"`, `"miner-m"`, `"carrier-xl"`, `"resupplier-xl"`.
- **Value:** Must exactly match `ShipResult::getShipLabel()` output — values are **case-sensitive**.
- **Silent fallback:** If the configured ship label is not found in the current build data, both consumers fall back to `array_rand()` without error.
- **Accessed via:** `BuildConfig::getExampleShip(string $shipType, string $shipSize): string`.

### 5. Ship Type Filtering Rules

**RULE:** `CargoSizeExtractor::resolveShipType()` MUST apply the early alias intercept **before** the standard keyword lookup.

The following hybrid ship classes must be aliased to standard output categories:

| Internal keyword (in macro name) | Maps to constant | Output folder |
|----------------------------------|-----------------|---------------|
| `scavenger` | `SHIP_TYPE_TRANSPORT` (`trans`) | `transport_*` |
| `terraformer` | `SHIP_TYPE_MINER` (`miner`) | `miner_*` |

**RULE:** Pure alias macros (XML `<macro>` element has a non-empty `alias=` attribute) MUST be skipped during `analyzeShipMacro()`. They have no `<physics>` element and inherit their values from the referenced macro. The same alias resolution in `ShipDataService::determineShipType()` applies the identical intercept for the GUI layer.

---

## 🚀 Performance Constraints

### 1. No Premature Optimization

**RULE:** Optimize ONLY when:
- Profiling shows bottleneck
- Build time is unacceptable (>5 minutes)

**Current approach:** Synchronous, simple, readable code.

---

### 2. Memory Efficiency

**RULE:** Don't load all XML files into memory at once.

✅ **Good:**
```php
foreach ($shipFiles as $shipFile) {
    $shipXML = new ShipXMLFile($shipFile, $dataFolder);
    // Process
    unset($shipXML);  // Free memory
}
```

---

## 🌍 Localization Constraints

### 1. Multi-Language Support

**RULE:** All user-facing text MUST be translatable.

✅ **Required:**
```php
$translation = new Translation(Translation::TYPE_NAME_TRANSPORT, [
    'multiplier' => 4
]);
$english = $translation->getByLanguageID(44);
$german = $translation->getByLanguageID(49);
```

❌ **Forbidden:**
```php
$name = "Cargo size x4 for transports";  // Hardcoded English
```

---

### 2. Supported Languages

**RULE:** Must support all X4 languages:
1. English (44)
2. German (49)
3. French (33)
4. Spanish (34)
5. Italian (39)
6. Russian (7)
7. Korean (82)

---

## 📂 File Organization Constraints

### 1. One Class Per File

**RULE:** Each file MUST contain exactly one class/interface/trait.

**Exception:** `functions.php` (global functions).

---

### 2. Namespace = Directory Structure

**RULE:** Namespace MUST match directory structure.

```
Mistralys\X4\Mods\CargoSizesMod\Build\CargoSizeExtractor
→ src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php
```

---

### 3. No Generated Files in Repository

**RULE:** Generated files MUST be in `.gitignore`.

```
/build/
/output/
/vendor/
dev-config.php
```

---

## 🔄 Versioning Constraints

### 1. Single Source of Truth

**RULE:** Version MUST be in `mod-version.txt` only.

```php
ModInfo::getVersion();  // Reads mod-version.txt
```

❌ **Don't duplicate:**
```php
const VERSION = '2.1.1';  // NO!
```

---

### 2. Semantic Versioning

**RULE:** Follow SemVer: `MAJOR.MINOR.PATCH`

- `MAJOR` - Breaking changes
- `MINOR` - New features (backward compatible)
- `PATCH` - Bug fixes

---

## ✅ Checklist for New Code

Before committing new code, verify:

- [ ] `declare(strict_types=1);` at top of file
- [ ] All methods have type hints (parameters and return)
- [ ] All exceptions extend `CargoSizeException`
- [ ] No `eval()`, `create_function()`, or dynamic code execution
- [ ] No database connections
- [ ] All file I/O is synchronous
- [ ] Namespace matches directory structure
- [ ] Class name matches file name
- [ ] PHPDoc for public methods
- [ ] Multi-language support for user-facing text
- [ ] No hardcoded credentials or secrets
- [ ] Follows naming conventions
- [ ] Generated files not in repository
- [ ] Tests written (if applicable)

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.5 | Jun 12, 2026 | Added Ship Type Filtering Rules (scavenger→transport, terraformer→miner, alias macro skip) |
| 1.3 | Feb 19, 2026 | Updated Adjusted Values Pattern example to use AdjustedAccelerationFactors (AdjustedDrag removed in v4.0 refactoring) |
| 1.2 | Feb 19, 2026 | Updated test directory example to reflect actual test suite (CargoSizesModTests/) after tier-based system removal |
| 1.0 | Feb 9, 2026 | Initial constraints documentation |
