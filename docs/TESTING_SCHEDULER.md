# 🧪 Testing Laravel Scheduler Locally

## ✅ Quick Commands

### 1. **Lihat Daftar Scheduled Tasks**
```bash
php artisan schedule:list
```

**Output:**
```
0 10 * * *  php artisan order:reminder-unpaid-order  Next Due: 9 hours from now
```

**Penjelasan:**
- `0 10 * * *` = Cron expression (jam 10:00 setiap hari)
- `Next Due` = Kapan schedule berikutnya akan jalan

---

### 2. **Test Run Scheduler (Manual Trigger)**
```bash
php artisan schedule:run
```

**Apa yang Terjadi:**
- Laravel cek semua scheduled tasks
- Kalau ada yang **due now** (waktunya sekarang), jalankan
- Kalau belum waktunya, skip

**Contoh Output:**
```
No scheduled commands are ready to run.
```
Artinya: Tidak ada task yang waktunya sekarang.

---

### 3. **Force Run Specific Command (Bypass Schedule)**
```bash
php artisan order:reminder-unpaid-order
```

**Kapan Pakai:**
- Test command logic tanpa tunggu schedule
- Debugging
- Manual trigger saat emergency

**Expected Output:**
```
Reminder emails dispatched for X unpaid orders.
```

---

## 🎯 **Scenario Testing**

### **Test 1: Command Jalan Manual (Tanpa Schedule)**

**Step:**
```bash
# 1. Jalankan queue worker di terminal 1
.\run-queue-emails.bat

# 2. Di terminal 2, trigger command manual
php artisan order:reminder-unpaid-order

# 3. Lihat terminal 1, seharusnya ada job yang diproses:
# Processing: App\Jobs\SendOrderReminderJob
```

**Sukses Jika:**
- ✅ Command return "Reminder emails dispatched for X orders"
- ✅ Queue worker proses SendOrderReminderJob
- ✅ Log menunjukkan email dispatched

---

### **Test 2: Scheduler Run (Check Current Time)**

**Step:**
```bash
# 1. Cek schedule list
php artisan schedule:list

# 2. Run scheduler (akan cek apakah ada task yang due)
php artisan schedule:run

# 3. Kalau sekarang bukan jam 10:00, output:
# "No scheduled commands are ready to run."
```

---

### **Test 3: Simulate Schedule Time (Ubah Jadwal Temporary)**

**Edit routes/console.php:** (temporary untuk testing)
```php
// Ubah dari dailyAt('10:00') ke everyMinute()
Schedule::command('order:reminder-unpaid-order')
    ->everyMinute()  // TEST MODE: Jalan setiap menit
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();
```

**Test:**
```bash
# 1. Start queue worker
.\run-queue-emails.bat

# 2. Di terminal lain, run scheduler
php artisan schedule:run

# 3. Tunggu 1 menit, run lagi
php artisan schedule:run

# 4. Check log: storage/logs/laravel.log
```

**Restore Setelah Test:**
```php
// Kembalikan ke dailyAt
Schedule::command('order:reminder-unpaid-order')
    ->dailyAt('10:00')  // PRODUCTION MODE
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();
```

---

## 🔍 **Debugging Issues**

### **Problem 1: Command Error (Exit Code 1)**

**Cek Error Detail:**
```bash
php artisan order:reminder-unpaid-order -vvv
```

**Common Issues:**
- Database connection error → Check .env DB_*
- OrderRepository method typo → Check GetPendingOrder()
- SendOrderReminderJob error → Check job file

---

### **Problem 2: Schedule Tidak Jalan**

**Checklist:**
```bash
# 1. Command exist?
php artisan list | grep order

# 2. Schedule registered?
php artisan schedule:list

# 3. Time correct?
# Scheduler pakai server timezone, bukan local PC!
```

---

### **Problem 3: Job Dispatched tapi Email Tidak Terkirim**

**Checklist:**
```bash
# 1. Queue worker running?
# Harus ada terminal yang run: .\run-queue-emails.bat

# 2. Job masuk queue?
# Check database table: jobs

SELECT * FROM jobs WHERE queue = 'emails' ORDER BY created_at DESC;

# 3. Failed jobs?
php artisan queue:failed
```

---

## 📊 **Monitoring Scheduler**

### **View Last Run Time**
```bash
php artisan schedule:list
```

**Output menunjukkan:**
- Last run time
- Next due time
- Command signature

---

### **Check Logs**
```bash
# Real-time log watching
tail -f storage/logs/laravel.log

# Windows PowerShell
Get-Content storage/logs/laravel.log -Wait -Tail 50
```

**Look for:**
```
[2025-12-15 10:00:00] local.INFO: Reminder command triggered at ...
[2025-12-15 10:00:01] local.INFO: Reminder email dispatched {"order_id":1,...}
```

---

## ⏰ **Schedule Expressions Cheat Sheet**

| Expression | Cron | Keterangan |
|------------|------|------------|
| `->everyMinute()` | `* * * * *` | Setiap menit (testing only!) |
| `->everyFiveMinutes()` | `*/5 * * * *` | Setiap 5 menit |
| `->hourly()` | `0 * * * *` | Setiap jam (00:00, 01:00, ...) |
| `->hourlyAt(15)` | `15 * * * *` | Setiap jam menit ke-15 (00:15, 01:15) |
| `->daily()` | `0 0 * * *` | Setiap hari jam 00:00 |
| `->dailyAt('13:00')` | `0 13 * * *` | Setiap hari jam 13:00 |
| `->weeklyOn(1, '8:00')` | `0 8 * * 1` | Setiap Senin jam 08:00 |
| `->monthly()` | `0 0 1 * *` | Tanggal 1 setiap bulan jam 00:00 |

---

## 🎓 **Analogi Testing:**

**Scheduler = Alarm HP:**
- `schedule:list` = Lihat daftar alarm
- `schedule:run` = Cek apakah ada alarm yang bunyi sekarang
- `php artisan command` = Bunyi alarm manual (paksa)

**Queue Worker = Postman:**
- Scheduler kirim "surat" (job) ke kotak pos (queue)
- Worker = Postman yang ambil & kirim surat ke tujuan

---

## ✅ **Ready for Production?**

**Local Testing Checklist:**
- [x] ✅ Schedule terdaftar (`schedule:list`)
- [x] ✅ Command jalan manual sukses
- [x] ✅ Job dispatched ke queue
- [x] ✅ Queue worker proses job
- [x] ✅ Email terkirim (atau log sukses)

**Next:** Setup cron job di production server! 🚀
