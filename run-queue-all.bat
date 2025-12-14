@echo off
REM ======================================
REM Laravel Queue Worker - ALL QUEUES dengan PRIORITY
REM ======================================
REM
REM Worker ini proses semua queue dengan urutan priority:
REM 1. webhooks (HIGH) - Payment processing
REM 2. emails (LOW) - Email reminders
REM 3. default - Other jobs
REM
REM BEST PRACTICE untuk development/testing lokal
REM
REM ======================================

echo.
echo ========================================
echo   Laravel Queue Worker - ALL QUEUES
echo   With Priority Order
echo ========================================
echo.
echo Starting worker dengan priority order...
echo.
echo Queue Priority:
echo 1. webhooks (HIGH) - Payment webhooks
echo 2. emails (LOW) - Email reminders
echo 3. default - Other jobs
echo.
echo Configuration:
echo - Max Tries: 3 (default)
echo - Timeout: 90 seconds
echo - Sleep: 3 seconds between jobs
echo.
echo Press CTRL+C to stop worker
echo.
echo ========================================
echo.

REM Jalankan queue worker dengan priority
REM Webhooks akan selalu diproses dulu sebelum emails
php artisan queue:work --queue=webhooks,emails,default --tries=3 --timeout=90 --sleep=3 --verbose

pause
