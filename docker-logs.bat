@echo off
REM ============================================
REM Docker Helper - View Logs
REM ============================================

set /p MODE="Select mode (dev/prod): "

if /i "%MODE%"=="dev" (
    echo [INFO] Showing development logs...
    docker-compose -f docker-compose.dev.yml logs -f
) else if /i "%MODE%"=="prod" (
    echo [INFO] Showing production logs...
    docker-compose logs -f
) else (
    echo [ERROR] Invalid mode. Use 'dev' or 'prod'
    pause
    exit /b 1
)
