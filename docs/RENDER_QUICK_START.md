# 🚀 Render.com - Quick Deploy Guide (15 Menit)

Deploy Laravel Payment Gateway ke Render.com dengan **gratis, tanpa kartu kredit**!

---

## ✅ Prerequisites

- [ ] Akun Render.com (daftar di https://render.com - GRATIS)
- [ ] Code sudah di GitHub (bisa push dari GitLab)
- [ ] Akun Midtrans untuk payment gateway

**Render Free Tier:**
- ✅ **750 jam/bulan** (cukup 1 app 24/7)
- ✅ PostgreSQL database gratis
- ✅ Auto-deploy dari GitHub
- ✅ HTTPS otomatis
- ✅ **TANPA kartu kredit!**

**Cons:**
- ⚠️ Sleep setelah 15 menit idle (cold start ~30 detik)
- ⚠️ 100GB bandwidth/bulan

---

## 🚀 Step-by-Step Deployment

### Step 1: Push Code ke GitHub

Render.com butuh code di GitHub (belum support GitLab direct):

```bash
# Pastikan sudah ada remote GitHub
git remote -v

# Jika belum, tambahkan:
git remote add github https://github.com/username-anda/payment-gateway.git

# Push ke GitHub
git add .
git commit -m "Deploy to Render"
git push github main
```

---

### Step 2: Buat Akun Render

1. Buka https://render.com
2. Klik **"Get Started"**
3. Sign up dengan GitHub (recommended)
4. Authorize Render untuk akses repo

---

### Step 3: Deploy Web Service

1. Di Render Dashboard, klik **"New +"** → **"Web Service"**
2. Connect GitHub repository Anda
3. Pilih repo: `payment-gateway-simple-api-`
4. Isi form:

**Basic Settings:**
- **Name:** `payment-gateway-api`
- **Region:** Singapore (paling dekat)
- **Branch:** `main`
- **Root Directory:** (kosongkan)
- **Runtime:** `Docker`

**Build & Deploy:**
- **Dockerfile Path:** `Dockerfile.dev` (atau `Dockerfile` jika punya)

Jika tidak pakai Docker, pilih `Native`:
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

Render akan mulai deploy (tunggu ~5-10 menit)

---

### Step 4: Tambahkan PostgreSQL Database

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

---

### Step 5: Link Database ke Web Service

1. Buka web service: `payment-gateway-api`
2. Tab **"Environment"** (sidebar kiri)
3. Klik **"Add Environment Variable"**
4. Add database connection:

**Cara Otomatis (Recommended):**

Render auto-generate `DATABASE_URL`. Tapi Laravel butuh variabel terpisah:

1. Buka database: `payment-gateway-db`
2. Tab **"Info"** → Copy nilai:
   - **Internal Database URL:** `postgresql://user:pass@host:5432/dbname`
3. Parse URL dan tambahkan ke web service environment:

```bash
# Laravel Database
DB_CONNECTION=pgsql
DB_HOST=<hostname dari database URL>
DB_PORT=5432
DB_DATABASE=payment_gateway
DB_USERNAME=<username dari database URL>
DB_PASSWORD=<password dari database URL>
```

**Cara Manual:**

Atau langsung link database:
1. Di web service environment variables
2. Klik **"Add from Render Database"**
3. Pilih: `payment-gateway-db`
4. Render auto-add: `DATABASE_URL`

Tapi tetap tambahkan manual untuk Laravel:
```bash
DB_CONNECTION=pgsql
DB_HOST=${{payment-gateway-db.HOST}}
DB_PORT=${{payment-gateway-db.PORT}}
DB_DATABASE=${{payment-gateway-db.DATABASE}}
DB_USERNAME=${{payment-gateway-db.USER}}
DB_PASSWORD=${{payment-gateway-db.PASSWORD}}
```

---

### Step 6: Setup Environment Variables

Di web service → **Environment**, tambahkan:

```bash
# Laravel
APP_NAME="Payment Gateway"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:xxxxx    # Generate: php artisan key:generate --show

# Database (sudah di-set dari step 5)

# Midtrans
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxx
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_IS_SANITIZED=true
MIDTRANS_IS_3DS=true

# Redis (optional - pakai file/database untuk queue di free tier)
REDIS_HOST=redis-xxxxx.render.com
REDIS_PASSWORD=xxxxx
REDIS_PORT=6379

# Atau pakai database sebagai cache/queue (untuk free tier):
CACHE_DRIVER=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database

# Mail (Mailtrap gratis atau Gmail)
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=xxxxx
MAIL_PASSWORD=xxxxx
MAIL_FROM_ADDRESS=noreply@paymentgateway.com
MAIL_FROM_NAME="${APP_NAME}"

# JWT
JWT_SECRET=xxxxx    # Generate: php artisan jwt:secret --show

# App URL
APP_URL=https://payment-gateway-api.onrender.com
```

**Tips:**
- Generate `APP_KEY`: `php artisan key:generate --show`
- Generate `JWT_SECRET`: `php artisan jwt:secret --show`
- `APP_DEBUG=false` untuk production!

Klik **"Save Changes"** → Render akan auto-redeploy.

---

### Step 7: Run Database Migrations

Setelah deploy selesai:

1. Di web service, klik tab **"Shell"** (sidebar kiri)
2. Tunggu terminal terbuka
3. Jalankan migrations:

```bash
php artisan migrate --force
```

4. (Optional) Seed data:
```bash
php artisan db:seed --force
```

5. Clear cache:
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

---

### Step 8: Dapatkan Domain & Test

1. Di web service dashboard, lihat bagian atas:
   - **URL:** `https://payment-gateway-api.onrender.com`
2. Copy URL
3. Test API:

```bash
# Test health check
curl https://payment-gateway-api.onrender.com/api/health

# Test API documentation
https://payment-gateway-api.onrender.com/api/documentation
```

4. Buka di browser untuk lihat Swagger UI

---

### Step 9: Setup Midtrans Webhook

Update webhook di Midtrans dashboard:

1. Login https://dashboard.midtrans.com
2. **Settings** → **Configuration**
3. **Payment Notification URL:**
   ```
   https://payment-gateway-api.onrender.com/api/midtrans/webhook
   ```
4. **Save**

---

## 🎉 Selesai! Aplikasi Live di Production!

✅ Laravel API live di: `https://payment-gateway-api.onrender.com`
✅ PostgreSQL database ready
✅ Auto-deploy setiap push ke GitHub
✅ HTTPS otomatis
✅ 750 jam/bulan gratis (cukup 24/7 untuk 1 app)

---

## 📊 Useful Render Commands

### Via Dashboard:

1. **Logs:** Tab "Logs" untuk real-time logs
2. **Shell:** Tab "Shell" untuk akses terminal
3. **Metrics:** Tab "Metrics" untuk monitoring
4. **Manual Deploy:** Button "Manual Deploy" → "Deploy latest commit"

### Via Render CLI (Optional):

```bash
# Install Render CLI
npm install -g @render/cli

# Login
render login

# View services
render services list

# View logs
render logs -s payment-gateway-api --tail 100

# Run command
render shell -s payment-gateway-api -c "php artisan migrate"
```

---

## 🐛 Troubleshooting

### Problem 1: Deploy Failed - Build Error

**Error:** `composer install failed`

**Solusi:**
1. Pastikan `composer.lock` ada di repo
2. Check PHP version di `composer.json`:
   ```json
   "require": {
       "php": "^8.4"
   }
   ```
3. Re-deploy

---

### Problem 2: 500 Error setelah Deploy

**Checklist:**
- [ ] `APP_KEY` sudah di-set?
- [ ] Database migrations sudah running?
- [ ] Environment variables lengkap?

**Fix:**
1. Buka Shell di Render
2. Run:
   ```bash
   php artisan migrate --force
   php artisan config:clear
   php artisan cache:clear
   ```

---

### Problem 3: Database Connection Failed

**Error:** `SQLSTATE[08006] could not connect to server`

**Solusi:**
1. Cek database variables di Environment:
   - `DB_HOST` harus pakai internal hostname (bukan external)
   - Pakai: `${{payment-gateway-db.HOST}}` bukan IP public
2. Pastikan database dan web service di **region yang sama**
3. Re-deploy

---

### Problem 4: Service Sleep setelah 15 Menit

Ini behavior normal Render free tier.

**Workaround:**
1. Pakai external monitoring (UptimeRobot) ping setiap 5 menit
2. Atau upgrade ke paid plan ($7/bulan)

**Setup UptimeRobot:**
1. Daftar di https://uptimerobot.com (gratis)
2. Add New Monitor:
   - Type: HTTP(s)
   - URL: `https://payment-gateway-api.onrender.com/api/health`
   - Interval: 5 minutes
3. Service akan selalu aktif!

---

## 🔒 Security Checklist

Sebelum production:

- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] `APP_KEY` generated dengan secure
- [ ] Database password strong
- [ ] JWT secret secure
- [ ] Midtrans keys correct (Sandbox vs Production)
- [ ] CORS configured di `config/cors.php`
- [ ] Rate limiting enabled di routes
- [ ] Disable unused endpoints
- [ ] Setup monitoring & alerts

---

## 💡 Pro Tips

### 1. Setup Redis (untuk Queue & Cache)

Render punya Redis addon ($10/bulan) atau pakai eksternal:

**Pakai Upstash (Gratis):**
1. Daftar https://upstash.com
2. Buat Redis database (10k commands/day gratis)
3. Copy connection URL
4. Add ke Render environment:
   ```bash
   REDIS_URL=rediss://default:xxxxx@xxxxx.upstash.io:6379
   CACHE_DRIVER=redis
   QUEUE_CONNECTION=redis
   SESSION_DRIVER=redis
   ```

### 2. Custom Domain

1. Di web service → **Settings** → **Custom Domain**
2. Add domain: `api.yourdomain.com`
3. Update DNS CNAME:
   ```
   api.yourdomain.com  CNAME  payment-gateway-api.onrender.com
   ```
4. Render auto-provision SSL (gratis)

### 3. Auto-Deploy dari GitLab (via GitHub Mirror)

Setup auto-sync GitLab → GitHub:

1. Di GitLab: **Settings** → **Repository** → **Mirroring repositories**
2. **Git repository URL:** `https://github.com/username/payment-gateway.git`
3. **Mirror direction:** Push
4. **Authentication:** Personal access token
5. **Mirror only protected branches:** ✅ main
6. **Save**

Setiap push ke GitLab main → auto-sync ke GitHub → Render auto-deploy!

### 4. Setup Health Check Endpoint

Render bisa monitor health. Buat route di Laravel:

```php
// routes/api.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now(),
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected'
    ]);
});
```

Di Render → **Settings** → **Health Check Path:** `/api/health`

### 5. Background Workers (untuk Queue)

Untuk jalankan queue workers:

1. Buat **Background Worker** service baru
2. **Build Command:** (sama dengan web service)
3. **Start Command:**
   ```bash
   php artisan queue:work --tries=3 --timeout=90
   ```
4. Link ke database yang sama
5. Environment variables sama dengan web service

---

## 💰 Cost Estimation

**Free Tier (Recommended untuk Portfolio):**
- Web Service: 750 jam/bulan (gratis)
- PostgreSQL: 1GB storage (gratis)
- Total: **$0/bulan** ✅

**Jika butuh always-on + Redis:**
- Web Service: $7/bulan
- PostgreSQL: Free tier
- Redis: $10/bulan (atau Upstash gratis)
- Total: **$7-17/bulan**

**Untuk portfolio/learning:** Pakai free tier sudah cukup!

---

## 📚 Resources

- [Render Docs](https://docs.render.com/)
- [Laravel on Render Guide](https://docs.render.com/deploy-laravel)
- [Render Community](https://community.render.com/)
- [Render Status](https://status.render.com/)

---

## 🚀 Next: Setup CI/CD

Sekarang aplikasi sudah live! 

Untuk auto-deploy dari GitLab CI/CD, lihat: [CICD_DEPLOYMENT_TUTORIAL.md](./CICD_DEPLOYMENT_TUTORIAL.md)

---

**Happy Deploying! 🎉**

Selamat! Aplikasi Laravel kamu sudah live di production dengan gratis!
