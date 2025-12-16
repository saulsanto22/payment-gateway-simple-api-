# 🚀 Setup Cron Job untuk Production

## 📋 Overview

**Local Development:**
- Jalankan manual: `php artisan schedule:run`
- Atau pakai helper script untuk testing

**Production:**
- Setup cron job untuk auto-trigger scheduler
- **HANYA 1 CRON JOB** needed! Laravel schedule semua tasks internal

---

## 🎯 **The Golden Rule:**

### ❌ **JANGAN Buat Multiple Cron:**
```bash
# ❌ BAD: Multiple cron entries
0 10 * * * php /path/artisan order:reminder
0 9 * * 1 php /path/artisan report:weekly
0 0 1 * * php /path/artisan invoice:monthly
```

**Problem:**
- Hard to manage (setiap task = 1 cron entry)
- Tidak versioned (cron di server, bukan di code)
- Sulit maintain (edit cron = SSH ke server)

### ✅ **LAKUKAN: Single Cron:**
```bash
# ✅ GOOD: Only 1 cron entry
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

**Benefit:**
- Semua schedule di `routes/console.php` (versioned!)
- Ubah schedule = edit code + deploy (no SSH needed)
- Laravel manage execution internal
- Same setup untuk dev/staging/prod

---

## 🐧 **Linux/Ubuntu Setup**

### **Step 1: Edit Crontab**

```bash
# Login sebagai user yang jalankan aplikasi (biasanya www-data atau ubuntu)
sudo crontab -e -u www-data

# Atau kalau pakai user sendiri
crontab -e
```

### **Step 2: Add This Single Line**

```bash
* * * * * cd /var/www/html/your-project && php artisan schedule:run >> /dev/null 2>&1
```

**Penjelasan:**
- `* * * * *` → Jalankan **setiap menit**
- `cd /var/www/html/your-project` → Masuk ke folder project
- `php artisan schedule:run` → Trigger Laravel scheduler
- `>> /dev/null 2>&1` → Buang output (optional, atau redirect ke log file)

### **Step 3: Verify Cron Registered**

```bash
# Check crontab entries
crontab -l

# Check cron is running
sudo service cron status
```

---

## 🪟 **Windows Server Setup**

### **Option 1: Task Scheduler (GUI)**

1. **Buka Task Scheduler**
   - Win + R → `taskschd.msc`

2. **Create Basic Task**
   - Name: `Laravel Scheduler`
   - Trigger: **Daily** at `00:00`
   - Repeat: **Every 1 minute** for **1 day**

3. **Action: Start a Program**
   - Program: `C:\laragon\bin\php\php-8.x\php.exe`
   - Arguments: `artisan schedule:run`
   - Start in: `C:\laragon\www\your-project`

4. **Settings**
   - ✅ Run whether user logged on or not
   - ✅ Run with highest privileges
   - ✅ If task fails, restart every 1 minute

### **Option 2: PowerShell Script + Task Scheduler**

**Create file: `run-scheduler.ps1`**
```powershell
# Set location
Set-Location "C:\laragon\www\your-project"

# Run scheduler
& "C:\laragon\bin\php\php-8.x\php.exe" artisan schedule:run

# Optional: Log to file
# & "C:\laragon\bin\php\php-8.x\php.exe" artisan schedule:run >> storage\logs\scheduler.log 2>&1
```

**Schedule via Task Scheduler:**
- Program: `powershell.exe`
- Arguments: `-File "C:\path\to\run-scheduler.ps1"`
- Trigger: Repeat every 1 minute

---

## 🐳 **Docker Setup**

### **Dockerfile**

```dockerfile
FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    cron \
    && rm -rf /var/lib/apt/lists/*

# Copy cron file
COPY docker/cron/laravel-scheduler /etc/cron.d/laravel-scheduler

# Give execution rights
RUN chmod 0644 /etc/cron.d/laravel-scheduler

# Apply cron job
RUN crontab /etc/cron.d/laravel-scheduler

# Start cron
CMD cron && php-fpm
```

### **Cron File: `docker/cron/laravel-scheduler`**

```bash
* * * * * cd /var/www/html && php artisan schedule:run >> /var/log/cron.log 2>&1
```

### **docker-compose.yml**

```yaml
services:
  app:
    build: .
    volumes:
      - .:/var/www/html
    command: sh -c "cron && php-fpm"
```

---

## ☁️ **Cloud Platform Specific**

### **Heroku**

**Procfile:**
```
web: vendor/bin/heroku-php-apache2 public/
scheduler: php artisan schedule:work
```

**Heroku Scheduler Add-on:**
```bash
heroku addons:create scheduler:standard
heroku addons:open scheduler
```

Add task: `php artisan schedule:run` (every 10 minutes)

---

### **AWS EC2 (Ubuntu)**

Same sebagai Linux setup:

```bash
sudo crontab -e -u www-data
```

Add:
```bash
* * * * * cd /var/www/html/your-project && php artisan schedule:run >> /dev/null 2>&1
```

---

### **Laravel Forge (Recommended!)**

Forge otomatis setup cron! Tinggal:

1. Deploy project
2. Forge auto-add cron entry
3. Done! ✅

Cron yang di-setup:
```bash
* * * * * cd /home/forge/your-site && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🔍 **Verifikasi Cron Berjalan**

### **Method 1: Check Logs**

```bash
# Laravel log
tail -f storage/logs/laravel.log | grep "Reminder command"

# Cron log (Linux)
grep CRON /var/log/syslog
```

### **Method 2: Add Dummy Schedule (Testing)**

```php
// routes/console.php - Add temporary test schedule
Schedule::command('inspire')
    ->everyMinute()
    ->appendOutputTo(storage_path('logs/scheduler.log'));
```

**Check log setelah 1-2 menit:**
```bash
cat storage/logs/scheduler.log
```

Kalau ada output = cron working! ✅

---

## ⚠️ **Common Issues & Solutions**

### **Issue 1: Cron Not Running**

**Check:**
```bash
# Cron service running?
sudo service cron status

# Cron registered?
crontab -l

# PHP path correct?
which php
```

**Fix:**
```bash
# Restart cron
sudo service cron restart

# Use full PHP path in cron
* * * * * cd /path && /usr/bin/php artisan schedule:run
```

---

### **Issue 2: Permission Denied**

**Symptoms:**
```
Permission denied: storage/logs/laravel.log
```

**Fix:**
```bash
# Set proper permissions
sudo chown -R www-data:www-data storage/
sudo chmod -R 775 storage/
```

---

### **Issue 3: Schedule Not Running (But Cron OK)**

**Check:**
```bash
# Manual test
php artisan schedule:run -v

# Check schedule list
php artisan schedule:list

# Check timezone
php artisan tinker
>>> config('app.timezone')
```

**Fix:**
Pastikan timezone di `.env` atau `config/app.php` benar:
```php
'timezone' => 'Asia/Jakarta',  // WIB
```

---

### **Issue 4: Multiple Servers (Load Balanced)**

**Problem:** Scheduler jalan di semua server (duplikat!)

**Solution:** Gunakan `onOneServer()`

```php
Schedule::command('order:reminder-unpaid-order')
    ->dailyAt('10:00')
    ->onOneServer();  // ← FIX: Only 1 server execute
```

**Requires:** Cache driver yang shared (Redis, Memcached, Database)

```env
CACHE_STORE=redis  # NOT file!
```

---

## 📊 **Production Monitoring**

### **Option 1: Laravel Horizon**

```bash
composer require laravel/horizon
php artisan horizon:install
```

Dashboard: `https://your-domain.com/horizon`
- Monitor scheduled tasks
- View metrics & throughput
- Failed job management

### **Option 2: Schedule Monitor Package**

```bash
composer require spatie/laravel-schedule-monitor
php artisan schedule-monitor:install
```

**Features:**
- Alert kalau schedule tidak jalan
- Slack/Discord notification
- Health check endpoint

### **Option 3: Manual Monitoring**

**Create health check endpoint:**

```php
// routes/api.php
Route::get('/health/scheduler', function () {
    $lastRun = Cache::get('scheduler_last_run');
    $isHealthy = $lastRun && $lastRun->diffInMinutes(now()) < 5;
    
    return response()->json([
        'healthy' => $isHealthy,
        'last_run' => $lastRun,
    ], $isHealthy ? 200 : 500);
});
```

```php
// routes/console.php
Schedule::call(function () {
    Cache::put('scheduler_last_run', now(), now()->addHour());
})->everyMinute();
```

**Monitor via UptimeRobot/Pingdom:**
- Ping `/health/scheduler` every 5 minutes
- Alert kalau return 500

---

## ✅ **Production Deployment Checklist**

**Before Deploy:**
- [ ] Test scheduler locally (`php artisan schedule:run`)
- [ ] Test command manual (`php artisan order:reminder-unpaid-order`)
- [ ] Verify queue workers running
- [ ] Check timezone configuration

**During Deploy:**
- [ ] Setup cron job (single line!)
- [ ] Verify cron registered (`crontab -l`)
- [ ] Set proper permissions
- [ ] Test manual run on server

**After Deploy:**
- [ ] Monitor logs first 24 hours
- [ ] Verify schedule executed (check logs)
- [ ] Setup monitoring/alerts
- [ ] Document for team

---

## 🎯 **Best Practices Summary**

✅ **DO:**
- Single cron entry (`schedule:run` every minute)
- All schedules di `routes/console.php` (versioned!)
- Use `withoutOverlapping()` untuk long tasks
- Use `onOneServer()` untuk multi-server
- Monitor dengan logs atau dashboard
- Test locally before deploy

❌ **DON'T:**
- Multiple cron entries per task
- Hardcode schedule di crontab
- Forget timezone configuration
- Run without monitoring
- Ignore failed tasks

---

## 📞 **Troubleshooting Flowchart**

```
Scheduler tidak jalan?
    │
    ├─> Cron running? (service cron status)
    │   └─> NO: sudo service cron start
    │
    ├─> Cron registered? (crontab -l)
    │   └─> NO: Setup cron again
    │
    ├─> PHP path correct? (which php)
    │   └─> NO: Use full path in cron
    │
    ├─> Schedule registered? (schedule:list)
    │   └─> NO: Check routes/console.php
    │
    ├─> Timezone correct? (config app.timezone)
    │   └─> NO: Update timezone
    │
    └─> Permission OK? (ls -la storage/)
        └─> NO: chown -R www-data:www-data
```

---

**Done! Production scheduler setup complete!** 🎉

Next: Monitor first execution & setup alerts! 📊
