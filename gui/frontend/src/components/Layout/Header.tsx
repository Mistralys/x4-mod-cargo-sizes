/**
 * Header - Application header component.
 *
 * @package X4 Cargo Sizes Mod - Physics Tuning GUI
 */

/**
 * App header with title and optional actions.
 */
export function Header() {
  return (
    <header className="bg-gradient-to-r from-blue-600 to-blue-800 text-white shadow-lg">
      <div className="container mx-auto px-6 py-4">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold">X4 Physics Tuning Tool</h1>
            <p className="text-sm text-blue-100 mt-1">
              Cargo Sizes Mod - Flight Mechanics Configuration
            </p>
          </div>
          <div className="flex items-center gap-4">
            <span className="text-sm text-blue-100">v3.0.0</span>
          </div>
        </div>
      </div>
    </header>
  );
}
