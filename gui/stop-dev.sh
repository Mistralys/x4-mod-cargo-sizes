#!/bin/bash
# Stop development servers for X4 Cargo Sizes Mod GUI

echo "Stopping development servers..."

if [ -f "logs/backend.pid" ]; then
    BACKEND_PID=$(cat logs/backend.pid)
    if kill -0 $BACKEND_PID 2>/dev/null; then
        kill $BACKEND_PID
        echo "Stopped backend server (PID: $BACKEND_PID)"
    else
        echo "Backend server not running"
    fi
    rm logs/backend.pid
fi

if [ -f "logs/frontend.pid" ]; then
    FRONTEND_PID=$(cat logs/frontend.pid)
    if kill -0 $FRONTEND_PID 2>/dev/null; then
        kill $FRONTEND_PID
        echo "Stopped frontend server (PID: $FRONTEND_PID)"
    else
        echo "Frontend server not running"
    fi
    rm logs/frontend.pid
fi

echo "Development servers stopped."
