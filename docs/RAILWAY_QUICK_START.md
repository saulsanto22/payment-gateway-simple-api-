# 🚂 Railway Quick Start Guide (untuk GitLab Users)

Panduan super cepat deploy Laravel ke Railway menggunakan CLI.

**Estimasi Waktu:** 15 menit ⚡

---

## ✅ Prerequisites

- [ ] Node.js terinstall (untuk Railway CLI)
- [ ] Railway account (gratis di railway.app)
- [ ] Project Laravel sudah jalan di local

---

## 🚀 Step-by-Step Deployment

### Step 1: Install Railway CLI

```bash
# Install via npm
npm install -g @railway/cli

# Verifikasi
railway --version
```

### Step 2: Login ke Railway

```bash
# Login (akan buka browser)
railway login

# Login success? Lanjut!
```

### Step 3: Initialize Project

```bash
# Masuk ke folder project
cd c:\laragon\www\payment-gateway-simple-api-

# Initialize Railway project
railway init

# Pilih: "Create new project"
# Masukkan nama: payment-gateway-laravel
# Pilih environment: production
```

### Step 4: Setup PostgreSQL Database

```bash
# Tambah PostgreSQL addon
railway add --database postgres

# Railway otomatis setup & inject environment variables:
# - DATABASE_URL
# - POSTGRES_USER, POSTGRES_PASSWORD, dll
```

### Step 5: Setup Environment Variables

```bash
# Set variables via CLI
railway variables set APP_NAME="Payment Gateway"
railway variables set APP_ENV=production
railway variables set APP_DEBUG=false

# Generate APP_KEY di local
php artisan key:generate --show
# Copy hasilnya, misalnya: base64:xxxxxxxxxxxxx

# Set APP_KEY
railway variables set APP_KEY="base64:xxxxxxxxxxxxx"

# Set Midtrans credentials
railway variables set MIDTRANS_SERVER_KEY="SB-Mid-server-xxxxx"
railway variables set MIDTRANS_CLIENT_KEY="SB-Mid-client-xxxxx"
railway variables set MIDTRANS_IS_PRODUCTION=false

# Queue & Cache
railway variables set QUEUE_CONNECTION=database
railway variables set CACHE_DRIVER=file
railway variables set SESSION_DRIVER=file
```

**Tips:** Untuk development awal, pakai driver sederhana (database, file). Nanti bisa upgrade ke Redis.

### Step 6: Deploy!

```bash
# Deploy aplikasi
railway up

# Railway akan:
# ✅ Upload code
# ✅ Detect Laravel
# ✅ Install dependencies
# ✅ Build aplikasi
# ✅ Start server

# Tunggu ~2-5 menit...
```

### Step 7: Run Migrations

```bash
# Setelah deploy success, run migrations
railway run php artisan migrate --force

# Seed database (opsional)
railway run php artisan db:seed --force
```

### Step 8: Get Domain URL

```bash
# Generate domain public
railway domain

# Akan dapat URL seperti: payment-gateway-production.up.railway.app

# Atau bisa custom domain di dashboard
```

### Step 9: Test API

```bash
# Test endpoint
curl https://your-app.railway.app/api/documentation

# Atau buka di browser
```

---

## 🎉 Done! API Sudah Live!

Aplikasi kamu sekarang:
- ✅ Live dengan HTTPS otomatis
- ✅ Database PostgreSQL running
- ✅ Environment variables configured
- ✅ Accessible dari mana saja

---

## 🔄 Update Code (Re-deploy)

Setiap kali update code:

```bash
# Option 1: Deploy via CLI
git add .
git commit -m "Update feature"
railway up

# Option 2: Push ke GitLab + CI/CD (jika sudah setup)
git push origin main
# GitLab pipeline otomatis deploy
```

---

## 🛠️ Useful Railway Commands

```bash
# Lihat logs real-time
railway logs

# Lihat status project
railway status

# Lihat environment variables
railway variables

# Run command di production
railway run php artisan cache:clear

# Open Railway dashboard
railway open

# Link existing project
railway link
```

---

## 🐛 Troubleshooting

### Error: "Command not found: railway"

```bash
# Windows: Restart terminal
# Linux/Mac: 
source ~/.bashrc  # atau ~/.zshrc
```

### Error: "Failed to deploy"

```bash
# Cek logs
railway logs

# Common issues:
# - APP_KEY belum di-set
# - Database migration failed
# - PHP version mismatch
```

### Error: 500 di production

```bash
# Clear cache
railway run php artisan config:clear
railway run php artisan cache:clear
railway run php artisan route:clear

# Re-run migrations
railway run php artisan migrate:fresh --force --seed
```

### Change PHP version

Railway auto-detect dari `composer.json`. Edit:

```json
{
  "require": {
    "php": "^8.4"
  }
}
```

Then re-deploy:
```bash
railway up
```

---

## 🔐 Security Checklist

Sebelum public:

- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] Strong `APP_KEY` (generated)
- [ ] Database password secure
- [ ] Midtrans credentials correct
- [ ] Rate limiting enabled
- [ ] CORS configured
- [ ] `.env` never committed to git

---

## 💡 Pro Tips

1. **Use Railway Redis** untuk cache & queue:
   ```bash
   railway add redis
   railway variables set CACHE_DRIVER=redis
   railway variables set QUEUE_CONNECTION=redis
   ```

2. **Setup Custom Domain:**
   - Railway dashboard → Settings → Domains
   - Add custom domain
   - Update DNS records

3. **Monitor Usage:**
   - Railway dashboard → Metrics
   - Track requests, memory, CPU

4. **Database Backups:**
   - Railway auto-backup setiap 24 jam (free tier)
   - Manual backup: PostgreSQL dumps via Railway dashboard

5. **Environment per Branch:**
   ```bash
   # Create staging environment
   railway environment create staging
   railway up --environment staging
   ```

---

## 📊 Cost Estimation

**Free Tier ($5 credit/bulan):**
- Cukup untuk portfolio
- ~500 hours/month runtime
- 1 GB RAM
- PostgreSQL included

**Kalau exceed:**
- $0.000231/GB-hour (sangat murah)
- Bisa top-up $10 → cukup 2-3 bulan

**Recommendation:** Free tier sudah lebih dari cukup untuk portfolio project!

---

## 🔗 Next Steps

Setelah deploy sukses:

1. Update webhook Midtrans dengan Railway URL
2. Test pembayaran dengan kartu test
3. Update README.md dengan live demo URL
4. Add Railway badge ke README
5. Setup GitLab CI/CD untuk auto-deploy

---

**Railway Dashboard:** https://railway.app/dashboard

**Railway Docs:** https://docs.railway.app

**Support:** Discord - https://discord.gg/railway

---

**Happy Deploying! 🚀**
