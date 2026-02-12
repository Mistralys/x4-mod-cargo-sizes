@echo off
REM Start both backend and frontend development servers for X4 Cargo Sizes Mod GUI
REM
REM Usage: start-dev.bat
REM
REM This script starts:
REM   1. PHP development server for backend API (port 8080)
REM   2. Vite development server for frontend (port 5173)
REM
REM Both servers run in separate terminal windows.

echo ========================================
echo X4 Cargo Sizes Mod - Physics Tuning GUI
echo ========================================
echo.
echo Starting development servers...
echo.

REM Check if backend directory exists
if not exist "backend\public\index.php" (
    echo ERROR: Backend not found. Please run from the gui/ directory.
    pause
    exit /b 1
)

REM Check if frontend directory exists
if not exist "frontend\package.json" (
    echo ERROR: Frontend not found. Please run from the gui/ directory.
    pause
    exit /b 1
)

echo Starting PHP backend server on http://localhost:8080
start "X4 GUI Backend" cmd /k "cd backend && php -S localhost:8080 -t public"

timeout /t 2 /nobreak >nul

echo Starting Vite frontend server on http://localhost:5173
start "X4 GUI Frontend" cmd /k "cd frontend && npm run dev"

echo.
echo ========================================
echo Development servers started!
echo ========================================
echo.
echo Backend API:  http://localhost:8080
echo Frontend UI:  http://localhost:5173
echo.
echo Press Ctrl+C in each terminal window to stop the servers.
echo.
pause
