/**
 * TwoColumnLayout - Left/right panel layout component.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import type { ReactNode } from 'react';

interface TwoColumnLayoutProps {
  leftPanel: ReactNode;
  rightPanel: ReactNode;
  leftWidth?: string;
}

/**
 * Two-column layout with adjustable widths.
 */
export function TwoColumnLayout({
  leftPanel,
  rightPanel,
  leftWidth = '40%',
}: TwoColumnLayoutProps) {
  return (
    <div className="flex gap-6 h-full">
      {/* Left panel */}
      <div
        className="bg-gray-50 rounded-lg shadow-sm border border-gray-200 overflow-y-auto"
        style={{ width: leftWidth }}
      >
        {leftPanel}
      </div>

      {/* Right panel */}
      <div className="flex-1 bg-gray-50 rounded-lg shadow-sm border border-gray-200 overflow-y-auto">
        {rightPanel}
      </div>
    </div>
  );
}
