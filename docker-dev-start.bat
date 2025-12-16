@echo off
REM ============================================
REM Docker Helper - Start Development Environment
REM ============================================
REM Analogi: Tombol power untuk nyalain semua
REM ============================================

echo [INFO] Starting Payment Gateway Development Environment...
echo.

REM Check if .env exists
if not exist .env (
    echo [ERROR] .env file not found!
    echo [INFO] Creating .env from .env.example...
    copy .env.example .env
    echo [INFO] Please configure .env file and run this script again.
    pause
    exit /b 1
)

REM Start containers
echo [INFO] Starting Docker containers...
docker-compose -f docker-compose.dev.yml up -d

REM Wait for containers to be healthy
echo [INFO] Waiting for containers to be ready...
timeout /t 10 /nobreak > nul

REM Check container status
echo.
echo [INFO] Container Status:
docker-compose -f docker-compose.dev.yml ps

REM Run migrations
echo.
echo [INFO] Running database migrations...
docker-compose -f docker-compose.dev.yml exec app php artisan migrate --force

REM Seed database (optional)
set /p SEED="Do you want to seed the database? (y/n): "
if /i "%SEED%"=="y" (
    echo [INFO] Seeding database...
    docker-compose -f docker-compose.dev.yml exec app php artisan db:seed
)

REM Generate app key if needed
docker-compose -f docker-compose.dev.yml exec app php artisan key:generate --ansi

REM Clear cache
echo [INFO] Clearing cache...
docker-compose -f docker-compose.dev.yml exec app php artisan config:clear
docker-compose -f docker-compose.dev.yml exec app php artisan cache:clear

echo.
echo ============================================
echo [SUCCESS] Development environment is ready!
echo ============================================
echo.
echo Application: http://localhost:8000
echo API Docs: http://localhost:8000/api/documentation
echo MailHog: http://localhost:8025
echo Database: localhost:5432
echo Redis: localhost:6379
echo.
echo To view logs: docker-compose -f docker-compose.dev.yml logs -f
echo To stop: docker-compose -f docker-compose.dev.yml down
echo ============================================
pause
