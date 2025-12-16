@echo off
REM ============================================
REM Docker Helper - Start Production Environment
REM ============================================

echo [INFO] Starting Payment Gateway Production Environment...
echo.

REM Check if .env.production exists
if not exist .env.production (
    echo [ERROR] .env.production file not found!
    echo [INFO] Please create .env.production with production settings.
    pause
    exit /b 1
)

REM Copy production env
copy /y .env.production .env

REM Build production image
echo [INFO] Building production image...
docker-compose build --no-cache

REM Start containers
echo [INFO] Starting containers...
docker-compose up -d

REM Wait for containers
echo [INFO] Waiting for containers to be ready...
timeout /t 15 /nobreak > nul

REM Run migrations
echo [INFO] Running migrations...
docker-compose exec app php artisan migrate --force

REM Optimize for production
echo [INFO] Optimizing for production...
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

echo.
echo ============================================
echo [SUCCESS] Production environment is ready!
echo ============================================
echo.
echo Application: http://localhost:8080
echo.
echo To view logs: docker-compose logs -f
echo To stop: docker-compose down
echo ============================================
pause
