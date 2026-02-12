/**
 * Results panel container component displaying physics calculation results.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import type { PhysicsResponse, PhysicsConfig, EngineDef } from '../../types/physics';
import { PhysicsOverview } from './PhysicsOverview';
import { ComparisonView } from './ComparisonView';
import { DiagnosticsPanel } from './DiagnosticsPanel';
import { Card } from '../UI/Card';
import { Spinner } from '../UI/Spinner';

interface ResultsPanelProps {
  data: PhysicsResponse | null;
  config: PhysicsConfig | null;
  engine?: EngineDef | null;
  loading?: boolean;
  error?: string | null;
}

export function ResultsPanel({ data, config, engine, loading, error }: ResultsPanelProps) {
  // Loading state
  if (loading) {
    return (
      <div className="space-y-6">
        <Card title="Physics Results">
          <div className="flex flex-col items-center justify-center py-16">
            <Spinner size="lg" />
            <p className="mt-4 text-sm text-gray-600">Calculating physics...</p>
          </div>
        </Card>
      </div>
    );
  }

  // Error state
  if (error) {
    return (
      <div className="space-y-6">
        <Card title="Physics Results">
          <div className="flex flex-col items-center justify-center py-12">
            <svg
              className="w-16 h-16 text-red-300 mb-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
            <p className="text-sm font-medium text-red-600">Calculation Error</p>
            <p className="mt-2 text-xs text-gray-500">{error}</p>
          </div>
        </Card>
      </div>
    );
  }

  // No data state
  if (!data || !config) {
    return (
      <div className="space-y-6">
        <Card title="Physics Results">
          <div className="flex flex-col items-center justify-center py-12 text-gray-500">
            <svg
              className="w-16 h-16 text-gray-300"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
              />
            </svg>
            <p className="mt-4 text-sm font-medium">Ready to Calculate</p>
            <p className="mt-2 text-xs text-gray-400">
              Adjust configuration parameters to see physics results
            </p>
          </div>
        </Card>
      </div>
    );
  }

  // Results display
  return (
    <div className="space-y-6">
      {/* Overview Cards */}
      <Card title="Physics Overview">
        <PhysicsOverview data={data} />
      </Card>

      {/* Comparison Tabs */}
      <ComparisonView data={data} engine={engine} />

      {/* Diagnostics */}
      <DiagnosticsPanel activeTier={data.activeTier} config={config} />
    </div>
  );
}
