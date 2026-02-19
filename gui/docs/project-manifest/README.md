# X4 Cargo Sizes Mod - Physics Tuning GUI - Project Manifest

> **Version:** 1.5  
> **Last Updated:** February 19, 2026  
> **Purpose:** Source of Truth for AI Agents

---

## 🎯 What is This?

This is the **Project Manifest** for the X4 Cargo Sizes Mod Physics Tuning GUI. These documents provide a comprehensive reference for AI agents and developers to understand the codebase architecture, patterns, constraints, and APIs **without reading every line of implementation code**.

---

## 📖 Manifest Documents

Read these documents in order when onboarding to the project:

| Priority | Document | Purpose | When to Use |
|----------|----------|---------|-------------|
| **1** | [tech-stack.md](tech-stack.md) | Runtime, dependencies, architectural patterns | **Understanding system architecture** |
| **2** | [constraints.md](constraints.md) | Non-negotiable rules and conventions | **BEFORE writing any code** |
| **3** | [file-tree.md](file-tree.md) | Complete directory structure | **Locating files quickly** |
| **4** | [public-api.md](public-api.md) | All public signatures (NO implementations) | **Finding methods, understanding contracts** |
| **5** | [data-flows.md](data-flows.md) | How data moves through the system | **Implementing features, debugging** |

---

## 🚀 Quick Start for AI Agents

### Recommended Reading Path

```
Step 1: Read tech-stack.md
        ↓ Understand: Architecture, patterns, dependencies
        
Step 2: Read constraints.md
        ↓ Know: What's REQUIRED and what's FORBIDDEN
        
Step 3: Check file-tree.md as needed
        ↓ Locate: Specific files without searching
        
Step 4: Reference public-api.md
        ↓ Find: Method signatures without reading source
        
Step 5: Study data-flows.md
        ↓ Understand: How data moves through the system
```

**Estimated Onboarding Time:** 15-20 minutes

---

## 🎮 Project Overview

### What is This GUI?

An **interactive web-based tool** for real-time physics tuning of the X4 Cargo Sizes Mod. It allows users to:

- **Configure Physics Parameters:** Acceleration responsiveness tuning
- **Test in Real-Time:** See results within 500ms (300ms debounce + API call)
- **Browse Game Data:** Ships, engines, thrust specifications
- **Save Configurations:** Update `build-config.json` for use with `composer build`

### Architecture at a Glance

```
┌─────────────────────────────────────────────────┐
│  Frontend (React 18 + TypeScript + Vite)       │
│  http://localhost:5173                          │
│  • User interactions                            │
│  • State management (hooks)                     │
│  • Results visualization                        │
└─────────────────────────────────────────────────┘
              ↓ JSON over HTTP
┌─────────────────────────────────────────────────┐
│  Backend (PHP 8.4 + Slim Framework 4)          │
│  http://localhost:8080                         │
│  • REST API endpoints                           │
│  • Service layer (wraps business logic)         │
│  • File I/O (config/build-config.json)         │
└─────────────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────────────┐
│  Core Business Logic (Parent Mod)              │
│  • PhysicsCalculator                            │
│  • X4 Core library integration                  │
└─────────────────────────────────────────────────┘
```

---

## 📊 Project Statistics

### Core Metrics

- **Frontend:** React 19.2, TypeScript 5.9.3 (strict mode), Vite 7.3.1
- **Backend:** PHP 8.4, Slim Framework 4, Composer 2.x, PHPStan 1.6.1+ (level 5)
- **Testing:** PHPUnit 12.5+ (49 tests, 986 assertions, <0.6s execution)
- **Architecture:** Stateless REST API with reactive frontend
- **Components:** ~15 React components, 3 backend services, 12 API endpoints
- **Total Lines (est.):** ~3,500 across frontend/backend
- **Dev Servers:** 2 (Frontend: 5173, Backend: 8080)

### Domain Organization

- **Backend Services:** 3 (PhysicsService, ShipDataService, ConfigService)
- **Frontend Hooks:** 3 (usePhysicsCalculation, useShipData, useConfig)
- **API Endpoints:** 12 (physics, bulk calc, ships, engines, config)
- **DTOs:** 5 (PhysicsRequest, PhysicsResponse, ShipDetails, EnginePerformance, ValidationResult)
- **React Components:** ~14 (ConfigPanel, ResultsPanel, ShipSelector, etc.)

---

## 🛡️ Core Philosophy

### 1. **Documentation First, Code Second**

The Project Manifest is the authoritative source. Agents MUST consult these documents before reading implementation code.

### 2. **Type Safety Everywhere**

- **Backend:** PHP 8.4 `declare(strict_types=1)`, readonly properties
- **Frontend:** TypeScript strict mode, no implicit `any`

### 3. **Contract-First API Design**

All API contracts are defined as type-safe DTOs mirroring frontend TypeScript types exactly.

### 4. **Local Development Only**

This is a **development tool**, not a production web app. No authentication, no multi-user concerns.

### 5. **Integrate, Don't Duplicate**

Reuse the parent mod's business logic (PhysicsCalculator, etc.) rather than reimplementing calculations.

---

## 🔍 Quick Reference Commands

### Starting the GUI

```bash
# Windows
cd gui
start-dev.bat

# Linux/Mac
cd gui
./start-dev.sh
```

### Stopping the GUI

```bash
# Linux/Mac
cd gui
./stop-dev.sh

# Windows
Ctrl+C in both terminal windows
```

### Installing Dependencies

```bash
# From project root
composer gui:install

# Or manually
cd gui/backend && composer install
cd gui/frontend && npm install
```

### Building Frontend for Production

```bash
cd gui/frontend
npm run build
```

---

## 🎓 Success Criteria

An agent has successfully integrated with this codebase when:

- ✅ Can navigate to any file using [file-tree.md](file-tree.md)
- ✅ Can find any method signature using [public-api.md](public-api.md)
- ✅ Knows all constraints from [constraints.md](constraints.md)
- ✅ Understands data flow from [data-flows.md](data-flows.md)
- ✅ Follows patterns from [tech-stack.md](tech-stack.md)
- ✅ Can run both dev servers successfully
- ✅ Writes code indistinguishable from existing codebase

---

## 📚 Additional Resources

### Related Documentation

- [API.md](../API.md) - Complete REST API reference (retained for detailed specs)
- [ARCHITECTURE.md](../ARCHITECTURE.md) - Detailed architecture documentation (retained for deep dives)
- [DEVELOPMENT.md](../DEVELOPMENT.md) - Development workflow and testing (retained for contributor guidelines)

### Parent Project

- [Project Root README](../../../README.md) - Main X4 Cargo Sizes Mod
- [Parent Project Manifest](../../../docs/agents/project-manifest/) - Core mod manifest
- [AGENTS.md](../../../AGENTS.md) - AI Agent operating system for parent project

### External Links

- [X4 Core Library](https://github.com/Mistralys/x4-core) - Game data access
- [Slim Framework](https://www.slimframework.com/) - PHP micro-framework
- [React Documentation](https://react.dev/) - React 18 reference
- [TypeScript Handbook](https://www.typescriptlang.org/docs/) - TypeScript guide

---

## 🔄 Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.5 | Feb 19, 2026 | Removed tier/drag/inertia/jerk UI and tests (WP-007/WP-008). Backend: 49 tests/986 assertions. Frontend: TierEditor deleted, acceleration-only schema. |
| 1.4 | Feb 18, 2026 | PHPStan integration (level 5), test count increased to 61 tests/1,028 assertions, DI refactoring, corrected framework versions (React 19.2, TypeScript 5.9.3, Vite 7.3.1) |
| 1.3 | Feb 17, 2026 | Added PHPUnit 12.5+ test infrastructure (25+ tests) |
| 1.0 | Feb 12, 2026 | Initial Project Manifest creation |

---

## 💡 Manifest Philosophy

### Why This Exists

Traditional codebases require AI agents to:
- ❌ Read 40+ source files to understand architecture
- ❌ Grep through directories to find files
- ❌ Infer patterns from inconsistent implementation
- ❌ Guess at constraints and conventions

This manifest provides:
- ✅ Architecture understanding in minutes, not hours
- ✅ Direct file paths without searching
- ✅ Explicit patterns and constraints
- ✅ Contract-first API reference

### Token Efficiency

Reading the manifest (~15-20 minutes) replaces reading dozens of source files (~4+ hours). This is a **12x efficiency gain** for AI agents.

### Maintenance Contract

**CRITICAL:** When making code changes, you **MUST** update the corresponding manifest documents. Future agents depend on this accuracy.

---

## 🏗️ Development Quick Reference

### Common Tasks

| Task | Command | Reference |
|------|---------|-----------|
| **Start GUI** | `composer gui:start-win` (Windows) or `./gui/start-dev.sh` | [constraints.md](constraints.md) |
| **Install Dependencies** | `composer gui:install` | [tech-stack.md](tech-stack.md) |
| **Build Frontend** | `cd gui/frontend && npm run build` | [tech-stack.md](tech-stack.md) |
| **Run Tests** | `cd gui/backend && composer test` | [DEVELOPMENT.md](../DEVELOPMENT.md#testing) |
| **Lint Code** | `cd gui/frontend && npm run lint` | [constraints.md](constraints.md) |

---

**Remember:** This manifest represents careful architectural decisions. Respect it, follow it, and update it. Future agents (and humans) depend on it.

