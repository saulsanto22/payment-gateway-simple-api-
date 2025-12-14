# 📋 Queue & Job - Panduan Lengkap

## 🎯 Apa itu Queue & Job?

### Analogi Sederhana
Bayangkan sebuah **Restoran Fast Food**:
- **Tanpa Queue (Synchronous)**: Customer pesan → tunggu sampai makanan jadi → baru customer berikutnya bisa pesan
- **Dengan Queue (Asynchronous)**: Customer pesan → dapat nomor antrian → duduk dulu → dipanggil saat makanan jadi

### Kapan Pakai Queue?
✅ **Harus pakai Queue:**
- Proses yang lama (>3 detik): kirim email, generate PDF, resize image
- External API call yang bisa timeout: payment gateway, SMS gateway
- Task yang bisa di-delay: notifikasi, report generation
- Task yang bisa failed dan perlu retry: webhook processing

❌ **Tidak perlu Queue:**
- CRUD sederhana (create, read, update, delete)
- Response yang perlu instant (login, get profile)
- Task yang harus atomic dengan request (bank transfer)

---

## 🔧 Komponen Penting Job

### 1. Job Properties

```php
class ProcessMidtransWebhook implements ShouldQueue
{
    use Queueable; // Bisa masuk queue
    
    // BEST PRACTICE: Tentukan limit retry
    public $tries = 3; // Coba 3x sebelum failed permanently
    
    // BEST PRACTICE: Timeout untuk prevent hanging
    public $timeout = 120; // Max 2 menit
    
    // BEST PRACTICE: Delay antar retry (exponential backoff)
    public $backoff = [30, 60, 120]; // Tunggu 30s, 1m, 2m
    
    // OPTIONAL: Max exception sebelum fail
    public $maxExceptions = 3;
}
```

**Penjelasan:**
- `$tries`: Berapa kali job akan di-retry jika gagal
  - Terlalu sedikit (1x) = Mudah gagal permanent
  - Terlalu banyak (10x) = Waste resources
  - **Sweet spot: 3-5x** untuk external API
  
- `$timeout`: Berapa lama max job boleh jalan
  - Prevent job hanging forever
  - Webhook processing: 30-120 detik cukup
  - Image processing: 5-10 menit
  
- `$backoff`: Delay antar retry (seconds)
  - Exponential backoff = delay makin lama
  - Kasih waktu external service untuk recovery
  - Contoh: [10, 30, 60] = 10s, 30s, 1m

---

## 🚀 Job Lifecycle

```
1. DISPATCH        → Job masuk ke queue database
   ↓
2. PENDING         → Tunggu worker ambil job
   ↓
3. PROCESSING      → Worker execute handle()
   ↓
4. COMPLETED ✅    → Success, hapus dari queue
   ↓ (jika exception)
5. RETRY           → Tunggu backoff, coba lagi
   ↓ (jika $tries habis)
6. FAILED ❌       → Masuk ke failed_jobs table
```

---

## 📊 Queue Drivers

Laravel support beberapa driver:

| Driver | Use Case | Pros | Cons |
|--------|----------|------|------|
| **sync** | Development/Testing | Simple, langsung jalan | Tidak asynchronous |
| **database** | Small-medium app | No setup, built-in | Slower, butuh polling |
| **redis** | Production recommended | Fast, reliable | Butuh Redis server |
| **sqs** | AWS deployment | Scalable, managed | Paid service |
| **beanstalkd** | High performance | Very fast | Need beanstalkd server |

**Untuk Project Ini:**
- Development: `database` (sudah setup)
- Production: `redis` (recommended) atau `database` (jika traffic rendah)

---

## 🎯 Job Dispatching Patterns

### 1. Immediate Dispatch (Default)
```php
ProcessMidtransWebhook::dispatch($payload);
```

### 2. Delayed Dispatch
```php
// Kirim reminder 1 jam kemudian
SendOrderReminderJob::dispatch($order)
    ->delay(now()->addHours(1));
```

### 3. Dispatch to Specific Queue
```php
// Queue berbeda untuk prioritas berbeda
ProcessMidtransWebhook::dispatch($payload)
    ->onQueue('webhooks'); // High priority

GenerateReportJob::dispatch($data)
    ->onQueue('reports'); // Low priority
```

### 4. Chain Jobs (Sequential)
```php
// Job A → Job B → Job C (berurutan)
Bus::chain([
    new ProcessPayment($order),
    new SendInvoice($order),
    new UpdateInventory($order),
])->dispatch();
```

### 5. Batch Jobs (Parallel)
```php
// Semua job jalan parallel, notify saat selesai semua
Bus::batch([
    new ExportCustomers($ids1),
    new ExportCustomers($ids2),
    new ExportCustomers($ids3),
])->then(function (Batch $batch) {
    // All jobs completed
})->dispatch();
```

---

## 🛡️ Error Handling Best Practices

### 1. Graceful Failure
```php
public function handle()
{
    try {
        // Business logic
    } catch (TemporaryException $e) {
        // Retry-able error
        Log::warning('Temporary error, will retry', ['error' => $e->getMessage()]);
        $this->release(30); // Release back to queue, retry in 30s
    } catch (PermanentException $e) {
        // Non-retry-able error
        Log::error('Permanent error, job failed', ['error' => $e->getMessage()]);
        $this->fail($e); // Mark as failed permanently
    }
}
```

### 2. Failed Job Handler
```php
public function failed(\Throwable $exception)
{
    // Dipanggil saat job failed permanent (setelah $tries habis)
    
    Log::error('Job failed permanently', [
        'job' => self::class,
        'payload' => $this->payload,
        'error' => $exception->getMessage(),
    ]);
    
    // Kirim alert ke developer
    // Slack::send('Job failed!');
    
    // Rollback logic jika perlu
    // $this->order->update(['status' => 'failed']);
}
```

---

## 🔍 Monitoring & Debugging

### 1. Laravel Horizon (Recommended untuk Production)
```bash
composer require laravel/horizon
php artisan horizon:install
php artisan horizon
```

**Fitur:**
- Real-time dashboard
- Job metrics & throughput
- Failed job management
- Auto-scaling workers

### 2. Manual Monitoring (Database Driver)

**Cek Failed Jobs:**
```bash
php artisan queue:failed
```

**Retry Failed Job:**
```bash
php artisan queue:retry 5  # Retry job ID 5
php artisan queue:retry all  # Retry semua
```

**Flush Failed Jobs:**
```bash
php artisan queue:flush  # Hapus semua failed jobs
```

### 3. Queue Worker Commands

**Start Worker:**
```bash
# Development
php artisan queue:work

# Production (with auto-restart)
php artisan queue:work --tries=3 --timeout=90

# Specific queue dengan priority
php artisan queue:work --queue=webhooks,emails,default
```

**Worker Daemon (Production):**
```bash
# Dengan supervisor (recommended)
# File: /etc/supervisor/conf.d/laravel-worker.conf
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=3
```

---

## 📈 Performance Tips

### 1. Queue Prioritization
```php
// config/queue.php
'connections' => [
    'database' => [
        'queue' => 'default', // Default queue name
    ],
],

// Jalankan worker dengan priority
php artisan queue:work --queue=high,medium,low
```

### 2. Job Chunking
```php
// Jangan dispatch 10,000 jobs sekaligus
// Gunakan chunk untuk batch processing
User::chunk(100, function ($users) {
    foreach ($users as $user) {
        SendNewsletterJob::dispatch($user);
    }
});
```

### 3. Rate Limiting
```php
use Illuminate\Queue\Middleware\RateLimited;

public function middleware()
{
    return [
        new RateLimited('external-api'), // Max 60 requests per minute
    ];
}
```

---

## ✅ Testing Jobs

### 1. Fake Queue
```php
Queue::fake();

// Dispatch job
ProcessMidtransWebhook::dispatch($payload);

// Assert job dispatched
Queue::assertPushed(ProcessMidtransWebhook::class);
```

### 2. Sync Execution (Test Logic)
```php
// Job jalan langsung, tidak masuk queue
ProcessMidtransWebhook::dispatchSync($payload);

// Assert hasil
expect($order->fresh()->status)->toBe(OrderStatus::PAID);
```

---

## 🎓 Rangkuman Best Practices

1. ✅ **Selalu set `$tries` dan `$timeout`** - Prevent infinite retry & hanging
2. ✅ **Gunakan `$backoff` untuk exponential backoff** - Kasih waktu recovery
3. ✅ **Implement `failed()` method** - Handle permanent failure
4. ✅ **Log setiap step** - Untuk debugging
5. ✅ **Use specific queues** - Prioritize critical jobs
6. ✅ **Test dengan Queue::fake()** - Test dispatching logic
7. ✅ **Test dengan dispatchSync()** - Test job logic
8. ✅ **Monitor failed jobs** - Setup alert system
9. ✅ **Use Horizon untuk production** - Better monitoring & management
10. ✅ **Graceful degradation** - Handle error dengan baik

---

## 🚨 Common Pitfalls

❌ **Jangan:**
- Passing Eloquent Models directly (serialize issue) → Pass ID instead
- Catch semua exception tanpa rethrow → Job tidak akan retry
- Queue job yang seharusnya synchronous (login, CRUD)
- Lupa set timeout → Job bisa hanging
- Lupa monitor failed jobs → Silent failure

✅ **Lakukan:**
- Pass primitive data atau ID
- Let exception bubble up untuk auto-retry
- Queue hanya untuk long-running task
- Set timeout realistis
- Setup monitoring & alerts
