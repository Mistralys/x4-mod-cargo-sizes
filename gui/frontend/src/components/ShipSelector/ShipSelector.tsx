/**
 * Ship selector container component.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import { useState, useCallback, useEffect } from 'react';
import { useShipData } from '../../hooks/useShipData';
import type { ShipType, ShipSize } from '../../types/ships';
import { TypeFilter } from './TypeFilter';
import { SizeFilter } from './SizeFilter';
import { ShipPicker } from './ShipPicker';
import { EnginePicker } from './EnginePicker';
import { Card } from '../UI/Card';
import { Spinner } from '../UI/Spinner';

interface ShipSelectorProps {
  onShipChange?: (shipId: string | null) => void;
  onEngineChange?: (engineId: string | null) => void;
}

export function ShipSelector({ onShipChange, onEngineChange }: ShipSelectorProps) {
  const {
    shipTypes,
    ships,
    shipDetails,
    engines,
    loading,
    error,
    loadShipTypes,
    loadShipsByType,
    loadShipDetails,
    loadEnginesForShip,
  } = useShipData();

  const [selectedType, setSelectedType] = useState<ShipType | null>(null);
  const [selectedSize, setSelectedSize] = useState<ShipSize | null>(null);
  const [selectedEngineId, setSelectedEngineId] = useState<string | null>(null);
  const [filteredShips, setFilteredShips] = useState(ships);

  // Load ship types on mount
  useEffect(() => {
    loadShipTypes();
  }, [loadShipTypes]);

  // Load ships when type changes
  useEffect(() => {
    if (selectedType) {
      loadShipsByType(selectedType);
    }
  }, [selectedType, loadShipsByType]);

  // Filter ships by size
  useEffect(() => {
    if (selectedSize) {
      setFilteredShips(ships.filter((ship) => ship.size === selectedSize));
    } else {
      setFilteredShips(ships);
    }
  }, [ships, selectedSize]);

  // Load engines when ship changes
  useEffect(() => {
    if (shipDetails) {
      loadEnginesForShip(shipDetails.id);
      onShipChange?.(shipDetails.id);
    } else {
      onShipChange?.(null);
    }
  }, [shipDetails, loadEnginesForShip, onShipChange]);

  // Notify parent of engine changes
  useEffect(() => {
    onEngineChange?.(selectedEngineId);
  }, [selectedEngineId, onEngineChange]);

  const handleTypeChange = useCallback((type: ShipType) => {
    setSelectedType(type);
    setSelectedSize(null); // Reset size filter
  }, []);

  const handleSizeChange = useCallback((size: ShipSize | null) => {
    setSelectedSize(size);
  }, []);

  const handleShipSelect = useCallback(
    (shipId: string) => {
      if (shipId) {
        loadShipDetails(shipId);
        setSelectedEngineId(null); // Reset engine when ship changes
      }
    },
    [loadShipDetails]
  );

  const handleEngineSelect = useCallback((engineId: string | null) => {
    setSelectedEngineId(engineId);
  }, []);

  if (error) {
    return (
      <div className="p-6">
        <Card title="Ship & Engine Selection">
          <div className="p-4 bg-red-50 border border-red-200 rounded-lg">
            <p className="text-sm text-red-800">
              <strong>Error:</strong> {error}
            </p>
          </div>
        </Card>
      </div>
    );
  }

  return (
    <div className="p-6">
      <Card title="Ship & Engine Selection">
        <div className="space-y-4">
          {shipTypes.length === 0 && loading ? (
            <div className="flex flex-col items-center justify-center py-8">
              <Spinner size="lg" />
              <p className="mt-4 text-sm text-gray-600">Loading ship types...</p>
            </div>
          ) : (
            <>
              <TypeFilter
                selectedType={selectedType}
                onTypeChange={handleTypeChange}
                availableTypes={shipTypes}
              />

              {selectedType && (
                <>
                  <SizeFilter selectedSize={selectedSize} onSizeChange={handleSizeChange} />

                  <ShipPicker
                    ships={filteredShips}
                    selectedShip={shipDetails}
                    onShipSelect={handleShipSelect}
                    loading={loading}
                  />

                  <EnginePicker
                    engines={engines}
                    selectedEngineId={selectedEngineId}
                    onEngineSelect={handleEngineSelect}
                    loading={loading}
                    shipSelected={!!shipDetails}
                  />
                </>
              )}
            </>
          )}
        </div>
      </Card>
    </div>
  );
}
