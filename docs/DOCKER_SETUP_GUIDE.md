# 🐳 Docker Setup Guide - From Zero to Running

**Tujuan:** Paham **SETIAP LANGKAH** setup Docker untuk Laravel project, bisa **repeat sendiri** untuk project lain.

---

## 📋 Table of Contents

1. [Prerequisites Check](#1-prerequisites-check)
2. [Understanding Docker Components](#2-understanding-docker-components)
3. [Decision Making: Architecture](#3-decision-making-architecture)
4. [Creating Dockerfile](#4-creating-dockerfile)
5. [Creating docker-compose.yml](#5-creating-docker-composeyml)
6. [Environment Configuration](#6-environment-configuration)
7. [Building & Running](#7-building--running)
8. [Database Setup](#8-database-setup)
9. [Testing & Verification](#9-testing--verification)
10. [Common Issues & Solutions](#10-common-issues--solutions)
11. [Daily Workflow](#11-daily-workflow)

---

## 1. Prerequisites Check

**Sebelum mulai, pastikan:**

```bash
# Check Docker installed
docker --version
# Expected: Docker version 20.x atau lebih baru

# Check Docker Compose
docker-compose --version
# Expected: Docker Compose version 2.x

# Check Docker running
docker ps
# Should show table header (no error)
```

**✅ Checklist:**
- [ ] Docker Desktop installed
- [ ] Docker Desktop running (icon di taskbar)
- [ ] Docker commands work di terminal

**❌ Jika gagal:**
- Download Docker Desktop: https://www.docker.com/products/docker-desktop
- Restart Docker Desktop
- Restart PC jika perlu

---

## 2. Understanding Docker Components

**Sebelum bikin file, pahami dulu konsep:**

### **2.1. Docker Image vs Container**

```
Image (Blueprint)          Container (Running Instance)
┌─────────────────┐       ┌─────────────────┐
│  PHP 8.4        │  →    │  Running PHP    │
│  Laravel Code   │       │  Process Active │
│  Dependencies   │       │  Port 8000      │
└─────────────────┘       └─────────────────┘
   (Static file)            (Running process)
```

**Analogi:**
- **Image** = Resep masakan (blueprint)
- **Container** = Masakan yang sudah jadi (running)

### **2.2. Components Yang Dibutuhkan**

```
┌─────────────────────────────────────────────┐
│  Docker Project Structure                   │
├─────────────────────────────────────────────┤
│  1. Dockerfile                              │
│     └─> Blueprint untuk build image         │
│                                             │
│  2. docker-compose.yml                      │
│     └─> Orchestrator multi-container        │
│                                             │
│  3. .dockerignore                           │
│     └─> File yang tidak di-copy ke image   │
│                                             │
│  4. .env (modified)                         │
│     └─> Environment variables untuk Docker  │
└─────────────────────────────────────────────┘
```

**Fungsi masing-masing:**
- **Dockerfile:** "Bagaimana cara bikin image Laravel?"
- **docker-compose.yml:** "Gimana cara jalanin Laravel + DB + Redis + MailHog bersamaan?"
- **.dockerignore:** "File apa yang tidak perlu di-copy (vendor, node_modules, .git)"
- **.env:** "Config connection ke services Docker"

---

## 3. Decision Making: Architecture

**Pertanyaan yang harus dijawab SEBELUM mulai coding:**

### **Q1: Berapa container yang dibutuhkan?**

**Analisis kebutuhan Laravel project:**
```
✅ Laravel App       → Container 1 (app)
✅ PostgreSQL        → Container 2 (db)
✅ Redis             → Container 3 (redis)
✅ Email Testing     → Container 4 (mailhog)
```

**Kenapa tidak dijadikan 1 container?**
❌ **Anti-pattern:** 1 container untuk semua
- Sulit di-scale (tidak bisa scale DB terpisah dari app)
- Rebuild app = rebuild DB (slow!)
- Tidak flexible

✅ **Best Practice:** 1 container = 1 concern
- Scale independent
- Replace service mudah (ganti PostgreSQL → MySQL cuma ubah 1 container)
- Fast rebuild (rebuild app tidak affect DB)

---

### **Q2: Base image apa untuk Laravel?**

**Pilihan:**
1. `php:8.4-apache` → PHP + Apache web server
2. `php:8.4-fpm` → PHP FastCGI Process Manager (production-ready)
3. `php:8.4-fpm-alpine` → FPM + Alpine Linux (smaller)
4. `php:8.4-cli` → PHP CLI only (no web server)

**Decision: `php:8.4-fpm-alpine`**

**Alasan:**
- ✅ **Alpine Linux:** Image size 50MB vs 400MB (Debian)
- ✅ **FPM:** Production-ready, better performance
- ✅ **Fast download:** Smaller = faster build
- ❌ **Trade-off:** Alpine uses `apk` instead of `apt-get` (minor syntax difference)

---

### **Q3: Development vs Production setup?**

**Differences:**

| Aspect | Development | Production |
|--------|-------------|------------|
| **Code mounting** | ✅ Mount (hot reload) | ❌ Copy to image |
| **Debug mode** | ✅ ON | ❌ OFF |
| **Optimization** | ❌ None | ✅ Full |
| **Dev dependencies** | ✅ Include | ❌ Exclude |
| **Email** | MailHog (fake) | Real SMTP |

**Decision untuk project ini:**
- Bikin 2 setup terpisah:
  - `Dockerfile.dev` + `docker-compose.dev.yml` (Development)
  - `Dockerfile` + `docker-compose.yml` (Production - untuk nanti)

---

## 4. Creating Dockerfile

**File:** `Dockerfile.dev`

### **Step 4.1: Start with Base Image**

```dockerfile
FROM php:8.4-fpm-alpine
```

**Penjelasan:**
- `FROM` = Mulai dari image apa
- `php:8.4-fpm-alpine` = Official PHP image, versi 8.4, with FPM, based on Alpine Linux

**Why PHP 8.4?**
- Cek composer.lock: `symfony/clock` requires PHP >=8.4
- Harus match dengan local development PHP version
- Untuk check: `php -v` di local (Laragon)

---

### **Step 4.2: Install System Dependencies**

```dockerfile
RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    autoconf \
    g++ \
    make
```

**Penjelasan:**
- `apk add` = Package manager di Alpine (seperti `apt-get` di Ubuntu)
- `--no-cache` = Tidak simpan cache (menghemat space)

**Detail packages:**
- `postgresql-dev` → Untuk compile PHP PostgreSQL extension
- `libpng-dev`, `libjpeg-turbo-dev`, `freetype-dev` → Untuk GD extension (image processing)
- `oniguruma-dev` → Untuk mbstring extension (multibyte string)
- `libzip-dev`, `zip`, `unzip` → Untuk zip extension
- `git` → Untuk composer install dari git repos
- `curl` → Download tool
- `autoconf`, `g++`, `make` → Compiler tools (untuk compile PECL extensions seperti Redis)

**Why need these?**
Laravel extensions requirement:
```
PHP Extensions:
✅ pdo_pgsql     → Connect to PostgreSQL
✅ gd            → Image manipulation (thumbnails, etc)
✅ mbstring      → String functions
✅ zip           → Zip/unzip files
✅ redis         → Connect to Redis
```

---

### **Step 4.3: Install PHP Extensions**

```dockerfile
# Install PostgreSQL, GD, and other extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        gd \
        pcntl \
        bcmath \
        zip \
        exif \
        mbstring

# Install Redis via PECL
RUN pecl install redis && docker-php-ext-enable redis
```

**Penjelasan:**
- `docker-php-ext-configure` = Configure extension sebelum install
- `docker-php-ext-install` = Install PHP extension
- `-j$(nproc)` = Parallel compilation (speed up)
- `pecl install` = Install extension dari PECL repository

**Why Redis via PECL, others via docker-php-ext?**
- Redis tidak tersedia di `docker-php-ext-install`
- PECL = PHP Extension Community Library (extra extensions)

---

### **Step 4.4: Install Composer**

```dockerfile
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer
```

**Penjelasan:**
- `COPY --from=` = Multi-stage build (copy dari image lain)
- `composer:2.7` = Official Composer image
- Ambil binary Composer, copy ke image kita

**Why not `curl https://getcomposer.org/installer`?**
- Official image = trusted, tested
- Faster (no download + install script)
- Specific version (2.7)

---

### **Step 4.5: Set Working Directory & Copy Code**

```dockerfile
WORKDIR /var/www/html

COPY . /var/www/html
```

**Penjelasan:**
- `WORKDIR` = Set default directory untuk commands selanjutnya
- `COPY . /var/www/html` = Copy semua file dari host (Windows) ke image

**Warning:** `.dockerignore` harus exclude:
```
vendor/
node_modules/
.git/
.env
storage/logs/*
```

**Why?**
- `vendor/` → Will be installed via `composer install` (untuk Linux, bukan Windows)
- `node_modules/` → Node modules (if any)
- `.git/` → Git history (tidak perlu di image)
- `.env` → Sensitive data (use environment variables)

---

### **Step 4.6: Install Composer Dependencies**

```dockerfile
RUN composer install --prefer-dist --optimize-autoloader
```

**Penjelasan:**
- `composer install` = Install packages dari `composer.lock`
- `--prefer-dist` = Download distribution files (faster than `--prefer-source`)
- `--optimize-autoloader` = Generate optimized class map (faster autoload)

**Why di image, bukan mount dari host?**
- Windows vendor/ incompatible dengan Linux
- Compiled extensions berbeda (Windows vs Linux)

---

### **Step 4.7: Set Permissions**

```dockerfile
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache
```

**Penjelasan:**
- `chown -R www-data:www-data` = Set owner ke user `www-data` (PHP-FPM user)
- `chmod 755` = Read/write/execute for owner, read/execute for others

**Why?**
Laravel needs write permission to:
- `storage/` → Logs, cache, uploaded files
- `bootstrap/cache/` → Compiled config, routes, views

---

### **Step 4.8: Expose Port & Start Command**

```dockerfile
EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
```

**Penjelasan:**
- `EXPOSE 8000` = Dokumentasi (port ini akan digunakan)
- `CMD` = Default command ketika container start
- `--host=0.0.0.0` = Listen on all network interfaces (biar bisa diakses dari host)
- `--port=8000` = Port number

**Why not `php-fpm`?**
- Development: `artisan serve` = simple, auto-reload
- Production: `php-fpm` + nginx = better performance

---

### **Complete Dockerfile.dev:**

```dockerfile
# Dockerfile.dev (Development)
FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    autoconf \
    g++ \
    make

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        gd \
        pcntl \
        bcmath \
        zip \
        exif \
        mbstring

# Install Redis
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application code
COPY . /var/www/html

# Install dependencies
RUN composer install --prefer-dist --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Expose port
EXPOSE 8000

# Start Laravel development server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
```

---

## 5. Creating docker-compose.yml

**File:** `docker-compose.dev.yml`

### **Why docker-compose?**

**Without docker-compose:**
```bash
# Start PostgreSQL
docker run -d --name db -p 5432:5432 -e POSTGRES_PASSWORD=secret postgres:16-alpine

# Start Redis
docker run -d --name redis -p 6379:6379 redis:7-alpine

# Start Laravel
docker run -d --name app -p 8000:8000 --link db --link redis laravel-app

# 😱 COMPLEX! And containers can't find each other easily
```

**With docker-compose:**
```bash
docker-compose up -d  # ✅ Start all services!
```

---

### **Step 5.1: Version & Services**

```yaml
version: '3.8'

services:
```

**Penjelasan:**
- `version: '3.8'` = Docker Compose file format version
- `services:` = List of containers to run

---

### **Step 5.2: App Service (Laravel)**

```yaml
app:
  build:
    context: .
    dockerfile: Dockerfile.dev
  container_name: payment-gateway-app-dev
  restart: unless-stopped
  working_dir: /var/www/html
```

**Penjelasan:**
- `app:` = Service name (bisa dipanggil via `docker-compose exec app ...`)
- `build.context: .` = Build dari current directory
- `build.dockerfile: Dockerfile.dev` = Use Dockerfile.dev (bukan default Dockerfile)
- `container_name:` = Custom container name (optional, tapi helpful)
- `restart: unless-stopped` = Auto restart jika crash (kecuali manual stop)
- `working_dir:` = Default directory di dalam container

---

### **Step 5.3: Environment Variables**

```yaml
environment:
  - APP_ENV=local
  - APP_DEBUG=true
  - DB_HOST=db              # ← Service name!
  - DB_PORT=5432
  - DB_DATABASE=payment_gateway_dev
  - DB_USERNAME=postgres
  - DB_PASSWORD=secret
  - REDIS_HOST=redis        # ← Service name!
  - REDIS_PORT=6379
  - MAIL_HOST=mailhog       # ← Service name!
  - MAIL_PORT=1025
```

**Penjelasan:**
- Environment variables yang override `.env`
- **PENTING:** `DB_HOST=db`, `REDIS_HOST=redis`, `MAIL_HOST=mailhog`
  - Ini **service names** dari docker-compose
  - Docker network auto-resolve name → IP address

**Why not use .env directly?**
- `.env` di host might have `DB_HOST=127.0.0.1` (tidak work di Docker)
- Environment variables = override for Docker environment
- Flexibility: different env per service

---

### **Step 5.4: Volumes (Code Mounting)**

```yaml
volumes:
  - .:/var/www/html                # Mount all code
  - /var/www/html/vendor           # Exclude vendor
  - /var/www/html/node_modules     # Exclude node_modules
```

**Penjelasan:**
- `.:/var/www/html` = Mount current directory (Windows) → `/var/www/html` (container)
- **Hot Reload:** Edit file di Windows = instantly reflected di container!

**Why exclude vendor & node_modules?**
- `vendor/` dari Windows = Windows binary (PHP extensions compiled for Windows)
- `vendor/` dari container = Linux binary (compiled for Linux)
- **Conflict!** Must use Linux vendor inside container

**How it works:**
```
/var/www/html/              ← Mounted from Windows (hot reload)
/var/www/html/vendor/       ← Anonymous volume (from image, not Windows)
/var/www/html/node_modules/ ← Anonymous volume (from image, not Windows)
```

---

### **Step 5.5: Port Mapping**

```yaml
ports:
  - "8000:8000"
```

**Penjelasan:**
- `"HOST:CONTAINER"`
- `8000:8000` = Port 8000 di Windows → forward to port 8000 di container

**Test:**
- Access `http://localhost:8000` dari browser Windows
- Request masuk ke Docker → diteruskan ke container app:8000

---

### **Step 5.6: Networks & Dependencies**

```yaml
networks:
  - payment-gateway-network

depends_on:
  - db
  - redis
```

**Penjelasan:**
- `networks:` = Join network `payment-gateway-network`
- `depends_on:` = Start `db` dan `redis` dulu sebelum `app`

**Why networks?**
- Isolate project networks
- Auto DNS resolution (service name → IP)

**depends_on limitation:**
- Only ensures start order (NOT wait until DB ready)
- DB might still initializing when app starts
- Solution: App should retry DB connection

---

### **Step 5.7: Database Service**

```yaml
db:
  image: postgres:16-alpine
  container_name: payment-gateway-db-dev
  restart: unless-stopped
  
  environment:
    - POSTGRES_DB=payment_gateway_dev
    - POSTGRES_USER=postgres
    - POSTGRES_PASSWORD=secret
  
  volumes:
    - postgres-data-dev:/var/lib/postgresql/data
  
  ports:
    - "5432:5432"
  
  networks:
    - payment-gateway-network
```

**Penjelasan:**
- `image: postgres:16-alpine` = Use official PostgreSQL image (no build needed)
- Environment = database name, user, password
- **Volume:** `postgres-data-dev:/var/lib/postgresql/data`
  - Named volume (managed by Docker)
  - Data persists even after container deleted

**Why named volume?**
```
Container deleted → Data LOST ❌

Named volume:
Container deleted → Data SAFE ✅ (in Docker volume)
```

---

### **Step 5.8: Redis Service**

```yaml
redis:
  image: redis:7-alpine
  container_name: payment-gateway-redis-dev
  restart: unless-stopped
  
  ports:
    - "6379:6379"
  
  networks:
    - payment-gateway-network
```

**Penjelasan:**
- Simple! Redis doesn't need configuration
- No volume (cache data = OK to lose on restart)

---

### **Step 5.9: MailHog Service (Email Testing)**

```yaml
mailhog:
  image: mailhog/mailhog:latest
  container_name: payment-gateway-mailhog-dev
  restart: unless-stopped
  
  ports:
    - "1025:1025"   # SMTP server
    - "8025:8025"   # Web UI
  
  networks:
    - payment-gateway-network
```

**Penjelasan:**
- MailHog = Fake SMTP server (catch emails)
- Port 1025 = SMTP (Laravel kirim email ke sini)
- Port 8025 = Web UI (http://localhost:8025 untuk lihat emails)

**Why MailHog?**
- Development: tidak perlu real Gmail SMTP
- See emails instantly di browser
- No risk of accidentally sending email to real users

---

### **Step 5.10: Volumes & Networks Declaration**

```yaml
volumes:
  postgres-data-dev:
    driver: local

networks:
  payment-gateway-network:
    driver: bridge
```

**Penjelasan:**
- **volumes:** = Declare named volumes
  - `driver: local` = Store on local disk
  
- **networks:** = Declare custom networks
  - `driver: bridge` = Default Docker network type (containers can talk to each other)

---

## 6. Environment Configuration

**File:** `.env`

### **Step 6.1: Backup Original .env**

```bash
cp .env .env.backup
```

**Why?**
- Keep original config for local Laragon development
- Can switch back anytime

---

### **Step 6.2: Modify .env for Docker**

**Changes needed:**

```env
# Database
DB_CONNECTION=pgsql
DB_HOST=db                    # ← Changed from 127.0.0.1
DB_PORT=5432
DB_DATABASE=payment_gateway_dev
DB_USERNAME=postgres
DB_PASSWORD=secret

# Redis
REDIS_HOST=redis              # ← Changed from 127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail (MailHog)
MAIL_MAILER=smtp
MAIL_HOST=mailhog             # ← Changed from smtp.gmail.com
MAIL_PORT=1025                # ← Changed from 587
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
```

**Why these changes?**
- `DB_HOST=db` → Service name in docker-compose
- `REDIS_HOST=redis` → Service name
- `MAIL_HOST=mailhog` → Service name
- `127.0.0.1` doesn't work (that's container itself, not other containers)

---

## 7. Building & Running

### **Step 7.1: Build Image**

```bash
docker-compose -f docker-compose.dev.yml build --no-cache
```

**Penjelasan:**
- `docker-compose` = Docker Compose command
- `-f docker-compose.dev.yml` = Use specific file (default is `docker-compose.yml`)
- `build` = Build images for services that have `build:` directive
- `--no-cache` = Don't use cache (clean build)

**What happens:**
1. Read `docker-compose.dev.yml`
2. Find services with `build:` (app service)
3. Build image from `Dockerfile.dev`
4. Tag image as `payment-gateway-simple-api--app`

**Time:**
- First build: ~30 minutes (download base image, install packages)
- Subsequent builds: ~2-5 minutes (use cache)

**Monitor:**
```
[+] Building 1956.1s (19/19) FINISHED
 => [stage-0 1/8] FROM docker.io/library/php:8.4-fpm-alpine  428.9s
 => [stage-0 2/8] RUN apk add --no-cache postgresql-dev...   867.5s
 => [stage-0 3/8] RUN pecl install redis...                  112.2s
 => [stage-0 7/8] RUN composer install...                     92.4s
```

---

### **Step 7.2: Start Containers**

```bash
docker-compose -f docker-compose.dev.yml up -d
```

**Penjelasan:**
- `up` = Create and start containers
- `-d` = Detached mode (run in background)

**What happens:**
1. Create Docker network: `payment-gateway-network`
2. Create volume: `postgres-data-dev`
3. Pull images (postgres, redis, mailhog) if not exist
4. Start containers in order:
   - Start `db` (PostgreSQL)
   - Start `redis`
   - Start `mailhog`
   - Start `app` (depends on db & redis)

**Time:** ~1 minute

**Verify:**
```bash
docker-compose -f docker-compose.dev.yml ps
```

Expected output:
```
NAME                          STATUS              PORTS
payment-gateway-app-dev       Up 47 seconds       0.0.0.0:8000->8000/tcp
payment-gateway-db-dev        Up About a minute   0.0.0.0:5432->5432/tcp
payment-gateway-redis-dev     Up About a minute   0.0.0.0:6379->6379/tcp
payment-gateway-mailhog-dev   Up About a minute   0.0.0.0:1025->1025/tcp, 0.0.0.0:8025->8025/tcp
```

---

## 8. Database Setup

### **Step 8.1: Run Migrations**

```bash
docker-compose -f docker-compose.dev.yml exec app php artisan migrate
```

**Penjelasan:**
- `exec app` = Execute command inside `app` container
- `php artisan migrate` = Run Laravel migrations

**What happens:**
1. Connect to `db:5432` (PostgreSQL container)
2. Create `migrations` table
3. Run all migration files
4. Create tables: users, products, orders, etc.

**Time:** ~10 seconds

---

### **Step 8.2: (Optional) Seed Data**

```bash
docker-compose -f docker-compose.dev.yml exec app php artisan db:seed
```

**Penjelasan:**
- Populate database with test data
- Useful for development

---

## 9. Testing & Verification

### **Step 9.1: Check All Containers Running**

```bash
docker ps
```

Expected: 4 containers with status "Up"

---

### **Step 9.2: Check Logs**

```bash
# All services
docker-compose -f docker-compose.dev.yml logs

# Specific service
docker-compose -f docker-compose.dev.yml logs app

# Follow logs (real-time)
docker-compose -f docker-compose.dev.yml logs -f app
```

**Look for errors:**
- "Connection refused" → Service not ready
- "Access denied" → Wrong credentials
- "Port already in use" → Conflict with local services

---

### **Step 9.3: Test API**

**Method 1: Browser**
```
http://localhost:8000/api/documentation
```

**Method 2: curl**
```bash
curl http://localhost:8000/api/documentation
```

**Expected:** Swagger UI page

---

### **Step 9.4: Test Database Connection**

```bash
docker-compose -f docker-compose.dev.yml exec app php artisan tinker
```

Inside tinker:
```php
DB::connection()->getPdo();
// Should return PDO object (connection success!)

\App\Models\User::count();
// Should return number of users
```

---

### **Step 9.5: Test Redis Connection**

```bash
docker-compose -f docker-compose.dev.yml exec app php artisan tinker
```

Inside tinker:
```php
Cache::put('test', 'Hello Docker!', 60);
Cache::get('test');
// Should return: "Hello Docker!"
```

---

### **Step 9.6: Test Email (MailHog)**

**Send test email:**
```bash
docker-compose -f docker-compose.dev.yml exec app php artisan tinker
```

Inside tinker:
```php
Mail::raw('Test email from Docker!', function($msg) {
    $msg->to('test@example.com')->subject('Docker Test');
});
```

**Check MailHog UI:**
```
http://localhost:8025
```

You should see the email!

---

## 10. Common Issues & Solutions

### **Issue 1: "Port 8000 already in use"**

**Cause:** Local Laravel server (Laragon) still running

**Solution:**
```bash
# Stop Laragon Apache/Nginx
# Or change port in docker-compose.dev.yml:
ports:
  - "8001:8000"  # Use port 8001 instead
```

---

### **Issue 2: "composer.lock requires PHP 8.4 but container has 8.3"**

**Cause:** PHP version mismatch

**Solution:**
```dockerfile
# In Dockerfile.dev, change:
FROM php:8.4-fpm-alpine  # Match with composer.lock requirement
```

Check composer.lock:
```bash
grep "php" composer.lock
```

---

### **Issue 3: "Cannot find autoconf"**

**Cause:** Missing build dependencies for PECL extensions

**Solution:**
```dockerfile
# Add to Dockerfile.dev RUN apk add:
autoconf \
g++ \
make
```

---

### **Issue 4: Containers keep restarting**

**Check logs:**
```bash
docker-compose -f docker-compose.dev.yml logs app
```

**Common causes:**
- Database connection failed (check DB_HOST, DB_PASSWORD)
- Missing .env file
- Permission issues

---

### **Issue 5: "Connection refused" to database**

**Cause:** App started before DB ready

**Solution:**
```bash
# Wait 10 seconds, then:
docker-compose -f docker-compose.dev.yml restart app
```

**Better solution:** Add healthcheck in docker-compose.yml (advanced)

---

### **Issue 6: Hot reload not working**

**Cause:** Volume mount issue

**Check:**
```bash
# Inside container:
docker-compose -f docker-compose.dev.yml exec app ls -la

# Should see your files
```

**Solution:** Restart Docker Desktop, then:
```bash
docker-compose -f docker-compose.dev.yml down
docker-compose -f docker-compose.dev.yml up -d
```

---

## 11. Daily Workflow

### **Starting work:**

```bash
# Start all containers
docker-compose -f docker-compose.dev.yml up -d

# Check status
docker-compose -f docker-compose.dev.yml ps

# View logs (optional)
docker-compose -f docker-compose.dev.yml logs -f app
```

---

### **During development:**

**Edit code:**
- Edit files di Windows (VS Code)
- Changes automatically reflected di container (hot reload)
- No need to restart container!

**Run artisan commands:**
```bash
docker-compose -f docker-compose.dev.yml exec app php artisan [command]

# Examples:
docker-compose -f docker-compose.dev.yml exec app php artisan route:list
docker-compose -f docker-compose.dev.yml exec app php artisan make:controller ProductController
docker-compose -f docker-compose.dev.yml exec app php artisan test
```

**Run composer:**
```bash
docker-compose -f docker-compose.dev.yml exec app composer install
docker-compose -f docker-compose.dev.yml exec app composer require vendor/package
```

---

### **Stopping work:**

```bash
# Stop all containers (keep data)
docker-compose -f docker-compose.dev.yml stop

# Stop and remove containers (keep data)
docker-compose -f docker-compose.dev.yml down

# Stop and remove everything (including volumes - DELETE DATA!)
docker-compose -f docker-compose.dev.yml down -v
```

---

### **Next day:**

```bash
# Start containers (reuse existing)
docker-compose -f docker-compose.dev.yml up -d
```

Data tetap ada! (karena volume)

---

## 12. Checklist: Setup Docker Sendiri

**Untuk project baru, follow checklist ini:**

### **Phase 1: Planning**
- [ ] Tentukan services yang dibutuhkan (app, db, cache, etc)
- [ ] Pilih base image untuk app
- [ ] Tentukan PHP version requirement (check composer.lock)
- [ ] List PHP extensions yang dibutuhkan

### **Phase 2: Create Files**
- [ ] Create `Dockerfile` atau `Dockerfile.dev`
- [ ] Create `docker-compose.yml` atau `docker-compose.dev.yml`
- [ ] Create `.dockerignore` (exclude vendor, node_modules, .git, .env)
- [ ] Backup `.env` → `.env.backup`
- [ ] Modify `.env` untuk Docker (DB_HOST, REDIS_HOST, etc)

### **Phase 3: Build & Test**
- [ ] Build image: `docker-compose build`
- [ ] Start containers: `docker-compose up -d`
- [ ] Check logs: `docker-compose logs`
- [ ] Verify all containers running: `docker ps`

### **Phase 4: Setup Application**
- [ ] Run migrations: `docker-compose exec app php artisan migrate`
- [ ] (Optional) Seed data: `docker-compose exec app php artisan db:seed`
- [ ] Test API: `curl http://localhost:PORT`
- [ ] Test database connection: `docker-compose exec app php artisan tinker`

### **Phase 5: Verify**
- [ ] Hot reload works (edit code, refresh browser)
- [ ] Database persists (stop/start containers, data still there)
- [ ] Logs accessible
- [ ] Can run artisan commands

---

## 13. Troubleshooting Workflow

**When something goes wrong:**

### **1. Check container status:**
```bash
docker ps -a
```
- Status "Up" = OK
- Status "Exited" = Crashed

### **2. Check logs:**
```bash
docker-compose logs [service-name]
```

### **3. Inspect container:**
```bash
# Enter container shell
docker-compose exec app sh

# Inside container:
ls -la                    # Check files
php -v                    # Check PHP version
php artisan config:cache  # Clear config cache
env                       # Check environment variables
```

### **4. Rebuild if needed:**
```bash
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

### **5. Nuclear option (reset everything):**
```bash
# WARNING: This deletes ALL data!
docker-compose down -v
docker system prune -a
docker-compose build --no-cache
docker-compose up -d
```

---

## 14. Next Steps: Production Setup

**Differences for production:**

1. **No code mounting** (copy code to image)
2. **Optimize autoloader** (composer install --no-dev --optimize-autoloader)
3. **Cache everything** (config:cache, route:cache, view:cache)
4. **Use php-fpm + nginx** (instead of artisan serve)
5. **Environment variables** from secrets (not .env)
6. **Multi-stage build** (smaller image size)

**We'll cover this after CI/CD setup!**

---

## 15. Summary: Key Concepts

**Docker Image:**
- Blueprint/template
- Built from Dockerfile
- Immutable (unchangeable)

**Docker Container:**
- Running instance of image
- Can be started/stopped/deleted
- Has own filesystem, but can mount volumes

**Volume:**
- Persistent storage
- Survives container deletion
- Types: named volume, bind mount

**Network:**
- Isolated network for containers
- Service name = hostname (automatic DNS)

**docker-compose:**
- Orchestrate multiple containers
- Define in YAML file
- One command to rule them all: `docker-compose up`

---

## 16. Learning Resources

**Official docs:**
- Docker: https://docs.docker.com/
- Docker Compose: https://docs.docker.com/compose/
- Laravel Docker: https://laravel.com/docs/sail (Sail = Laravel's official Docker)

**When to use what:**
- **Sail:** Quick start, opinionated (Laravel's way)
- **Custom Docker:** Full control, learn Docker deeply (what we did)

---

**Sekarang kamu sudah punya complete guide!** 

Simpan file ini, next time setup Docker project baru, tinggal follow step-by-step.

**Questions?** Tanya bagian mana yang masih unclear!
