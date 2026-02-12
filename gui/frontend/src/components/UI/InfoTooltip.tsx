/**
 * InfoTooltip - Hover tooltip for inline documentation.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import { useState } from 'react';

interface InfoTooltipProps {
  content: string;
  children?: React.ReactNode;
}

/**
 * Tooltip component that displays documentation on hover.
 */
export function InfoTooltip({ content, children }: InfoTooltipProps) {
  const [isVisible, setIsVisible] = useState(false);

  return (
    <div className="relative inline-block">
      <button
        type="button"
        className="inline-flex items-center justify-center w-5 h-5 ml-1 text-xs text-gray-500 border border-gray-300 rounded-full hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
        onMouseEnter={() => setIsVisible(true)}
        onMouseLeave={() => setIsVisible(false)}
        onFocus={() => setIsVisible(true)}
        onBlur={() => setIsVisible(false)}
        aria-label="More information"
      >
        {children || '?'}
      </button>

      {isVisible && (
        <div className="absolute z-50 w-64 p-3 mb-2 text-sm text-white bg-gray-900 rounded-lg shadow-lg bottom-full left-1/2 transform -translate-x-1/2">
          <div className="relative">
            {content}
            <div className="absolute w-3 h-3 bg-gray-900 transform rotate-45 -bottom-4 left-1/2 -translate-x-1/2"></div>
          </div>
        </div>
      )}
    </div>
  );
}
