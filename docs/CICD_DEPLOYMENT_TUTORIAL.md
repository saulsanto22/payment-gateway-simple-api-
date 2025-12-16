# 🚀 Tutorial: Setup CI/CD GitLab untuk Deployment Otomatis

Tutorial ini akan mengajarkan Anda cara setup **CI/CD Pipeline** dari nol sampai aplikasi live di production dengan Render.com.

---

## 📋 Apa yang Akan Kita Pelajari?

1. **GitLab CI/CD Pipeline** - Automation testing & deployment
2. **Container Registry** - Menyimpan Docker images
3. **Render.com Deployment** - Deploy aplikasi ke cloud (gratis, tanpa CC)
4. **Environment Variables** - Manage secrets dengan aman
5. **Production Monitoring** - Monitor deployment status

**Estimasi Waktu:** 30-45 menit

---

## ✅ Prerequisites

Sebelum mulai, pastikan sudah punya:

- [ ] Akun GitLab (gratis di gitlab.com)
- [ ] Code sudah di-push ke GitLab repository
- [ ] Akun Render.com (gratis di render.com)
- [ ] Akun GitHub (untuk connect ke Render)
- [ ] Akun Midtrans (untuk payment gateway)

---

## 🎯 Step 1: Setup GitLab Repository

### 1.1 Buat Repository di GitLab

1. Buka https://gitlab.com
2. Klik **"New Project"** → **"Create blank project"**
3. Isi:
   - **Project name:** `payment-gateway-laravel`
   - **Visibility:** Public (untuk portfolio) atau Private
4. Klik **"Create project"**

### 1.2 Push Code ke GitLab

```bash
# Di terminal, masuk ke folder project
cd c:\laragon\www\payment-gateway-simple-api-

# Initialize git (jika belum)
git init

# Add remote GitLab
git remote add origin https://gitlab.com/username-anda/payment-gateway-laravel.git

# Add semua files
git add .

# Commit
git commit -m "Initial commit: Laravel 12 Payment Gateway with CI/CD"

# Push ke GitLab
git push -u origin main
```

**Troubleshooting:**
- Jika error "remote origin already exists": `git remote remove origin` dulu
- Jika minta password: Generate GitLab Personal Access Token di Settings → Access Tokens

---

## 🔧 Step 2: Aktifkan GitLab CI/CD

### 2.1 Verifikasi File .gitlab-ci.yml

File `.gitlab-ci.yml` sudah ada di root project. File ini berisi:

```yaml
stages:
  - test     # Jalankan 52 automated tests
  - build    # Build Docker image
  - deploy   # Deploy ke Render.com
```

### 2.2 Aktifkan Container Registry (untuk Docker images)

1. Di GitLab project, buka **Settings** → **General**
2. Expand **Visibility, project features, permissions**
3. Pastikan **Container Registry** enabled
4. Klik **Save changes**

### 2.3 Lihat Pipeline Pertama Kali

Setelah push code dengan `.gitlab-ci.yml`:

1. Buka **CI/CD** → **Pipelines**
2. Akan ada pipeline yang running otomatis
3. Klik pipeline untuk lihat progress

**Pipeline akan menjalankan:**
- ✅ Pint (code style check)
- ✅ Test (52 automated tests)
- ⏸️ Build (pending manual approval)
- ⏸️ Deploy (pending manual approval)

---

## 🎨 Step 3: Setup Render.com (Deployment Target)

Render.com adalah platform cloud gratis tanpa kartu kredit untuk deploy aplikasi Laravel.

### 3.1 Buat Akun Render

1. Buka https://render.com
2. Klik **"Get Started"**
3. Sign up dengan GitHub (recommended)
4. Authorize Render untuk akses GitHub repos

**Render Free Tier:**
- ✅ **750 jam/bulan gratis** (cukup 24/7 untuk 1 app)
- ✅ PostgreSQL database gratis (1GB)
- ✅ Auto-deploy dari GitHub
- ✅ HTTPS otomatis
- ✅ **TANPA kartu kredit!** 🎉

**Cons:**
- ⚠️ Sleep setelah 15 menit idle (cold start ~30 detik)
- ⚠️ 100GB bandwidth/bulan

### 3.2 Push Code ke GitHub

> ⚠️ **Penting:** Render butuh code di GitHub (belum support GitLab direct).

Tapi tenang, kita bisa setup **auto-sync GitLab → GitHub**:

```bash
# 1. Tambah remote GitHub (jika belum)
git remote add github https://github.com/username-anda/payment-gateway.git

# 2. Push ke GitHub
git push github main
```

**Setup Auto-Sync GitLab → GitHub (Optional tapi Recommended):**

1. Di GitLab: **Settings** → **Repository** → **Mirroring repositories**
2. **Git repository URL:** `https://github.com/username/payment-gateway.git`
3. **Mirror direction:** Push
4. **Authentication:** Personal access token (dari GitHub)
5. **Mirror only protected branches:** ✅ main
6. Klik **"Mirror repository"**

Sekarang setiap push ke GitLab main → auto-sync ke GitHub!

### 3.3 Deploy Web Service di Render

1. Di Render Dashboard, klik **"New +"** → **"Web Service"**
2. Connect GitHub repository
3. Pilih repo: `payment-gateway-simple-api-`
4. Isi form:

**Basic Settings:**
- **Name:** `payment-gateway-api`
- **Region:** Singapore (paling dekat)
- **Branch:** `main`
- **Root Directory:** (kosongkan)

**Build & Deploy:**
- **Runtime:** Pilih `Docker` jika ada Dockerfile
- **Build Command:**
  ```bash
  composer install --no-dev --optimize-autoloader && php artisan config:cache && php artisan route:cache && php artisan view:cache
  ```
- **Start Command:**
  ```bash
  php artisan serve --host=0.0.0.0 --port=$PORT
  ```

**Instance Type:**
- Pilih: **Free** (750 jam/bulan)

5. Klik **"Create Web Service"**

Render akan mulai deploy (tunggu ~5-10 menit).

### 3.4 Tambahkan PostgreSQL Database

1. Di Render Dashboard, klik **"New +"** → **"PostgreSQL"**
2. Isi:
   - **Name:** `payment-gateway-db`
   - **Database:** `payment_gateway`
   - **User:** `payment_user`
   - **Region:** Singapore (sama dengan web service)
   - **PostgreSQL Version:** 16
   - **Plan:** **Free** (1GB storage)
3. Klik **"Create Database"**

Tunggu ~2 menit sampai database ready.

### 3.5 Setup Environment Variables

1. Di Render, buka web service: `payment-gateway-api`
2. Tab **"Environment"** (sidebar kiri)
3. Klik **"Add Environment Variable"**
4. Tambahkan satu per satu:

```bash
# Laravel
APP_NAME="Payment Gateway"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:xxxxx    # Generate dengan: php artisan key:generate --show

# Database (link dari Render database)
DB_CONNECTION=pgsql
DB_HOST=${{payment-gateway-db.HOST}}
DB_PORT=${{payment-gateway-db.PORT}}
DB_DATABASE=${{payment-gateway-db.DATABASE}}
DB_USERNAME=${{payment-gateway-db.USER}}
DB_PASSWORD=${{payment-gateway-db.PASSWORD}}

# Midtrans
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxx
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_IS_SANITIZED=true
MIDTRANS_IS_3DS=true

# Redis (pakai Railway Redis addon atau eksternal)
REDIS_HOST=xxxxx
REDIS_PASSWORD=xxxxx
REDIS_PORT=6379

# Mail (bisa pakai Mailtrap, SendGrid, atau Gmail)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=xxxxx
MAIL_PASSWORD=xxxxx
MAIL_FROM_ADDRESS=noreply@paymentgateway.com

# Queue & Cache (pakai database untuk free tier)
QUEUE_CONNECTION=database
CACHE_DRIVER=database
SESSION_DRIVER=database

# Atau pakai Redis eksternal (Upstash gratis):
# REDIS_URL=rediss://default:xxxxx@xxxxx.upstash.io:6379
# QUEUE_CONNECTION=redis
# CACHE_DRIVER=redis
# SESSION_DRIVER=redis
# JWT
JWT_SECRET=xxxxx    # Generate: php artisan jwt:secret --show

# App URL (akan dapat setelah deploy)
APP_URL=https://payment-gateway-api.onrender.com
```

**Tips:**
- Generate `APP_KEY`: Jalankan `php artisan key:generate --show` di lokal
- Generate `JWT_SECRET`: Jalankan `php artisan jwt:secret --show` di lokal
- Simpan semua secret keys dengan aman
- `APP_DEBUG=false` untuk production!
- Setelah tambah variables, klik **"Save Changes"** → Render auto-redeploy

### 3.6 Dapatkan Render Deploy Hook

Untuk CI/CD automation via GitLab, kita butuh Render Deploy Hook:

1. Di Render, buka web service: `payment-gateway-api`
2. Tab **"Settings"** (sidebar kiri)
3. Scroll ke **"Build & Deploy"** section
4. Copy **"Deploy Hook URL"**:
   ```
   https://api.render.com/deploy/srv-xxxxxxxxxxxxx?key=yyyyyyyyyyyyy
   ```
5. Simpan URL ini untuk GitLab CI/CD variables

**Deploy Hook** adalah URL khusus yang bisa di-trigger untuk deploy otomatis.

---

## 🔐 Step 4: Setup GitLab CI/CD Variables

Variables ini digunakan oleh pipeline untuk deploy otomatis ke Railway.

### 4.1 Tambahkan Variables di GitLab

1. Di GitLab project, buka **Settings** → **CI/CD**
2. Expand **Variables** section
3. Klik **"Add variable"**

Tambahkan variables berikut:

| Key | Value | Protected | Masked |
|-----|-------|-----------|--------|
| `RENDER_DEPLOY_HOOK` | Deploy Hook URL dari Render | ✅ | ✅ |
| `RENDER_SERVICE_URL` | URL aplikasi (contoh: https://payment-gateway-api.onrender.com) | ❌ | ❌ |

**Cara dapat RENDER_DEPLOY_HOOK:**
1. Di Render web service → **Settings**
2. Section **"Build & Deploy"**
3. Copy **"Deploy Hook URL"**
4. Paste ke GitLab variable `RENDER_DEPLOY_HOOK`

### 4.2 Setup Protected Branches (Optional tapi Recommended)

1. Di GitLab: **Settings** → **Repository** → **Protected branches**
2. Protect branch `main`:
   - Allowed to merge: Maintainers
   - Allowed to push: No one (hanya via merge request)

---

## 🚀 Step 5: Run CI/CD Pipeline

### 5.1 Trigger Pipeline

Ada 3 cara trigger pipeline:

**Cara 1: Push code**
```bash
# Edit file apa saja
git add .
git commit -m "Test CI/CD pipeline"
git push origin main
```

**Cara 2: Manual trigger di GitLab**
1. Buka **CI/CD** → **Pipelines**
2. Klik **"Run pipeline"**
3. Pilih branch: `main`
4. Klik **"Run pipeline"**

**Cara 3: Via Merge Request**
1. Buat branch baru: `git checkout -b feature/test-cicd`
2. Edit file, commit, push
3. Buat Merge Request di GitLab
4. Pipeline otomatis running untuk test

### 5.2 Monitor Pipeline Execution

Pipeline akan eksekusi dalam urutan:

```
┌─────────────────────────────────────┐
│  STAGE 1: TEST (Auto)               │
├─────────────────────────────────────┤
│  ├─ pint: Code style check          │ ⏱️ ~30 detik
│  └─ test: Run 52 tests              │ ⏱️ ~2-3 menit
└─────────────────────────────────────┘
         ↓ (Jika test PASS)
┌─────────────────────────────────────┐
│  STAGE 2: BUILD (Auto)              │
├─────────────────────────────────────┤
│  └─ build: Docker image             │ ⏱️ ~5-8 menit
└─────────────────────────────────────┘
         ↓
┌─────────────────────────────────────┐
│  STAGE 3: DEPLOY (Manual)           │
├─────────────────────────────────────┤
│  └─ deploy_railway: Deploy to prod  │ ⏱️ ~2 menit
│     (Klik tombol "Play" untuk run)  │
└─────────────────────────────────────┘
```

**Klik job untuk lihat logs:**
- Job hijau ✅ = Success
- Job merah ❌ = Failed (lihat logs untuk debug)
- Job biru 🔵 = Running

### 5.3 Deploy ke Production (Manual Trigger)

Setelah test & build selesai:

1. Di pipeline view, klik job **"deploy_railway"**
2. Klik tombol ▶️ **"Play"** atau **"Run"**
3. Railway akan deploy aplikasi
4. Tunggu ~2 menit
5. Aplikasi live! 🎉

### 5.4 Verifikasi Deployment

Check apakah aplikasi sudah live:

```bash
# Test API endpoint
curl https://payment-gateway-api.onrender.com/api/documentation

# Atau buka di browser
https://payment-gateway-api.onrender.com/api/documentation
```

---

## 🐛 Troubleshooting

### Problem 1: Test Stage Failed

**Error:** `SQLSTATE[08006] [7] could not connect to server`

**Solusi:**
- Cek service PostgreSQL di `.gitlab-ci.yml` (harus `postgres:16-alpine`)
- Cek variables `DB_HOST=postgres` bukan `localhost`

---

### Problem 2: Build Stage Failed

**Error:** `Cannot find Dockerfile`

**Solusi:**
```yaml
# Di .gitlab-ci.yml, pastikan path benar:
script:
  - docker build -f Dockerfile.dev -t ...
```

---

### Problem 3: Deploy Failed - Render Deploy Hook Invalid

**Error:** `Failed to trigger Render deployment`

**Solusi:**
1. Cek Deploy Hook URL di Render Settings
2. Update variable `RENDER_DEPLOY_HOOK` di GitLab CI/CD settings
3. Pastikan URL lengkap dengan `?key=xxxxx`
4. Re-run pipeline

---

### Problem 4: Deployment Success tapi 500 Error

**Checklist:**
- [ ] `APP_KEY` sudah di-set di Railway?
- [ ] Database migrations sudah running?
- [ ] Semua environment variables sudah lengkap?

**Fix:**
```bash
# Di Render dashboard, buka Shell (tab sidebar)
# Tunggu terminal terbuka, lalu jalankan:
php artisan migrate --force
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

---

## 📊 Step 6: Monitoring & Logs

### 6.1 Lihat Logs di Render

1. Render dashboard → Pilih service `payment-gateway-api`
2. Tab **"Logs"** (sidebar kiri) untuk real-time logs
3. Tab **"Events"** untuk deployment history
4. Tab **"Metrics"** untuk monitoring CPU/Memory

**Tips:**
- Logs auto-refresh setiap beberapa detik
- Bisa search/filter logs di UI
- Download logs untuk analysis

### 6.2 Monitor Pipeline di GitLab

**Pipeline Status Badge:**

Tambahkan di README.md:

```markdown
[![Pipeline Status](https://gitlab.com/username-anda/payment-gateway-laravel/badges/main/pipeline.svg)](https://gitlab.com/username-anda/payment-gateway-laravel/-/commits/main)
```

**Email Notifications:**

GitLab otomatis kirim email kalau pipeline failed.

### 6.3 Setup Midtrans Webhook di Production

Jangan lupa update webhook URL di Midtrans dashboard:

1. Login ke https://dashboard.midtrans.com
2. **Settings** → **Configuration**
3. **Payment Notification URL:**
   ```
   https://payment-gateway-api.onrender.com/api/midtrans/webhook
   ```
4. Klik **Save**

---

## 🎯 Step 7: Workflow Development Sehari-hari

### Cara Kerja CI/CD dalam Daily Development:

```
1. Bikin fitur baru di local
   ↓
2. git checkout -b feature/new-feature
   ↓
3. Coding...
   ↓
4. git add . && git commit -m "Add new feature"
   ↓
5. git push origin feature/new-feature
   ↓
6. Buat Merge Request di GitLab
   ↓
7. Pipeline otomatis run tests ✅
   ↓
8. Review code (optional)
   ↓
9. Merge to main
   ↓
10. Pipeline otomatis test & build ✅
   ↓
11. Klik "Deploy" untuk production 🚀
```

**Best Practices:**
- ✅ Selalu test di local dulu: `php artisan test`
- ✅ Never push langsung ke `main`, selalu pakai branch + MR
- ✅ Deploy hanya setelah semua test hijau
- ✅ Monitor logs setelah deploy
- ✅ Rollback jika ada error (Railway punya history)

---

## 🎉 Selesai!

Sekarang kamu punya:

✅ **Automated Testing** - 52 tests running otomatis setiap push
✅ **Automated Build** - Docker image ter-build otomatis
✅ **Automated Deployment** - 1-click deploy ke Render.com
✅ **Professional Workflow** - GitLab CI/CD pipeline lengkap
✅ **Live Production App** - API live di Render.com dengan HTTPS (gratis!)

---

## 📚 Resources

- [GitLab CI/CD Docs](https://docs.gitlab.com/ee/ci/)
- [Render Docs](https://docs.render.com/)
- [Laravel on Render Guide](https://docs.render.com/deploy-laravel)
- [Docker Best Practices](https://docs.docker.com/develop/dev-best-practices/)
- [Laravel Deployment](https://laravel.com/docs/12.x/deployment)

---

## 💡 Next Steps

1. **Setup monitoring**: Render Metrics, UptimeRobot (prevent sleep), Sentry error tracking
2. **Add more stages**: Security scanning, performance testing
3. **Auto-deploy**: Ubah `when: manual` jadi `when: on_success` di .gitlab-ci.yml
4. **Staging environment**: Buat web service kedua untuk `staging`
5. **Database backups**: Render auto-backup database (daily)
6. **Custom domain**: Setup domain sendiri di Render Settings

---

**Happy Deploying! 🚀**

Jika ada pertanyaan atau error, check troubleshooting section atau tanya di:
- GitLab CI/CD Forum: https://forum.gitlab.com
- Render Community: https://community.render.com
- Render Docs: https://docs.render.com
