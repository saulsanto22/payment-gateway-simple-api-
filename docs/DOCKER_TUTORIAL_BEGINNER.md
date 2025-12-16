# 🎓 Docker Tutorial - Step by Step

## Level 1: Kenalan dengan Docker

### Check apakah Docker sudah installed
```bash
docker --version
```

**Kalau belum ada:** Download di https://www.docker.com/products/docker-desktop

---

## Level 2: Test Docker dengan Container Sederhana

### 1️⃣ Run container pertama kamu (super simple!)
```bash
docker run hello-world
```

**Apa yang terjadi?**
```
1. Docker cari image "hello-world" di laptop kamu → Tidak ada!
2. Docker download dari Docker Hub (kayak GitHub tapi untuk Docker)
3. Docker bikin container dari image itu
4. Container jalan → print "Hello from Docker!"
5. Container mati (karena tugasnya cuma print)
```

**Analogi:** Kamu pesan pizza delivery → Pizza datang → Kamu makan → Box pizza dibuang

---

### 2️⃣ Run container yang tetap hidup
```bash
docker run -d nginx
```

**Penjelasan:**
- `docker run` = Perintah untuk bikin & jalankan container
- `-d` = Detached mode (jalan di background)
- `nginx` = Nama image (web server)

**Check container yang jalan:**
```bash
docker ps
```

Kamu akan lihat:
```
CONTAINER ID   IMAGE   COMMAND   STATUS   PORTS
abc123def456   nginx   ...       Up       80/tcp
```

**Analogi:** Kamu nyalain AC → AC jalan terus di background

**Stop container:**
```bash
docker stop abc123def456
```

---

## Level 3: Test Project Laravel dengan Docker

### 1️⃣ Cek file-file Docker di project kamu

**File yang ada:**
```
📁 payment-gateway-simple-api-/
├── Dockerfile                 ← Blueprint untuk Laravel container
├── docker-compose.yml         ← Orchestrator (jalanin semua container)
├── docker-compose.dev.yml     ← Khusus development
├── .dockerignore              ← File yang ga perlu masuk container
└── docker/
    ├── nginx/nginx.conf       ← Config web server
    ├── php/php-fpm.conf       ← Config PHP
    └── supervisor/supervisord.conf  ← Process manager
```

### 2️⃣ Mari kita baca Dockerfile (line by line)

**Buka file:** `Dockerfile`

```dockerfile
# STAGE 1: Install dependencies
FROM composer:2.7 AS composer-stage
↑
Analogi: Ini kayak kamu bilang "Aku mau pake kitchen yang udah ada Mixer"

COPY composer.json composer.lock /app/
↑
Analogi: Copy daftar belanjaan ke dapur

RUN composer install ...
↑
Analogi: Belanja semua bahan (install dependencies)

# STAGE 2: Runtime
FROM php:8.2-fpm-alpine
↑
Analogi: Sekarang pake kitchen utama (lebih kecil & cepat)

COPY --from=composer-stage /app/vendor /var/www/html/vendor
↑
Analogi: Ambil hasil belanjaan dari dapur 1, pindah ke dapur 2
        (Jadi ga perlu bawa Mixer ke kitchen utama!)
```

**Kenapa 2 stage?**
- Stage 1 = Kitchen lengkap (ada mixer, oven, semua alat)
- Stage 2 = Kitchen minimalis (cuma kompor & piring)
- Result: Image lebih kecil! (dari 500MB → 150MB)

### 3️⃣ Mari kita baca docker-compose.yml

**Buka file:** `docker-compose.dev.yml` (yang simple dulu)

```yaml
services:
  app:                    # Container utama (Laravel)
    build: .              # Build dari Dockerfile.dev
    ports:
      - "8000:8000"       # Port laptop:Port container
    depends_on:           # Tunggu yang ini dulu
      - db
      - redis
  
  db:                     # Container PostgreSQL
    image: postgres:16    # Pakai image jadi dari Docker Hub
    environment:
      - POSTGRES_DB=payment_gateway_dev
      - POSTGRES_PASSWORD=secret
  
  redis:                  # Container Redis
    image: redis:7
```

**Analogi:**
```
🏢 Food Court

🍕 Pizza Store (app)
   - Bahan: Tepung, Keju (dari Dockerfile.dev)
   - Buka jam 8000 (port 8000)
   - Butuh: Gudang (db) & Kasir (redis)

📦 Gudang (db)
   - Barang: PostgreSQL database
   - Password: secret

💰 Kasir (redis)
   - Service: Cache & Queue
```

---

## Level 4: Jalankan Project dengan Docker (PRAKTIK!)

### Scenario 1: Development Mode (Recommended untuk belajar)

#### Step 1: Pastikan Docker Desktop jalan
```
Buka Docker Desktop → Tunggu sampai status "Running"
```

#### Step 2: Test docker command
```bash
docker --version
docker-compose --version
```

Kalau keluar versi → Siap! ✅

#### Step 3: Jalankan development environment

**Cara 1: Pakai helper script (RECOMMENDED)**
```bash
# Double click file ini:
docker-dev-start.bat
```

Script akan otomatis:
1. ✅ Check .env file
2. ✅ Start semua container (app, db, redis, mailhog)
3. ✅ Run migration
4. ✅ Seed database

**Cara 2: Manual (biar paham step-by-step)**
```bash
# 1. Copy .env
cp .env.example .env

# 2. Start containers
docker-compose -f docker-compose.dev.yml up -d

# 3. Tunggu sampai semua container jalan (30 detik)
docker-compose -f docker-compose.dev.yml ps

# 4. Run migration
docker-compose -f docker-compose.dev.yml exec app php artisan migrate

# 5. Seed database
docker-compose -f docker-compose.dev.yml exec app php artisan db:seed
```

#### Step 4: Test aplikasi
```
Buka browser:
- http://localhost:8000           → Laravel app
- http://localhost:8000/api/documentation  → Swagger
- http://localhost:8025           → MailHog (email testing)
```

#### Step 5: Test API via Swagger
```
1. Buka http://localhost:8000/api/documentation
2. Coba endpoint POST /api/auth/register
3. Isi data → Execute
4. Kalau berhasil → Docker jalan sempurna! 🎉
```

---

## Level 5: Understanding What's Happening

### Ketika kamu run `docker-compose up`:

```
STEP 1: Docker baca docker-compose.dev.yml
   ↓
STEP 2: Build/Download semua images
   - Dockerfile.dev untuk Laravel
   - postgres:16 dari Docker Hub
   - redis:7 dari Docker Hub
   - mailhog dari Docker Hub
   ↓
STEP 3: Bikin containers dari images
   - Container app (Laravel)
   - Container db (PostgreSQL)
   - Container redis (Redis)
   - Container mailhog (MailHog)
   ↓
STEP 4: Setup networking
   - Semua container bisa ngobrol (via network "payment-gateway-network")
   ↓
STEP 5: Mount volumes
   - Source code kamu → Mount ke container
   - Database data → Save di volume (persist)
   ↓
STEP 6: Start semua containers
   ✅ App ready di http://localhost:8000
```

### Di dalam container "app" ada apa?

**Masuk ke container (kayak SSH):**
```bash
docker-compose -f docker-compose.dev.yml exec app sh
```

Sekarang kamu ada **di dalam** container! Coba:
```bash
pwd          # /var/www/html (working directory)
ls           # Lihat file Laravel
php -v       # PHP 8.2
which nginx  # Nginx installed
```

**Keluar:**
```bash
exit
```

**Analogi:** Kamu masuk ke rumah kos → Lihat-lihat isinya → Keluar

---

## Level 6: Common Commands (Cheat Sheet)

### View Logs (penting untuk debug!)
```bash
# Semua container
docker-compose -f docker-compose.dev.yml logs -f

# Specific container
docker-compose -f docker-compose.dev.yml logs -f app

# Last 50 lines
docker-compose -f docker-compose.dev.yml logs --tail=50 app
```

### Run Artisan Commands
```bash
# Migrate
docker-compose -f docker-compose.dev.yml exec app php artisan migrate

# Seed
docker-compose -f docker-compose.dev.yml exec app php artisan db:seed

# Tinker
docker-compose -f docker-compose.dev.yml exec app php artisan tinker

# Clear cache
docker-compose -f docker-compose.dev.yml exec app php artisan cache:clear
```

### Container Management
```bash
# Lihat container yang jalan
docker-compose -f docker-compose.dev.yml ps

# Stop semua
docker-compose -f docker-compose.dev.yml down

# Restart container
docker-compose -f docker-compose.dev.yml restart app

# Rebuild image (kalau ada perubahan Dockerfile)
docker-compose -f docker-compose.dev.yml build --no-cache
```

### Cleanup (kalau mau reset semua)
```bash
# Stop & hapus containers + volumes (DANGER: database akan hilang!)
docker-compose -f docker-compose.dev.yml down -v

# Hapus images yang ga kepake
docker image prune -a

# Hapus semua (containers, images, volumes, networks)
docker system prune -a --volumes
```

---

## Level 7: Troubleshooting

### Problem 1: Port already in use
```
Error: Bind for 0.0.0.0:8000 failed: port is already allocated
```

**Solution:**
```bash
# Check apa yang pakai port 8000
netstat -ano | findstr :8000

# Kill process (ganti PID dengan hasil di atas)
taskkill /PID 12345 /F

# Atau ganti port di docker-compose.dev.yml:
ports:
  - "8001:8000"  # Pakai port 8001 di laptop
```

### Problem 2: Container tidak start
```bash
# Check logs untuk tau error
docker-compose -f docker-compose.dev.yml logs app
```

Common issues:
- ❌ .env belum ada → Copy dari .env.example
- ❌ Database belum ready → Tunggu 10 detik lagi
- ❌ Permission error → Run as administrator

### Problem 3: Database connection error
```
SQLSTATE[08006] [7] could not connect to server: Connection refused
```

**Solution:**
```bash
# Check apakah container db jalan
docker-compose -f docker-compose.dev.yml ps

# Restart db container
docker-compose -f docker-compose.dev.yml restart db

# Check DB_HOST di .env harus "db" (bukan localhost!)
```

---

## 🎯 Quiz: Test Pemahaman Kamu

**Q1:** Apa beda Docker Image vs Docker Container?
<details>
<summary>Jawaban</summary>
- Image = Blueprint/Resep (ga bisa jalan)
- Container = Running instance dari image (bisa jalan)
Analogi: Image = Resep masakan, Container = Piring makanan jadi
</details>

**Q2:** Kenapa pakai multi-stage build di Dockerfile?
<details>
<summary>Jawaban</summary>
Supaya image final lebih kecil. Stage 1 buat compile/install (butuh banyak tools), Stage 2 cuma ambil hasil akhir (ga perlu tools).
Analogi: Stage 1 = Dapur lengkap, Stage 2 = Cuma meja makan
</details>

**Q3:** Apa fungsi docker-compose.yml?
<details>
<summary>Jawaban</summary>
Define & jalankan multiple containers sekaligus. Seperti "Mall Manager" yang ngatur banyak toko (containers) biar jalan bareng.
</details>

---

## 🚀 Next Steps

Setelah paham basic Docker, coba:

1. ✅ Jalankan development environment
2. ✅ Test semua API endpoints
3. ✅ Lihat logs ketika ada request
4. ✅ Masuk ke container & explore
5. ✅ Coba stop & start ulang

**Kalau udah lancar:** Lanjut ke production deployment! 🎉

---

## 💡 Tips Belajar Docker

1. **Jangan takut error** - Docker aman! Worst case: delete & start ulang
2. **Banyak eksperimen** - Coba stop/start, lihat logs, masuk container
3. **Pakai analogi** - Bandingin dengan yang kamu udah paham
4. **One concept at time** - Image dulu → Container → Compose → Production

**Remember:** Docker is like LEGO! 🧱
- Image = Instruksi LEGO
- Container = Hasil rakitan
- Compose = Set LEGO dengan banyak pieces

---

## 🆘 Still Confused?

**Pertanyaan paling sering:**

❓ "Kenapa ga langsung install PHP, PostgreSQL, dll di laptop?"
✅ Bisa! Tapi:
   - Setup lama (install 1-1)
   - Beda laptop beda config
   - Susah rollback kalau error
   - Docker = 1 command semua jalan!

❓ "Container vs Virtual Machine bedanya apa?"
✅ VM = Sewa 1 rumah utuh (berat, lambat)
   Container = Sewa 1 kamar (ringan, cepat)

❓ "Production pakai Docker juga?"
✅ YES! Semua big companies (Google, Netflix, Uber) pakai Docker/Kubernetes

---

**Happy Learning! Kalau masih ada yang bingung, tanya aja! 🚀**
