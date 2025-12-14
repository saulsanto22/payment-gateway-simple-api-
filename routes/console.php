<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes & Scheduled Tasks
|--------------------------------------------------------------------------
|
| File ini untuk define:
| 1. Artisan commands (inline)
| 2. Scheduled tasks (cron jobs)
|
| BEST PRACTICE:
| - Gunakan Schedule facade untuk scheduling
| - Add logging untuk monitor execution
| - Prevent overlap untuk long-running tasks
| - Set timezone jika perlu
|
*/

// Example command (bawaan Laravel)
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks (Cron Jobs)
|--------------------------------------------------------------------------
|
| Define semua scheduled tasks di sini.
| Cron job di server hanya perlu 1 line:
| * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
|
*/

/**
 * Send reminder emails untuk unpaid orders
 *
 * SCHEDULE: Setiap hari jam 10:00 AM (WIB)
 * WHY: Customer biasanya cek email pagi hari (9-11 AM)
 *
 * FLOW:
 * 1. Command cari semua order dengan status PENDING
 * 2. Untuk setiap order, dispatch SendOrderReminderJob ke queue 'emails'
 * 3. Queue worker (emails) akan kirim email reminder
 *
 * BEST PRACTICES yang di-apply:
 * - withoutOverlapping(): Prevent command jalan 2x parallel (kalau yg lama belum selesai)
 * - onOneServer(): Kalau multi-server, hanya 1 server yang jalankan
 * - runInBackground(): Jalan di background, tidak block scheduler
 */
Schedule::command('order:reminder-unpaid-order')
    ->dailyAt('10:00')              // Jam 10 pagi setiap hari
    ->withoutOverlapping()          // Prevent overlap (kalau command lama belum selesai)
    ->onOneServer()                 // Kalau multi-server, hanya 1 yang jalankan
    ->runInBackground();            // Jalan di background, tidak block scheduler lain

/**
 * TODO: Add more scheduled tasks sesuai kebutuhan
 *
 * Contoh tasks yang umum:
 * - Database backup (daily/weekly)
 * - Cleanup old logs (weekly)
 * - Generate reports (daily/monthly)
 * - Expire old orders (hourly)
 * - Update currency rates (daily)
 */
