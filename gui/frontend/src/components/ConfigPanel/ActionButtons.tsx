/**
 * Action buttons for saving and resetting configuration.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

import { useState, useCallback } from 'react';
import { Spinner } from '../UI/Spinner';

interface ActionButtonsProps {
  onSave: () => Promise<void>;
  onReset: () => void;
  isSaving: boolean;
  saveError?: string | null;
}

export function ActionButtons({ onSave, onReset, isSaving, saveError }: ActionButtonsProps) {
  const [showSaveConfirm, setShowSaveConfirm] = useState(false);
  const [showResetConfirm, setShowResetConfirm] = useState(false);
  const [showSuccess, setShowSuccess] = useState(false);

  const handleSaveClick = useCallback(() => {
    setShowSaveConfirm(true);
  }, []);

  const handleSaveConfirm = useCallback(async () => {
    setShowSaveConfirm(false);
    try {
      await onSave();
      setShowSuccess(true);
      setTimeout(() => setShowSuccess(false), 3000);
    } catch {
      // Error handled by parent component
    }
  }, [onSave]);

  const handleSaveCancel = useCallback(() => {
    setShowSaveConfirm(false);
  }, []);

  const handleResetClick = useCallback(() => {
    setShowResetConfirm(true);
  }, []);

  const handleResetConfirm = useCallback(() => {
    setShowResetConfirm(false);
    onReset();
  }, [onReset]);

  const handleResetCancel = useCallback(() => {
    setShowResetConfirm(false);
  }, []);

  return (
    <div className="space-y-4">
      {/* Action Buttons */}
      <div className="flex gap-3">
        <button
          type="button"
          onClick={handleSaveClick}
          disabled={isSaving}
          className="flex-1 flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
        >
          {isSaving ? (
            <>
              <Spinner size="sm" />
              <span>Saving...</span>
            </>
          ) : (
            <>
              <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z" />
              </svg>
              <span>Save Config</span>
            </>
          )}
        </button>

        <button
          type="button"
          onClick={handleResetClick}
          disabled={isSaving}
          className="flex-1 flex items-center justify-center gap-2 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
        >
          <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path
              fillRule="evenodd"
              d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"
              clipRule="evenodd"
            />
          </svg>
          <span>Reset to Default</span>
        </button>
      </div>

      {/* Save Confirmation Modal */}
      {showSaveConfirm && (
        <div className="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
          <p className="text-sm text-yellow-800 mb-3">
            <strong>Save Configuration?</strong>
            <br />
            This will overwrite <code className="bg-yellow-100 px-1 py-0.5 rounded">
              config/build-config.json
            </code>
            . After saving, you must run <code className="bg-yellow-100 px-1 py-0.5 rounded">
              composer build
            </code>{' '}
            to regenerate the mod.
          </p>
          <div className="flex gap-2">
            <button
              type="button"
              onClick={handleSaveConfirm}
              className="px-3 py-1 text-sm bg-yellow-600 text-white rounded hover:bg-yellow-700 transition-colors"
            >
              Yes, Save
            </button>
            <button
              type="button"
              onClick={handleSaveCancel}
              className="px-3 py-1 text-sm bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition-colors"
            >
              Cancel
            </button>
          </div>
        </div>
      )}

      {/* Reset Confirmation Modal */}
      {showResetConfirm && (
        <div className="p-4 bg-red-50 border border-red-200 rounded-lg">
          <p className="text-sm text-red-800 mb-3">
            <strong>Reset to Default Values?</strong>
            <br />
            This will discard all your current changes and restore the default physics configuration.
          </p>
          <div className="flex gap-2">
            <button
              type="button"
              onClick={handleResetConfirm}
              className="px-3 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700 transition-colors"
            >
              Yes, Reset
            </button>
            <button
              type="button"
              onClick={handleResetCancel}
              className="px-3 py-1 text-sm bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition-colors"
            >
              Cancel
            </button>
          </div>
        </div>
      )}

      {/* Success Message */}
      {showSuccess && (
        <div className="p-3 bg-green-50 border border-green-200 rounded-lg">
          <div className="flex items-center gap-2 text-green-800">
            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
              <path
                fillRule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                clipRule="evenodd"
              />
            </svg>
            <span className="text-sm font-medium">Configuration saved successfully!</span>
          </div>
        </div>
      )}

      {/* Error Message */}
      {saveError && (
        <div className="p-3 bg-red-50 border border-red-200 rounded-lg">
          <div className="flex items-center gap-2 text-red-800">
            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
              <path
                fillRule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                clipRule="evenodd"
              />
            </svg>
            <span className="text-sm">{saveError}</span>
          </div>
        </div>
      )}
    </div>
  );
}
