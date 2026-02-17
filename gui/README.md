# X4 Cargo Sizes Mod - Physics Tuning GUI

> **Interactive web-based GUI for real-time physics tuning and visualization**

> 🤖 **For AI Agents & Developers:** See the [Project Manifest](docs/project-manifest/README.md) for a structured overview of architecture, constraints, and data flows.

This GUI provides a visual interface for configuring and testing the X4 Cargo Sizes Mod's physics adjustments. You can adjust drag reduction, inertia, acceleration parameters, and instantly see how they affect ship performance with different cargo multipliers.

## Features

- **Real-time Physics Calculation**: Adjust parameters and see results within 500ms (300ms debounce + API round-trip)
- **Ship & Engine Selection**: Browse extracted game data, select specific ships and engines, view detailed thrust data
- **Visual Comparisons**: Side-by-side original vs. adjusted values with color-coded percentage changes
- **Configuration Management**: Save tuned configurations to `build-config.json` for use with `composer build`
- **Tier-Based System**: Edit drag and jerk reduction tiers for fine-grained control across cargo multiplier ranges
- **Responsive UI**: Modern React + TypeScript frontend with TailwindCSS styling

## Architecture Overview

```
/gui/
  /backend/         → PHP/Slim REST API (port 8080)
  /frontend/        → React/TypeScript/Vite UI (port 5173)
  start-dev.bat     → Windows start script
  start-dev.sh      → Linux/Mac start script
  stop-dev.sh       → Linux/Mac stop script
```

- **Backend**: PHP 8.4+ with Slim Framework 4 providing REST API endpoints
- **Frontend**: React 18 + TypeScript (strict mode) + Vite 7 + TailwindCSS v4
- **Data Flow**: Frontend → API → PhysicsCalculator → Response → ResultsPanel

See [docs/API.md](docs/API.md) for complete API documentation.

---

## Prerequisites

### Required

- **PHP 8.4+** with CLI support
- **Node.js 18+** and **npm 9+**
- **Composer 2.x**
- **X4 game data extracted** (via `mistralys/x4-data-extractor`)

### Verify Prerequisites

```bash
# Check PHP version
php -v  # Should be 8.4.0 or higher

# Check Node.js and npm versions
node -v  # Should be v18.0.0 or higher
npm -v   # Should be 9.0.0 or higher

# Check Composer version
composer -V  # Should be 2.x
```

---

## Installation

### Option 1: Using Composer Scripts (Recommended)

From the **project root**:

```bash
# Install both backend and frontend dependencies
composer gui:install
```

This runs:
- `composer install` in `gui/backend/`
- `npm install` in `gui/frontend/`

### Option 2: Manual Installation

From the **gui/** directory:

```bash
# Install backend dependencies
cd backend
composer install
cd ..

# Install frontend dependencies
cd frontend
npm install
cd ..
```

---

## Running the GUI

### Development Mode

#### Windows

From the **gui/** directory:

```cmd
start-dev.bat
```

This opens two terminal windows:
- **Backend API**: `http://localhost:8080`
- **Frontend UI**: `http://localhost:5173`

Press `Ctrl+C` in each window to stop.

#### Linux/Mac

From the **gui/** directory:

```bash
# Make scripts executable (first time only)
chmod +x start-dev.sh stop-dev.sh

# Start servers
./start-dev.sh
```

Servers run in the background with logs in `gui/logs/`:
- `backend.log` - PHP API server output
- `frontend.log` - Vite dev server output

**To stop servers:**

```bash
./stop-dev.sh
```

#### Using Composer Scripts

From the **project root**:

```bash
# Windows
composer gui:start-win

# Linux/Mac
composer gui:start
```

### Production Build

To build the frontend for production:

```bash
cd gui/frontend
npm run build
```

Output goes to `gui/frontend/dist/` and can be served by any static web server.

---

## Usage Workflow

### 1. Start Development Servers

```bash
# From gui/ directory
start-dev.bat        # Windows
./start-dev.sh       # Linux/Mac
```

### 2. Open Frontend

Navigate to `http://localhost:5173` in your browser.

### 3. Configure Physics Parameters

**Left Panel: Configuration**

- **Cargo Multiplier**: Select preset (2x, 4x, 6x, 8x, 10x) or enter custom value
- **Drag Reduction Factor**: Adjust base drag reduction (0.5–2.0)
- **Inertia Impact Factor**: Control rotational inertia effect (0.0–1.0)
- **Acceleration Responsiveness**: Tune jerk adjustments (0.5–2.0)
- **Effective Ratio Cap**: Toggle capping for extreme multipliers
- **Tier Editors**: Add/remove/edit drag and jerk reduction tiers

**Ship Selection**

- Filter by **Type**: Transport, Mining, Auxiliary, Carrier
- Filter by **Size**: XS, S, M, L, XL
- Select **Ship**: Choose from filtered list
- Select **Engine**: Pick compatible engine (optional for more detailed calculations)

### 4. View Results

**Right Panel: Physics Results**

- **Overview**: Mass ratio, effective ratio, original/adjusted mass
- **Comparisons** (tabs):
  - **Engine**: TWR, acceleration (when engine selected)
  - **Drag**: All axes (forward, reverse, horizontal, vertical, pitch, yaw, roll)
  - **Inertia**: Rotational values (pitch, yaw, roll)
  - **Jerk**: Acceleration rates (forward, boost, travel)
- **Diagnostics**: Active tier, configuration impact, tier breakdown

### 5. Save Configuration

Click **"Save Config"** → Confirm → Configuration written to `config/build-config.json`

This config is used by `composer build` to generate mod files.

### 6. Generate Mod

From **project root**:

```bash
composer build
```

Output: `build/` directory with mod XML files and ZIP packages.

---

## Troubleshooting

### Backend Issues

#### Backend server won't start

**Error**: `Address already in use`

**Solution**: Port 8080 is occupied. Kill the process or change the port:

```bash
# Windows
netstat -ano | findstr :8080
taskkill /PID <PID> /F

# Linux/Mac
lsof -ti:8080 | xargs kill -9
```

#### API returns 404 errors

**Check**:
- Backend server is running on `http://localhost:8080`
- File `gui/backend/public/index.php` exists
- Composer autoloader generated: `gui/backend/vendor/autoload.php`

**Fix**:
```bash
cd gui/backend
composer install
```

#### CORS errors in browser console

**Check**: CorsMiddleware is registered in `gui/backend/public/index.php`

**Fix**: CorsMiddleware should already be present. If missing, check WP-003 implementation.

### Frontend Issues

#### Frontend build fails with TypeScript errors

**Solution**: Ensure strict type compliance:

```bash
cd gui/frontend
npm run build
```

Fix any reported TypeScript errors. All files must use `declare(strict_types=1)` equivalent (strict: true in tsconfig).

#### Dependencies missing

**Error**: `Cannot find module 'axios'` or similar

**Solution**:
```bash
cd gui/frontend
npm install
```

Verify `gui/frontend/node_modules/` contains: axios, react, react-dom, lodash, recharts

#### Vite dev server won't start

**Error**: Port 5173 already in use

**Solution**: Change port in `gui/frontend/vite.config.ts`:

```typescript
export default defineConfig({
  server: {
    port: 5174, // Change port
  }
})
```

### Configuration Issues

#### "Failed to load configuration" error

**Check**:
- File exists: `config/build-config.json` (in project root, not gui/)
- File is valid JSON
- Backend can read the file (path resolution: 4 levels up from `gui/backend/public/`)

**Fix**:
```bash
# From project root
# Ensure config file exists with valid structure
cat config/build-config.json
```

#### Saved config doesn't persist

**Check**: Write permissions on `config/build-config.json`

**Fix** (Linux/Mac):
```bash
chmod 664 config/build-config.json
```

### Calculation Issues

#### Physics calculations don't update

**Check**:
1. Browser console for JavaScript errors
2. Network tab for failed API calls
3. Backend logs for PHP errors

**Debug**:
- Open browser DevTools (F12)
- Check Console tab for errors
- Check Network tab → `POST /api/calculate/physics` → Response

#### Results show "Select a ship first"

**Cause**: No ship selected

**Solution**: Use Ship Selector (left panel) to choose a ship type, filter by size, and select a ship.

#### Engine performance not showing

**Cause**: No engine selected

**Solution**: After selecting a ship, choose an engine from the Engine Picker dropdown. Engine performance calculations require an engine.

---

## Development Tips

### Hot Reload

Both servers support hot reload:
- **Backend**: Restart PHP server after PHP code changes
- **Frontend**: Vite auto-reloads on file changes (no restart needed)

### Debugging

**Backend API**:
```bash
# View backend logs (Linux/Mac)
tail -f gui/logs/backend.log

# Test endpoint directly
curl http://localhost:8080/api/config
```

**Frontend**:
- Use React DevTools browser extension
- Check browser console (F12 → Console)
- Use Network tab to inspect API calls

### Code Style

**Backend** (PHP):
- PSR-12 coding standard
- Strict types: `declare(strict_types=1);`
- Type hints on all parameters and return types

**Frontend** (TypeScript):
- Strict mode enabled
- No `any` types (use proper interfaces)
- Functional components with hooks

### Testing

**Backend** (PHPUnit 12.5+):

The GUI backend has a comprehensive PHPUnit test suite with 25+ tests covering Services, Utils, DTOs, and API layers.

```bash
# Run all tests (Unit + Integration)
cd gui/backend
composer test

# Run only unit tests (fast)
composer test:unit

# Generate HTML coverage report (requires XDebug)
export XDEBUG_MODE=coverage  # Linux/Mac
set XDEBUG_MODE=coverage     # Windows CMD
$env:XDEBUG_MODE="coverage"  # Windows PowerShell
composer test:coverage

# Coverage report will be in gui/backend/coverage/index.html
```

**Test Structure:**
- **Unit Tests:** `tests/Unit/` - Fast, isolated tests with mocked dependencies
- **Integration Tests:** `tests/Integration/` - Full workflow tests (future endpoint tests)
- **Execution Time:** All 25 tests run in <0.2 seconds
- **Coverage:** HTML reports generated in `coverage/` directory

See [docs/DEVELOPMENT.md](docs/DEVELOPMENT.md#testing) for detailed testing guidelines.

**Frontend** (build verification):
```bash
cd gui/frontend
npm run build
```

---

## File Structure

```
/gui/
  start-dev.bat              # Windows start script
  start-dev.sh               # Linux/Mac start script
  stop-dev.sh                # Linux/Mac stop script
  README.md                  # This file
  /backend/
    composer.json            # Backend dependencies
    /public/
      index.php              # API entry point
    /src/
      /API/
        /Endpoints/          # PhysicsEndpoint, ShipsEndpoint, ConfigEndpoint
        /Middleware/         # CorsMiddleware
        Router.php           # Route registration
      /Services/             # PhysicsService, ShipDataService, ConfigService
      /DTOs/                 # Data Transfer Objects
      /Exceptions/           # GUIException
  /frontend/
    package.json             # Frontend dependencies
    vite.config.ts           # Vite configuration
    tsconfig.json            # TypeScript configuration
    tailwind.config.js       # TailwindCSS configuration
    /src/
      App.tsx                # Main application component
      main.tsx               # Entry point
      /components/
        /ConfigPanel/        # Physics configuration UI
        /ShipSelector/       # Ship and engine selection UI
        /ResultsPanel/       # Physics results display
        /Layout/             # Header, Footer, TwoColumnLayout
        /UI/                 # Reusable components (Card, Tabs, Spinner, etc.)
      /hooks/                # Custom React hooks
      /services/             # API client
      /types/                # TypeScript type definitions
      /styles/               # Global CSS
  /docs/
    API.md                   # API endpoint documentation
  /logs/                     # Server logs (created by start-dev.sh)
```

---

## Updating Configuration

After tuning physics parameters in the GUI:

1. Click **"Save Config"** in the GUI
2. Confirm the save operation
3. Configuration written to `config/build-config.json`
4. Run `composer build` from project root
5. Install generated mod from `build/` directory

**Note**: The GUI only updates `build-config.json`. You must run `composer build` to regenerate mod files.

---

## Performance Notes

- **API Response Time**: Target < 100ms for single calculations
- **Debounce**: Slider changes debounced at 300ms to reduce API load
- **Caching**: Ship/engine data cached client-side to avoid redundant fetches
- **Initial Load**: Target < 2 seconds for complete page load

---

## Known Limitations & Future Enhancements

The GUI is production-ready for its intended use as a local development tool. The following items are non-blocking opportunities for future enhancement:

### Current Limitations

1. **Sample Ship Data**: Ship selection currently uses hardcoded sample ships. Future versions will support full ship catalog from extracted game files.

2. **Sample Physics Values**: Initial calculations use sample drag/inertia/jerk values. The API supports real values; future frontend versions will enable loading actual ship physics from XML files.

3. **Engine Compatibility**: Engine list is filtered by ship size but doesn't reflect actual ship-engine compatibility. Future versions will implement full compatibility matrix.

### Potential Enhancements

Based on code review (February 2026), the following non-critical improvements have been identified:

- **Path Validation** (CR-001): Add validation to ConfigService file path operations for additional robustness
- **Error Codes** (CR-002): Implement specific error codes for different failure modes to improve debugging
- **Configuration Constants** (CR-003): Extract magic numbers (gravity, sample values) to dedicated configuration
- **Response Caching** (CR-004): Add server-side caching for ship/engine lookups to improve performance
- **PHPDoc Coverage** (CR-005): Add documentation blocks for complex private methods

These enhancements are **not required** for current functionality but may be implemented in future versions to support expanded use cases or production deployment.

---

## Contributing

See project documentation for contribution guidelines.

For GUI-specific development:
- Follow existing component patterns
- Maintain TypeScript strict mode
- Use TailwindCSS utility classes (no custom CSS unless necessary)
- Test with multiple ships and cargo multipliers

---

## Related Documentation

- [API Documentation](docs/API.md) - Complete REST API reference
- [Physics Tuning Guide](../docs/physics-tuning-guide.md) - Physics concepts and parameters
- [Project README](../README.md) - Main project documentation

---

## Support

For issues, bugs, or questions:
- Check this README's troubleshooting section
- Review [docs/API.md](docs/API.md) for API details
- Check project issues on GitHub (if applicable)

---

**Version**: 1.0  
**Last Updated**: February 12, 2026
