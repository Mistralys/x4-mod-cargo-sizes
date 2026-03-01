/**
 * Main application component.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import { useState, useEffect, useCallback, useMemo } from 'react';
import { Header } from './components/Layout/Header';
import { TwoColumnLayout } from './components/Layout/TwoColumnLayout';
import { Footer } from './components/Layout/Footer';
import { ConfigPanel } from './components/ConfigPanel/ConfigPanel';
import { ShipSelector } from './components/ShipSelector/ShipSelector';
import { ResultsPanel } from './components/ResultsPanel/ResultsPanel';
import { usePhysicsCalculation } from './hooks/usePhysicsCalculation';
import { useClassRange } from './hooks/useClassRange';
import type { BuildConfig } from './types/config';
import type { PhysicsConfig, EngineDef, ClassRangeRequest } from './types/physics';
import type { ShipDetails } from './types/ships';

function App() {
  const { result, loading, error, calculate } = usePhysicsCalculation();
  const { result: classRangeResult, loading: classRangeLoading, error: classRangeError, calculate: calculateClassRange } = useClassRange();

  const [currentConfig, setCurrentConfig] = useState<BuildConfig | null>(null);
  const [selectedMultiplier, setSelectedMultiplier] = useState<number>(2);
  const [selectedEngineId, setSelectedEngineId] = useState<string | null>(null);
  const [shipDetails, setShipDetails] = useState<ShipDetails | null>(null);
  const [engines, setEngines] = useState<EngineDef[]>([]);
  
  // Extract shipId and shipType for class-range calculations
  const [shipId, setShipId] = useState<string | null>(null);
  const [shipType, setShipType] = useState<string | null>(null);

  // Derive selected engine from engine ID and engines array (memoized)
  const selectedEngine = useMemo<EngineDef | null>(() => {
    if (selectedEngineId && engines.length > 0) {
      return engines.find((e) => e.id === selectedEngineId) || null;
    }
    return null;
  }, [selectedEngineId, engines]);

  // Trigger physics calculation when all required data is available
  const triggerCalculation = useCallback(() => {
    if (!currentConfig || !shipDetails) {
      return;
    }

    const flightMechanics = currentConfig['flight-mechanics'];
    const adjustedCargo = shipDetails.cargo * selectedMultiplier;

    const physicsConfig: PhysicsConfig = {
      baseMass: shipDetails.mass,
      originalCargo: shipDetails.cargo,
      adjustedCargo,
      cargoMultiplier: selectedMultiplier,
      accelerationResponsiveness: flightMechanics.accelerationResponsiveness,
      engineId: selectedEngineId,
      shipId: shipDetails.id,
    };

    calculate(physicsConfig);
  }, [currentConfig, shipDetails, selectedMultiplier, selectedEngineId, calculate]);

  // Trigger class-range calculation when all required data is available
  const triggerClassRangeCalculation = useCallback(() => {
    if (!currentConfig || !shipType) {
      return;
    }

    const flightMechanics = currentConfig['flight-mechanics'];

    const classRangeRequest: ClassRangeRequest = {
      shipType,
      cargoMultiplier: selectedMultiplier,
      accelerationResponsiveness: flightMechanics.accelerationResponsiveness,
      engineId: selectedEngineId,
    };

    calculateClassRange(classRangeRequest);
  }, [currentConfig, shipType, selectedMultiplier, selectedEngineId, calculateClassRange]);

  // Trigger both calculations when dependencies change
  useEffect(() => {
    triggerCalculation();
    triggerClassRangeCalculation();
  }, [triggerCalculation, triggerClassRangeCalculation]);

  const handleConfigChange = useCallback((config: BuildConfig, multiplier: number) => {
    setCurrentConfig(config);
    setSelectedMultiplier(multiplier);
  }, []);

  const handleEngineChange = useCallback((engineId: string | null) => {
    setSelectedEngineId(engineId);
  }, []);

  const handleShipDetailsChange = useCallback((details: ShipDetails | null) => {
    setShipDetails(details);
    // Extract shipId and shipType for class-range calculations
    if (details) {
      setShipId(details.id);
      setShipType(details.type);
    } else {
      setShipId(null);
      setShipType(null);
    }
  }, []);

  const handleEnginesChange = useCallback((enginesList: EngineDef[]) => {
    setEngines(enginesList);
  }, []);

  const leftPanel = (
    <div className="space-y-6">
      <ShipSelector 
        onEngineChange={handleEngineChange}
        onShipDetailsChange={handleShipDetailsChange}
        onEnginesChange={handleEnginesChange}
      />
      <ConfigPanel onChange={handleConfigChange} />
    </div>
  );

  const rightPanel = (
    <ResultsPanel
      data={result}
      config={
        currentConfig
          ? {
              baseMass: shipDetails?.mass || 0,
              originalCargo: shipDetails?.cargo || 0,
              adjustedCargo: (shipDetails?.cargo || 0) * selectedMultiplier,
              cargoMultiplier: selectedMultiplier,
              accelerationResponsiveness:
                currentConfig['flight-mechanics'].accelerationResponsiveness,
              engineId: selectedEngineId,
              shipId: shipId,
            }
          : null
      }
      engine={selectedEngine}
      loading={loading}
      error={error}
      classRangeData={classRangeResult}
      classRangeLoading={classRangeLoading}
      classRangeError={classRangeError}
      shipSize={shipDetails?.size || 'M'}
    />
  );

  return (
    <div className="flex flex-col h-screen bg-gray-100">
      <Header />
      <main className="flex-1 container mx-auto px-6 py-6 overflow-hidden">
        <TwoColumnLayout leftPanel={leftPanel} rightPanel={rightPanel} />
      </main>
      <Footer />
    </div>
  );
}

export default App;
