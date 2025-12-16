# ⏰ PHASE 3: Task Scheduling (Cronjob)

## 🎯 Apa itu Task Scheduling?

### **Analogi Sederhana: Alarm Pagi**

Bayangkan kamu punya **Alarm** di HP:
- **Tanpa Alarm**: Kamu harus bangun sendiri, bisa telat sekolah/kerja
- **Dengan Alarm**: HP otomatis bunyi jam 6 pagi SETIAP HARI, tanpa kamu atur manual

**Task Scheduling = Alarm untuk aplikasi kamu!**

---

## 🤔 **Kenapa Perlu Scheduling?**

### **Real-World Scenario:**

**Case: Order Reminder Email**

**Tanpa Scheduling (Manual):**
```
❌ Kamu harus login server setiap hari
❌ Jalankan command: php artisan send:reminder
❌ Kalau lupa 1 hari = reminder tidak terkirim
❌ Kalau libur/sakit = sistem berhenti
```

**Dengan Scheduling (Otomatis):**
```
✅ Server otomatis jalankan setiap jam 10 pagi
✅ Tidak perlu login manual
✅ Jalan 24/7, bahkan kamu tidur
✅ Konsisten & reliable
```

---

## 📊 **Perbedaan: Queue vs Scheduling**

Ini yang sering bikin bingung! Mari saya jelaskan:

### **Queue (Phase 2):**
```
USER ACTION → TRIGGER JOB → QUEUE → WORKER PROSES

Contoh:
Customer bayar → Webhook masuk → Job masuk queue → Worker proses
```

**Karakteristik:**
- ⏱️ **Triggered by event** (ada yang trigger)
- 🔄 **Immediate/delayed** (bisa langsung atau delay)
- 📝 **Event-driven** (ada aksi dulu)

### **Scheduling (Phase 3):**
```
WAKTU TERTENTU → AUTO TRIGGER → COMMAND JALAN

Contoh:
Jam 10:00 AM → Otomatis jalankan → Send reminder ke semua unpaid orders
```

**Karakteristik:**
- ⏰ **Time-based** (berdasarkan waktu)
- 🔁 **Recurring** (berulang: harian, mingguan, bulanan)
- 🤖 **Automatic** (tidak perlu trigger manual)

---

## 🎬 **Real Example di Project Ini:**

### **Scenario: Reminder untuk Unpaid Orders**

**Problem:**
- Ada order dengan status PENDING (belum dibayar)
- Kita mau kirim email reminder ke customer
- Kirim **setiap hari jam 10 pagi**

**Solution Flow:**

```
1. SCHEDULER (Jam 10 AM setiap hari)
   ↓
2. Jalankan Command: ReminderUnpaidOrder
   ↓
3. Command cari semua order PENDING
   ↓
4. Untuk setiap order, dispatch Job ke Queue
   ↓
5. SendOrderReminderJob kirim email
```

**Perhatikan:**
- **Scheduler** = Trigger otomatis setiap jam 10 AM
- **Command** = Logic untuk cari unpaid orders
- **Job** = Kirim email (sudah kita buat di Phase 2!)

---

## 🔄 **Lifecycle: Scheduling + Queue (Gabungan Phase 2 & 3)**

```
⏰ SCHEDULER                  📋 COMMAND                   🎯 JOB (QUEUE)
─────────────────────────────────────────────────────────────────────
Jam 10:00 AM                 Get unpaid orders           Send email job 1
(auto trigger)               (order ID: 1, 2, 3)         Send email job 2
     │                              │                    Send email job 3
     │                              │                          │
     └──────────────> Trigger ──────┴─> Dispatch Jobs ────────┘
                                              │
                                              ↓
                                        Queue: emails
                                              │
                                              ↓
                                        Worker Process
                                              │
                                              ↓
                                        Email Sent! ✅
```

**Keren kan?** Scheduler + Queue = Automation Power! 🚀

---

## 📅 **Contoh Scheduling Patterns:**

### **1. Daily (Harian)**
```php
// Setiap hari jam 10 pagi
$schedule->command('order:reminder')->dailyAt('10:00');
```
**Use case:** Send daily reminder, daily report

### **2. Hourly (Setiap Jam)**
```php
// Setiap jam (01:00, 02:00, 03:00, ...)
$schedule->command('cleanup:temp')->hourly();
```
**Use case:** Clean cache, cleanup temp files

### **3. Every X Minutes**
```php
// Setiap 15 menit
$schedule->command('check:status')->everyFifteenMinutes();
```
**Use case:** Health check, monitoring

### **4. Specific Days**
```php
// Setiap Senin jam 9 pagi
$schedule->command('report:weekly')->weeklyOn(1, '09:00');
```
**Use case:** Weekly report, weekly backup

### **5. Monthly**
```php
// Tanggal 1 setiap bulan, jam 00:00
$schedule->command('invoice:generate')->monthlyOn(1, '00:00');
```
**Use case:** Monthly invoice, monthly summary

---

## 🛠️ **Components Laravel Scheduler:**

### **1. Schedule Definition (app/Console/Kernel.php)**
```php
protected function schedule(Schedule $schedule)
{
    // DEFINE KAPAN & APA yang mau dijalankan
    $schedule->command('order:reminder')->dailyAt('10:00');
}
```

### **2. Command (app/Console/Commands/)**
```php
class ReminderUnpaidOrder extends Command
{
    // LOGIC apa yang mau dijalankan
    public function handle()
    {
        // Cari unpaid orders
        // Kirim reminder
    }
}
```

### **3. Cron Job (Server)**
```bash
# TRIGGER scheduler setiap menit
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

**Wait, kenapa cron run setiap menit tapi task jalan jam 10 AM?**

**Penjelasan:**
- Cron hanya **check** setiap menit: "Ada task yang perlu jalan sekarang?"
- Laravel Scheduler yang **decide**: "Oh, sekarang jam 10 AM, jalankan reminder!"
- Jadi cron = checker, scheduler = decision maker

---

## 🎓 **Analogi Lengkap:**

### **Cron Job = Security Guard**
- Patrol setiap menit
- Cek: "Ada tugas yang perlu dikerjakan?"

### **Laravel Scheduler = Manager**
- Punya daftar tugas + jadwal
- Bilang ke security: "Kalau jam 10 AM, jalankan task A"

### **Command = Worker**
- Terima instruksi dari manager
- Jalankan tugas actual (cari orders, kirim email)

### **Job/Queue = Sub-task**
- Task kecil yang di-dispatch oleh command
- Diproses parallel oleh queue worker

---

## ✅ **Kenapa Laravel Scheduler Better daripada Multiple Cron Jobs?**

### **Traditional Way (Multiple Cron):**
```bash
# Di crontab, harus define semua schedule manual
0 10 * * * php artisan order:reminder
0 9 * * 1 php artisan report:weekly
0 0 1 * * php artisan invoice:monthly
0 */6 * * * php artisan cleanup:temp
```

**Problems:**
- ❌ Edit schedule = harus akses server
- ❌ Susah track schedule di codebase
- ❌ Setiap environment (dev, staging, prod) beda setup
- ❌ Hard to test locally

### **Laravel Scheduler (Single Cron):**
```bash
# Cron: HANYA 1 BARIS!
* * * * * php artisan schedule:run
```

```php
// Schedule definition di CODE (versioned, trackable)
protected function schedule(Schedule $schedule)
{
    $schedule->command('order:reminder')->dailyAt('10:00');
    $schedule->command('report:weekly')->weeklyOn(1, '09:00');
    $schedule->command('invoice:monthly')->monthlyOn(1, '00:00');
    $schedule->command('cleanup:temp')->everySixHours();
}
```

**Benefits:**
- ✅ Edit schedule = edit code (versioned di Git!)
- ✅ Easy to test locally: `php artisan schedule:list`
- ✅ Same setup untuk dev/staging/prod
- ✅ Chainable, conditional, overlap prevention
- ✅ Built-in logging & notifications

---

## 🎯 **Next Steps:**

Sekarang kita akan implementasi:

1. ✅ Setup Scheduler di `app/Console/Kernel.php`
2. ✅ Test command yang sudah ada: `ReminderUnpaidOrder`
3. ✅ Test scheduler locally
4. ✅ Setup cron untuk production

**Siap lanjut implementasi?** 🚀
