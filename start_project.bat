@echo off
echo Starting Smart AI Recruitment System...

echo Starting Backend Server (Laravel)...
start cmd /k "cd backend && php artisan serve"

echo Starting Frontend Assets (Vite)...
start cmd /k "cd backend && npm run dev"

echo Starting AI Service (FastAPI)...
start cmd /k "cd ai-service && python -m uvicorn app.main:app --port 8001 --reload"

echo Starting Queue Worker (Background Jobs)...
start cmd /k "cd backend && php artisan queue:work"

echo All services are starting in separate windows.
echo Please wait a few seconds and then open http://localhost:8000 in your browser.
pause
