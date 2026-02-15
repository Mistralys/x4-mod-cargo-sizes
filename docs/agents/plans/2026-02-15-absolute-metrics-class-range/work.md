# Work Packages: Absolute Metrics with Class-Wide Range Display

> **Plan:** [plan.md](plan.md)  
> **Date Created:** 2026-02-15  
> **Total Work Packages:** 8  

---

## Overview

This plan is decomposed into 8 work packages following the natural phase boundaries. The dependency chain flows from backend foundation (WP-001) through backend class-range service (WP-002) to frontend types/API (WP-003), then fans out into parallel frontend work (WP-004–WP-007), and concludes with documentation (WP-008).

### Dependency Graph

```
WP-001 (Backend Data Foundation)
  └── WP-002 (Class-Range Backend)
        └── WP-003 (Frontend Types & API Client)
              ├── WP-004 (Frontend Display Components)
              │     ├── WP-006 (Wiring & Integration) ← also depends on WP-005
              │     └── WP-007 (Update Single-Ship Results)
              └── WP-005 (Context Phrases Utility)
                    └── WP-006 (Wiring & Integration)
                          
WP-008 (Manifest Documentation) ← depends on WP-001 through WP-007
```

---

## Work Package Summary

| ID | Title | Dependencies | Status | Assigned To | Detail |
|----|-------|-------------|--------|-------------|--------|
| WP-001 | Replace Hardcoded Backend Data with Real x4-core Values | — | READY | Lead Implementation Engineer | [WP-001.md](work/WP-001.md) |
| WP-002 | Create Class-Range Backend Service and Endpoint | WP-001 | READY | Lead Implementation Engineer | [WP-002.md](work/WP-002.md) |
| WP-003 | Frontend Type Definitions and API Client | WP-002 | READY | Lead Implementation Engineer | [WP-003.md](work/WP-003.md) |
| WP-004 | Frontend Display Components | WP-003 | READY | Lead Implementation Engineer | [WP-004.md](work/WP-004.md) |
| WP-005 | Context Phrases Utility | WP-003 | READY | Lead Implementation Engineer | [WP-005.md](work/WP-005.md) |
| WP-006 | Frontend Wiring and Integration | WP-004, WP-005 | READY | Lead Implementation Engineer | [WP-006.md](work/WP-006.md) |
| WP-007 | Update Existing Single-Ship Results with Absolute Metrics | WP-004 | READY | Lead Implementation Engineer | [WP-007.md](work/WP-007.md) |
| WP-008 | Manifest Documentation Updates | WP-001–WP-007 | READY | Documentation Agent | [WP-008.md](work/WP-008.md) |

---

## Phase Mapping

| Phase | Plan Steps | Work Package | Description |
|-------|-----------|--------------|-------------|
| Phase 1 | Steps 1–9 | WP-001 | Replace all hardcoded placeholder values with real x4-core data |
| Phase 2 | Steps 10–16 | WP-002 | New class-range service, DTOs, endpoint, route |
| Phase 3+4 | Steps 17–22 | WP-003 | TypeScript types, API client method, useClassRange hook |
| Phase 5 | Steps 23–27 | WP-004 | RangeBar, AbsoluteMetricCard, WorstCaseCard, ClassRangePanel |
| Phase 7 | Step 31 | WP-005 | Context phrase utility functions |
| Phase 6 | Steps 28–30 | WP-006 | App.tsx wiring, state management, integration |
| Phase 8 | Steps 32–33 | WP-007 | Augment existing single-ship result views |
| — | Manifest | WP-008 | Update all GUI project manifest documents |

---

## Recommended Execution Order

1. **WP-001** — Foundation (no dependencies)
2. **WP-002** — Backend class-range (depends on WP-001)
3. **WP-003** — Frontend types & API (depends on WP-002)
4. **WP-004** + **WP-005** — Can run in parallel (both depend on WP-003)
5. **WP-006** + **WP-007** — Can run in parallel (WP-006 depends on WP-004+WP-005; WP-007 depends on WP-004)
6. **WP-008** — Documentation (depends on all previous)

## Files Impact Summary

### New Files (12)
- Backend: 4 DTOs + 1 service + 1 endpoint = **6 new PHP files**
- Frontend: 1 hook + 4 components + 1 utility = **6 new TS/TSX files**

### Modified Files (16)
- Backend: 6 PHP files (services, DTOs, router)
- Frontend: 4 TS/TSX files (types, API, App.tsx, hooks)
- Frontend: 3 component files (PhysicsOverview, ComparisonView, EnginePerformanceDisplay)
- Frontend: 1 ResultsPanel component
- Documentation: 5-6 manifest files
