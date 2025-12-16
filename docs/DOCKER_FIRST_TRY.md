# 🎯 Docker First Try - Panduan Super Gampang!

**Target:** Dalam 15 menit, kamu bisa jalanin Laravel project pakai Docker!

---

## Step 0: Install Docker Desktop (Sekali Aja)

### 1. Download Docker Desktop
- **Windows:** https://www.docker.com/products/docker-desktop
- Klik "Download for Windows"
- File size: ~500MB (tunggu download selesai)

### 2. Install
- Double-click installer
- Next → Next → Install
- Restart komputer kalau diminta

### 3. Buka Docker Desktop
- Cari di Start Menu: "Docker Desktop"
- Tunggu sampai status pojok kiri bawah: **"Docker Desktop is running"** 🟢
- Kalau ada notif "WSL 2 installation is incomplete" → Follow instruksi install WSL 2

### 4. Test Docker Jalan
Buka PowerShell atau Command Prompt, ketik:
```bash
docker --version
```

Kalau keluar versi (misal: `Docker version 24.0.6`) → **BERHASIL! ✅**

---

## Step 1: Test Docker Pertama Kali (Hello World)

Masih di PowerShell/CMD, ketik:
```bash
docker run hello-world
```

**Apa yang terjadi:**
```
1. Docker cari image "hello-world" di laptop → Ga ada!
2. Docker download dari internet (Docker Hub)
3. Docker jalankan container
4. Print message "Hello from Docker!"
5. Container mati (tugasnya selesai)
```

**Kalau muncul "Hello from Docker!" → Docker sudah jalan! 🎉**

---

## Step 2: Test Docker dengan Web Server

Sekarang coba jalankan web server:

```bash
docker run -d -p 8080:80 nginx
```

**Penjelasan command:**
- `docker run` = Jalankan container
- `-d` = Detached mode (jalan di background)
- `-p 8080:80` = Port laptop 8080 → ke port container 80
- `nginx` = Nama image (web server)

**Test apakah jalan:**
1. Buka browser
2. Ketik: `http://localhost:8080`
3. Kalau muncul "Welcome to nginx!" → **BERHASIL! ✅**

**Stop container:**
```bash
# Lihat container yang jalan
docker ps

# Copy CONTAINER ID (contoh: a1b2c3d4e5f6)
# Stop container
docker stop a1b2c3d4e5f6
```

---

## Step 3: Jalankan Laravel Project Kamu (MAIN EVENT!)

### Persiapan

**1. Buka PowerShell/CMD**

**2. Masuk ke folder project:**
```bash
cd C:\laragon\www\payment-gateway-simple-api-
```

**3. Check file Docker ada:**
```bash
dir docker-compose.dev.yml
```

Kalau file ada → Lanjut! ✅

---

### Cara Paling Gampang (Recommended!)

**Double-click file ini:**
```
docker-dev-start.bat
```

Script akan otomatis:
1. ✅ Check .env file
2. ✅ Download semua images (PostgreSQL, Redis, dll)
3. ✅ Start 4 containers
4. ✅ Run migration database
5. ✅ Seed data

**Tunggu 2-3 menit** (pertama kali download images)

**Kalau berhasil, akan muncul:**
```
============================================
[SUCCESS] Development environment is ready!
============================================

Application: http://localhost:8000
API Docs: http://localhost:8000/api/documentation
MailHog: http://localhost:8025
Database: localhost:5432
Redis: localhost:6379
```

---

### Test Aplikasi Jalan

**1. Buka browser:**
```
http://localhost:8000/api/documentation
```

**2. Kalau Swagger UI muncul → BERHASIL! 🎉🎉🎉**

**3. Test API:**
- Klik endpoint `POST /api/auth/register`
- Klik "Try it out"
- Isi data:
  ```json
  {
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }
  ```
- Klik "Execute"
- Kalau dapat response 201 Created → API jalan sempurna! ✅

---

## Step 4: Lihat Apa yang Terjadi

### Check Container yang Jalan

```bash
docker ps
```

Output:
```
CONTAINER ID   IMAGE              STATUS    PORTS
abc123...      laravel-app-dev    Up        0.0.0.0:8000->8000/tcp
def456...      postgres:16        Up        0.0.0.0:5432->5432/tcp
ghi789...      redis:7            Up        0.0.0.0:6379->6379/tcp
jkl012...      mailhog/mailhog    Up        0.0.0.0:8025->8025/tcp
```

Sekarang kamu punya 4 container jalan:
- ✅ Laravel app
- ✅ PostgreSQL database
- ✅ Redis cache
- ✅ MailHog email testing

**Analogi:** Kamu baru aja "nyalain" 4 komputer virtual di laptop kamu!

---

### Lihat Logs (Debugging)

```bash
# Double-click file ini:
docker-logs.bat

# Atau manual:
docker-compose -f docker-compose.dev.yml logs -f
```

Ini kayak "tail -f" tapi untuk Docker. Kamu bisa lihat real-time apa yang terjadi!

---

### Masuk ke Dalam Container (Opsional)

Pengen lihat "dalem" container Laravel?

```bash
docker-compose -f docker-compose.dev.yml exec app sh
```

Sekarang kamu ada **di dalam** container! Coba:
```bash
pwd              # /var/www/html
ls -la           # Lihat file Laravel
php -v           # PHP 8.2
php artisan --version
```

**Keluar:**
```bash
exit
```

**Analogi:** Kamu "SSH" ke komputer virtual → Lihat-lihat → Keluar

---

## Step 5: Stop Docker (Kalau Udah Selesai)

**Double-click file ini:**
```
docker-dev-stop.bat
```

Atau manual:
```bash
docker-compose -f docker-compose.dev.yml down
```

Semua container akan stop & dihapus. **Data database tetap aman** (disimpan di volume).

---

## 🎯 Recap: Yang Baru Aja Kamu Lakukan

```
✅ Install Docker Desktop
✅ Test Docker dengan hello-world
✅ Test Docker dengan nginx
✅ Jalankan Laravel project dengan 4 containers
✅ Test API via Swagger UI
✅ Lihat logs
✅ Stop containers

Total waktu: ~15 menit
```

**Selamat! Kamu baru aja jalan-in Laravel pakai Docker! 🎉**

---

## ❓ Troubleshooting (Kalau Ada Masalah)

### Problem 1: "docker: command not found"
**Solusi:**
- Docker Desktop belum install → Install dulu
- Docker Desktop belum jalan → Buka aplikasinya, tunggu sampai status "Running"

### Problem 2: "Port already in use"
```
Error: Bind for 0.0.0.0:8000 failed: port is already allocated
```

**Solusi:**
```bash
# Stop Laragon atau XAMPP yang pakai port 8000
# Atau ganti port di docker-compose.dev.yml:
ports:
  - "8001:8000"  # Pakai port 8001
```

### Problem 3: Container tidak start
**Solusi:**
```bash
# Lihat logs untuk tau kenapa error
docker-compose -f docker-compose.dev.yml logs app

# Common issues:
# - .env belum ada → Copy dari .env.example
# - Permission error → Run CMD as Administrator
# - WSL 2 belum install → Follow instruksi Docker Desktop
```

### Problem 4: "Cannot connect to database"
**Solusi:**
```bash
# Tunggu 10-15 detik lagi (database butuh waktu startup)
# Restart database container:
docker-compose -f docker-compose.dev.yml restart db
```

### Problem 5: Download lambat
**Solusi:**
- Image pertama kali download ~500MB-1GB
- Kalau internet lambat, tunggu aja (one-time only!)
- Next time startup cuma 10 detik (sudah ada di laptop)

---

## 💡 Kenapa Docker Keren?

**Before Docker:**
```
Setup Laravel di laptop baru:
1. Install PHP ⏰ 10 menit
2. Install Composer ⏰ 5 menit
3. Install PostgreSQL ⏰ 15 menit
4. Install Redis ⏰ 10 menit
5. Konfigurasi semua ⏰ 20 menit
6. Troubleshoot errors ⏰ 30 menit
───────────────────────────────
Total: ~90 menit (1.5 jam!)
```

**With Docker:**
```
Setup Laravel di laptop baru:
1. Install Docker Desktop ⏰ 5 menit
2. Double-click docker-dev-start.bat ⏰ 3 menit
───────────────────────────────
Total: ~8 menit! 🚀
```

**Plus:**
- ✅ Sama persis di semua laptop (Windows/Mac/Linux)
- ✅ Ga conflict dengan project lain
- ✅ Bisa punya PHP 8.2 & PHP 7.4 di laptop yang sama
- ✅ Delete & reinstall cuma 1 command

---

## 🎓 Next Level (Kalau Udah Paham)

### Run Artisan Commands
```bash
# Migration
docker-compose -f docker-compose.dev.yml exec app php artisan migrate

# Seed
docker-compose -f docker-compose.dev.yml exec app php artisan db:seed

# Tinker
docker-compose -f docker-compose.dev.yml exec app php artisan tinker

# Clear cache
docker-compose -f docker-compose.dev.yml exec app php artisan cache:clear
```

### Test MailHog (Email Testing)
1. Buka: http://localhost:8025
2. Register user via API
3. Order reminder akan muncul di MailHog (bukan ke email beneran)
4. Perfect untuk testing!

### Check Resource Usage
```bash
# Lihat CPU, Memory yang dipakai
docker stats
```

---

## 📚 Summary Konsep Docker

```
┌────────────────────────────────────────┐
│         DOCKER CONCEPTS                │
├────────────────────────────────────────┤
│                                        │
│  📦 IMAGE                              │
│  = Blueprint/Resep                     │
│  = Template yang ga bisa jalan         │
│  Example: "laravel-app:latest"         │
│                                        │
│         ↓ (docker run)                 │
│                                        │
│  🎪 CONTAINER                          │
│  = Running instance dari image         │
│  = Bisa jalan, bisa stop               │
│  Example: "payment-gateway-app-dev"    │
│                                        │
│         ↓ (komunikasi via network)     │
│                                        │
│  🌐 NETWORK                            │
│  = LAN virtual untuk containers        │
│  = Biar containers bisa ngobrol        │
│                                        │
│         ↓ (save data persistent)       │
│                                        │
│  💾 VOLUME                             │
│  = Storage yang ga hilang              │
│  = Database data, uploads              │
│                                        │
└────────────────────────────────────────┘
```

---

## 🎯 Challenge Sekarang!

**Coba sekarang juga:**

1. [ ] Install Docker Desktop
2. [ ] Test `docker run hello-world`
3. [ ] Double-click `docker-dev-start.bat`
4. [ ] Buka http://localhost:8000/api/documentation
5. [ ] Test register user
6. [ ] Screenshot & bangga! 😎

**Total waktu:** 15 menit max!

**Kalau berhasil → Kamu udah PAHAM Docker! 🎉**

---

## 🆘 Stuck? Tanya Aja!

Kalau ada error atau bingung di step manapun:
1. Screenshot error message
2. Copy paste error log
3. Tanya!

**Remember:** Everyone starts here. Docker learning curve steep di awal, tapi worth it! 💪

---

**Happy Dockering! 🐳**
