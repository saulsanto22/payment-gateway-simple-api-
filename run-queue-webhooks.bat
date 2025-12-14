@echo off
REM ======================================
REM Laravel Queue Worker - HIGH PRIORITY (Webhooks)
REM ======================================
REM
REM Worker ini khusus untuk proses payment webhook dari Midtrans
REM PRIORITY: HIGH - Harus diproses secepat mungkin
REM
REM Configuration:
REM - Queue: webhooks
REM - Tries: 3x
REM - Timeout: 90 seconds
REM - Retry backoff: 10s, 30s, 60s
REM
REM ======================================

echo.
echo ========================================
echo   Laravel Queue Worker - WEBHOOKS
echo   Priority: HIGH (Payment Processing)
echo ========================================
echo.
echo Starting worker untuk queue 'webhooks'...
echo.
echo Configuration:
echo - Max Tries: 3
echo - Timeout: 90 seconds
echo - Queue: webhooks only
echo.
echo Press CTRL+C to stop worker
echo.
echo ========================================
echo.

REM Jalankan queue worker untuk webhooks
php artisan queue:work --queue=webhooks --tries=3 --timeout=90 --sleep=3 --verbose

pause
