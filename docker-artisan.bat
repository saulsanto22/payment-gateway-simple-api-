@echo off
REM ============================================
REM Docker Helper - Execute Artisan Commands
REM ============================================

set /p MODE="Select mode (dev/prod): "

if /i "%MODE%"=="dev" (
    docker-compose -f docker-compose.dev.yml exec app php artisan %*
) else if /i "%MODE%"=="prod" (.github/workflows/
├── tests.yml         ← Auto run tests
├── deploy.yml        ← Auto deploy
└── scheduled.yml     ← Health checks
    docker-compose exec app php artisan %*
) else (
    echo [ERROR] Invalid mode. Use 'dev' or 'prod'
    pause
    exit /b 1
)
