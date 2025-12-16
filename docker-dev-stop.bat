@echo off
REM ============================================
REM Docker Helper - Stop Development Environment
REM ============================================

echo [INFO] Stopping Payment Gateway Development Environment...
echo.

docker-compose -f docker-compose.dev.yml down

echo.
echo [SUCCESS] Containers stopped successfully!
echo.
echo To remove volumes (database data): docker-compose -f docker-compose.dev.yml down -v
pause
