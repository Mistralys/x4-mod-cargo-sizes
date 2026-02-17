# AI Agent Operating System - X4 Cargo Sizes Mod

> **Version:** 1.3
> **Last Updated:** February 17, 2026
> **Purpose:** Source of Truth and Operating Procedures for AI Agents

---

## 🎯 Core Philosophy

### 1. **Manifest First, Code Second**
The **Project Manifest** is the authoritative source of truth. Agents MUST consult documentation before reading implementation code. This saves tokens and ensures architectural consistency.

### 2. **Context Efficiency**
Use the manifest and file-tree to minimize unnecessary file system searches. Every token counts.

### 3. **High Integrity**
If code contradicts the manifest, the **code is likely wrong** and should be flagged for correction.

---

## 📚 Project Manifest - Start Here!

### 🎯 Location
`/docs/agents/project-manifest/`

### 📖 Manifest Documents (Read in Order)

| Priority | Document | Purpose | When to Use |
|----------|----------|---------|-------------|
| **1** | [README.md](docs/agents/project-manifest/README.md) | Entry point, navigation, quick reference | **ALWAYS START HERE** |
| **2** | [constraints.md](docs/agents/project-manifest/constraints.md) | Non-negotiable rules and conventions | **BEFORE writing any code** |
| **3** | [tech-stack.md](docs/agents/project-manifest/tech-stack.md) | Runtime, dependencies, architectural patterns | Understanding patterns before coding |
| **4** | [public-api.md](docs/agents/project-manifest/public-api.md) | All public signatures (NO implementations) | Finding methods, understanding APIs |
| **5** | [file-tree.md](docs/agents/project-manifest/file-tree.md) | Complete directory structure | Locating files, understanding organization |
| **6** | [data-flows.md](docs/agents/project-manifest/data-flows.md) | How data moves through the system | Implementing features, debugging flows |

---

## 🚀 Quick Start Workflow

### For New Agents Entering the Codebase

```
┌─────────────────────────────────────────────────────────────┐
│ STEP 1: Read Project Manifest README                        │
│ Location: /docs/agents/project-manifest/README.md           │
│ Time: 3-5 min                                               │
│ Output: High-level understanding of the project             │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ STEP 2: CRITICAL - Internalize Constraints                 │
│ Document: constraints.md                                    │
│ Focus: File I/O (sync only), No databases, Strict types    │
│ Time: 5-7 min                                               │
│ Output: Know what's FORBIDDEN and what's REQUIRED          │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ STEP 3: Understand Architectural Patterns                   │
│ Document: tech-stack.md                                     │
│ Focus: Extractor-Builder, XML File Representation, Build   │
│ Time: 5 min                                                 │
│ Output: Know the 11 core patterns used throughout          │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ STEP 4: Reference Public API as Needed                     │
│ Document: public-api.md                                     │
│ Usage: Lookup method signatures without reading source     │
│ Output: Know what methods exist and their contracts        │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ STEP 5: Locate Files Using File Tree                       │
│ Document: file-tree.md                                      │
│ Usage: Find files without grepping the filesystem          │
│ Output: Direct paths to relevant files                     │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ STEP 6: Understand Build Process                           │
│ Document: data-flows.md                                     │
│ Usage: See how XML → Extraction → Building → Output flows  │
│ Output: Mental model of build system                       │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ READY: Begin Implementation                                 │
│ • Follow patterns from tech-stack.md                       │
│ • Obey rules from constraints.md                           │
│ • Reference public-api.md for signatures                   │
│ • Update manifest when adding features                     │
└─────────────────────────────────────────────────────────────┘
```

**Total Onboarding Time:** 15-20 minutes  
**Token Efficiency:** High (avoids reading 40+ source files)

---

## 🎮 Project Context

### What is This?

**X4 Cargo Sizes Mod** is a **build tool** and **mod generator** for the X4: Foundations space simulation game. The project consists of two main components:

#### 1. Core Mod (CLI Build System)
A command-line tool for building X4 mod packages using PHP 8.4+.

**Features:**
- Extract XML ship definitions from X4 game files
- Calculate cargo size multipliers (2x, 4x, 8x, 10x, etc.)
- Adjust flight mechanics to compensate for increased mass
- Generate mod XML files that override game defaults
- Package mods into distributable formats (ZIP, FOMOD installer)

**Manifest:** `/docs/agents/project-manifest/` (this directory)

#### 2. Physics Tuning GUI (Web Interface)
An interactive web-based GUI for real-time physics parameter tuning.

**Features:**
- Visual configuration with sliders and forms
- Real-time physics calculation feedback (<500ms)
- Ship and engine data browsing
- Configuration management (read/write `build-config.json`)
- Used for tuning parameters before running CLI build

**Location:** `/gui` subdirectory  
**Manifest:** `/gui/docs/project-manifest/` → [GUI Manifest](gui/docs/project-manifest/README.md)  
**Start:** `composer gui:start-win` or `cd gui && ./start-dev.sh`

**Typical Workflow:**
1. Use GUI to tune physics parameters visually
2. Save configuration to `build-config.json`
3. Run CLI build: `composer build`
4. Test mod in X4 game
5. Iterate as needed

### Core Purpose (CLI Build System)

1. Extract XML ship definitions from X4 game files
2. Calculate cargo size multipliers (2x, 4x, 8x, 10x, etc.)
3. Adjust flight mechanics to compensate for increased mass
4. Generate mod XML files that override game defaults
5. Package mods into distributable formats (ZIP, FOMOD installer)

### Tech Stack Quick Reference

**Core Mod (CLI):**
- **Language:** PHP 8.4+
- **CLI Tool:** Yes (Composer scripts)
- **Database:** No (XML files only)
- **Dependencies:** mistralys/x4-core library
- **Build System:** Custom Extractor-Builder pattern
- **Output Format:** X4 game mod files (XML)

**Physics Tuning GUI:**
- **Backend:** PHP 8.4+ with Slim Framework 4 (REST API)
- **Frontend:** React 18 + TypeScript + Vite + TailwindCSS v4
- **Architecture:** Stateless REST API with reactive frontend
- **Ports:** Backend (8080), Frontend (5173)
- **Shared Config:** `config/build-config.json`

### Project Type

```
Build Tool / Mod Generator
├── Input:  Extracted X4 game XML files
├── Process: Calculate, adjust, transform ship data
└── Output: Mod packages ready for game installation
```

---

## 📝 Manifest Maintenance Rules

**CRITICAL:** When making code changes, you MUST update the corresponding manifest documents. Failure to do so breaks the contract with future agents.

### Change → Document Mapping Table

| Code Change | Documents to Update | Specific Sections |
|-------------|---------------------|-------------------|
| **Add new Builder class** | `tech-stack.md`, `public-api.md`, `file-tree.md`, `data-flows.md` | Build System Pattern, Build namespace, src/Mods/.../Build/, Build Process Flow |
| **Add new Extractor class** | `tech-stack.md`, `public-api.md`, `file-tree.md`, `data-flows.md` | Extractor-Builder Pattern, Build namespace, XML Processing Flow |
| **Add new XML file type** | `tech-stack.md`, `public-api.md`, `file-tree.md`, `data-flows.md` | XML File Representation Pattern, XML namespace, XML Processing Flow |
| **Add new Output class** | `public-api.md`, `file-tree.md`, `data-flows.md` | Output namespace, src/Mods/.../Output/, Output Generation Flow |
| **Add new Exception class** | `tech-stack.md`, `public-api.md`, `file-tree.md` | Exception Hierarchy, Root namespace, src/Mods/.../CargoSizeException.php |
| **Add new Reference generator** | `public-api.md`, `file-tree.md`, `data-flows.md` | References namespace, src/Mods/.../References/, Documentation Generation |
| **Add new FOMOD component** | `tech-stack.md`, `public-api.md`, `file-tree.md`, `data-flows.md` | FOMOD Pattern, FOMOD namespace, FOMOD Flow |
| **Add public method** | `public-api.md` | Relevant class section |
| **Add public constant** | `public-api.md` | Relevant class section |
| **Add cargo multiplier** | `constraints.md`, `data-flows.md` | Build Configuration, Build Process Flow |
| **Change naming convention** | `constraints.md`, `tech-stack.md` | Naming Conventions section |
| **Add new architectural pattern** | `tech-stack.md`, `data-flows.md`, `constraints.md` | Architectural Patterns, relevant flow section, Architectural Constraints |
| **Add new Composer dependency** | `tech-stack.md`, `constraints.md` | Composer Dependencies, Dependency Management |
| **Add new config file** | `file-tree.md`, `data-flows.md`, `constraints.md` | config/ directory, Configuration Flow, Configuration Rules |
| **Change file I/O approach** | `constraints.md`, `tech-stack.md` | File I/O Constraints, relevant pattern |
| **Add new build script** | `tech-stack.md`, `constraints.md` | Composer Scripts, Build Process |
| **Add new test suite** | `constraints.md`, `file-tree.md` | Testing Constraints, tests/ directory |
| **Change XML processing** | `constraints.md`, `tech-stack.md`, `data-flows.md` | XML Constraints, XML Pattern, XML Processing Flow |
| **Add translation** | `constraints.md`, `public-api.md` | Localization, config/translations.json |
| **Modify build order** | `data-flows.md`, `constraints.md` | Build Process Flow, Build Process Constraints |
| **Add ship type filter** | `constraints.md`, `data-flows.md`, `tech-stack.md` | Ship Type Filtering, Build Configuration, Filter Pattern |
| **Change output structure** | `file-tree.md`, `data-flows.md`, `constraints.md` | build/ directory, Output Flow, Output Constraints |

### Maintenance Checklist

Before committing code changes:
- [ ] Identified which manifest documents need updates
- [ ] Updated all relevant manifest documents
- [ ] Verified no contradictions between code and manifest
- [ ] Updated "Last Updated" date in affected documents
- [ ] Updated version number if architectural changes made

---

## ⚡ Efficiency Rules - Search Smart

### **RULE 1: Manifest Before Source**
**NEVER** read source files for information that's in the manifest.

#### Decision Tree
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
    
Need to see how build process works?
    ├─ YES → Check data-flows.md
    └─ NO  → Continue
    
ONLY THEN → Read source files for implementation details
```

### **RULE 2: File Tree First**
Before using `grep_search` or `file_search`:
1. Open [file-tree.md](docs/agents/project-manifest/file-tree.md)
2. Navigate the visual tree structure
3. Identify exact file paths
4. Read specific files directly

**Saves:** 80% of filesystem operations

### **RULE 3: Public API First**
Before reading class files:
1. Open [public-api.md](docs/agents/project-manifest/public-api.md)
2. Search for the class name
3. Review signatures and contracts
4. Only read source if implementation logic needed

**Saves:** 90% of source file reads

### **RULE 4: Data Flow First**
Before implementing a feature:
1. Open [data-flows.md](docs/agents/project-manifest/data-flows.md)
2. Find the relevant flow pattern (Build Process, XML Processing, etc.)
3. Follow the established pattern
4. Reference public-api.md for specific signatures

**Saves:** Hours of architecture discovery

### **RULE 5: Constraints Always**
Before writing ANY code:
1. Open [constraints.md](docs/agents/project-manifest/constraints.md)
2. Review relevant sections (File I/O, Strict Types, Naming, etc.)
3. Follow established rules exactly
4. Never deviate without explicit user approval

**Prevents:** Architecture violations and rework

---

## 🚨 Failure Protocol & Decision Matrix

### When Encountering Issues

| Scenario | Action | Priority | Documents to Consult |
|----------|--------|----------|---------------------|
| **Ambiguous requirement** | Use most restrictive interpretation from constraints.md | MUST | constraints.md |
| **Manifest/Code conflict** | Trust manifest, flag code for correction | MUST | All manifest docs |
| **Missing documentation** | Document the gap, implement conservatively, update manifest | MUST | constraints.md for conventions |
| **Unclear pattern** | Find similar pattern in tech-stack.md, follow it exactly | MUST | tech-stack.md, data-flows.md |
| **Unknown method signature** | Check public-api.md before reading source | MUST | public-api.md |
| **Untested code path** | Write tests following constraints.md, mark as new coverage | SHOULD | constraints.md (Testing) |
| **Performance concern** | Follow "No Premature Optimization" rule | SHOULD | constraints.md (Performance) |
| **Security question** | Follow constraints.md security rules strictly | MUST | constraints.md (Security) |
| **Naming uncertainty** | Follow constraints.md naming conventions exactly | MUST | constraints.md (Naming) |
| **Architectural decision** | Match existing patterns from tech-stack.md | MUST | tech-stack.md |
| **File location uncertainty** | Use file-tree.md structure | MUST | file-tree.md |
| **Build process confusion** | Study data-flows.md diagrams | MUST | data-flows.md |
| **Dependency question** | Check tech-stack.md allowed dependencies | MUST | tech-stack.md, constraints.md |
| **File I/O approach** | Use synchronous only (constraints.md) | MUST | constraints.md (File I/O) |
| **XML parsing question** | Follow XML File Representation pattern | MUST | tech-stack.md, constraints.md |
| **Error handling** | Follow exception hierarchy in tech-stack.md | MUST | tech-stack.md, constraints.md |
| **User request conflicts with constraints** | Flag conflict, request clarification | MUST | constraints.md |
| **X4 Core library usage** | Reference x4-core public API and patterns | MUST | tech-stack.md (Dependencies) |
| **Build configuration question** | Check config/build-config.json and constraints | MUST | constraints.md, data-flows.md |

### Conflict Resolution Priority

When faced with conflicting information:

```
1. constraints.md (Non-negotiable rules)
2. public-api.md (Established contracts)
3. tech-stack.md (Architectural patterns)
4. data-flows.md (Established flows)
5. file-tree.md (Structural organization)
6. Source code (Implementation details)
7. User request (May conflict with architecture)
```

If user request conflicts with items 1-6, **explicitly state the conflict** and request clarification.

### Special Case: X4 Core Library

This project depends heavily on the **mistralys/x4-core** library. When working with core classes:

1. Reference x4-core's own manifest if available
2. Follow x4-core's patterns and conventions
3. Do not modify x4-core classes (dependency, not part of this project)
4. Wrap or extend x4-core classes if customization needed

---

## 📊 Project Statistics

### Core Metrics
- **Language:** PHP 8.4+
- **Architecture:** Build Tool (CLI)
- **Primary Pattern:** Extractor-Builder with XML Processing
- **Total Classes:** ~40
- **Total Lines (est.):** ~5,000
- **Config Files:** 2 JSON + 1 translation JSON
- **Output Format:** X4 Mod Files (XML + ZIP)
- **Testing:** PHPUnit 9.5+
- **Static Analysis:** PHPStan 1.6+

### Domain Organization
- **Build System:** ~15 classes (Extractors, Builders, OutputBuilders)
- **XML Processing:** ~10 classes (ShipXMLFile, CargoXMLFile, ContentXMLRenderer)
- **Output Generation:** ~8 classes (ZipBuilder, FOModBuilder, ReferenceBuilder)
- **Core:** 2 classes (ModInfo, CargoSizeException)

### Key Numbers
- **Supported Cargo Multipliers:** 2x, 4x, 8x, 10x (configurable)
- **Ship Types:** Transport, Mining, Auxiliary, Carrier
- **Ship Sizes:** S, M, L, XL
- **Supported Languages:** 44 (English), 49 (German), 33 (French), 34 (Spanish), 39 (Italian), 007 (Russian), 082 (Korean)
- **Build Targets:** Full packages, Custom FOMOD, Reference docs
- **Output Formats:** ZIP, FOMOD installer

---

## 🔍 Quick Reference Commands

### Finding Information

```bash
# Find a class location
→ Open file-tree.md, search for class name

# Find method signature
→ Open public-api.md, search for class name

# Understand a pattern
→ Open tech-stack.md, find pattern section

# See build process flow
→ Open data-flows.md, find Build Process Flow

# Check if something is allowed
→ Open constraints.md, search for topic

# Find X4 Core library usage
→ Open tech-stack.md, Composer Dependencies section
```

### Common Tasks

```bash
# Add a new cargo multiplier
→ Study: tech-stack.md (Build System Pattern)
→ Follow: data-flows.md (Build Process Flow)
→ Reference: constraints.md (Build Configuration)
→ Update: constraints.md + data-flows.md

# Add a new ship type filter
→ Study: data-flows.md (Ship Filtering Flow)
→ Use: Existing filter patterns
→ Reference: public-api.md (Extractor signatures)
→ Update: tech-stack.md + data-flows.md

# Add new XML processing
→ Study: data-flows.md (XML Processing Flow)
→ Extend: BaseXMLFile pattern
→ Reference: public-api.md (XML namespace)
→ Update: tech-stack.md + public-api.md + file-tree.md

# Generate custom build
→ Study: data-flows.md (Build Process Flow)
→ Use: CargoSizeBuildTools::build()
→ Reference: config/build-config.json

# Add new output format
→ Study: data-flows.md (Output Generation Flow)
→ Follow: Builder pattern
→ Update: tech-stack.md + public-api.md + file-tree.md
```

---

## 🛡️ Guardrails

### What Agents MUST Do
- ✅ Read manifest before source code
- ✅ Follow all constraints.md rules (sync file I/O, no databases, strict types)
- ✅ Update manifest when adding features
- ✅ Use established patterns from tech-stack.md
- ✅ Reference public-api.md for signatures
- ✅ Follow build flows from data-flows.md
- ✅ Flag conflicts between manifest and code
- ✅ Ask for clarification when uncertain
- ✅ Respect X4 Core library boundaries
- ✅ Test with PHPUnit before committing

### What Agents MUST NOT Do
- ❌ Skip reading constraints.md
- ❌ Deviate from established patterns
- ❌ Add code without updating manifest
- ❌ Use async file I/O (synchronous only)
- ❌ Add database queries or connections
- ❌ Ignore naming conventions
- ❌ Create exceptions outside hierarchy
- ❌ Modify X4 Core library classes
- ❌ Add dependencies without checking constraints
- ❌ Use eval() or execute user code
- ❌ Disable strict_types
- ❌ Add UI code (this is a CLI tool)
- ❌ Add web server code (not a web app)

---

## 📖 Additional Resources

### External Documentation
- [Composer](https://getcomposer.org/) - Dependency management
- [PHPUnit](https://phpunit.de/) - Testing framework
- [PHPStan](https://phpstan.org/) - Static analysis
- [X4 Foundations](https://www.egosoft.com/games/x4/info_en.php) - Game documentation

### Related Projects
- [X4 Core](https://github.com/Mistralys/x4-core) - Parent library
- [X4 Data Extractor](https://github.com/Mistralys/x4-data-extractor) - Game data extraction
- [X4 Game Notes](https://github.com/Mistralys/x4-game-notes) - Game documentation

### Support
- **Issues:** GitHub Issues
- **Nexus Mods:** [X4 Cargo Sizes Mod page](https://www.nexusmods.com/x4foundations/mods/)

---

## 🔄 Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.3 | Feb 17, 2026 | Added GUI test infrastructure (PHPUnit 11), Service Container pattern, Parameter Object pattern, strict equality refactoring. Updated manifest docs to v1.3. |
---

## 💡 Pro Tips for Maximum Efficiency

1. **Bookmark the manifest README** - It's your starting point for everything
2. **Memorize the constraints** - Sync file I/O, No databases, Strict types
3. **Keep public-api.md open** - Reference it constantly
4. **Understand the 11 patterns** - Extractor-Builder, XML File Representation, Build Pipeline, etc.
5. **Trust the manifest** - If code conflicts, the manifest is probably right
6. **Update as you go** - Don't batch manifest updates, do them immediately
7. **Use the decision tree** - It saves tokens and time
8. **Follow the build flows** - data-flows.md has all the answers
9. **Respect X4 Core boundaries** - Use it, don't modify it
10. **Test before committing** - Run PHPUnit and PHPStan

---

## 🎓 Success Criteria

An agent has successfully integrated with this codebase when:
- ✅ Can navigate to any file using file-tree.md
- ✅ Can find any method signature using public-api.md
- ✅ Knows all 11 architectural patterns by heart
- ✅ Never violates constraints.md rules (especially file I/O, databases, strict types)
- ✅ Updates manifest with every code change
- ✅ Follows established build flows
- ✅ Writes code indistinguishable from existing codebase
- ✅ Can run `composer build` successfully
- ✅ Understands X4 Core library integration

**Estimated Time to Proficiency:** 20 minutes with manifest, 4+ hours without

---

## 🏗️ Build System Quick Reference

### Running Builds

```bash
# Full build (all cargo multipliers)
composer build

# Custom build with specific multipliers
php -f src/Mods/CargoSizesMod/Build/CargoSizeBuildTools.php -- --multipliers=2,4,8

# Generate FOMOD installer
php -f src/Mods/CargoSizesMod/Build/FOModBuilder.php

# Generate cargo size reference
php -f src/Mods/CargoSizesMod/References/CargoSizeReferenceBuilder.php
```

### Build Process Overview

```
1. Extract → Read X4 game XML files
2. Calculate → Apply cargo multipliers
3. Adjust → Compensate flight mechanics
4. Generate → Create mod XML files
5. Package → Build ZIP/FOMOD distributions
```

For detailed flow, see [data-flows.md](docs/agents/project-manifest/data-flows.md).

---

## 🤝 Integration with X4 Core

This project is tightly integrated with **mistralys/x4-core**. Key integration points:

### X4 Core Usage
- **Data Access:** Use X4 Core to read game data (Factions, Wares, Ships, etc.)
- **XML Utilities:** Leverage X4 Core's XML parsing utilities
- **Application Framework:** Extend X4Application for CLI tools
- **Exception Hierarchy:** Extend X4Exception for error handling

### Boundaries
- **Don't Modify:** Never edit X4 Core classes directly
- **Wrap/Extend:** Create wrappers or subclasses if customization needed
- **Follow Patterns:** Respect X4 Core's architectural patterns
- **Update Separately:** X4 Core is a separate dependency

### Common X4 Core Classes Used
- `X4\X4Application` - Base application class
- `X4\X4Exception` - Base exception class
- `X4\Database\*` - Game data collections
- `X4\XML\*` - XML utilities

---

**Remember:** This manifest represents careful architectural decisions. Respect it, follow it, and update it. Future agents (and humans) depend on it.

---

## 📋 Critical Reminders

### Before Every Coding Session
1. ✅ Read constraints.md (especially File I/O, Databases, Strict Types)
2. ✅ Check tech-stack.md for relevant patterns
3. ✅ Reference public-api.md for method signatures
4. ✅ Follow data-flows.md for implementation guidance

### After Every Code Change
1. ✅ Update manifest documents
2. ✅ Run `composer dump-autoload` if new classes were created
3. ✅ Run PHPUnit tests (`composer test`)
4. ✅ Run PHPStan analysis (`composer analyze`)
5. ✅ Test build process (`composer build`)

### When Stuck
1. ✅ Re-read constraints.md
2. ✅ Check data-flows.md for similar patterns
3. ✅ Search public-api.md for existing methods
4. ✅ Review tech-stack.md for architectural guidance
5. ✅ Ask for clarification (don't guess)

---

**End of Agent Operating System Documentation**
