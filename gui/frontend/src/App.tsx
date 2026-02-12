/**
 * Main application component.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import { useState, useEffect, useCallback } from 'react';
import { Header } from './components/Layout/Header';
import { TwoColumnLayout } from './components/Layout/TwoColumnLayout';
import { Footer } from './components/Layout/Footer';
import { ConfigPanel } from './components/ConfigPanel/ConfigPanel';
import { ShipSelector } from './components/ShipSelector/ShipSelector';
import { ResultsPanel } from './components/ResultsPanel/ResultsPanel';
import { usePhysicsCalculation } from './hooks/usePhysicsCalculation';
import { useShipData } from './hooks/useShipData';
import type { BuildConfig } from './types/config';
import type { PhysicsConfig } from './types/physics';

function App() {
  const { result, loading, error, calculate } = usePhysicsCalculation();
  const { shipDetails, engines } = useShipData();

  const [currentConfig, setCurrentConfig] = useState<BuildConfig | null>(null);
  const [selectedMultiplier, setSelectedMultiplier] = useState<number>(2);
  const [selectedEngineId, setSelectedEngineId] = useState<string | null>(null);
  const [selectedEngine, setSelectedEngine] = useState<any>(null);

  // Update selected engine when engine ID or engines array changes
  useEffect(() => {
    if (selectedEngineId && engines.length > 0) {
      const engine = engines.find((e) => e.id === selectedEngineId);
      setSelectedEngine(engine || null);
    } else {
      setSelectedEngine(null);
    }
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
      useEffectiveRatioCap: flightMechanics.useEffectiveRatioCap,
      dragReductionFactor: flightMechanics.dragReductionFactor,
      inertiaImpactFactor: flightMechanics.inertiaImpactFactor,
      accelerationResponsiveness: flightMechanics.accelerationResponsiveness,
      dragReductionTiers: flightMechanics.dragReductionTiers,
      jerkReductionTiers: flightMechanics.jerkReductionTiers,
      engineId: selectedEngineId,
    };

    calculate(physicsConfig);
  }, [currentConfig, shipDetails, selectedMultiplier, selectedEngineId, calculate]);

  // Trigger calculation when dependencies change
  useEffect(() => {
    triggerCalculation();
  }, [triggerCalculation]);

  const handleConfigChange = useCallback((config: BuildConfig, multiplier: number) => {
    setCurrentConfig(config);
    setSelectedMultiplier(multiplier);
  }, []);

  const handleEngineChange = useCallback((engineId: string | null) => {
    setSelectedEngineId(engineId);
  }, []);

  const leftPanel = (
    <div className="space-y-6">
      <ConfigPanel onChange={handleConfigChange} />
      <ShipSelector onEngineChange={handleEngineChange} />
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
              useEffectiveRatioCap: currentConfig['flight-mechanics'].useEffectiveRatioCap,
              dragReductionFactor: currentConfig['flight-mechanics'].dragReductionFactor,
              inertiaImpactFactor: currentConfig['flight-mechanics'].inertiaImpactFactor,
              accelerationResponsiveness:
                currentConfig['flight-mechanics'].accelerationResponsiveness,
              dragReductionTiers: currentConfig['flight-mechanics'].dragReductionTiers,
              jerkReductionTiers: currentConfig['flight-mechanics'].jerkReductionTiers,
              engineId: selectedEngineId,
            }
          : null
      }
      engine={selectedEngine}
      loading={loading}
      error={error}
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

