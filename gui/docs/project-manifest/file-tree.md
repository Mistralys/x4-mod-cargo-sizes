# File Tree - Directory Structure

> **Version:** 1.3  
> **Last Updated:** February 16, 2026  
> **Purpose:** Visual directory structure for quick file location

---

## Overview

This document provides a complete visual map of the GUI's directory structure. Use this to quickly locate files without searching the filesystem.

---

## Complete Directory Tree

```
gui/
├── README.md                           # GUI overview and setup guide
├── start-dev.bat                       # Windows dev server start script
├── start-dev.sh                        # Linux/Mac dev server start script
├── stop-dev.sh                         # Linux/Mac dev server stop script
│
├── docs/                               # 📚 Documentation
│   ├── project-manifest/               # 🤖 AI Agent Project Manifest
│   │   ├── README.md                   # Manifest entry point (START HERE)
│   │   ├── tech-stack.md               # Runtime, dependencies, patterns
│   │   ├── constraints.md              # Rules and conventions
│   │   ├── file-tree.md                # This file
│   │   ├── public-api.md               # Public signature reference
│   │   └── data-flows.md               # Data flow diagrams
│   │
│   ├── API.md                          # Detailed REST API reference
│   ├── ARCHITECTURE.md                 # Detailed architecture docs
│   └── DEVELOPMENT.md                  # Development workflow guide
│
├── backend/                            # 🔧 PHP Backend (API Server)
│   ├── composer.json                   # PHP dependencies (Slim, etc.)
│   ├── composer.lock                   # Locked dependency versions
│   ├── phpunit.xml                     # PHPUnit configuration (NEW in v1.3)
│   │
│   ├── tests/                          # 🧪 Test suite (NEW in v1.3)
│   │   ├── bootstrap.php               # Test autoloader
│   │   ├── Unit/                       # Unit tests (fast, no external dependencies)
│   │   │   ├── Utils/
│   │   │   │   └── PhysicsCalculationHelperTest.php  # Trait unit tests
│   │   │   ├── Services/
│   │   │   │   └── ClassRangeServiceTest.php         # DI mocking demo
│   │   │   ├── API/
│   │   │   │   └── ServiceContainerTest.php          # Container tests
│   │   │   └── DTOs/
│   │   │       └── PhysicsResponseDataTest.php       # DTO tests
│   │   └── Integration/                # Integration tests (slower, test full flows)
│   │       └── Endpoints/
│   │           └── .gitkeep            # Placeholder for future endpoint tests
│   │
│   ├── public/                         # 🌐 Web server document root
│   │   └── index.php                   # API entry point, Slim bootstrap
│   │
│   ├── src/                            # 💻 Backend source code
│   │   ├── API/                        # API layer
│   │   │   ├── ServiceContainer.php    # DI container (NEW in v1.3)
│   │   │   ├── Router.php              # Route definitions
│   │   │   │
│   │   │   ├── Endpoints/              # API endpoint handlers
│   │   │   │   ├── PhysicsEndpoint.php # Physics calculation routes
│   │   │   │   ├── ClassRangeEndpoint.php # Class-range calculation routes
│   │   │   │   ├── ShipsEndpoint.php   # Ship/engine data routes
│   │   │   │   └── ConfigEndpoint.php  # Configuration routes
│   │   │   │
│   │   │   └── Middleware/             # HTTP middleware
│   │   │       └── CorsMiddleware.php  # CORS support
│   │   │
│   │   ├── Services/                   # 🔨 Service layer (business logic wrappers)
│   │   │   ├── PhysicsService.php      # Physics calculations
│   │   │   ├── ClassRangeService.php   # Class-wide aggregation service
│   │   │   ├── ShipDataService.php     # Ship/engine data access
│   │   │   └── ConfigService.php       # Configuration management
│   │   │
│   │   ├── Utils/                      # 🔧 Utilities (NEW in v1.2.0)
│   │   │   └── PhysicsCalculationHelper.php   # Shared calculation trait
│   │   │
│   │   ├── DTOs/                       # 📦 Data Transfer Objects
│   │   │   ├── PhysicsRequest.php      # Input contract for physics calc
│   │   │   ├── PhysicsResponse.php     # Output contract for physics calc
│   │   │   ├── EnginePerformance.php   # Engine performance data
│   │   │   ├── PhysicsData.php         # Physics values (drag/inertia/jerk)
│   │   │   ├── PhysicsResponseData.php # Parameter object (NEW in v1.3)
│   │   │   ├── ReductionTiers.php      # Reduction tier configuration
│   │   │   ├── ShipDetails.php         # Ship detail data
│   │   │   ├── ClassRangeRequest.php   # Input contract for class-range calc
│   │   │   ├── ClassRangeResponse.php  # Output contract for class-range calc
│   │   │   ├── RangeMetric.php         # Min/max/median range for a metric
│   │   │   ├── ShipMetricSummary.php   # Worst/best case ship summary
│   │   │   └── ValidationResult.php    # Config validation result
│   │   │
│   │   └── Exceptions/                 # ⚠️ Exception classes
│   │       └── GUIException.php        # GUI-specific exceptions
│   │
│   └── vendor/                         # 📚 Composer dependencies (auto-generated)
│       ├── autoload.php                # Composer autoloader
│       ├── slim/                       # Slim Framework
│       ├── nikic/                      # FastRoute
│       └── ...
│
└── frontend/                           # ⚛️ React Frontend (UI)
    ├── package.json                    # npm dependencies
    ├── package-lock.json               # Locked dependency versions
    ├── vite.config.ts                  # Vite build configuration
    ├── tsconfig.json                   # TypeScript configuration
    ├── tsconfig.app.json               # App-specific TS config
    ├── tsconfig.node.json              # Node-specific TS config
    ├── tailwind.config.js              # TailwindCSS configuration
    ├── postcss.config.js               # PostCSS configuration
    ├── eslint.config.js                # ESLint configuration
    ├── index.html                      # HTML entry point
    │
    ├── public/                         # 🌍 Static assets (served as-is)
    │   └── docs/                       # Public documentation
    │
    ├── src/                            # 💻 Frontend source code
    │   ├── main.tsx                    # React app entry point
    │   ├── App.tsx                     # Root app component
    │   ├── App.css                     # Root app styles
    │   ├── index.css                   # Global styles
    │   │
    │   ├── assets/                     # 🖼️ Static assets (images, etc.)
    │   │
    │   ├── components/                 # ⚛️ React components
    │   │   │
    │   │   ├── Layout/                 # Layout components
    │   │   │   ├── Header.tsx          # App header
    │   │   │   └── Footer.tsx          # App footer (if exists)
    │   │   │
    │   │   ├── ConfigPanel/            # Configuration panel components
    │   │   │   ├── ConfigPanel.tsx     # Main config panel
    │   │   │   ├── CargoMultiplierSlider.tsx
    │   │   │   ├── DragReductionSlider.tsx
    │   │   │   ├── InertiaSlider.tsx
    │   │   │   ├── TierEditor.tsx      # Tier configuration editor
    │   │   │   └── ...
    │   │   │
    │   │   ├── ShipSelector/           # Ship selection components
    │   │   │   ├── ShipSelector.tsx    # Main ship selector
    │   │   │   ├── ShipTypeDropdown.tsx
    │   │   │   ├── ShipList.tsx        # Ship list table
    │   │   │   └── EngineSelector.tsx  # Engine selection
    │   │   │
    │   │   ├── ResultsPanel/           # Results display components
    │   │   │   ├── ResultsPanel.tsx    # Main results panel
    │   │   │   ├── PhysicsOverview.tsx # Physics overview with absolute metrics
    │   │   │   ├── AbsoluteMetricCard.tsx # Absolute metric display card
    │   │   │   ├── ClassRangePanel.tsx # Class-wide range panel
    │   │   │   ├── WorstCaseCard.tsx   # Worst/best case ship display
    │   │   │   ├── MassRatioDisplay.tsx
    │   │   │   ├── DragComparison.tsx  # Drag before/after comparison
    │   │   │   ├── InertiaComparison.tsx
    │   │   │   ├── JerkComparison.tsx
    │   │   │   ├── EnginePerformanceDisplay.tsx
    │   │   │   └── ...
    │   │   │
    │   │   └── UI/                     # Reusable UI components
    │   │       ├── Button.tsx          # Button component
    │   │       ├── Input.tsx           # Input component
    │   │       ├── Slider.tsx          # Slider component
    │   │       ├── Card.tsx            # Card container
    │   │       ├── RangeBar.tsx        # Horizontal range bar component
    │   │       └── ...
    │   │
    │   ├── hooks/                      # 🎣 Custom React hooks
    │   │   ├── usePhysicsCalculation.ts # Physics calculation hook
    │   │   ├── useClassRange.ts        # Class-range calculation hook
    │   │   ├── useShipData.ts          # Ship data fetching hook
    │   │   └── useConfig.ts            # Configuration management hook
    │   │
    │   ├── services/                   # 🔌 API client services
    │   │   ├── api.ts                  # Main API client (axios-based)
    │   │   └── storage.ts              # LocalStorage utilities
    │   │
    │   ├── types/                      # 📝 TypeScript type definitions
    │   │   ├── physics.d.ts            # Physics-related types
    │   │   ├── ships.d.ts              # Ship/engine types
    │   │   └── config.d.ts             # Configuration types
    │   │
    │   ├── utils/                      # 🔧 Utility functions
    │   │   └── metricContext.ts        # Contextual phrases for physics metrics
    │   │
    │   └── styles/                     # 🎨 Shared styles
    │       └── globals.css             # Global CSS (Tailwind imports)
    │
    └── node_modules/                   # 📚 npm dependencies (auto-generated)
```

---

## Key Directories Explained

### Backend Structure

| Directory | Purpose | Key Files |
|-----------|---------|-----------|
| `backend/public/` | Web server document root | `index.php` (Slim entry point) |
| `backend/src/API/` | API layer (routing, middleware) | `Router.php`, `CorsMiddleware.php`, `ServiceContainer.php` |
| `backend/src/Services/` | Business logic wrappers | `PhysicsService.php`, `ShipDataService.php`, `ConfigService.php`, `ClassRangeService.php` |
| `backend/src/Utils/` | Shared utilities (traits) | `PhysicsCalculationHelper.php` (since 1.2.0) |
| `backend/src/DTOs/` | Type-safe data contracts | `PhysicsRequest.php`, `PhysicsResponse.php`, `PhysicsResponseData.php`, `ClassRangeRequest.php` |
| `backend/src/Exceptions/` | Exception classes | `GUIException.php` |
| `backend/tests/` | Test suite | `Unit/`, `Integration/` (since 1.3.0) |

### Frontend Structure

| Directory | Purpose | Key Files |
|-----------|---------|-----------|
| `frontend/src/components/` | React components | `ConfigPanel.tsx`, `ResultsPanel.tsx`, `ShipSelector.tsx` |
| `frontend/src/hooks/` | Custom React hooks | `usePhysicsCalculation.ts`, `useShipData.ts`, `useConfig.ts` |
| `frontend/src/services/` | API client | `api.ts` (axios-based) |
| `frontend/src/types/` | TypeScript types | `physics.d.ts`, `ships.d.ts`, `config.d.ts` |

### Documentation Structure

### Documentation Structure

| File | Purpose |
|------|---------|
| `docs/project-manifest/README.md` | **Manifest entry point** (start here!) |
| `docs/project-manifest/tech-stack.md` | Tech stack and patterns |
| `docs/project-manifest/constraints.md` | Rules and conventions |
| `docs/project-manifest/file-tree.md` | This file (directory structure) |
| `docs/project-manifest/public-api.md` | Public API signatures |
| `docs/project-manifest/data-flows.md` | Data flow diagrams |
| `docs/API.md` | Detailed REST API reference |
| `docs/ARCHITECTURE.md` | Detailed architecture docs |
| `docs/DEVELOPMENT.md` | Development workflow guide |

---

## File Naming Conventions

### Backend (PHP)

- **Classes:** `PascalCase.php` (e.g., `PhysicsService.php`)
- **One class per file:** Filename matches class name
- **Interfaces:** `PascalCaseInterface.php` (if used)

### Frontend (TypeScript/React)

- **Components:** `PascalCase.tsx` (e.g., `ConfigPanel.tsx`)
- **Hooks:** `camelCase.ts` starting with `use` (e.g., `usePhysicsCalculation.ts`)
- **Services:** `camelCase.ts` (e.g., `api.ts`)
- **Types:** `camelCase.d.ts` (e.g., `physics.d.ts`)

---

## Entry Points

### Backend Entry Point

**File:** `backend/public/index.php`

```php
<?php
// Slim Framework bootstrap
// Loads router, registers middleware, starts server
```

**How to start:**
```bash
cd gui/backend
php -S localhost:8080 -t public
```

### Frontend Entry Point

**File:** `frontend/src/main.tsx`

```typescript
// React app bootstrap
// Renders <App /> into DOM
```

**How to start:**
```bash
cd gui/frontend
npm run dev
```

---

## Configuration Files

### Backend Configuration

| File | Purpose |
|------|---------|
| `backend/composer.json` | PHP dependency management |
| `backend/composer.lock` | Locked PHP dependency versions |

### Frontend Configuration

| File | Purpose |
|------|---------|
| `frontend/package.json` | npm dependency management |
| `frontend/vite.config.ts` | Vite build tool configuration |
| `frontend/tsconfig.json` | TypeScript base configuration |
| `frontend/tsconfig.app.json` | App-specific TypeScript config |
| `frontend/tailwind.config.js` | TailwindCSS theme/configuration |
| `frontend/eslint.config.js` | ESLint linting rules |
| `frontend/postcss.config.js` | PostCSS configuration |

---

## Build Output Directories

### Frontend Build Output

**Directory:** `frontend/dist/` (created by `npm run build`)

**Contains:**
- Optimized static assets (HTML, CSS, JS)
- Ready for deployment (though not used in this local dev tool)

**Note:** `dist/` is gitignored and should not be committed.

---

## gitignore

Key patterns ignored by version control:

```
# Dependencies
backend/vendor/
frontend/node_modules/

# Build outputs
frontend/dist/

# Environment files
.env
dev-config.php

# IDE files
.vscode/
.idea/
```

---

## Quick File Lookup

### Need to find...

| What | Where |
|------|-------|
| **Physics calculation logic** | `backend/src/Services/PhysicsService.php` |
| **API route definitions** | `backend/src/API/Router.php` |
| **API endpoint handlers** | `backend/src/API/Endpoints/*.php` |
| **Configuration management** | `backend/src/Services/ConfigService.php` |
| **Physics API client** | `frontend/src/services/api.ts` (physicsApi) |
| **Physics result display** | `frontend/src/components/ResultsPanel/*.tsx` |
| **Configuration UI** | `frontend/src/components/ConfigPanel/*.tsx` |
| **Ship selection UI** | `frontend/src/components/ShipSelector/*.tsx` |
| **Custom React hooks** | `frontend/src/hooks/*.ts` |
| **TypeScript type definitions** | `frontend/src/types/*.d.ts` |
| **PHP DTOs** | `backend/src/DTOs/*.php` |
| **CORS middleware** | `backend/src/API/Middleware/CorsMiddleware.php` |
| **Exception classes** | `backend/src/Exceptions/GUIException.php` |

---

## Scripts and Utilities

### Root Scripts

| Script | Location | Purpose |
|--------|----------|---------|
| `start-dev.bat` | `gui/start-dev.bat` | Start both servers (Windows) |
| `start-dev.sh` | `gui/start-dev.sh` | Start both servers (Linux/Mac) |
| `stop-dev.sh` | `gui/stop-dev.sh` | Stop both servers (Linux/Mac) |

### Composer Scripts (from project root)

```bash
composer gui:install     # Install all dependencies
composer gui:start-win   # Start GUI (Windows)
```

### npm Scripts (from frontend/)

```bash
npm run dev      # Start Vite dev server
npm run build    # Build for production
npm run preview  # Preview production build
npm run lint     # Run ESLint
```

---

## Parent Project Integration

### Files NOT in gui/ (used by backend)

The GUI integrates with the parent mod's business logic:

| Parent File | Used By | Purpose |
|-------------|---------|---------|
| `src/Mods/CargoSizesMod/Output/Physics/PhysicsCalculator.php` | `PhysicsService` | Core physics calculations |
| `src/Mods/CargoSizesMod/Output/Physics/AdjustedDrag.php` | `PhysicsService` | Drag adjustments |
| `src/Mods/CargoSizesMod/Output/Physics/AdjustedInertia.php` | `PhysicsService` | Inertia adjustments |
| `src/Mods/CargoSizesMod/Output/Jerk/AdjustedJerk.php` | `PhysicsService` | Jerk adjustments |
| `src/Mods/CargoSizesMod/Build/ReductionTier.php` | `PhysicsService` | Tier-based reductions |
| `config/build-config.json` | `ConfigService` | Build configuration file |

---

**End of File Tree Documentation**

