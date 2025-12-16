# 🐳 Docker Setup Guide

## 📚 Analogi Docker untuk Pemula

**Docker itu seperti shipping container di pelabuhan:**
- **Container** = Box barang (app + dependencies lengkap)
- **Image** = Blueprint untuk bikin container
- **Docker Compose** = Crane yang ngatur banyak container sekaligus
- **Volume** = Gudang penyimpanan yang bisa dipindah-pindah

**Kenapa pakai Docker?**
1. **"Works on my machine" = Works everywhere** - Jaminan jalan di semua komputer
2. **Isolasi** - Setiap app punya environment sendiri
3. **Scalability** - Tinggal tambah container kalau perlu
4. **Easy deployment** - Satu command deploy ke server

---

## 🏗️ Architecture Overview

```
┌─────────────────────────────────────────────────┐
│          Payment Gateway Container               │
├─────────────────────────────────────────────────┤
│  ┌────────┐  ┌─────────┐  ┌──────────────┐    │
│  │ Nginx  │→│ PHP-FPM │→│ Laravel App  │    │
│  └────────┘  └─────────┘  └──────────────┘    │
│                                                  │
│  ┌──────────────────────────────────────┐      │
│  │   Supervisor (Process Manager)        │      │
│  │  - Nginx                              │      │
│  │  - PHP-FPM                            │      │
│  │  - Queue Worker (webhooks - 2 proc)  │      │
│  │  - Queue Worker (default - 1 proc)   │      │
│  │  - Scheduler                          │      │
│  └──────────────────────────────────────┘      │
└─────────────────────────────────────────────────┘
           ↓               ↓               ↓
    ┌──────────┐    ┌──────────┐    ┌──────────┐
    │PostgreSQL│    │  Redis   │    │ MailHog  │
    │ Database │    │Cache+Queue│   │Email Test│
    └──────────┘    └──────────┘    └──────────┘
```

**Penjelasan:**
- **Nginx** = Receptionist (terima HTTP request)
- **PHP-FPM** = Chef (process PHP code)
- **Supervisor** = Manager (pastikan semua jalan terus)
- **Queue Worker** = Staff delivery (process jobs async)
- **Scheduler** = Alarm clock (run cronjob otomatis)

---

## 🚀 Quick Start

### Development Mode

**1️⃣ Pertama kali setup:**
```bash
# Double-click file ini
docker-dev-start.bat
```

Script ini akan otomatis:
- ✅ Start semua container (app, db, redis, mailhog)
- ✅ Run migrations
- ✅ Seed database (kalau mau)
- ✅ Generate app key
- ✅ Clear cache

**2️⃣ Access aplikasi:**
- **API**: http://localhost:8000
- **Swagger Docs**: http://localhost:8000/api/documentation
- **MailHog** (Email testing): http://localhost:8025
- **Database**: localhost:5432 (user: postgres, pass: secret)
- **Redis**: localhost:6379

**3️⃣ Stop development:**
```bash
docker-dev-stop.bat
```

---

## 📦 Docker Commands Cheat Sheet

### View Logs
```bash
# Real-time logs (all containers)
docker-logs.bat

# Or manual:
docker-compose -f docker-compose.dev.yml logs -f

# Specific container
docker-compose -f docker-compose.dev.yml logs -f app
```

### Run Artisan Commands
```bash
# Via helper script
docker-artisan.bat
# Pilih mode (dev/prod), lalu masukkan command
# Example: migrate, db:seed, queue:work

# Or manual:
docker-compose -f docker-compose.dev.yml exec app php artisan migrate
docker-compose -f docker-compose.dev.yml exec app php artisan db:seed
docker-compose -f docker-compose.dev.yml exec app php artisan tinker
```

### Access Container Shell
```bash
# Bash into Laravel container
docker-compose -f docker-compose.dev.yml exec app sh

# Bash into database
docker-compose -f docker-compose.dev.yml exec db psql -U postgres -d payment_gateway_dev
```

### Container Management
```bash
# View running containers
docker-compose -f docker-compose.dev.yml ps

# Restart specific service
docker-compose -f docker-compose.dev.yml restart app

# Rebuild image (after Dockerfile change)
docker-compose -f docker-compose.dev.yml build --no-cache app

# Remove everything (including volumes)
docker-compose -f docker-compose.dev.yml down -v
```

---

## 🏭 Production Deployment

### 1️⃣ Prepare Production Environment

**Create `.env.production`:**
```env
APP_NAME="Payment Gateway"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...  # Generate with: php artisan key:generate

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=payment_gateway
DB_USERNAME=postgres
DB_PASSWORD=YOUR_STRONG_PASSWORD_HERE

REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=YOUR_REDIS_PASSWORD_HERE

QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-server
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password

MIDTRANS_SERVER_KEY=your-server-key
MIDTRANS_CLIENT_KEY=your-client-key
MIDTRANS_IS_PRODUCTION=true
```

### 2️⃣ Deploy to Production

```bash
# Double-click file ini
docker-prod-start.bat
```

Script akan:
- ✅ Build optimized production image (multi-stage)
- ✅ Start containers
- ✅ Run migrations
- ✅ Cache config, routes, views
- ✅ Start supervisor (nginx + php-fpm + queue + scheduler)

**Access:**
- **API**: http://localhost:8080
- **Database**: localhost:5433
- **Redis**: localhost:6380

---

## 🔍 Troubleshooting

### Problem: Container tidak start

**Check logs:**
```bash
docker-compose -f docker-compose.dev.yml logs app
```

**Common issues:**
- ❌ Port sudah dipakai → Ganti port di `docker-compose.yml`
- ❌ .env tidak ada → Copy dari `.env.example`
- ❌ Database connection failed → Tunggu beberapa detik (healthcheck)

### Problem: Permission denied di storage/

**Fix:**
```bash
docker-compose -f docker-compose.dev.yml exec app chmod -R 775 storage bootstrap/cache
docker-compose -f docker-compose.dev.yml exec app chown -R www-data:www-data storage bootstrap/cache
```

### Problem: Migration failed

**Reset database:**
```bash
# Development (DANGER: deletes all data!)
docker-compose -f docker-compose.dev.yml down -v
docker-compose -f docker-compose.dev.yml up -d
docker-compose -f docker-compose.dev.yml exec app php artisan migrate:fresh --seed
```

### Problem: Queue tidak jalan

**Check queue workers:**
```bash
# Lihat logs supervisor
docker-compose exec app cat /var/log/supervisor/queue-webhooks.log
docker-compose exec app cat /var/log/supervisor/queue-default.log

# Restart queue workers
docker-compose exec app supervisorctl restart laravel-queue-webhooks:*
docker-compose exec app supervisorctl restart laravel-queue-default:*
```

---

## 🎯 Best Practices

### Development
1. ✅ Gunakan `docker-compose.dev.yml` untuk development
2. ✅ Source code di-mount → Perubahan langsung apply (hot reload)
3. ✅ Queue pakai `database` driver → Lebih gampang debug
4. ✅ MailHog untuk test email → Tidak kirim ke real email

### Production
1. ✅ Multi-stage build → Image lebih kecil (hanya runtime, no dev tools)
2. ✅ Supervisor → Manage multiple processes (nginx, php-fpm, queue, scheduler)
3. ✅ Redis untuk queue → Lebih fast & reliable dari database
4. ✅ Health checks → Docker auto-restart kalau container unhealthy
5. ✅ Cache config/routes/views → Performance boost

### Security
1. ✅ `.env` di `.dockerignore` → Jangan masuk ke image
2. ✅ Strong password untuk database & redis
3. ✅ Non-root user (www-data) untuk run app
4. ✅ Expose minimal ports

---

## 📊 Monitoring

### Check Health Status
```bash
# Check if containers are healthy
docker-compose ps

# Health check endpoint
curl http://localhost:8000/health
```

### Resource Usage
```bash
# Monitor CPU, Memory, Network
docker stats

# Specific container
docker stats payment-gateway-app
```

### Database Backup
```bash
# Backup PostgreSQL
docker-compose exec db pg_dump -U postgres payment_gateway > backup.sql

# Restore
docker-compose exec -T db psql -U postgres payment_gateway < backup.sql
```

---

## 🎓 Learning Resources

**Mau belajar lebih dalam?**
1. **Docker Basics**: https://docs.docker.com/get-started/
2. **Docker Compose**: https://docs.docker.com/compose/
3. **Multi-stage builds**: https://docs.docker.com/build/building/multi-stage/
4. **Supervisor**: http://supervisord.org/

**Analogi terakhir:**
- **Docker** = Mainan LEGO (building blocks)
- **Image** = Instruksi LEGO (blueprint)
- **Container** = Hasil rakitan LEGO (running app)
- **Volume** = Kotak penyimpanan LEGO (persistent data)
- **Network** = Meja main bareng (containers communicate)

---

## 🆘 Support

Kalau ada issue atau pertanyaan:
1. Check logs dulu: `docker-logs.bat`
2. Check container status: `docker-compose ps`
3. Google error message
4. Tanya di GitHub Issues

**Happy Dockerizing! 🐳**
