# Development Guide

> **X4 Cargo Sizes Mod - Physics Tuning GUI**  
> **Version:** 1.4  
> **Last Updated:** February 18, 2026

---

> 📘 **Quick Start:** New to the codebase? Start with the [Project Manifest](project-manifest/README.md) for system architecture. This document provides development workflow, coding standards, and contributor guidelines.

---

## Table of Contents

1. [Getting Started](#getting-started)
2. [Development Workflow](#development-workflow)
3. [Code Standards](#code-standards)
4. [Testing](#testing)
5. [Debugging](#debugging)
6. [Contributing Guidelines](#contributing-guidelines)
7. [Common Tasks](#common-tasks)
8. [Troubleshooting](#troubleshooting)

---

## Getting Started

### Prerequisites

Ensure you have the following installed:

- **PHP 8.4+** with CLI support
- **Composer 2.x**
- **Node.js 18+** and **npm 9+**
- **X4 game data extracted** (via `mistralys/x4-data-extractor`)

### Initial Setup

```bash
# Clone the repository (if not already done)
git clone https://github.com/yourusername/x4-mod-cargo-sizes.git
cd x4-mod-cargo-sizes

# Install parent project dependencies
composer install

# Install GUI dependencies
composer gui:install

# This is equivalent to:
# cd gui/backend && composer install
# cd gui/frontend && npm install
```

### Running the Development Servers

#### Windows

```cmd
cd gui
start-dev.bat
```

This starts:
- **Backend API:** `http://localhost:8080`
- **Frontend Dev Server:** `http://localhost:5173`

#### Linux/Mac

```bash
cd gui
./start-dev.sh
```

#### Manual Start

```bash
# Terminal 1: Start backend
cd gui/backend
php -S localhost:8080 -t public

# Terminal 2: Start frontend
cd gui/frontend
npm run dev
```

### Stopping Development Servers

#### Linux/Mac

```bash
cd gui
./stop-dev.sh
```

#### Windows

Press `Ctrl+C` in each terminal window.

---

## Development Workflow

### Recommended IDE Setup

**Visual Studio Code** with the following extensions:

#### Backend (PHP)
- **PHP Intelephense** - PHP language server
- **PHPStan** - Static analysis integration
- **PHPUnit Test Explorer** - Test runner integration

#### Frontend (TypeScript/React)
- **ESLint** - JavaScript/TypeScript linting
- **Prettier** - Code formatting
- **Tailwind CSS IntelliSense** - TailwindCSS autocomplete
- **TypeScript Vue Plugin (Volar)** - Better TypeScript support

### Branch Strategy

- **`main`**: Stable production code
- **`develop`**: Active development branch
- **Feature branches**: `feature/description-of-feature`
- **Bugfix branches**: `bugfix/description-of-bug`

### Commit Conventions

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <description>

[optional body]

[optional footer]
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, whitespace)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Build process, tooling, dependencies

**Examples:**
```
feat(frontend): add tier editor for jerk reduction
fix(backend): correct engine thrust calculation for large ships
docs(gui): update ARCHITECTURE.md with engine performance flow
refactor(hooks): extract debounce logic to separate utility
test(services): add unit tests for ConfigService validation
```

---

## Code Standards

### PHP Code Standards

#### General Rules

1. **Strict Types:** Every PHP file must declare `declare(strict_types=1);` at the top
2. **Type Hints:** All function parameters and return types must have type hints
3. **Readonly Properties:** Use `readonly` for immutable properties (PHP 8.1+)
4. **Namespace:** All classes must use the namespace `Mistralys\X4\Mods\CargoSizesMod\GUI\*`
5. **PSR-12:** Follow PSR-12 coding style

#### Example

```php
<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\Services;

use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\PhysicsRequest;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\PhysicsResponse;

/**
 * Physics calculation service.
 */
class PhysicsService
{
    /**
     * Calculate physics values.
     *
     * @param PhysicsRequest $request Input parameters
     * @return PhysicsResponse Calculated results
     * @throws GUIException
     */
    public function calculatePhysics(PhysicsRequest $request): PhysicsResponse
    {
        // Implementation
    }
}
```

#### File Structure

- **One class per file**
- **Filename matches class name** (e.g., `PhysicsService.php` contains `PhysicsService` class)
- **Use namespaces** matching directory structure

#### Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| Classes | PascalCase | `PhysicsService` |
| Methods | camelCase | `calculatePhysics()` |
| Properties | camelCase | `$baseMass` |
| Constants | UPPER_SNAKE_CASE | `MAX_MULTIPLIER` |

#### Documentation

All public methods must have PHPDoc blocks:

```php
/**
 * Short description.
 *
 * Longer description if needed.
 *
 * @param Type $param Description
 * @return Type Description
 * @throws ExceptionType When this happens
 */
```

### TypeScript/React Code Standards

#### General Rules

1. **Strict Mode:** TypeScript `strict` mode enabled in `tsconfig.json`
2. **No `any`:** Avoid `any` type unless absolutely necessary (document why)
3. **Functional Components:** Use function components, not class components
4. **Hooks:** Use React hooks for state and lifecycle
5. **Export:** Named exports preferred over default exports (except for pages/routes)

#### Example

```typescript
import { useState, useEffect } from 'react';
import type { PhysicsConfig } from '../types/physics';

interface Props {
  initialConfig: PhysicsConfig;
  onChange: (config: PhysicsConfig) => void;
}

export function ConfigPanel({ initialConfig, onChange }: Props) {
  const [config, setConfig] = useState<PhysicsConfig>(initialConfig);

  useEffect(() => {
    onChange(config);
  }, [config, onChange]);

  return (
    <div>
      {/* Component JSX */}
    </div>
  );
}
```

#### Component Structure

```typescript
// 1. Imports (external first, then internal)
import { useState } from 'react';
import type { SomeType } from '../types/something';

// 2. Type definitions
interface Props {
  // ...
}

// 3. Component function
export function ComponentName({ prop1, prop2 }: Props) {
  // 4. Hooks
  const [state, setState] = useState();

  // 5. Event handlers
  const handleClick = () => {
    // ...
  };

  // 6. Render
  return (
    <div>{/* JSX */}</div>
  );
}
```

#### Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| Components | PascalCase | `ConfigPanel` |
| Files | PascalCase for components | `ConfigPanel.tsx` |
| Hooks | camelCase, `use` prefix | `usePhysicsCalculation` |
| Functions | camelCase | `calculatePhysics` |
| Constants | UPPER_SNAKE_CASE | `MAX_MULTIPLIER` |

#### Props and State

- **Props:** Use named interfaces (`interface Props`)
- **State:** Explicitly type state variables
- **Event Handlers:** Prefix with `handle` (e.g., `handleClick`, `handleChange`)

### CSS/TailwindCSS Standards

1. **Utility-First:** Use TailwindCSS utility classes
2. **Custom Classes:** Avoid custom CSS unless necessary
3. **Responsive:** Use responsive prefixes (`md:`, `lg:`) for breakpoints
4. **Dark Mode:** Not currently implemented (future consideration)

#### Example

```tsx
<div className="flex flex-col space-y-4 p-6 bg-white rounded-lg shadow-md">
  <h2 className="text-xl font-semibold text-gray-800">Configuration</h2>
  <button className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
    Save
  </button>
</div>
```

---

## Testing

### Backend Testing (PHPUnit 12.5+)

The GUI backend uses **PHPUnit 12.5+** for automated testing. The test suite includes 61 tests with 1,028 assertions covering Services, Utils, DTOs, and API layers.

#### Directory Structure

```
gui/backend/tests/
├── bootstrap.php                           # Test autoloader
├── Unit/                                   # Unit tests (fast, isolated)
│   ├── Utils/
│   │   └── PhysicsCalculationHelperTest.php
│   ├── Services/
│   │   ├── ClassRangeServiceTest.php
│   │   └── ShipDataServiceTest.php              # NEW in v1.3
│   ├── API/
│   │   └── ServiceContainerTest.php
│   └── DTOs/
│       └── PhysicsResponseDataTest.php
└── Integration/                            # Integration tests (full workflows)
    └── Endpoints/
        └── .gitkeep                        # Placeholder for future endpoint tests
```

#### Running Tests

```bash
# From gui/backend directory
cd gui/backend

# Run all tests (Unit + Integration)
composer test

# Run only unit tests (faster)
composer test:unit

# Generate HTML coverage report
# First, configure XDebug:
export XDEBUG_MODE=coverage  # Linux/Mac
set XDEBUG_MODE=coverage     # Windows CMD
$env:XDEBUG_MODE="coverage"  # Windows PowerShell

# Then run coverage
composer test:coverage

# Open coverage report
open coverage/index.html  # Mac
xdg-open coverage/index.html  # Linux
start coverage/index.html  # Windows
```

**Output:**
```
PHPUnit 12.5.12 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.4.0
Configuration: phpunit.xml

.............................................................. 61 / 61 (100%)

Time: 00:00.470, Memory: 12.00 MB

OK (61 tests, 1028 assertions)
```

#### Writing Tests

**Unit Test Example:**

```php
<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Mistralys\X4\Mods\CargoSizesMod\GUI\Services\PhysicsService;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\PhysicsRequest;

class PhysicsServiceTest extends TestCase
{
    /**
     * Test that calculatePhysics returns correct values for valid input.
     */
    public function testCalculatePhysicsReturnsCorrectValues(): void
    {
        $service = new PhysicsService();
        $request = new PhysicsRequest(
            shipClass: 'transporter',
            multiplier: 4,
            // ... other parameters
        );
        
        $result = $service->calculatePhysics($request);
        
        $this->assertInstanceOf(PhysicsResponse::class, $result);
        $this->assertEqualsWithDelta(10000.0, $result->cargoMass, 0.01);
    }
    
    /**
     * Test that exception is thrown for invalid multiplier.
     */
    public function testCalculatePhysicsThrowsExceptionOnInvalidMultiplier(): void
    {
        $this->expectException(GUIException::class);
        $this->expectExceptionMessage('Invalid multiplier');
        
        $service = new PhysicsService();
        // ... create request with invalid multiplier
        $service->calculatePhysics($request);
    }
}
```

**Mocking Dependencies:**

```php
public function testServiceWithMockedDependency(): void
{
    // Create mock
    $mockShipData = $this->createMock(ShipDataService::class);
    $mockShipData->method('getShips')
        ->willReturn([/* test fixtures */]);
    
    // Inject mock via constructor (DI pattern)
    $service = new ClassRangeService($mockShipData);
    
    $result = $service->calculateClassRangeData('transporter', 4);
    
    $this->assertInstanceOf(ClassRangeResponse::class, $result);
}

// Example: ShipDataServiceTest (v1.3)
public function testGetShipDetailsReturnsCorrectData(): void
{
    // Mock X4 Core dependencies
    $mockShipDefs = $this->createMock(ShipDefs::class);
    $mockEngineDefs = $this->createMock(EngineDefs::class);
    
    // Inject mocks for test isolation
    $service = new ShipDataService($mockShipDefs, $mockEngineDefs);
    
    // Test behavior without touching X4 Core singletons
    $result = $service->getShipDetails('ship_arg_xl_trader_01_a');
    $this->assertInstanceOf(ShipDetails::class, $result);
}
```

#### Test Naming Conventions

- **Test files:** `{ClassName}Test.php` (e.g., `PhysicsServiceTest.php`)
- **Test methods:** `test{MethodName}{Condition}` (e.g., `testCalculatePhysicsReturnsCorrectValues`)
- **Descriptive names:** Clearly describe what is being tested
- **Use camelCase:** Follow PHP naming conventions

#### Test Coverage Goals

- **Services:** 80%+ coverage
- **Utils/Traits:** 90%+ coverage
- **DTOs:** 100% coverage (simple validation logic)
- **API/Endpoints:** 70%+ coverage (future goal)
- **Overall:** Target 85%+ coverage

**Current Coverage (as of v1.3):**
- 80+ tests covering Utils, Services, DTOs, API layers
- 1100+ assertions (including 870 in ShipDataService, 46 in ConfigService)
- Execution time: <0.5 seconds
- **ShipDataService:** 12 tests with comprehensive coverage of all 5 public methods plus cache isolation testing
- **ConfigService:** 16 tests with comprehensive CRUD and validation testing using isolated temporary files
- **PhysicsService:** 8 tests covering core business logic, optional parameters, and determinism verification

#### Testing Considerations

**XDebug Configuration:**

Code coverage generation requires XDebug with coverage mode enabled:

```bash
# Set before running coverage
export XDEBUG_MODE=coverage  # Linux/Mac
set XDEBUG_MODE=coverage     # Windows CMD
$env:XDEBUG_MODE="coverage"  # Windows PowerShell
```

**Test Autoloader:**

The test bootstrap (`tests/bootstrap.php`) loads both backend and main project autoloaders. This is necessary for accessing X4 Core library classes but creates coupling between backend tests and main project dependencies.

**Known Patterns:**

- **Anonymous Class Pattern:** `PhysicsCalculationHelperTest` uses an anonymous class to test private trait methods. This is a creative but non-standard approach.
- **Injectable Dependencies (v1.3):** ConfigService accepts injectable $configPath, ShipDataService accepts injectable ShipDefs/EngineDefs for test isolation.
- **Instance-Level Caches (v1.3):** ShipDataService uses instance properties (no `static`) ensuring each test gets fresh cache state.
- **Hybrid Testing Strategy (v1.3):** `ShipDataServiceTest` demonstrates a dual approach:
  - **Integration Tests:** Use real X4 Core game data via `dev-config.php` bootstrap for comprehensive coverage
  - **Unit Tests:** Use mocked dependencies for isolated testing of specific behaviors
  - **Comprehensive Documentation:** Class-level docblocks explain the testing rationale and approach
  - **Cache Isolation:** Explicit tests verify instance-level caches don't leak between service instances
- **Temporary File Isolation (v1.3):** `ConfigServiceTest` demonstrates isolated file-based testing:
  - **Per-Test Temp Files:** Each test uses `sys_get_temp_dir()` + `uniqid()` for unique config files
  - **Complete Isolation:** No shared state between tests, each test modifies its own config file
  - **Automatic Cleanup:** `tearDown()` method ensures temp files are removed after each test
  - **CRUD Coverage:** Tests cover both read operations (`getConfig()`) and write operations (`updateConfig()`)
  - **Validation Testing:** Comprehensive edge case coverage for config validation rules
- **Determinism Testing (v1.3):** `PhysicsServiceTest` demonstrates verification of consistent output:
  - **Identical Input/Output:** Same `PhysicsRequest` produces identical `PhysicsResponse` across multiple calls
  - **Backward Compatibility:** Tests verify service works with optional parameters (shipId, engineId)
  - **Integration Coverage:** Uses real X4 Core ship/engine data for comprehensive testing
  - **Business Logic Focus:** Tests core physics calculations rather than data access layers
- **Parameter Objects:** Some DTOs (e.g., `ClassRangeRequest`) have many constructor parameters (9+). Consider grouping related parameters into nested DTOs for better maintainability.

### Frontend Testing

#### Running Tests (Future)

Currently, frontend tests are not implemented. When added:

```bash
cd gui/frontend
npm test
```

#### Recommended Testing Stack

- **Vitest** - Fast test runner compatible with Vite
- **React Testing Library** - Component testing
- **Mock Service Worker (MSW)** - API mocking

### Static Analysis

#### PHP (PHPStan)

The GUI backend uses **PHPStan 2.1+** for static analysis at level 5, matching the main project's configuration.

```bash
# From gui/backend/ directory
composer analyze

# From project root (via gui:analyze script)
composer gui:analyze

# Specific level (0-9, higher = stricter)
./vendor/bin/phpstan analyse --level 8 src/
```

**Configuration:** `gui/backend/phpstan.neon`
- **Level:** 5 (intermediate strictness)
- **Paths:** `src/` and `tests/`
- **Bootstrap:** `tests/bootstrap.php` (ensures X4 Core initialization)
- **Ignore Patterns:** Minimal, justified only for framework-level patterns

**Goal:** Level 5 compliance with 0 errors for all GUI backend code.

#### TypeScript

TypeScript's compiler handles static analysis:

```bash
cd gui/frontend
npm run build
```

If the build succeeds, there are no type errors.

**Type Safety Process:**
- **Backend DTOs are authoritative** - TypeScript types must match PHP DTO `toArray()` output
- **Regular audits required** - Frontend types should be audited against backend changes
- **Strict compliance** - All TypeScript files use strict mode with no `any` types
- **Build validation** - TypeScript compilation must pass before deployment

---

## Debugging

### Backend Debugging

#### Enable Error Display

Edit `gui/backend/public/index.php`:

```php
// Add at the top for development
ini_set('display_errors', '1');
error_reporting(E_ALL);
```

#### Logging

Add debug output:

```php
error_log("Debug: " . print_r($someVariable, true));
```

Check logs:
- **Windows:** PHP CLI outputs to console
- **Linux/Mac:** Check `/var/log/php_errors.log` or console output

#### Xdebug

Install Xdebug for step-through debugging:

1. Install Xdebug: `pecl install xdebug`
2. Add to `php.ini`:
   ```ini
   zend_extension=xdebug.so
   xdebug.mode=debug
   xdebug.start_with_request=yes
   ```
3. Configure VS Code with PHP Debug extension
4. Set breakpoints and run

### Frontend Debugging

#### Browser DevTools

- **Console:** View errors and `console.log()` output
- **Network Tab:** Inspect API requests/responses
- **React DevTools:** Inspect component state and props

#### React DevTools

Install [React Developer Tools](https://react.dev/learn/react-developer-tools):
- Chrome: [Extension](https://chrome.google.com/webstore/detail/react-developer-tools/)
- Firefox: [Extension](https://addons.mozilla.org/en-US/firefox/addon/react-devtools/)

#### Debugging Tips

```typescript
// Debug API calls
console.log('Sending request:', request);
const response = await physicsApi.calculate(request);
console.log('Received response:', response);

// Debug state changes
useEffect(() => {
  console.log('Config changed:', config);
}, [config]);

// Debug renders
console.log('Component rendering with props:', props);
```

---

## Contributing Guidelines

### Pull Request Process

1. **Fork the repository** and create a feature branch from `develop`
2. **Make your changes** following code standards
3. **Write tests** for new functionality
4. **Run tests and static analysis** to ensure everything passes
5. **Update documentation** if needed (ARCHITECTURE.md, API.md, README.md)
6. **Commit with clear messages** following Conventional Commits
7. **Push to your fork** and open a Pull Request against `develop`

### Pull Request Checklist

- [ ] Code follows PHP and TypeScript standards
- [ ] All tests pass (`composer test`)
- [ ] PHPStan analysis passes (`composer analyze`)
- [ ] TypeScript compiles without errors (`npm run build`)
- [ ] Frontend types match backend DTO shapes (after DTO changes)
- [ ] Documentation updated (if applicable)
- [ ] Commit messages follow Conventional Commits
- [ ] PR description explains what and why (not just how)

### Code Review Guidelines

**For Reviewers:**
- Check code quality and adherence to standards
- Verify tests cover new functionality
- Ensure performance considerations addressed
- Suggest improvements, don't demand perfection

**For Contributors:**
- Respond to feedback constructively
- Make requested changes or explain why you disagree
- Squash commits before merging (if requested)

---

## Common Tasks

### Adding a New API Endpoint

1. **Create DTO** (if needed) in `gui/backend/src/DTOs/`
2. **Add Service Method** in relevant service (`gui/backend/src/Services/`)
3. **Create Endpoint Handler** in `gui/backend/src/API/Endpoints/`
4. **Register Route** in `gui/backend/src/API/Router.php`
5. **Update Frontend API Client** in `gui/frontend/src/services/api.ts`
6. **Add TypeScript Type** (if needed) in `gui/frontend/src/types/`
7. **Document** in `gui/docs/API.md`

### Adding a New Frontend Component

1. **Create Component File** in appropriate directory (`gui/frontend/src/components/`)
2. **Define Props Interface** at the top of the file
3. **Implement Component** using functional component pattern
4. **Add to Parent Component** by importing and rendering
5. **Style with TailwindCSS** utility classes

### Adding a Configuration Parameter

1. **Update `build-config.json`** schema with new parameter
2. **Update DTOs**:
   - `gui/backend/src/DTOs/PhysicsRequest.php`
   - `gui/frontend/src/types/physics.d.ts` (mirror exactly)
3. **Update Services** to handle new parameter
4. **Add UI Control** in `ConfigPanel` (slider, toggle, etc.)
5. **Update Documentation** in tooltips and `docs/physics-tuning-guide.md`

### Debugging API Issues

1. **Check Backend Logs:** Look for PHP errors in console
2. **Check Network Tab:** Inspect request/response in browser DevTools
3. **Verify CORS:** Ensure CORS middleware is active
4. **Test with curl:**
   ```bash
   curl -X POST http://localhost:8080/api/calculate/physics \
     -H "Content-Type: application/json" \
     -d '{"baseMass":100,"originalCargo":50,...}'
   ```
5. **Check DTO Validation:** Ensure request matches expected structure

---

## Troubleshooting

### Backend Issues

#### "Class not found" Errors

**Cause:** Autoloader not finding the class.

**Solution:**
```bash
cd gui/backend
composer dump-autoload
```

Ensure the namespace matches the directory structure.

#### "Call to undefined function" Errors

**Cause:** Parent project autoloader not loaded.

**Solution:** Verify `public/index.php` loads both autoloaders:
```php
require_once __DIR__ . '/../../../vendor/autoload.php'; // Parent
require_once __DIR__ . '/../vendor/autoload.php';        // Backend
```

#### CORS Errors

**Cause:** Frontend origin not allowed.

**Solution:** Check `gui/backend/src/API/Middleware/CorsMiddleware.php` allows `http://localhost:5173`.

### Frontend Issues

#### "Module not found" Errors

**Cause:** Dependency not installed or import path incorrect.

**Solution:**
```bash
cd gui/frontend
npm install
```

Verify import paths use correct relative paths.

#### Type Errors

**Cause:** Type mismatch between backend DTO and frontend type.

**Solution:** Compare `gui/backend/src/DTOs/*.php` with `gui/frontend/src/types/*.d.ts`. Ensure field names and types match exactly.

#### API Calls Fail

**Cause:** Backend not running or proxy misconfigured.

**Solution:**
1. Verify backend is running: `curl http://localhost:8080/api/ships/types`
2. Check `vite.config.ts` proxy configuration:
   ```typescript
   server: {
     proxy: {
       '/api': 'http://localhost:8080'
     }
   }
   ```

### Performance Issues

#### Slow API Response

**Cause:** PhysicsCalculator doing expensive calculations.

**Solution:**
- Check if unnecessary recalculations happen
- Optimize PhysicsCalculator logic
- Add backend caching (in-memory cache for ship data)

#### Excessive API Calls

**Cause:** Debounce not working or state changes triggering unnecessary calls.

**Solution:**
- Verify debounce is active in `usePhysicsCalculation` (300ms)
- Check `useEffect` dependencies to avoid infinite loops
- Use React DevTools to inspect re-renders

---

## Development Best Practices

### 1. Keep Components Small

Each component should have a **single responsibility**. If a component exceeds 150 lines, consider splitting it.

### 2. Extract Business Logic to Hooks

Don't put API calls or complex logic directly in components. Use custom hooks:

```typescript
// ✅ Good
const { result, loading, calculate } = usePhysicsCalculation();

// ❌ Bad
const [result, setResult] = useState();
const calculate = async () => {
  // ... API call logic in component
};
```

### 3. Type Everything

Use TypeScript's type system fully:

```typescript
// ✅ Good
const [config, setConfig] = useState<BuildConfig | null>(null);

// ❌ Bad
const [config, setConfig] = useState(null);
```

### 4. Handle Loading and Error States

Every async operation should handle loading and error states:

```typescript
if (loading) return <Spinner />;
if (error) return <ErrorMessage error={error} />;
return <Results data={result} />;
```

### 5. Document Complex Logic

Add comments for non-obvious logic:

```php
// Calculate the effective reduction percentage based on the tier system.
// Tiers are selected by finding the highest maxMultiplier that doesn't
// exceed the current cargo multiplier.
$tier = $this->findTierForMultiplier($tiers, $multiplier);
```

### 6. Test Edge Cases

Write tests for edge cases:
- Zero values
- Negative values (if validation allows)
- Maximum values
- Empty arrays
- Null values

---

## Resources

### Documentation

- [PHP Documentation](https://www.php.net/docs.php)
- [Slim Framework Documentation](https://www.slimframework.com/docs/)
- [React Documentation](https://react.dev/)
- [TypeScript Handbook](https://www.typescriptlang.org/docs/handbook/intro.html)
- [TailwindCSS Documentation](https://tailwindcss.com/docs)
- [Vite Documentation](https://vite.dev/)

### Project-Specific

- [ARCHITECTURE.md](ARCHITECTURE.md) - System design and patterns
- [API.md](API.md) - API endpoint documentation
- [README.md](../README.md) - Setup and usage guide
- [physics-tuning-guide.md](../../docs/physics-tuning-guide.md) - Physics parameter explanations

### Tools

- [Composer](https://getcomposer.org/) - PHP dependency management
- [npm](https://www.npmjs.com/) - Node.js package manager
- [PHPUnit](https://phpunit.de/) - PHP testing framework
- [PHPStan](https://phpstan.org/) - PHP static analysis
- [ESLint](https://eslint.org/) - JavaScript/TypeScript linting

---

## Getting Help

### Issues and Bugs

- **Check existing issues:** [GitHub Issues](https://github.com/yourusername/x4-mod-cargo-sizes/issues)
- **Report new bugs:** Use the bug report template
- **Request features:** Use the feature request template

### Discussion

- **GitHub Discussions:** For questions and ideas
- **Discord/Forum:** (if available)

### Contact

- **Maintainer:** Sebastian Mordziol
- **Email:** s.mordziol@mistralys.eu
- **GitHub:** [@Mistralys](https://github.com/Mistralys)

---

**Happy Coding!**

Last Updated: February 18, 2026
