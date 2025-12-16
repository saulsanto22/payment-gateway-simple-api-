# 🎓 Tutorial Lengkap: Dari Nol sampai Live dengan Render + CI/CD

**Target:** Pemula yang belum pernah pakai CI/CD dan Render
**Waktu:** 1-2 jam (santai, sambil belajar)
**Hasil:** Aplikasi live + pipeline CI/CD working

---

## 🗺️ Roadmap Pembelajaran

```
PART 1: Deploy Manual ke Render (20 menit)
  └─→ Aplikasi live di internet!
       └─→ Kamu punya URL untuk dicoba

PART 2: Setup GitLab CI/CD (30 menit)  
  └─→ Pipeline automated testing
       └─→ Setiap push = 52 tests running

PART 3: Connect CI/CD ke Render (20 menit)
  └─→ Push code = auto-deploy!
       └─→ Professional workflow lengkap
```

---

# PART 1: Deploy Manual ke Render (20 Menit) ⚡

## Tujuan
Buat aplikasi Laravel kamu **live di internet** dengan URL sendiri, **GRATIS**.

---

## Step 1.1: Cek Setup GitLab (5 menit)

### Apakah code sudah di GitLab?

```bash
# Di terminal, masuk folder project
cd c:\laragon\www\payment-gateway-simple-api-

# Cek remote repository
git remote -v
```

**Kemungkinan hasil:**

### ✅ Kalau sudah ada GitLab:
```
gitlab  https://gitlab.com/username/repo.git (fetch)
gitlab  https://gitlab.com/username/repo.git (push)
```
→ **Bagus! Lanjut ke Step 1.2**

### ❌ Kalau belum ada atau kosong:
```
(tidak ada output, atau hanya github)
```
→ **Setup dulu:**

```bash
# 1. Buat repo baru di gitlab.com
# Buka: https://gitlab.com/projects/new
# - Project name: payment-gateway-api
# - Visibility: Public (untuk portfolio) atau Private
# - Klik "Create project"

# 2. Add remote GitLab
git remote add gitlab https://gitlab.com/USERNAME-KAMU/payment-gateway-api.git

# 3. Push code
git add .
git commit -m "Initial commit: Payment Gateway API"
git push -u gitlab main
```

**Ganti `USERNAME-KAMU` dengan username GitLab kamu!**

---

## Step 1.2: Buat Akun GitHub (5 menit)

> ⚠️ **Kenapa butuh GitHub?** 
> Render.com saat ini hanya support GitHub untuk auto-deploy (belum support GitLab direct).
> Tapi tenang, nanti kita bisa setup **auto-sync GitLab → GitHub**!

### Apakah sudah punya akun GitHub?

**Belum?** 
1. Buka https://github.com/signup
2. Daftar (gratis)
3. Verify email

**Sudah?** Lanjut!

### Push Code ke GitHub:

```bash
# 1. Buat repo baru di github.com
# Buka: https://github.com/new
# - Repository name: payment-gateway-api
# - Public atau Private (terserah)
# - JANGAN centang "Add README" (kita udah punya)
# - Klik "Create repository"

# 2. Add remote GitHub
git remote add github https://github.com/USERNAME-KAMU/payment-gateway-api.git

# 3. Push ke GitHub
git push github main
```

**Cek hasil:** Buka `https://github.com/USERNAME-KAMU/payment-gateway-api` → code harus muncul!

---

## Step 1.3: Setup Render Account (2 menit)

1. Buka https://render.com
2. Klik **"Get Started"** (pojok kanan atas)
3. **Pilih "Sign up with GitHub"** (recommended)
4. Authorize Render untuk akses repo GitHub kamu
5. **DONE!** Kamu sudah login ke dashboard Render

**Cek:** Dashboard Render harus terbuka, ada tombol "New +" di kiri atas.

---

## Step 1.4: Deploy Web Service (8 menit)

Ini bagian paling penting!

### A. Buat Web Service

1. Di Render Dashboard, klik **"New +"** (pojok kiri atas)
2. Pilih **"Web Service"**
3. **Connect GitHub repository:**
   - Scroll cari repo: `payment-gateway-api`
   - Klik **"Connect"**

### B. Konfigurasi Service

Isi form dengan teliti:

| Field | Value |
|-------|-------|
| **Name** | `payment-gateway-api` |
| **Region** | **Singapore** (paling dekat ke Indonesia) |
| **Branch** | `main` |
| **Root Directory** | (kosongkan) |
| **Runtime** | **Native** (pilih ini, JANGAN Docker) |
| **Build Command** | (copy paste ini): |

```bash
composer install --no-dev --optimize-autoloader && php artisan config:cache && php artisan route:cache && php artisan view:cache
```

| Field | Value |
|-------|-------|
| **Start Command** | (copy paste ini): |

```bash
php artisan serve --host=0.0.0.0 --port=$PORT
```

| **Instance Type** | **Free** (750 jam/bulan) |

### C. Environment Variables (PENTING!)

Scroll ke bawah ke section **"Environment Variables"**

Klik **"Add Environment Variable"** dan tambahkan satu-per-satu:

```bash
# 1. Laravel Basic
APP_NAME=Payment Gateway
APP_ENV=production
APP_DEBUG=false

# 2. Generate APP_KEY dulu di lokal:
# Jalankan di terminal: php artisan key:generate --show
# Copy hasilnya, lalu paste ke:
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

# 3. Database (SKIP dulu, nanti kita isi setelah buat database)

# 4. JWT Secret
# Generate di lokal: php artisan jwt:secret --show
# Copy hasilnya:
JWT_SECRET=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

# 5. Midtrans (pakai Sandbox dulu)
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxx
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_IS_SANITIZED=true
MIDTRANS_IS_3DS=true

# 6. Cache & Queue (pakai database untuk free tier)
CACHE_DRIVER=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database

# 7. Mail (pakai Mailtrap untuk testing)
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=xxxx
MAIL_PASSWORD=xxxx
MAIL_FROM_ADDRESS=noreply@paymentgateway.com
MAIL_FROM_NAME=Payment Gateway

# 8. App URL (isi nanti setelah tahu URL-nya)
# APP_URL=https://payment-gateway-api.onrender.com
```

### D. Deploy!

1. Scroll paling bawah
2. Klik **"Create Web Service"** (tombol biru besar)
3. **Tunggu ~5-10 menit** (Render sedang build & deploy)

**Progress yang muncul:**
- 🔵 Building... (install dependencies)
- 🔵 Deploying...
- ✅ **Live!** (kalau sukses)

**Kalau ERROR:**
- Lihat tab "Logs" untuk cek error
- Biasanya masalah di Build Command atau environment variables

---

## Step 1.5: Buat PostgreSQL Database (3 menit)

Aplikasi butuh database!

1. Klik **"New +"** lagi (pojok kiri atas)
2. Pilih **"PostgreSQL"**
3. Isi:
   - **Name:** `payment-gateway-db`
   - **Database:** `payment_gateway`
   - **User:** `payment_user`
   - **Region:** **Singapore** (HARUS sama dengan web service!)
   - **PostgreSQL Version:** **16**
   - **Plan:** **Free** (1GB storage)
4. Klik **"Create Database"**
5. **Tunggu ~2 menit** sampai status jadi "Available"

---

## Step 1.6: Connect Database ke Web Service (5 menit)

### A. Dapatkan Database Credentials

1. Buka database `payment-gateway-db` yang baru dibuat
2. Tab **"Info"** (di sidebar kiri)
3. Lihat section **"Connections"**
4. Copy informasi ini (klik icon 📋 untuk copy):

```
Internal Database URL: postgresql://payment_user:xxxx@xxxx/payment_gateway
Hostname: xxxx-xxxx.oregon-postgres.render.com
Port: 5432
Database: payment_gateway
Username: payment_user
Password: xxxxxxxxxx
```

### B. Update Environment Variables Web Service

1. Buka web service: `payment-gateway-api`
2. Tab **"Environment"** (sidebar kiri)
3. Klik **"Add Environment Variable"**
4. Tambahkan satu-per-satu (pakai info dari langkah A):

```bash
DB_CONNECTION=pgsql
DB_HOST=xxxx-xxxx.oregon-postgres.render.com    # dari Hostname
DB_PORT=5432
DB_DATABASE=payment_gateway
DB_USERNAME=payment_user
DB_PASSWORD=xxxxxxxxxx    # dari Password
```

5. Klik **"Save Changes"**

**Render akan auto-redeploy** (tunggu ~2 menit lagi)

---

## Step 1.7: Run Database Migrations (3 menit)

Sekarang database sudah connect, tapi masih kosong (no tables).

1. Buka web service: `payment-gateway-api`
2. Tab **"Shell"** (sidebar kiri)
3. Tunggu terminal terbuka (~30 detik)
4. Jalankan commands ini satu-per-satu:

```bash
# 1. Run migrations (buat tables)
php artisan migrate --force

# Tunggu sampai selesai, harus muncul:
# ✅ Migration table created successfully
# ✅ Migrating: 2025_10_01_143437_create_products_table
# ... (dan seterusnya)

# 2. Seed data (optional, untuk testing)
php artisan db:seed --force

# 3. Clear cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

**Kalau ERROR:**
- Cek apakah DB credentials benar
- Cek apakah database status "Available"

---

## Step 1.8: Test Aplikasi Live! 🎉 (2 menit)

### Dapatkan URL Aplikasi:

1. Di web service dashboard, lihat bagian paling atas
2. Ada URL: `https://payment-gateway-api-xxxx.onrender.com`
3. Copy URL ini!

### Test API:

```bash
# Option 1: Pakai browser
# Buka: https://payment-gateway-api-xxxx.onrender.com/api/documentation

# Option 2: Pakai curl di terminal
curl https://payment-gateway-api-xxxx.onrender.com/api/health
```

**Hasil yang diharapkan:**
```json
{
  "status": "healthy",
  "timestamp": "2025-12-16T10:30:00.000000Z",
  "database": "connected"
}
```

---

## 🎉 CONGRATULATIONS! Part 1 SELESAI!

Kamu sudah punya:
- ✅ Aplikasi Laravel live di internet
- ✅ URL public untuk dicoba
- ✅ Database PostgreSQL working
- ✅ API Documentation accessible

**URL Kamu:** `https://payment-gateway-api-xxxx.onrender.com`

---

# PART 2: Setup GitLab CI/CD (30 Menit) 🔄

## Tujuan
Buat **automated pipeline** yang jalankan 52 tests setiap kali kamu push code.

---

## Apa itu CI/CD? (Penjelasan Simple)

**CI/CD = Continuous Integration / Continuous Deployment**

Bayangin:
```
Tanpa CI/CD:
1. Kamu coding → 2. Push code → 3. ??? → 4. Deploy manual

Dengan CI/CD:
1. Kamu coding → 2. Push code → 3. AUTO: Test (52 tests) → 4. AUTO: Deploy → 5. Live!
```

**Keuntungan:**
- ✅ Setiap push = otomatis di-test
- ✅ Kalau test gagal = nggak di-deploy (safety!)
- ✅ Nggak perlu manual deploy lagi
- ✅ Professional workflow (kayak di perusahaan besar)

---

## Step 2.1: Cek File .gitlab-ci.yml (2 menit)

File ini sudah ada di project kamu! Ini adalah "recipe" untuk GitLab CI/CD.

```bash
# Cek apakah file ada:
ls .gitlab-ci.yml
```

**Kalau ada:** Lanjut ke Step 2.2 ✅

**Kalau tidak ada:** File hilang, tapi tenang! Sudah ada di repo. Coba:
```bash
git status
git pull gitlab main
```

---

## Step 2.2: Pahami Pipeline (5 menit)

Buka file `.gitlab-ci.yml`, lihat strukturnya:

```yaml
stages:
  - test      # ← Stage 1: Jalankan tests
  - build     # ← Stage 2: Build Docker image
  - deploy    # ← Stage 3: Deploy ke Render
```

**Pipeline akan jalan begini:**

```
┌─────────────────────┐
│  STAGE 1: TEST      │
│  ├─ Pint (style)    │  ← Cek code style
│  └─ Test (52 tests) │  ← Run semua tests
└─────────────────────┘
         ↓ (kalau PASS)
┌─────────────────────┐
│  STAGE 2: BUILD     │
│  └─ Build Docker    │  ← Build image
└─────────────────────┘
         ↓
┌─────────────────────┐
│  STAGE 3: DEPLOY    │
│  └─ Deploy Render   │  ← Trigger Render deploy
└─────────────────────┘
```

---

## Step 2.3: Enable GitLab CI/CD (3 menit)

1. Buka project di GitLab.com
2. Sidebar kiri: **Settings** → **General**
3. Expand: **Visibility, project features, permissions**
4. Pastikan ini ENABLED:
   - ✅ **CI/CD**
   - ✅ **Container Registry**
5. Klik **"Save changes"**

---

## Step 2.4: Push ke GitLab & Trigger Pipeline (5 menit)

Pipeline akan jalan otomatis saat kamu push code:

```bash
# 1. Pastikan semua changes di-commit
git status

# 2. Kalau ada yang belum di-commit:
git add .
git commit -m "Setup untuk CI/CD"

# 3. Push ke GitLab
git push gitlab main
```

**Apa yang terjadi:**
- GitLab otomatis detect file `.gitlab-ci.yml`
- GitLab create pipeline baru
- Pipeline mulai running!

---

## Step 2.5: Monitor Pipeline Pertama (10 menit)

1. Buka project di GitLab
2. Sidebar kiri: **CI/CD** → **Pipelines**
3. Kamu akan lihat pipeline pertama **running** (icon 🔵)

**Klik pipeline** untuk lihat detail:

```
Job: pint (running...)
  └─ Checking code style...
  
Job: test (waiting...)
  └─ Will run after pint finishes
```

**Timeline:**
- **0-1 menit:** Pint running (cek code style)
- **1-4 menit:** Test running (52 tests dengan PostgreSQL + Redis)
- **4+ menit:** Build stage (Docker image)

**Kemungkinan hasil:**

### ✅ Pipeline PASS (hijau):
```
✅ pint     (30 detik)
✅ test     (3 menit)
⏸️  build    (manual trigger)
⏸️  deploy   (manual trigger)
```
→ **Bagus! Tests working!** Lanjut Part 3.

### ❌ Pipeline FAIL (merah):
```
✅ pint     (30 detik)
❌ test     (error)
⏸️  build    (skipped)
```
→ **Klik job "test"** untuk lihat error. Biasanya:
- DB connection error
- Missing environment variable
- Test failed

**Cara fix:**
1. Lihat log error
2. Fix di lokal: `php artisan test`
3. Commit & push lagi

---

## Step 2.6: Pahami Test Results (5 menit)

Kalau pipeline PASS, klik job **"test"** untuk lihat detail:

```
✓ Tests:    52 passed (243 assertions)
  Duration: 2.85s
```

**Ini artinya:**
- ✅ 52 tests jalan semua
- ✅ 243 assertions di-check
- ✅ Semua PASS (no errors)

**Tests apa yang jalan?**
- Authentication tests (login, register, JWT)
- Product tests (CRUD, stock management)
- Cart tests (add, update, checkout)
- Order tests (create, status, payment)
- Webhook tests (Midtrans notification)

---

## 🎉 CONGRATULATIONS! Part 2 SELESAI!

Kamu sudah punya:
- ✅ GitLab CI/CD pipeline working
- ✅ 52 tests running otomatis
- ✅ Code style check otomatis
- ✅ Professional workflow

**Pipeline Badge:**
Copy-paste ini ke README:
```markdown
[![Pipeline](https://gitlab.com/USERNAME-KAMU/payment-gateway-api/badges/main/pipeline.svg)](https://gitlab.com/USERNAME-KAMU/payment-gateway-api/-/pipelines)
```

---

# PART 3: Connect CI/CD ke Render (20 Menit) 🚀

## Tujuan
Buat pipeline otomatis **deploy ke Render** setelah tests PASS.

---

## Step 3.1: Dapatkan Render Deploy Hook (5 menit)

Deploy Hook = URL khusus untuk trigger deployment via API.

1. Buka web service di Render: `payment-gateway-api`
2. Tab **"Settings"** (sidebar kiri)
3. Scroll ke section: **"Build & Deploy"**
4. Copy **"Deploy Hook URL"**:
   ```
   https://api.render.com/deploy/srv-xxxxxxxxxxxx?key=yyyyyyyyyyyy
   ```
5. **Simpan URL ini** (kita butuh untuk GitLab)

---

## Step 3.2: Setup GitLab CI/CD Variables (10 menit)

CI/CD Variables = rahasia yang disimpan aman di GitLab (nggak kelihatan di code).

### A. Buka GitLab Settings

1. Project di GitLab
2. **Settings** → **CI/CD**
3. Expand: **Variables**
4. Klik **"Add variable"**

### B. Add Variable 1: RENDER_DEPLOY_HOOK

- **Key:** `RENDER_DEPLOY_HOOK`
- **Value:** (paste Deploy Hook URL dari Step 3.1)
- **Type:** Variable
- **Flags:**
  - ✅ **Mask variable** (rahasia, disensor di log)
  - ✅ **Protect variable** (hanya untuk branch main)
- Klik **"Add variable"**

### C. Add Variable 2: RENDER_SERVICE_URL

- **Key:** `RENDER_SERVICE_URL`
- **Value:** `https://payment-gateway-api-xxxx.onrender.com` (URL aplikasi kamu)
- **Type:** Variable
- **Flags:**
  - ❌ Mask variable (nggak perlu)
  - ❌ Protect variable (nggak perlu)
- Klik **"Add variable"**

**Cek:** Harus ada 2 variables di list!

---

## Step 3.3: Test Deploy Manual (5 menit)

Sebelum automation, test deploy manual dulu.

1. GitLab: **CI/CD** → **Pipelines**
2. Pilih pipeline terakhir yang PASS
3. Lihat job **"deploy_render"** (icon ⏸️ = manual trigger)
4. Klik job **"deploy_render"**
5. Klik tombol **▶️ Play** (pojok kanan atas)

**Apa yang terjadi:**
- GitLab trigger Render Deploy Hook
- Render pull latest code dari GitHub
- Render rebuild & redeploy
- ~2-3 menit

**Cek hasil:**
- Job jadi ✅ hijau = deploy sukses
- Buka aplikasi: `https://payment-gateway-api-xxxx.onrender.com/api/documentation`
- Harus muncul Swagger UI terbaru

---

## 🎉 CONGRATULATIONS! SEMUA SELESAI! 🎊

Kamu sudah punya **Full CI/CD Pipeline**:

```
1. Coding di local
   ↓
2. git push gitlab main
   ↓
3. GitLab auto-run 52 tests ✅
   ↓
4. Klik "Deploy" button
   ↓
5. Auto-deploy ke Render 🚀
   ↓
6. Aplikasi live di production!
```

---

## 📊 Apa yang Kamu Dapat:

### 🏆 Skills
- ✅ Deploy aplikasi ke production (Render)
- ✅ Setup CI/CD pipeline (GitLab)
- ✅ Automated testing
- ✅ Environment management
- ✅ Git workflow (GitLab + GitHub)

### 💼 Portfolio Assets
- ✅ Live demo URL
- ✅ Pipeline badge
- ✅ Professional GitHub/GitLab profile
- ✅ Production-ready code

### 🚀 Next Level
- Setup auto-deploy (ubah `when: manual` → `when: on_success`)
- Add staging environment
- Setup monitoring (UptimeRobot)
- Custom domain
- Performance optimization

---

## 🐛 Troubleshooting Common Issues

### Issue 1: Pipeline Failed - Test Error

**Error:** `Tests failed`

**Debug:**
1. GitLab: Klik job "test" → lihat log
2. Cari line dengan `FAILED`
3. Fix test di lokal:
   ```bash
   php artisan test --filter NamaTestYangGagal
   ```
4. Commit & push lagi

---

### Issue 2: Deploy Hook Failed

**Error:** `Failed to trigger Render deployment`

**Solusi:**
1. Cek Deploy Hook URL di GitLab variables
2. Pastikan URL lengkap dengan `?key=xxxxx`
3. Test manual di terminal:
   ```bash
   curl -X POST "https://api.render.com/deploy/srv-xxx?key=yyy"
   ```
4. Harus return: `{"ok": true}`

---

### Issue 3: Aplikasi 500 Error setelah Deploy

**Checklist:**
- [ ] `APP_KEY` sudah di-set di Render?
- [ ] Database migrations sudah running?
- [ ] JWT_SECRET sudah di-set?

**Fix:**
1. Render: Shell
2. Run:
   ```bash
   php artisan migrate --force
   php artisan config:clear
   php artisan cache:clear
   ```

---

### Issue 4: Tests Pass Locally tapi Fail di Pipeline

**Biasanya karena:**
- Environment different (PostgreSQL version, PHP version)
- Missing dependencies
- Database seeds issue

**Debug:**
1. Cek `.gitlab-ci.yml` → PHP version harus sama dengan lokal
2. Cek `services:` → PostgreSQL version
3. Run test di lokal dengan PostgreSQL (jangan SQLite)

---

## 📚 Resources untuk Belajar Lebih

### GitLab CI/CD
- [GitLab CI/CD Docs](https://docs.gitlab.com/ee/ci/) - Official docs
- [GitLab CI/CD Examples](https://docs.gitlab.com/ee/ci/examples/) - Sample configs
- [GitLab CI/CD Variables](https://docs.gitlab.com/ee/ci/variables/) - Environment variables

### Render.com
- [Render Docs](https://docs.render.com/) - Comprehensive guides
- [Laravel on Render](https://docs.render.com/deploy-laravel) - Laravel specific
- [Render Community](https://community.render.com/) - Ask questions

### Testing
- [Pest Docs](https://pestphp.com/docs) - Test framework
- [Laravel Testing](https://laravel.com/docs/12.x/testing) - Official guide

---

## 💡 Pro Tips

### 1. Auto-Deploy (Advanced)

Edit `.gitlab-ci.yml`, ubah deploy job:

```yaml
deploy_render:
  rules:
    - if: '$CI_COMMIT_BRANCH == "main"'
      when: on_success  # ← Ubah dari "manual" jadi "on_success"
```

Sekarang **setiap push ke main = auto-deploy**! (Hati-hati, pastikan tests selalu pass)

---

### 2. Setup Staging Environment

Buat 2 services di Render:
- `payment-gateway-api` (production)
- `payment-gateway-api-staging` (testing)

Edit `.gitlab-ci.yml`:
```yaml
deploy_staging:
  stage: deploy
  script:
    - curl -X POST "$RENDER_DEPLOY_HOOK_STAGING"
  rules:
    - if: '$CI_COMMIT_BRANCH == "develop"'
```

---

### 3. Monitor dengan UptimeRobot

Biar aplikasi nggak sleep:

1. Daftar https://uptimerobot.com
2. Add Monitor:
   - Type: HTTP(s)
   - URL: `https://payment-gateway-api-xxxx.onrender.com/api/health`
   - Interval: 5 minutes
3. Done! Service always awake.

---

### 4. GitLab → GitHub Auto-Sync

Setup mirror repository:

1. GitLab: **Settings** → **Repository**
2. Expand: **Mirroring repositories**
3. Add:
   - URL: `https://github.com/USERNAME/payment-gateway-api.git`
   - Direction: **Push**
   - Auth: Personal Access Token (dari GitHub)
4. Save

Sekarang **push ke GitLab = auto-sync ke GitHub**!

---

## 🎓 Selamat Belajar!

Kamu sudah menyelesaikan tutorial lengkap dari nol sampai punya:
- ✅ Aplikasi live di production
- ✅ CI/CD pipeline working
- ✅ Automated testing
- ✅ Professional workflow

**Kalau ada yang stuck, jangan sungkan tanya!** 

Dokumentasi lengkap juga ada di:
- [RENDER_QUICK_START.md](./RENDER_QUICK_START.md) - Render setup detail
- [CICD_DEPLOYMENT_TUTORIAL.md](./CICD_DEPLOYMENT_TUTORIAL.md) - CI/CD deep dive

---

**Happy Learning & Coding! 🚀**
