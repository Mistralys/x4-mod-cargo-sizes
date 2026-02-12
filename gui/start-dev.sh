#!/bin/bash
# Start both backend and frontend development servers for X4 Cargo Sizes Mod GUI
#
# Usage: ./start-dev.sh
#
# This script starts:
#   1. PHP development server for backend API (port 8080)
#   2. Vite development server for frontend (port 5173)
#
# Both servers run in the background with output to log files.

echo "========================================"
echo "X4 Cargo Sizes Mod - Physics Tuning GUI"
echo "========================================"
echo ""
echo "Starting development servers..."
echo ""

# Check if backend directory exists
if [ ! -f "backend/public/index.php" ]; then
    echo "ERROR: Backend not found. Please run from the gui/ directory."
    exit 1
fi

# Check if frontend directory exists
if [ ! -f "frontend/package.json" ]; then
    echo "ERROR: Frontend not found. Please run from the gui/ directory."
    exit 1
fi

# Create logs directory if it doesn't exist
mkdir -p logs

# Start backend server
echo "Starting PHP backend server on http://localhost:8080"
cd backend
php -S localhost:8080 -t public > ../logs/backend.log 2>&1 &
BACKEND_PID=$!
cd ..

# Wait a moment for backend to start
sleep 2

# Start frontend server
echo "Starting Vite frontend server on http://localhost:5173"
cd frontend
npm run dev > ../logs/frontend.log 2>&1 &
FRONTEND_PID=$!
cd ..

# Save PIDs for cleanup
echo $BACKEND_PID > logs/backend.pid
echo $FRONTEND_PID > logs/frontend.pid

echo ""
echo "========================================"
echo "Development servers started!"
echo "========================================"
echo ""
echo "Backend API:  http://localhost:8080"
echo "Frontend UI:  http://localhost:5173"
echo ""
echo "Backend PID:  $BACKEND_PID (log: logs/backend.log)"
echo "Frontend PID: $FRONTEND_PID (log: logs/frontend.log)"
echo ""
echo "To stop servers, run: ./stop-dev.sh"
echo "Or kill processes manually: kill $BACKEND_PID $FRONTEND_PID"
echo ""

# Optional: Follow logs
# tail -f logs/backend.log logs/frontend.log
