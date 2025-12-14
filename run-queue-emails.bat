@echo off
REM ======================================
REM Laravel Queue Worker - LOW PRIORITY (Emails)
REM ======================================
REM
REM Worker ini untuk proses kirim email reminder
REM PRIORITY: LOW - Boleh delay beberapa menit
REM
REM Configuration:
REM - Queue: emails
REM - Tries: 5x (lebih toleran untuk email)
REM - Timeout: 60 seconds
REM - Retry backoff: 1m, 2m, 5m, 10m
REM
REM ======================================

echo.
echo ========================================
echo   Laravel Queue Worker - EMAILS
echo   Priority: LOW (Email Reminders)
echo ========================================
echo.
echo Starting worker untuk queue 'emails'...
echo.
echo Configuration:
echo - Max Tries: 5
echo - Timeout: 60 seconds
echo - Queue: emails only
echo.
echo Press CTRL+C to stop worker
echo.
echo ========================================
echo.

REM Jalankan queue worker untuk emails
php artisan queue:work --queue=emails --tries=5 --timeout=60 --sleep=3 --verbose

pause
