/**
 * Local storage helpers for persisting UI state.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

const STORAGE_KEYS = {
  LAST_SHIP_ID: 'x4-gui-last-ship',
  LAST_ENGINE_ID: 'x4-gui-last-engine',
  LAST_MULTIPLIER: 'x4-gui-last-multiplier',
  LAST_SHIP_TYPE: 'x4-gui-last-ship-type',
  UI_STATE: 'x4-gui-ui-state',
} as const;

/**
 * UI state that can be persisted.
 */
export interface UIState {
  leftPanelWidth?: number;
  activeTab?: string;
  expandedSections?: string[];
}

/**
 * Storage API.
 */
export const storage = {
  /**
   * Get last selected ship ID.
   */
  getLastShipId(): string | null {
    return localStorage.getItem(STORAGE_KEYS.LAST_SHIP_ID);
  },

  /**
   * Save last selected ship ID.
   */
  setLastShipId(shipId: string): void {
    localStorage.setItem(STORAGE_KEYS.LAST_SHIP_ID, shipId);
  },

  /**
   * Get last selected engine ID.
   */
  getLastEngineId(): string | null {
    return localStorage.getItem(STORAGE_KEYS.LAST_ENGINE_ID);
  },

  /**
   * Save last selected engine ID.
   */
  setLastEngineId(engineId: string): void {
    localStorage.setItem(STORAGE_KEYS.LAST_ENGINE_ID, engineId);
  },

  /**
   * Get last used cargo multiplier.
   */
  getLastMultiplier(): number | null {
    const value = localStorage.getItem(STORAGE_KEYS.LAST_MULTIPLIER);
    return value ? parseFloat(value) : null;
  },

  /**
   * Save last used cargo multiplier.
   */
  setLastMultiplier(multiplier: number): void {
    localStorage.setItem(STORAGE_KEYS.LAST_MULTIPLIER, multiplier.toString());
  },

  /**
   * Get last selected ship type.
   */
  getLastShipType(): string | null {
    return localStorage.getItem(STORAGE_KEYS.LAST_SHIP_TYPE);
  },

  /**
   * Save last selected ship type.
   */
  setLastShipType(shipType: string): void {
    localStorage.setItem(STORAGE_KEYS.LAST_SHIP_TYPE, shipType);
  },

  /**
   * Get UI state.
   */
  getUIState(): UIState {
    const value = localStorage.getItem(STORAGE_KEYS.UI_STATE);
    return value ? JSON.parse(value) : {};
  },

  /**
   * Save UI state.
   */
  setUIState(state: UIState): void {
    localStorage.setItem(STORAGE_KEYS.UI_STATE, JSON.stringify(state));
  },

  /**
   * Clear all persisted data.
   */
  clearAll(): void {
    Object.values(STORAGE_KEYS).forEach((key) => {
      localStorage.removeItem(key);
    });
  },
};
