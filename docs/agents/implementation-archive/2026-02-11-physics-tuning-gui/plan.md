# Plan: Physics Tuning GUI

## Summary

Create an interactive web-based GUI for tuning physics configuration parameters with real-time feedback on flight characteristics. The GUI will allow developers to adjust drag reduction, jerk reduction, inertia, and other physics parameters using sliders, while immediately seeing the calculated impact on different ships, engines, and cargo multipliers. The system will reuse existing PHP calculation logic via a lightweight REST API, avoiding code duplication. Engine selection and thrust-to-weight ratio calculations are included using X4 Core's comprehensive engine data API.

---

## Approach / Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│ Frontend (React SPA)                                         │
│ - Interactive sliders for all config parameters             │
│ - Real-time calculation display                             │
│ - Ship type/size/specific ship selector                     │
│ - Engine selector with thrust data display                  │
│ - Thrust-to-weight ratio & acceleration estimates           │
│ - Side-by-side before/after comparison                      │
└─────────────────────────────────────────────────────────────┘
                            ↓ HTTP REST API
┌─────────────────────────────────────────────────────────────┐
│ Backend (PHP API Server)                                     │
│ - REST endpoints for physics calculations                   │
│ - Wraps PhysicsCalculator (reuses existing logic)           │
│ - Integrates X4 Core EngineDef for thrust data             │
│ - Serves ship data from extracted game files                │
│ - Configuration management (read/write JSON)                │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ Existing PHP Classes (No Changes Required)                  │
│ - PhysicsCalculator                                          │
│ - AdjustedDrag, AdjustedInertia, AdjustedJerk              │
│ - ShipXMLFile, CargoXMLFile                                 │
│ - CargoSizeExtractor                                         │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ X4 Core Library (Dependency)                                │
│ - ShipDefs::getInstance()                                   │
│ - EngineDefs::getInstance()                                 │
│ - EngineDef::getThrustForward() and related methods         │
│ - ShipDef::getEngines() for compatible engines             │
└─────────────────────────────────────────────────────────────┘
```

### Technology Stack

**Backend:**
- **PHP 8.4+** - Reuse existing runtime
- **PHP Built-in Server** - `php -S localhost:8080`
- **Slim Framework** (or plain PHP) - Lightweight REST API routing
- **Existing Classes** - PhysicsCalculator, ship data extractors

**Frontend:**
- **React 18+** - Component-based UI framework
- **TypeScript** - Type safety matching PHP's strict types
- **Vite** - Fast build tool and dev server
- **TailwindCSS** - Utility-first styling
- **React Hook Form** - Form state management
- **Recharts** - Data visualization (optional)

**Why React + Vite + TypeScript?**
- ✅ Fast development with hot reload
- ✅ Type safety matches PHP's strict_types
- ✅ Rich ecosystem for UI components
- ✅ Easy to add charts/graphs later
- ✅ Small bundle size with Vite

**Alternative Consideration: Electron (for future standalone app)**

If standalone distribution becomes important, the React frontend can be wrapped in Electron with minimal changes:
```
Electron Shell → React Frontend → HTTP → PHP API Backend (packaged)
```

---

## Rationale

### Key Design Decisions

**1. Why Local Web Server + SPA over Electron?**

✅ **Pros:**
- Simplest architecture (leverage PHP's strengths)
- No new runtime dependencies (PHP already required)
- Easy debugging (browser devtools)
- Faster development cycle
- Smaller codebase size

❌ **Cons:**
- Requires running two servers during development (PHP + Vite)
- Not a standalone desktop app (acceptable for developer tool)

**2. Why REST API over CLI Interface?**

**REST API Approach:**
```php
// Backend: api/calculate/physics
POST /api/calculate/physics
{
  "baseMass": 205,
  "originalCargo": 42000,
  "cargoMultiplier": 10,
  "config": { "dragReductionFactor": 1.2, ... }
}

Response: {
  "massRatio": 206.0,
  "effectiveRatio": 10.0,
  "adjustedDrag": { "forward": 0.15, ... },
  ...
}
```

**CLI Interface Approach:**
```bash
# Would require parsing JSON input/output
php api.php calculate-physics --input=input.json --output=output.json
```

✅ **REST is better because:**
- Natural fit for web frontend
- Easier error handling
- Better for real-time updates
- Simpler state management
- Can add WebSocket later for live updates

**3. Why Reuse PhysicsCalculator vs. Duplicate in JS?**

- ✅ Single source of truth (no sync issues)
- ✅ Guaranteed consistency with build output
- ✅ Easier to maintain (one codebase for logic)
- ✅ All PHP optimizations/fixes apply to GUI automatically

**4. Why TypeScript Frontend?**

- Matches PHP's `declare(strict_types=1)` philosophy
- Catches errors at compile time
- Better IDE support
- Self-documenting API contracts

---

## Detailed Steps

### Phase 1: Project Setup & Infrastructure

**1.1. Initialize GUI Directory Structure**
```
/gui
  /backend          # PHP REST API
    /src
      /API
        /Endpoints
          PhysicsEndpoint.php
          ShipsEndpoint.php
          ConfigEndpoint.php
        Router.php
      /Services
        PhysicsService.php    # Wraps PhysicsCalculator
        ShipDataService.php   # Wraps CargoSizeExtractor
        ConfigService.php     # Manages build-config.json
    /public
      index.php              # API entry point
    composer.json            # Slim framework + dependencies
  /frontend         # React SPA
    /src
      /components
        /ConfigPanel
          SliderGroup.tsx
          TierEditor.tsx
        /ResultsPanel
          PhysicsDisplay.tsx
          ComparisonView.tsx
        /ShipSelector
          ShipPicker.tsx
          SizeFilter.tsx
      /services
        api.ts              # API client
      /types
        physics.d.ts        # Type definitions
      App.tsx
      main.tsx
    package.json
    vite.config.ts
    tsconfig.json
  README.md                 # Setup and usage instructions
  start-dev.sh              # Convenience script (starts both servers)
  start-dev.bat             # Windows version
```

**1.2. Backend - Install Slim Framework**
```json
{
  "require": {
    "slim/slim": "^4.13",
    "slim/psr7": "^1.6",
    "php": ">=8.4"
  },
  "autoload": {
    "psr-4": {
      "Mistralys\\X4\\Mods\\CargoSizesMod\\GUI\\": "backend/src/"
    }
  }
}
```

**1.3. Frontend - Initialize React + Vite + TypeScript**
```bash
cd gui/frontend
npm create vite@latest . -- --template react-ts
npm install
npm install axios react-hook-form recharts tailwindcss
```

---

### Phase 2: Backend API Development

**2.1. Create PHP API Services Layer**

**PhysicsService.php** - Wraps existing PhysicsCalculator
```php
class PhysicsService
{
    public function calculatePhysics(PhysicsRequest $request): PhysicsResponse
    {
        $calculator = new PhysicsCalculator(
            baseMass: $request->baseMass,
            originalCargo: $request->originalCargo,
            adjustedCargo: $request->adjustedCargo,
            cargoMultiplier: $request->cargoMultiplier,
            useEffectiveRatioCap: $request->config->useEffectiveRatioCap
        );
        
        $adjustedDrag = AdjustedDrag::create(/* ... */);
        // ... more calculations
        
        // Calculate engine performance if engineId provided
        $enginePerformance = null;
        if ($request->engineId !== null) {
            $enginePerformance = $this->calculateEnginePerformance(
                $request->engineId,
                $calculator->getOriginalFullMass(),
                $calculator->getAdjustedFullMass()
            );
        }
        
        return new PhysicsResponse(
            physics: /* all calculated values */,
            enginePerformance: $enginePerformance
        );
    }
    
    /**
     * Calculate engine performance metrics using X4 Core EngineDef.
     */
    private function calculateEnginePerformance(
        string $engineId,
        float $originalMass,
        float $adjustedMass
    ): EnginePerformance
    {
        $engine = \Mistralys\X4\Database\Engines\EngineDefs::getInstance()
            ->getByID($engineId);
        
        $thrustForward = $engine->getThrustForward();
        
        // Thrust-to-weight ratio (Newton's per kg, simplified as thrust/mass)
        $originalTWR = $thrustForward / $originalMass;
        $adjustedTWR = $thrustForward / $adjustedMass;
        $twrReduction = (($originalTWR - $adjustedTWR) / $originalTWR) * 100;
        
        // Estimated acceleration (m/s²) = Force / Mass
        // Note: In-game units may differ, this is simplified
        $estimatedAccelOriginal = $originalTWR;
        $estimatedAccelAdjusted = $adjustedTWR;
        
        return new EnginePerformance(
            engineId: $engine->getID(),
            engineLabel: $engine->getLabel(),
            thrustForward: $thrustForward,
            thrustReverse: $engine->getThrustReverse(),
            boostThrust: $engine->getBoostThrust(),
            travelThrust: $engine->getTravelThrust(),
            originalTWR: $originalTWR,
            adjustedTWR: $adjustedTWR,
            twrReductionPercent: $twrReduction,
            estimatedAccelOriginal: $estimatedAccelOriginal,
            estimatedAccelAdjusted: $estimatedAccelAdjusted
        );
    }
}
```

**ShipDataService.php** - Provides ship data
```php
class ShipDataService
{
    public function getShipTypes(): array
    public function getShipsByType(string $type): array
    public function getShipDetails(string $shipId): ShipDetails
    
    /**
     * Get engines compatible with a specific ship.
     * Uses X4 Core's ShipDef::getEngines() and EngineDef for thrust data.
     */
    public function getEnginesForShip(string $shipId): array // Returns EngineDef[]
    {
        $shipDef = \Mistralys\X4\Database\Ships\ShipDefs::getInstance()->getByID($shipId);
        $engineWares = $shipDef->getEngines()->getAll(); // Returns WareDef[]
        
        return array_map(function(\Mistralys\X4\Database\Wares\WareDef $ware) {
            // Find full engine data with thrust values
            $engineDef = \Mistralys\X4\Database\Engines\EngineDefs::getInstance()
                ->findByMacro($ware->getMacroID());
                
            if ($engineDef === null) {
                throw new GUIException('Engine not found: ' . $ware->getID());
            }
            
            return [
                'id' => $engineDef->getID(),
                'label' => $engineDef->getLabel(),
                'size' => $engineDef->getSize(),
                'macroID' => $engineDef->getMacroID(),
                'thrustForward' => $engineDef->getThrustForward(),
                'thrustReverse' => $engineDef->getThrustReverse(),
                'boostThrust' => $engineDef->getBoostThrust(),
                'boostAcceleration' => $engineDef->getBoostAcceleration(),
                'boostDuration' => $engineDef->getBoostDuration(),
                'travelThrust' => $engineDef->getTravelThrust(),
                'makerRace' => $engineDef->getMakerRace(),
                'mk' => $engineDef->getMk(),
            ];
        }, $engineWares);
    }
    
    public function getAllEngines(): array // Returns EngineDef[] (all engines)
}
```

**ConfigService.php** - Manages configuration
```php
class ConfigService
{
    public function getConfig(): BuildConfig
    public function updateConfig(BuildConfig $config): void
    public function validateConfig(BuildConfig $config): ValidationResult
}
```

**2.2. Create REST API Endpoints**

**Router.php** - Define API routes
```php
$app = Slim\Factory\AppFactory::create();

// Physics calculations
$app->post('/api/calculate/physics', PhysicsEndpoint::class . ':calculate');
$app->post('/api/calculate/batch', PhysicsEndpoint::class . ':calculateBatch');

// Ship data
$app->get('/api/ships/types', ShipsEndpoint::class . ':getTypes');
$app->get('/api/ships/{type}', ShipsEndpoint::class . ':getShipsByType');
$app->get('/api/ships/details/{shipId}', ShipsEndpoint::class . ':getDetails');
$app->get('/api/ships/{shipId}/engines', ShipsEndpoint::class . ':getEnginesForShip');
$app->get('/api/engines', ShipsEndpoint::class . ':getAllEngines');

// Configuration
$app->get('/api/config', ConfigEndpoint::class . ':get');
$app->post('/api/config', ConfigEndpoint::class . ':update');
$app->post('/api/config/validate', ConfigEndpoint::class . ':validate');

// CORS for local development
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type');
});
```

**2.3. Define API Contracts (JSON DTOs)**

**PhysicsRequest:**
```json
{
  "shipId": "ship_arg_m_trans_container_01_a_macro",
  "engineId": "engine_arg_m_allround_01_mk1",
  "baseMass": 205.0,
  "originalCargo": 42000,
  "adjustedCargo": 420000,
  "cargoMultiplier": 10,
  "config": {
    "dragReductionFactor": 1.0,
    "dragReductionTiers": [...],
    "jerkReductionTiers": [...],
    "inertiaImpactFactor": 0.5,
    "useEffectiveRatioCap": true,
    "accelerationResponsiveness": 1.0
  }
}
```

**Note:** `engineId` is optional. When provided, the response includes engine performance metrics (thrust-to-weight ratio, estimated acceleration).

**PhysicsResponse:**
```json
{
  "shipInfo": {
    "id": "ship_arg_m_trans_container_01_a_macro",
    "name": "Magnetar",
    "type": "transport",
    "size": "m"
  },
  "physics": {
    "massRatio": 206.0,
    "effectiveRatio": 10.0,
    "originalFullMass": 42205.0,
    "adjustedFullMass": 420205.0
  },
  "enginePerformance": {
    "engineId": "engine_arg_m_allround_01_mk1",
    "engineLabel": "ARG M All-round Engine Mk1",
    "thrustForward": 2400,
    "thrustReverse": 2280,
    "boostThrust": 6.5,
    "travelThrust": 28.3,
    "originalThrustToWeightRatio": 0.0568,
    "adjustedThrustToWeightRatio": 0.0057,
    "thrustToWeightReductionPercent": 90.0,
    "estimatedAccelerationOriginal": 5.68,
    "estimatedAccelerationAdjusted": 0.57
  },
  "adjustedValues": {
    "drag": {
      "forward": 0.15,
      "reverse": 0.12,
      "horizontal": 0.10,
      "vertical": 0.10,
      "pitch": 0.20,
      "yaw": 0.20,
      "roll": 0.15,
      "reductionPercent": 70
    },
    "inertia": {
      "pitch": 1500.0,
      "yaw": 1500.0,
      "roll": 1200.0,
      "increasePercent": 50
    },
    "jerk": {
      "forward": { "accel": 8.5, "decel": 8.5, "ratio": 0.7 },
      "boost": { "accel": 12.0, "ratio": 0.65 },
      "travel": { "accel": 10.0, "decel": 10.0, "ratio": 0.65 },
      "reductionPercent": 35
    }
  }
}
```

---

### Phase 3: Frontend Development

**3.1. Create Type Definitions (mirrors PHP DTOs)**

**types/physics.d.ts**
```typescript
export interface PhysicsConfig {
  dragReductionFactor: number;
  dragReductionTiers: Tier[];
  jerkReductionTiers: Tier[];
  inertiaImpactFactor: number;
  useEffectiveRatioCap: boolean;
  accelerationResponsiveness: number;
}

export interface Tier {
  maxMultiplier: number;
  reductionPercent: number;
}

export interface PhysicsResponse {
  shipInfo: ShipInfo;
  physics: PhysicsData;
  adjustedValues: AdjustedValues;
}

export interface EngineDef {
  id: string;
  label: string;
  size: string;
  macroID: string;
  thrustForward: number;
  thrustReverse: number;
  boostThrust: number;
  boostAcceleration: number;
  boostDuration: number;
  travelThrust: number;
  makerRace: string;
  mk: number;
}

// ... more type definitions
```

**3.2. Create API Client**

**services/api.ts**
```typescript
import axios from 'axios';

const API_BASE = 'http://localhost:8080/api';

export const physicsApi = {
  calculate: (request: PhysicsRequest) =>
    axios.post<PhysicsResponse>(`${API_BASE}/calculate/physics`, request),
  
  calculateBatch: (requests: PhysicsRequest[]) =>
    axios.post<PhysicsResponse[]>(`${API_BASE}/calculate/batch`, requests),
};

export const shipsApi = {
  getTypes: () => axios.get<ShipType[]>(`${API_BASE}/ships/types`),
  getShipsByType: (type: string) =>
    axios.get<ShipInfo[]>(`${API_BASE}/ships/${type}`),
  getDetails: (shipId: string) =>
    axios.get<ShipDetails>(`${API_BASE}/ships/details/${shipId}`),
  getEnginesForShip: (shipId: string) =>
    axios.get<EngineDef[]>(`${API_BASE}/ships/${shipId}/engines`),
  getAllEngines: () =>
    axios.get<EngineDef[]>(`${API_BASE}/engines`),
};

export const configApi = {
  get: () => axios.get<PhysicsConfig>(`${API_BASE}/config`),
  update: (config: PhysicsConfig) =>
    axios.post(`${API_BASE}/config`, config),
  validate: (config: PhysicsConfig) =>
    axios.post<ValidationResult>(`${API_BASE}/config/validate`, config),
};
```

**3.3. Build UI Components**

**App Layout Structure:**
```tsx
<App>
  <Header />
  <TwoColumnLayout>
    <LeftPanel>
      <ConfigPanel>
        <CargoMultiplierSelector />
        <DragReductionSection>
          <SliderInput name="dragReductionFactor" />
          <TierEditor tiers={dragReductionTiers} />
        </DragReductionSection>
        <JerkReductionSection>
          <SliderInput name="jerkReductionTiers" />
          <TierEditor tiers={jerkReductionTiers} />
        </JerkReductionSection>
        <InertiaSection>
          <SliderInput name="inertiaImpactFactor" />
        </InertiaSection>
        <AccelerationSection>
          <SliderInput name="accelerationResponsiveness" />
        </AccelerationSection>
        <ToggleInput name="useEffectiveRatioCap" />
        <ActionButtons>
          <SaveConfigButton />
          <ResetToDefaultButton />
        </ActionButtons>
      </ConfigPanel>
    </LeftPanel>
    
    <RightPanel>
      <ShipSelector>
        <TypeFilter />
        <SizeFilter />
        <SpecificShipPicker />
        <EnginePicker />
      </ShipSelector>
      
      <ResultsPanel>
        <PhysicsOverview>
          <MetricCard label="Mass Ratio" value={206.0} />
          <MetricCard label="Effective Ratio" value={10.0} />
          <MetricCard label="Original Mass" value={42205} />
          <MetricCard label="Adjusted Mass" value={420205} />
        </PhysicsOverview>
        
        <ComparisonTabs>
          <Tab label="Engine">
            <EnginePerformanceDisplay
              engine={enginePerformance}
              showThrustToWeightRatio={true}
              showEstimatedAcceleration={true}
            />
          </Tab>
          <Tab label="Drag">
            <ValueComparison
              original={originalDrag}
              adjusted={adjustedDrag}
              showPercentChange={true}
            />
          </Tab>
          <Tab label="Inertia">
            <ValueComparison
              original={originalInertia}
              adjusted={adjustedInertia}
            />
          </Tab>
          <Tab label="Jerk">
            <JerkComparison original={...} adjusted={...} />
          </Tab>
          <Tab label="Acceleration">
            <AccelerationFactorsDisplay />
          </Tab>
        </ComparisonTabs>
        
        <DiagnosticsPanel>
          <PhysicsExplanation config={currentConfig} />
          <TierIndicator currentMultiplier={10} activeTier={4} />
        </DiagnosticsPanel>
      </ResultsPanel>
    </RightPanel>
  </TwoColumnLayout>
</App>
```

**3.4. Implement Real-Time Calculation**

**usePhysicsCalculation Hook:**
```typescript
import { useEffect, useState } from 'react';
import { physicsApi } from '../services/api';
import { debounce } from 'lodash';

export function usePhysicsCalculation(
  config: PhysicsConfig,
  shipId: string,
  cargoMultiplier: number
) {
  const [result, setResult] = useState<PhysicsResponse | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const calculate = debounce(async () => {
      setLoading(true);
      try {
        const response = await physicsApi.calculate({
          shipId,
          cargoMultiplier,
          config,
          // baseMass, originalCargo, adjustedCargo fetched from ship data
        });
        setResult(response.data);
        setError(null);
      } catch (err) {
        setError(err.message);
      } finally {
        setLoading(false);
      }
    }, 300); // Debounce slider changes

    calculate();
    return () => calculate.cancel();
  }, [config, shipId, cargoMultiplier]);

  return { result, loading, error };
}
```

**3.5. Add Inline Documentation Components**

**InfoTooltip Component:**
```tsx
<SliderInput
  name="dragReductionFactor"
  label="Drag Reduction Factor"
  min={0.5}
  max={2.0}
  step={0.1}
  tooltip={
    <InfoTooltip>
      <strong>Drag Reduction Factor</strong>
      <p>
        Multiplier applied to drag reduction calculations. Higher values
        reduce drag more aggressively, making ships faster but potentially
        harder to control.
      </p>
      <p>
        <strong>Recommended range:</strong> 0.8 - 1.2
      </p>
      <p>
        <strong>Default:</strong> 1.0 (standard physics)
      </p>
    </InfoTooltip>
  }
/>
```

Pull documentation content from `docs/physics-tuning-guide.md` (embed in frontend or fetch from backend).

---

### Phase 4: Engine Selection Implementation

**Backend: Engine Data Service**

**ShipDataService - Engine Methods:**
```php
class ShipDataService
{
    /**
     * Get engines compatible with a specific ship.
     * Uses X4 Core's ShipDef::getEngines() method.
     */
    public function getEnginesForShip(string $shipId): array
    {
        $shipDef = ShipDefs::getInstance()->getByID($shipId);
        $engineWares = $shipDef->getEngines()->getAll(); // Returns WareDef[]
        
        return array_map(function(WareDef $ware) {
            return [
                'id' => $ware->getID(),
                'label' => $ware->getLabel(),
                'size' => $ware->getSize(),
                'macroID' => $ware->getMacroID(),
                // Note: Thrust data may require XML parsing if not in WareDef
            ];
        }, $engineWares);
    }
    
    /**
     * Get all available engines.
     */
    public function getAllEngines(): array
    {
        // Use WareDefs finder to get all engine wares
        // Filter by tag or connection type for engines
        $wares = WareDefs::getInstance()->findWares()
            ->selectTag('engine') // If engines have a tag
            ->getAll();
            
        return array_map(/* format similar to above */, $wares);
    }
}
```

**Frontend: Engine Picker Component**

**EnginePicker.tsx:**
```tsx
import { useEffect, useState } from 'react';
import { shipsApi } from '../services/api';
import { EngineDef } from '../types/physics';

interface EnginePickerProps {
  shipId: string;
  selectedEngine: string | null;
  onChange: (engineId: string) => void;
}

export function EnginePicker({ shipId, selectedEngine, onChange }: EnginePickerProps) {
  const [engines, setEngines] = useState<EngineDef[]>([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (!shipId) return;
    
    setLoading(true);
    shipsApi.getEnginesForShip(shipId)
      .then(response => setEngines(response.data))
      .finally(() => setLoading(false));
  }, [shipId]);

  if (loading) return <div>Loading engines...</div>;
  if (engines.length === 0) return <div>No engines available for this ship</div>;

  return (
    <div className="engine-picker">
      <label>Engine</label>
      <select
        value={selectedEngine || ''}
        onChange={(e) => onChange(e.target.value)}
      >
        <option value="">-- Select Engine --</option>
        {engines.map(engine => (
          <option key={engine.id} value={engine.id}>
            {engine.label} ({engine.size.toUpperCase()})
          </option>
        ))}
      </select>
      <InfoTooltip>
        Select an engine to see how different thrust values affect physics.
        Engine selection impacts acceleration and handling characteristics.
      </InfoTooltip>
    </div>
  );
}
```

**Integration Notes:**
- X4 Core provides `ShipDef::getEngines()` for compatible engines
- X4 Core provides `EngineDef` class with complete thrust data via `EngineDefs::getInstance()`
- All thrust values available: `getThrustForward()`, `getThrustReverse()`, `getBoostThrust()`, `getTravelThrust()`
- No XML parsing required - data is pre-extracted in `x4-core/data/engines.json`
- Engine thrust can be used for mass-adjusted performance calculations
- Display thrust-to-weight ratio and acceleration estimates in UI

---

### Phase 5: Developer Experience Improvements

**5.1. Create Start Scripts**

**start-dev.sh** (Linux/Mac)
```bash
#!/bin/bash
echo "Starting X4 Cargo Sizes Mod - Physics Tuning GUI"
echo "================================================"
echo ""
echo "Starting PHP API server on http://localhost:8080..."
cd gui/backend && php -S localhost:8080 -t public &
PHP_PID=$!

echo "Starting React dev server on http://localhost:5173..."
cd gui/frontend && npm run dev &
VITE_PID=$!

echo ""
echo "✅ Both servers started!"
echo "   - API:      http://localhost:8080"
echo "   - Frontend: http://localhost:5173"
echo ""
echo "Press Ctrl+C to stop both servers"

trap "kill $PHP_PID $VITE_PID" EXIT
wait
```

**start-dev.bat** (Windows)
```batch
@echo off
echo Starting X4 Cargo Sizes Mod - Physics Tuning GUI
echo ================================================
echo.
echo Starting PHP API server on http://localhost:8080...
start "PHP API" /D gui\backend php -S localhost:8080 -t public

echo Starting React dev server on http://localhost:5173...
start "React Frontend" /D gui\frontend npm run dev

echo.
echo Both servers started!
echo   - API:      http://localhost:8080
echo   - Frontend: http://localhost:5173
echo.
echo Close the terminal windows to stop the servers
pause
```

**5.2. Create README.md**

Document:
- Prerequisites (PHP 8.4+, Node.js 18+)
- Installation steps
- Running the GUI
- Architecture overview
- API documentation
- Troubleshooting

**5.3. Add to Root Composer Scripts**

Update `/composer.json`:
```json
{
  "scripts": {
    "gui:install": "cd gui/backend && composer install && cd ../frontend && npm install",
    "gui:start": "bash gui/start-dev.sh",
    "gui:start-win": "gui\\start-dev.bat"
  }
}
```

---

## Dependencies

### Required Before Implementation
- ✅ PHP 8.4+ (already required)
- ✅ PhysicsCalculator class (exists)
- ✅ Ship XML extraction (exists)
- ✅ build-config.json structure (exists)

### New Dependencies to Install

**Backend (PHP):**
- `slim/slim` ^4.13 - REST API routing
- `slim/psr7` ^1.6 - PSR-7 HTTP message implementation

**Frontend (Node.js):**
- `react` ^18.2 - UI framework
- `react-dom` ^18.2 - DOM rendering
- `typescript` ^5.3 - Type safety
- `vite` ^5.0 - Build tool
- `axios` ^1.6 - HTTP client
- `react-hook-form` ^7.49 - Form state management
- `tailwindcss` ^3.4 - Styling
- `recharts` ^2.10 - Data visualization (optional)
- `lodash` ^4.17 - Utility functions (debounce)

### External Requirements
- Node.js 18+ (for frontend development)
- Modern web browser (Chrome, Firefox, Edge)

---

## Required Components

### Backend Files to Create

```
/gui/backend/
  ├── composer.json
  ├── composer.lock
  ├── public/
  │   └── index.php                    # API entry point
  └── src/
      ├── API/
      │   ├── Router.php                # Route definitions
      │   ├── Middleware/
      │   │   └── CorsMiddleware.php    # CORS handling
      │   └── Endpoints/
      │       ├── PhysicsEndpoint.php   # Physics calculations
      │       ├── ShipsEndpoint.php     # Ship data
      │       └── ConfigEndpoint.php    # Configuration CRUD
      ├── Services/
      │   ├── PhysicsService.php        # Wraps PhysicsCalculator
      │   ├── ShipDataService.php       # Wraps ship extractors
      │   └── ConfigService.php         # Config file management
      ├── DTOs/
      │   ├── PhysicsRequest.php
      │   ├── PhysicsResponse.php
      │   ├── EnginePerformance.php
      │   ├── ShipDetails.php
      │   └── ValidationResult.php
      └── Exceptions/
          └── GUIException.php
```

### Frontend Files to Create

```
/gui/frontend/
  ├── package.json
  ├── package-lock.json
  ├── vite.config.ts
  ├── tsconfig.json
  ├── index.html
  ├── tailwind.config.js
  ├── public/
  │   └── docs/                         # Embedded documentation
  │       └── physics-tuning-guide.md
  └── src/
      ├── main.tsx                      # Entry point
      ├── App.tsx                       # Root component
      ├── types/
      │   ├── physics.d.ts
      │   ├── ships.d.ts
      │   └── config.d.ts
      ├── services/
      │   ├── api.ts                    # API client
      │   └── storage.ts                # Local storage helpers
      ├── hooks/
      │   ├── usePhysicsCalculation.ts
      │   ├── useShipData.ts
      │   └── useConfig.ts
      ├── components/
      │   ├── Layout/
      │   │   ├── Header.tsx
      │   │   ├── TwoColumnLayout.tsx
      │   │   └── Footer.tsx
      │   ├── ConfigPanel/
      │   │   ├── ConfigPanel.tsx
      │   │   ├── SliderInput.tsx
      │   │   ├── TierEditor.tsx
      │   │   ├── ToggleInput.tsx
      │   │   └── ActionButtons.tsx
      │   ├── ShipSelector/
      │   │   ├── ShipSelector.tsx
      │   │   ├── TypeFilter.tsx
      │   │   ├── SizeFilter.tsx
      │   │   ├── ShipPicker.tsx
      │   │   └── EnginePicker.tsx
      │   ├── ResultsPanel/
      │   │   ├── ResultsPanel.tsx
      │   │   ├── PhysicsOverview.tsx
      │   │   ├── MetricCard.tsx
      │   │   ├── ComparisonView.tsx
      │   │   ├── ValueComparison.tsx
      │   │   ├── EnginePerformanceDisplay.tsx
      │   │   └── DiagnosticsPanel.tsx
      │   └── UI/
      │       ├── InfoTooltip.tsx
      │       ├── Tabs.tsx
      │       ├── Card.tsx
      │       └── Spinner.tsx
      └── styles/
          └── globals.css               # Tailwind imports
```

### Documentation Files

```
/gui/
  ├── README.md                         # Setup and usage guide
  ├── start-dev.sh                      # Linux/Mac start script
  ├── start-dev.bat                     # Windows start script
  └── docs/
      ├── API.md                        # API endpoint documentation
      ├── ARCHITECTURE.md               # System design overview
      └── DEVELOPMENT.md                # Contributing guide
```

---

## Assumptions

1. **PHP 8.4+ is available** - Required for backend API
2. **Node.js 18+ is available** - Required for frontend development
3. **X4 game data is already extracted** - Ship data must exist in extracted format
4. **build-config.json structure is stable** - No breaking changes to config format
5. **PhysicsCalculator API is stable** - No breaking changes to calculation methods
6. **Local development environment** - Not deploying to production server (developer tool)
7. **No authentication needed** - Local-only tool, no security requirements
8. **Engine data is available via X4 Core** - `ShipDef::getEngines()` returns compatible engines
9. **Engine thrust data is available** - `EngineDef::getThrustForward()` and related methods provide all thrust values
10. **EngineDefs collection is loaded in-memory** - No database queries, fast access

---

## Constraints

### Architectural Constraints (from constraints.md)

✅ **Must follow:**
- All PHP code must have `declare(strict_types=1)`
- All PHP methods must have type hints
- All PHP exceptions must extend `CargoSizeException` (or create `GUIException extends CargoSizeException`)
- Namespace must be `Mistralys\X4\Mods\CargoSizesMod\GUI\*`
- Follow existing naming conventions (PascalCase classes, camelCase methods)
- Use synchronous file I/O only (no async)
- No database connections (all data from files)

✅ **TypeScript must match PHP strictness:**
- Enable `strict: true` in tsconfig.json
- Use explicit types (no `any` unless absolutely necessary)
- Match PHP DTOs exactly

### Technical Constraints

- **CORS:** Frontend (localhost:5173) must be able to call Backend (localhost:8080)
- **File Paths:** Backend must correctly resolve paths to parent project files
- **Config Format:** Must maintain compatibility with existing build-config.json
- **Build Process:** GUI should NOT interfere with existing CLI build process
- **No Shared State:** Backend must be stateless (each request independent)

### User Experience Constraints

- **Real-time feedback:** UI must update within 500ms of slider change
- **Responsive design:** Must work on 1920x1080 and larger screens
- **Clear documentation:** Every slider must have tooltip explaining effect
- **Error handling:** All API errors must be displayed to user clearly
- **Save/Load:** Changes to config must be persistable

---

## Out of Scope

### Explicitly NOT Included in This Plan

❌ **Comparison Mode (Multiple Configs Side-by-Side)**
- Would allow comparing 2-3 different config sets simultaneously
- **Decision:** MVP uses single config; add in future if users request

❌ **Historical Config Versions**
- Tracking changes to config over time
- **Decision:** Out of scope; users can use Git for version control

❌ **Cloud Sync / Sharing Configs**
- Sharing config presets with other users
- **Decision:** Local-only tool; users can share JSON files manually

❌ **Automated Testing of GUI**
- E2E tests with Playwright/Cypress
- **Decision:** Manual testing sufficient for developer tool

❌ **Standalone Desktop App (Electron)**
- Packaging as executable
- **Decision:** Web-based is sufficient; can add Electron wrapper later if needed

❌ **Integration with Game**
- Live testing in X4 game
- Automatic mod installation
- **Decision:** GUI is for config tuning only; use existing build process to test

❌ **Graphical Flight Characteristic Comparison**
- Charts/graphs showing ship performance curves
- **Decision:** Show numeric values first; add visualizations in future if valuable

---

## Acceptance Criteria

### Functional Requirements

✅ **Configuration Editing**
- [ ] User can adjust `dragReductionFactor` via slider (0.5 - 2.0)
- [ ] User can edit drag reduction tier thresholds and percentages
- [ ] User can edit jerk reduction tier thresholds and percentages
- [ ] User can adjust `inertiaImpactFactor` via slider (0.0 - 1.0)
- [ ] User can toggle `useEffectiveRatioCap` on/off
- [ ] User can adjust `accelerationResponsiveness` via slider (0.5 - 2.0)
- [ ] All sliders have inline tooltips explaining their effect

✅ **Ship Selection**
- [ ] User can filter ships by type (transport, miner, auxiliary, carrier)
- [ ] User can filter ships by size (xs, s, m, l, xl)
- [ ] User can select a specific ship from dropdown
- [ ] User can select compatible engines for the chosen ship
- [ ] Ship name, type, size displayed clearly
- [ ] Engine count displayed for selected ship
- [ ] Engine thrust values displayed (forward, reverse, boost, travel)
- [ ] Thrust-to-weight ratio calculated and displayed

✅ **Real-Time Calculation**
- [ ] Adjusted values update within 500ms of slider change
- [ ] No full-page refresh required
- [ ] Loading indicator shown during calculation
- [ ] Errors displayed clearly if calculation fails

✅ **Results Display**
- [ ] Show mass ratio, effective ratio
- [ ] Show original full mass, adjusted full mass
- [ ] Show original vs. adjusted drag values (all axes)
- [ ] Show original vs. adjusted inertia values (pitch, yaw, roll)
- [ ] Show original vs. adjusted jerk values (forward, boost, travel)
- [ ] Show percentage changes for all values
- [ ] Indicate which tier is active for current multiplier
- [ ] Show engine thrust-to-weight ratio (original and adjusted)
- [ ] Show estimated acceleration based on thrust and mass

✅ **Configuration Persistence**
- [ ] User can save config changes to build-config.json
- [ ] User can reset to default values
- [ ] Confirmation prompt before overwriting config
- [ ] Success/error feedback after save/reset

✅ **Cargo Multiplier Selection**
- [ ] User can select cargo multiplier (2x, 4x, 6x, 8x, 10x, custom)
- [ ] Changes to multiplier immediately recalculate all values

### Non-Functional Requirements

✅ **Performance**
- [ ] Initial page load < 2 seconds
- [ ] API response time < 100ms for single calculation
- [ ] UI remains responsive during calculation
- [ ] No memory leaks during extended use

✅ **Usability**
- [ ] All sliders have clear min/max values
- [ ] All numeric inputs accept keyboard entry
- [ ] Tab navigation works correctly
- [ ] Tooltips are readable and helpful
- [ ] Layout is clear and uncluttered

✅ **Code Quality**
- [ ] Backend follows all constraints.md rules
- [ ] Frontend uses TypeScript strict mode
- [ ] All API endpoints documented in API.md
- [ ] README.md has complete setup instructions
- [ ] No console errors in browser devtools

✅ **Compatibility**
- [ ] Works on Chrome 120+
- [ ] Works on Firefox 120+
- [ ] Works on Edge 120+
- [ ] Works on Windows 10+, Linux, macOS
- [ ] PHP 8.4+ required and detected

---

## Testing Strategy

### Backend Testing

**Unit Tests (PHPUnit)**
- Test PhysicsService calculations match PhysicsCalculator directly
- Test ShipDataService retrieves correct ship data
- Test ConfigService reads/writes build-config.json correctly
- Test input validation (negative values, invalid ranges)

**Integration Tests**
- Test full API endpoint flow (request → service → response)
- Test CORS headers present
- Test error handling (malformed requests, missing files)

**Manual Testing**
- Start API server, test all endpoints with curl/Postman
- Verify responses match expected JSON schema
- Test with different cargo multipliers and ship types

### Frontend Testing

**Component Testing (Vitest + React Testing Library)**
- Test SliderInput component updates state correctly
- Test ShipSelector filters ships correctly
- Test ResultsPanel displays calculations correctly
- Test API client handles errors gracefully

**Manual Testing**
- Test all sliders produce correct API calls
- Test ship selection updates calculations
- Test save/reset config buttons work
- Test UI on different screen sizes
- Test with slow network (throttle in devtools)

**Cross-Browser Testing**
- Test on Chrome, Firefox, Edge
- Verify no layout issues
- Verify API calls work (no CORS issues)

### End-to-End Testing

**Manual E2E Flow:**
1. Start both servers
2. Open frontend in browser
3. Select ship (e.g., Magnetar, transport, M)
4. Choose cargo multiplier (10x)
5. Adjust drag reduction factor slider
6. Verify calculations update in real-time
7. Verify adjusted drag values change
8. Click "Save Config"
9. Verify build-config.json updated correctly
10. Run `composer build` to confirm mod builds successfully

---

## Risks & Mitigations

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| **PHP calculation performance too slow for real-time updates** | High - Poor UX if calculations lag | Low | Use debounced API calls (300ms delay); consider caching common calculations; optimize PhysicsCalculator if needed |
| **Frontend/Backend version mismatch (DTO changes)** | Medium - API errors if types don't match | Medium | Use TypeScript to generate types from PHP DTOs; validate API responses at runtime |
| **Engine not found in EngineDefs** | Medium - Some engines might not have thrust data | Low | Handle null case gracefully; show "Engine data unavailable" message; most engines should have data |
| **CORS issues during development** | Medium - API calls blocked by browser | Low | Configure CORS middleware correctly; document in README; provide troubleshooting steps |
| **File path resolution issues (backend to parent project)** | High - Cannot read ship data or config | Medium | Use relative paths from backend to parent project; document paths in README; test on Windows and Linux |
| **Node.js/PHP version incompatibility** | High - Cannot run servers | Low | Document minimum versions in README (PHP 8.4+, Node 18+); add version checks to start scripts |
| **Ship data not extracted** | High - No ships available to select | Medium | Check for extracted data on startup; show error message with instructions to run X4 Data Extractor |
| **Config validation failure** | Medium - Invalid config saved, breaks build | Medium | Add strict validation in ConfigService before saving; show validation errors to user; backup original config |
| **Slider range too restrictive** | Low - User wants to test extreme values | Medium | Allow custom input fields alongside sliders; add "Advanced Mode" toggle for wider ranges |
| **UI too complex for first-time users** | Medium - Steep learning curve | Medium | Add tooltips for every control; embed physics-tuning-guide.md in UI; add "Quick Tour" modal on first load |
| **GUI changes don't reflect in CLI build** | Critical - Inconsistent behavior | Low | **Always use ConfigService to update build-config.json**; provide clear confirmation after save |

---

## Next Steps After Plan Approval

1. **Create `/gui` directory structure** (5 min)
2. **Initialize backend Composer project** (10 min)
3. **Initialize frontend Vite + React + TypeScript project** (10 min)
4. **Implement PhysicsService with engine performance calculations** (2-3 hours)
5. **Implement REST API endpoints** (2-3 hours)
6. **Implement ShipDataService with X4 Core engine integration** (1-2 hours)
7. **Implement API client in frontend** (1 hour)
8. **Build ConfigPanel UI component** (2-3 hours)
9. **Build ShipSelector with EnginePicker** (2-3 hours)
10. **Build ResultsPanel with EnginePerformanceDisplay** (2-3 hours)
11. **Implement real-time calculation hook** (1 hour)
12. **Add inline documentation tooltips** (1-2 hours)
13. **Test end-to-end flow with engine selection** (1-2 hours)
14. **Write README.md and API.md** (1 hour)
15. **Create start scripts** (30 min)

**Estimated total time:** 18-24 hours of development

---

## Related Documentation

- [constraints.md](../project-manifest/constraints.md) - Architectural rules
- [tech-stack.md](../project-manifest/tech-stack.md) - Existing patterns
- [public-api.md](../project-manifest/public-api.md) - PhysicsCalculator API
- [data-flows.md](../project-manifest/data-flows.md) - Build process flow
- [physics-tuning-guide.md](../../physics-tuning-guide.md) - Physics explanation
- [engine-data-guide.md](../engine-data-guide.md) - X4 Core engine data access API

---

**AGENT: Planning**  
**STATUS: READY_FOR_PM**
