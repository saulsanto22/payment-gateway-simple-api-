# 🚀 Menjalankan Queue Workers

## 📋 Overview

Project ini menggunakan **3 queue berbeda** dengan priority:
1. **webhooks** (HIGH) - Payment notifications dari Midtrans
2. **emails** (LOW) - Email reminders untuk unpaid orders
3. **default** - Queue umum (jika ada)

---

## 🔧 Development (Lokal)

### 1. Start Single Worker (Semua Queue)

```bash
php artisan queue:work
```

**Kapan Pakai:**
- Development/testing lokal
- Traffic rendah
- Hanya test functionality

**Kekurangan:**
- Semua queue priority sama
- Jika ada banyak email job, webhook bisa delay

---

### 2. Start Worker dengan Priority (Recommended untuk Dev)

```bash
php artisan queue:work --queue=webhooks,emails,default
```

**Penjelasan:**
- Worker akan cek queue `webhooks` dulu
- Jika kosong, baru cek `emails`
- Jika kosong, baru cek `default`

**Benefit:**
- Payment webhook SELALU diproses first
- Email tidak akan block payment processing

**Test Priority:**
```bash
# Terminal 1: Start worker
php artisan queue:work --queue=webhooks,emails,default

# Terminal 2: Dispatch jobs
php artisan tinker
>>> SendOrderReminderJob::dispatch(1)->onQueue('emails');
>>> ProcessMidtransWebhook::dispatch(['order_id' => 'TEST'])->onQueue('webhooks');

# Lihat di Terminal 1: Webhook diproses dulu meski email dispatch lebih dulu!
```

---

### 3. Start Multiple Workers (Parallel Processing)

```bash
# Terminal 1: Dedicated untuk webhooks (high priority)
php artisan queue:work --queue=webhooks --tries=3 --timeout=90

# Terminal 2: Dedicated untuk emails (low priority)
php artisan queue:work --queue=emails --tries=5 --timeout=60

# Terminal 3: Backup untuk default queue
php artisan queue:work --queue=default
```

**Benefit:**
- Webhook dan email diproses parallel
- Tidak saling block
- Max throughput

---

### 4. Watch Mode (Auto-restart saat code berubah)

```bash
php artisan queue:listen --queue=webhooks,emails,default
```

**Kapan Pakai:**
- Development saat lagi coding job
- Otomatis reload saat file berubah

**Kekurangan:**
- Lebih lambat dari `queue:work`
- RAM usage lebih tinggi

---

## 🚀 Production

### Option 1: Supervisor (Recommended)

**Install Supervisor:**
```bash
# Ubuntu/Debian
sudo apt-get install supervisor

# CentOS/RHEL  
sudo yum install supervisor
```

**Create Config File:**
```bash
sudo nano /etc/supervisor/conf.d/laravel-worker-webhooks.conf
```

**Config untuk Webhooks Worker:**
```ini
[program:laravel-worker-webhooks]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/artisan queue:work --queue=webhooks --tries=3 --timeout=90 --sleep=3 --max-jobs=1000
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/your/storage/logs/worker-webhooks.log
stopwaitsecs=3600
```

**Config untuk Emails Worker:**
```bash
sudo nano /etc/supervisor/conf.d/laravel-worker-emails.conf
```

```ini
[program:laravel-worker-emails]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/artisan queue:work --queue=emails --tries=5 --timeout=60 --sleep=3 --max-jobs=1000
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/your/storage/logs/worker-emails.log
stopwaitsecs=3600
```

**Start Supervisor:**
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

**Check Status:**
```bash
sudo supervisorctl status
```

**Restart Workers:**
```bash
# Setelah deploy code baru
sudo supervisorctl restart all

# Atau via artisan
php artisan queue:restart
```

---

### Option 2: Systemd Service

**Create Service File:**
```bash
sudo nano /etc/systemd/system/laravel-queue-webhooks.service
```

```ini
[Unit]
Description=Laravel Queue Worker - Webhooks
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/your/project
ExecStart=/usr/bin/php /path/to/your/artisan queue:work --queue=webhooks --tries=3 --timeout=90
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

**Enable & Start:**
```bash
sudo systemctl daemon-reload
sudo systemctl enable laravel-queue-webhooks
sudo systemctl start laravel-queue-webhooks
sudo systemctl status laravel-queue-webhooks
```

---

### Option 3: Laravel Horizon (Best untuk Production)

**Install Horizon:**
```bash
composer require laravel/horizon
php artisan horizon:install
```

**Publish Config:**
```bash
php artisan vendor:publish --provider="Laravel\Horizon\HorizonServiceProvider"
```

**Edit config/horizon.php:**
```php
'environments' => [
    'production' => [
        'supervisor-webhooks' => [
            'queue' => ['webhooks'],
            'balance' => 'simple',
            'processes' => 3,
            'tries' => 3,
            'timeout' => 90,
        ],
        'supervisor-emails' => [
            'queue' => ['emails'],
            'balance' => 'simple',
            'processes' => 1,
            'tries' => 5,
            'timeout' => 60,
        ],
    ],
],
```

**Start Horizon:**
```bash
php artisan horizon
```

**Access Dashboard:**
```
http://your-domain.com/horizon
```

**Setup dengan Supervisor:**
```ini
[program:horizon]
process_name=%(program_name)s
command=php /path/to/your/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/your/storage/logs/horizon.log
stopwaitsecs=3600
```

---

## 🔍 Monitoring & Debugging

### Check Queue Status

```bash
# Cek failed jobs
php artisan queue:failed

# Retry specific failed job
php artisan queue:retry 5

# Retry all failed jobs
php artisan queue:retry all

# Flush all failed jobs
php artisan queue:flush

# Work specific job
php artisan queue:work --once
```

### Check Database Queue Table

```sql
-- Lihat pending jobs
SELECT * FROM jobs ORDER BY created_at DESC;

-- Count per queue
SELECT queue, COUNT(*) as total FROM jobs GROUP BY queue;

-- Lihat failed jobs
SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 10;
```

### Log Monitoring

```bash
# Real-time log watching
tail -f storage/logs/laravel.log

# Filter untuk job logs
tail -f storage/logs/laravel.log | grep "Processing"

# Failed jobs only
tail -f storage/logs/laravel.log | grep "failed"
```

---

## ⚡ Performance Tips

### 1. Optimize Worker Count

**Rule of Thumb:**
- Webhooks: 2-3 workers (high throughput)
- Emails: 1-2 workers (rate limited by SMTP)
- CPU cores: Total workers < CPU cores

**Test Load:**
```bash
# Dispatch 1000 jobs
php artisan tinker
>>> for($i=0; $i<1000; $i++) { ProcessMidtransWebhook::dispatch(['test' => $i])->onQueue('webhooks'); }
```

### 2. Set Max Jobs per Worker

```bash
php artisan queue:work --max-jobs=1000
```

**Why:**
- Prevent memory leaks
- Worker restart otomatis setelah 1000 jobs
- Fresh process = fresh memory

### 3. Set Max Time per Worker

```bash
php artisan queue:work --max-time=3600
```

**Why:**
- Worker restart setiap 1 jam
- Prevent long-running worker issues

### 4. Database Optimization

```sql
-- Add index untuk faster queue processing
CREATE INDEX jobs_queue_index ON jobs(queue);
CREATE INDEX jobs_reserved_at_index ON jobs(reserved_at);
```

---

## 🚨 Troubleshooting

### Worker Tidak Jalan

```bash
# Check queue driver di .env
QUEUE_CONNECTION=database

# Check database table ada
php artisan queue:table
php artisan migrate

# Check permission
sudo chown -R www-data:www-data storage/
```

### Job Stuck/Hanging

```bash
# Clear stuck jobs
php artisan queue:clear

# Restart workers
php artisan queue:restart
```

### Failed Jobs Terus

```bash
# Check error
php artisan queue:failed

# Read specific failed job
php artisan queue:failed-table
php artisan migrate
```

---

## 📊 Best Practices Summary

✅ **DO:**
- Use multiple queues dengan priority
- Set proper timeout & tries
- Monitor failed jobs regularly
- Use Supervisor/Horizon di production
- Log semua job execution
- Set max-jobs untuk prevent memory leak
- Restart workers setelah deployment

❌ **DON'T:**
- Run `queue:work` di development tanpa restart
- Ignore failed jobs
- Set timeout terlalu rendah
- Queue job yang seharusnya synchronous
- Forget to monitor queue length

---

## 🔄 Deployment Checklist

**Sebelum Deploy:**
- [ ] Test semua job di staging
- [ ] Set proper queue configuration
- [ ] Setup supervisor/systemd

**Saat Deploy:**
- [ ] `php artisan queue:restart` (graceful restart)
- [ ] `sudo supervisorctl restart all` (force restart)

**Setelah Deploy:**
- [ ] Check `php artisan queue:failed`
- [ ] Monitor queue length: `SELECT COUNT(*) FROM jobs`
- [ ] Check worker logs: `sudo supervisorctl tail -f laravel-worker-webhooks`

---

## 📞 Help & Support

**Queue tidak jalan:**
1. Check `.env` → `QUEUE_CONNECTION=database`
2. Check table jobs exists
3. Check worker running: `ps aux | grep queue:work`

**Job always failed:**
1. Check error: `php artisan queue:failed`
2. Check timeout cukup
3. Check dependencies (external API)
4. Test dengan `dispatchSync()` untuk debugging

**Performance issue:**
1. Check queue length growth
2. Add more workers
3. Optimize job code
4. Consider Redis driver
