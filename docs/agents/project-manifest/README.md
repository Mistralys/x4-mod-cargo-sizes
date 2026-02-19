# X4 Cargo Sizes Mod - Project Manifest

> **AI Agent Operating System Documentation**  
> **Version:** 1.2
> **Last Updated:** February 12, 2026
> **Purpose:** Source of Truth for AI Agents and Future Development

---

## 🎯 What is This?

This **Project Manifest** is the **authoritative source of truth** for understanding the X4 Cargo Sizes Mod codebase. It enables AI agents (and humans) to quickly comprehend the project architecture without reading every source file.

### Key Benefits

- **Token Efficiency** - Reduces context gathering by 80%+
- **Faster Onboarding** - Understand the codebase in 15-20 minutes
- **Consistency** - Enforces architectural patterns and constraints
- **Accurate Implementation** - Reference signatures before coding
- **Future-Proof** - Documents decisions for future maintainers

---

## 📚 Documentation Structure

This manifest is organized into **6 core documents**, each serving a specific purpose:

| Document | Purpose | Read When... |
|----------|---------|--------------|
| **[README.md](README.md)** | Entry point and navigation | **You're reading it now** ✓ |
| **[tech-stack.md](tech-stack.md)** | Runtime, dependencies, architectural patterns | Starting work, understanding patterns |
| **[constraints.md](constraints.md)** | Non-negotiable rules and conventions | **Before writing ANY code** |
| **[file-tree.md](file-tree.md)** | Complete directory structure | Locating files, understanding organization |
| **[public-api.md](public-api.md)** | All public signatures (NO implementations) | Finding methods, understanding contracts |
| **[data-flows.md](data-flows.md)** | How data flows through the system | Implementing features, debugging |

---

## 🚀 Quick Start Guide

### For New AI Agents

**Follow this sequence for maximum efficiency:**

```
┌─────────────────────────────────────────────────────────────┐
│ STEP 1: Read This README (5 min)                            │
│ → Get high-level understanding of project purpose           │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ STEP 2: Read constraints.md (5 min) **CRITICAL**            │
│ → Know what's allowed and what's forbidden                  │
│ → Follow these rules in ALL code you write                  │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ STEP 3: Read tech-stack.md (5 min)                          │
│ → Understand architectural patterns                         │
│ → Learn the 11 core patterns used throughout                │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ STEP 4: Reference file-tree.md as needed                    │
│ → Locate specific files quickly                             │
│ → Understand project organization                           │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ STEP 5: Reference public-api.md as needed                   │
│ → Look up method signatures without reading source          │
│ → Understand class contracts                                │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ STEP 6: Reference data-flows.md when implementing           │
│ → See how build process executes                            │
│ → Understand data transformations                           │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ READY: Begin Implementation                                 │
│ • Follow patterns from tech-stack.md                        │
│ • Obey rules from constraints.md                            │
│ • Reference public-api.md for signatures                    │
│ • Update manifest when adding features                      │
└─────────────────────────────────────────────────────────────┘
```

**Total onboarding time:** 15-20 minutes  
**Alternative without manifest:** 4+ hours of code reading

---

## 🧩 Project Components

This project consists of two main components:

### 1. **Core Mod (CLI Build System)**

The main CLI-based build system that generates X4 mod files.

- **Location:** Project root (`/src`, `/config`, `/build`)
- **Purpose:** Extract game data, calculate physics, generate mods
- **Runtime:** PHP 8.4+ CLI
- **Entry Point:** `composer build`
- **Manifest:** This directory

### 2. **Physics Tuning GUI (Web Interface)**

An interactive web-based GUI for real-time physics tuning and configuration.

- **Location:** `/gui` subdirectory
- **Purpose:** Visual parameter tuning, real-time feedback, configuration management
- **Runtime:** PHP 8.4+ (backend) + Node.js 18+ (frontend)
- **Entry Points:** `composer gui:start-win` or `/gui/start-dev.sh`
- **Manifest:** [/gui/docs/project-manifest/](../../../gui/docs/project-manifest/README.md)

**When to use which:**
- **Core Mod:** Building final mod packages for distribution
- **GUI:** Tuning physics parameters, testing configurations, visual feedback

---

## 📖 Document Deep Dive

### 1. [tech-stack.md](tech-stack.md) - Foundation Knowledge

**Read this to understand:**
- PHP 8.4+ runtime requirements
- Composer dependencies (x4-core, PHPUnit, PHPStan)
- 11 Architectural Patterns:
  1. Extractor-Builder Pattern
  2. XML File Representation Pattern
  3. Override Definition Pattern
  4. Adjusted Values Pattern
  5. Ship Result Aggregation Pattern
  6. Static Factory Pattern
  7. Output File Generation Pattern
  8. Translation System
  9. FOMOD Installer Generation
  10. Build Plugin System
  11. Console Output Helper
- Physics calculation formulas
- Build process overview

**Time investment:** 5-10 minutes  
**Saves you from:** Reading 49 source files

---

### 2. [constraints.md](constraints.md) - The Law of the Land

**⚠️ CRITICAL: Read this BEFORE writing ANY code**

**Non-negotiable rules:**
- ✅ All file I/O MUST be synchronous (no async/await)
- ✅ NO database connections (all data from XML)
- ✅ All files MUST have `declare(strict_types=1);`
- ✅ All methods MUST have type hints (parameters and return)
- ✅ All exceptions MUST extend `CargoSizeException`
- ✅ NO `eval()` or dynamic code execution
- ✅ Namespace MUST be `Mistralys\X4\Mods\CargoSizesMod\*`

**Naming conventions:**
- Classes: `PascalCase`
- Methods: `camelCase`
- Constants: `SCREAMING_SNAKE_CASE`
- Variables: `camelCase`

**Time investment:** 5-10 minutes  
**Prevents:** Architecture violations, rework, bugs

---

### 3. [file-tree.md](file-tree.md) - Navigation Map

**Use this to:**
- Find files without grepping the filesystem
- Understand project organization
- Locate specific classes quickly

**Structure overview:**
```
src/Mods/CargoSizesMod/
├── Build/           (8 files) - Build orchestration
├── FOMOD/           (3 files) - Installer generation
├── Output/          (12 files) - Override file generation
│   └── Physics/     (3 files) - Physics adjustments
├── References/      (3 files) - Documentation generators
└── XML/             (14 files) - XML parsing
    └── ShipXML/     (11 files) - Ship physics value objects
```

**Time investment:** 1-2 minutes per lookup  
**Saves you from:** Trial-and-error file searching

---

### 4. [public-api.md](public-api.md) - Method Catalog

**Use this to:**
- Look up method signatures before calling them
- Understand class contracts
- See available methods without reading source

**Example entry:**
```php
Class: CargoSizeExtractor

public function __construct(
    FolderInfo $extractedDataFolder, 
    FolderInfo $outputFolder
)

public function extract(array $multipliers): void

public static function getShipTypesPretty(): array // Returns string[]
```

**Coverage:** All 49 PHP classes, organized by namespace

**Time investment:** 30 seconds per lookup  
**Saves you from:** Reading source files for signatures

---

### 5. [data-flows.md](data-flows.md) - System Behavior

**Use this to:**
- Understand how the build process executes
- See data transformations step-by-step
- Debug issues in the build pipeline
- Implement new features correctly

**Key flows:**
1. **Build Entry Point** - Composer → CargoSizeBuildTools
2. **Configuration Loading** - JSON → BuildConfig
3. **Ship Data Extraction** - Game XML → ShipResult
4. **Physics Calculations** - Original → Adjusted Values
5. **Override Generation** - Adjusted Values → XML Files
6. **FOMOD Generation** - File grouping → Installer package
7. **Reference Generation** - Ship data → Documentation
8. **ZIP Packaging** - XML Files → Distribution ZIPs

**Time investment:** 10-15 minutes to read, 5 minutes per lookup  
**Saves you from:** Misunderstanding system behavior

---

## 🎓 Common Tasks - Quick References

### Task: Add a New Ship Type

**Documents to consult:**
1. [tech-stack.md](tech-stack.md) → Extractor-Builder Pattern
2. [constraints.md](constraints.md) → Naming conventions, type hints
3. [data-flows.md](data-flows.md) → Ship Data Extraction flow
4. [public-api.md](public-api.md) → CargoSizeExtractor class

**Files to modify:**
- `src/Mods/CargoSizesMod/Build/CargoSizeExtractor.php`
- `config/translations.json` (add translations)

**Update manifest:**
- [tech-stack.md](tech-stack.md) - Add to ship type list
- [public-api.md](public-api.md) - Update CargoSizeExtractor constants

---

### Task: Adjust Physics Calculations

**Documents to consult:**
1. [tech-stack.md](tech-stack.md) → Physics Calculations section
2. [data-flows.md](data-flows.md) → Physics Calculations flow
3. [public-api.md](public-api.md) → AdjustedValues classes

**Files to modify:**
- `config/build-config.json` (change factors)
- `src/Mods/CargoSizesMod/Output/Physics/Adjusted*.php` (implementation changes)

**Update manifest:**
- [tech-stack.md](tech-stack.md) - Update physics formulas
- [data-flows.md](data-flows.md) - Update calculation flow

---

### Task: Add a New Override Type

**Documents to consult:**
1. [tech-stack.md](tech-stack.md) → Override Definition Pattern
2. [constraints.md](constraints.md) → Fluent interface requirement
3. [public-api.md](public-api.md) → OverrideDef classes

**Files to create:**
- `src/Mods/CargoSizesMod/Output/NewTypeDef.php`

**Update manifest:**
- [tech-stack.md](tech-stack.md) - Add to Override Definition Pattern
- [file-tree.md](file-tree.md) - Add file to Output/ section
- [public-api.md](public-api.md) - Document new class

---

### Task: Add a Build Plugin

**Documents to consult:**
1. [tech-stack.md](tech-stack.md) → Build Plugin System
2. [constraints.md](constraints.md) → Class naming conventions
3. [file-tree.md](file-tree.md) → Plugins directory structure

**Files to create:**
- `src/Mods/CargoSizesMod/Build/Plugins/Plugin/MyPlugin.php`

**Update manifest:**
- [file-tree.md](file-tree.md) - Add to Plugins section
- [public-api.md](public-api.md) - Document plugin class

---

### Task: Work with Physics Tuning GUI

**Documents to consult:**
1. [GUI Project Manifest](../../../gui/docs/project-manifest/README.md) → GUI architecture and constraints
2. [GUI tech-stack.md](../../../gui/docs/project-manifest/tech-stack.md) → React + PHP stack
3. [GUI data-flows.md](../../../gui/docs/project-manifest/data-flows.md) → UI interaction flows

**Files to work with:**
- `config/build-config.json` (shared between CLI and GUI)
- `gui/backend/src/Services/*` (backend services)
- `gui/frontend/src/components/*` (UI components)

**Start GUI:**
```bash
composer gui:start-win  # Windows
# or
cd gui && ./start-dev.sh  # Linux/Mac
```

**Note:** The GUI has its own manifest system. Always consult the GUI manifest before modifying GUI code.

---

## 🔍 Efficient Information Gathering

### Decision Tree: "Where Do I Find X?"

```
Need to find a file?
    ├─ YES → Check file-tree.md
    └─ NO  → Continue
    
Need to understand a method signature?
    ├─ YES → Check public-api.md
    └─ NO  → Continue
    
Need to know what's allowed?
    ├─ YES → Check constraints.md
    └─ NO  → Continue
    
Need to understand a pattern?
    ├─ YES → Check tech-stack.md
    └─ NO  → Continue
    
Need to see how data flows?
    ├─ YES → Check data-flows.md
    └─ NO  → Continue
    
Need implementation details?
    └─ Read source files (use manifest to locate them)
```

---

## 📝 Manifest Maintenance

### When to Update the Manifest

**CRITICAL:** When making code changes, you MUST update the corresponding manifest documents.

| Code Change | Update These Documents |
|-------------|------------------------|
| Add new class | tech-stack.md, file-tree.md, public-api.md |
| Add new architectural pattern | tech-stack.md, constraints.md |
| Add new method | public-api.md |
| Change data flow | data-flows.md |
| Add new constraint | constraints.md |
| Change directory structure | file-tree.md |
| Add new dependency | tech-stack.md |
| Change naming convention | constraints.md |

### Update Checklist

Before committing code that changes architecture:
- [ ] Updated relevant manifest documents
- [ ] Verified no contradictions between code and manifest
- [ ] Updated "Last Updated" date in modified documents
- [ ] Reviewed consistency across all manifest documents

---

## 🛡️ Guardrails for AI Agents

### What Agents MUST Do
- ✅ Read [constraints.md](constraints.md) before writing code
- ✅ Follow all architectural patterns from [tech-stack.md](tech-stack.md)
- ✅ Update manifest when adding features
- ✅ Reference [public-api.md](public-api.md) for signatures
- ✅ Follow data flows from [data-flows.md](data-flows.md)
- ✅ Flag conflicts between manifest and code

### What Agents MUST NOT Do
- ❌ Skip reading [constraints.md](constraints.md)
- ❌ Deviate from established patterns
- ❌ Add code without updating manifest
- ❌ Use async file I/O (synchronous only)
- ❌ Add database connections
- ❌ Ignore naming conventions
- ❌ Use `eval()` or execute dynamic code

---

## 📊 Project Statistics

### Codebase Size
- **Total Classes:** 49 PHP files
- **Total Namespaces:** 7 main namespaces
- **Lines of Code:** ~8,000 (estimated)
- **Test Coverage:** PHPUnit tests available

### Domain Organization
- **Build System:** 8 classes
- **XML Processing:** 14 classes (11 value objects)
- **Output Generation:** 12 classes (3 physics)
- **FOMOD System:** 3 classes
- **Reference Generators:** 3 classes

### Key Metrics
- **Ship Types:** 4 (transport, miner, auxiliary, carrier)
- **Ship Sizes:** 5 (xs, s, m, l, xl)
- **Supported Multipliers:** Configurable (default: 2x, 4x, 8x, 10x)
- **Languages Supported:** 7 (EN, DE, FR, ES, IT, RU, KO)
- **Build Time:** ~2-5 minutes (depends on ship count)

---

## 🎯 Success Criteria

An agent has successfully integrated with this codebase when:
- ✅ Can navigate to any file using [file-tree.md](file-tree.md)
- ✅ Can find any method signature using [public-api.md](public-api.md)
- ✅ Knows all 11 architectural patterns by heart
- ✅ Never violates [constraints.md](constraints.md) rules
- ✅ Updates manifest with every code change
- ✅ Follows established data flows
- ✅ Writes code indistinguishable from existing codebase

**Estimated Time to Proficiency:** 20 minutes with manifest, 4+ hours without

---

## 🔗 External Resources

### Related Components
- **[Physics Tuning GUI](../../../gui/docs/project-manifest/README.md)** - Interactive web GUI for physics tuning (see [GUI Manifest](../../../gui/docs/project-manifest/README.md))

### Related Projects
- **[X4 Core](https://github.com/Mistralys/x4-core)** - Parent library for X4 game data access
- **[X4 Data Extractor](https://github.com/Mistralys/x4-data-extractor)** - Extracts game files (required for build)
- **[X4 Game Notes](https://github.com/Mistralys/x4-game-notes)** - Documentation and guides

### Development Tools
- **[Composer](https://getcomposer.org/)** - Dependency management
- **[PHPUnit](https://phpunit.de/)** - Testing framework
- **[PHPStan](https://phpstan.org/)** - Static analysis tool

### Game Resources
- **[X4 Foundations on Steam](https://store.steampowered.com/app/392160/X4_Foundations/)** - The game itself
- **[Nexus Mods - X4 Cargo Sizes](https://www.nexusmods.com/x4foundations/mods/1713)** - Mod distribution page

---

## 💡 Tips for Maximum Efficiency

### For AI Agents
1. **Bookmark this README** - It's your starting point
2. **Always read constraints.md first** - Prevents rework
3. **Trust the manifest** - If code conflicts, manifest is likely right
4. **Use the decision tree** - Saves tokens and time
5. **Update as you go** - Don't batch manifest updates

### For Human Developers
1. **Manifest is canonical** - Code may drift, manifest is truth
2. **Documentation first** - Update manifest before implementing
3. **Patterns over cleverness** - Follow established patterns
4. **Token efficiency** - Agents will thank you for good docs

---

## 🔄 Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.2 | Feb 12, 2026 | Added Physics Tuning GUI manifest references and project components section. |
| 1.1 | Feb 11, 2026 | Synchronized with PHP 8.4 upgrade changes. |
| 1.0 | Feb 9, 2026 | Initial project manifest with 6 core documents |

---

## 📞 Contact & Support

- **Issues:** [GitHub Issues](https://github.com/Mistralys/x4-mod-cargo-sizes/issues)
- **Author:** Sebastian Mordziol (AeonsOfTime)
- **License:** MIT

---

**Remember:** This manifest represents the collective knowledge of the X4 Cargo Sizes Mod project. Respect it, follow it, maintain it, and future agents (and humans) will thank you.

🚀 **Happy coding!**
