# 🚀 GitLab CI/CD Setup Guide - From Zero to Auto-Deploy

**Tujuan:** Setup **automated testing & deployment** untuk Laravel project menggunakan GitLab CI/CD. Setiap push → auto test → auto deploy jika sukses.

---

## 📋 Table of Contents

1. [What is CI/CD?](#1-what-is-cicd)
2. [GitLab CI/CD Overview](#2-gitlab-cicd-overview)
3. [Prerequisites](#3-prerequisites)
4. [Understanding GitLab CI/CD Components](#4-understanding-gitlab-cicd-components)
5. [Creating .gitlab-ci.yml](#5-creating-gitlab-ciyml)
6. [Setting Up Variables & Secrets](#6-setting-up-variables--secrets)
7. [Testing Stage](#7-testing-stage)
8. [Build Stage](#8-build-stage)
9. [Deploy Stage](#9-deploy-stage)
10. [Monitoring & Debugging](#10-monitoring--debugging)
11. [Best Practices](#11-best-practices)
12. [Common Issues & Solutions](#12-common-issues--solutions)

---

## 1. What is CI/CD?

### **CI = Continuous Integration**
```
Developer push code → Automatically run tests → Report results
```

**Benefits:**
- ✅ Catch bugs early (before merge to main)
- ✅ Ensure code quality
- ✅ Prevent breaking changes

### **CD = Continuous Deployment**
```
Tests passed → Automatically build → Deploy to production
```

**Benefits:**
- ✅ Fast deployment (no manual steps)
- ✅ Consistent deployment process
- ✅ Rollback easy (revert commit)

### **Real-World Flow:**

```
┌─────────────────────────────────────────────────────────────┐
│  Developer Workflow                                         │
├─────────────────────────────────────────────────────────────┤
│  1. Developer write code di local                           │
│  2. git commit + git push origin main                       │
│  3. 🤖 GitLab CI/CD automatically triggered                 │
│  4. Stage 1: Run Tests (PHPUnit/Pest)                       │
│     ├─ ✅ All tests pass → Continue                         │
│     └─ ❌ Tests fail → Stop, notify developer              │
│  5. Stage 2: Build Docker Image                             │
│  6. Stage 3: Deploy to Production Server                    │
│  7. 🎉 Website live dengan code terbaru!                    │
│                                                              │
│  Total time: ~5-10 minutes (fully automated)                │
└─────────────────────────────────────────────────────────────┘
```

---

## 2. GitLab CI/CD Overview

### **Why GitLab CI/CD?**

**vs GitHub Actions:**
| Feature | GitLab CI/CD | GitHub Actions |
|---------|--------------|----------------|
| **Free minutes** | 400 min/month (free tier) | 2000 min/month |
| **Configuration** | `.gitlab-ci.yml` | `.github/workflows/*.yml` |
| **Docker support** | Native (Docker-in-Docker) | Need setup |
| **Complexity** | More powerful, steeper learning | Simpler, marketplace |
| **Self-hosted runners** | Easy setup | Medium setup |

**GitLab CI/CD strengths:**
- ✅ Built-in Container Registry (push Docker images)
- ✅ Native Kubernetes integration
- ✅ Better secrets management
- ✅ Advanced caching strategies

---

### **Key Concepts:**

```
Pipeline
├── Stage 1: Test
│   ├── Job: Run PHPUnit
│   └── Job: Code quality check
├── Stage 2: Build
│   └── Job: Build Docker image
└── Stage 3: Deploy
    └── Job: Deploy to server
```

**Terminology:**
- **Pipeline:** Full CI/CD workflow (test → build → deploy)
- **Stage:** Phase in pipeline (test, build, deploy)
- **Job:** Specific task in stage (run tests, build image)
- **Runner:** Machine that executes jobs

---

## 3. Prerequisites

### **3.1. GitLab Account & Repository**

**Checklist:**
- [ ] GitLab account created (https://gitlab.com)
- [ ] Project repository created
- [ ] Code pushed to GitLab

**Create new GitLab project:**
```bash
# If project belum ada di GitLab:
cd C:\laragon\www\payment-gateway-simple-api-

# Initialize git (if not yet)
git init
git add .
git commit -m "Initial commit"

# Add GitLab remote
git remote add origin https://gitlab.com/your-username/payment-gateway-api.git
git push -u origin main
```

---

### **3.2. GitLab Runner**

**What is GitLab Runner?**
- Machine (server/VM) yang execute CI/CD jobs
- GitLab menyediakan **shared runners** (gratis)
- Atau bisa setup **self-hosted runner** (unlimited minutes)

**Check available runners:**
1. Go to GitLab project
2. Settings → CI/CD → Runners
3. Should see "Shared runners" available

**For this guide:** We'll use **shared runners** (no setup needed)

---

### **3.3. Deployment Target**

**Options:**

**Option 1: Railway (Recommended for beginners)**
- ✅ Free tier available
- ✅ Automatic HTTPS
- ✅ Easy setup (connect Git repo)
- ✅ PostgreSQL included

**Option 2: DigitalOcean App Platform**
- ✅ $5/month (no free tier)
- ✅ Better performance
- ✅ More control

**Option 3: VPS (DigitalOcean/Vultr/Linode)**
- ✅ Full control
- ✅ Cheapest long-term
- ❌ More complex setup (need setup Docker, nginx, SSL)

**For this guide:** We'll setup for **Railway** (easiest) + provide examples for VPS

---

## 4. Understanding GitLab CI/CD Components

### **4.1. .gitlab-ci.yml Structure**

```yaml
# Top-level configuration
image: php:8.4-alpine        # Default Docker image
stages:                       # Define stages order
  - test
  - build
  - deploy

variables:                    # Global variables
  APP_NAME: payment-gateway

# Jobs
test:unit:                    # Job name
  stage: test                 # Which stage?
  script:                     # Commands to run
    - composer install
    - php artisan test
  only:                       # When to run?
    - main
    - merge_requests
```

---

### **4.2. Execution Flow**

```
1. Push code to GitLab
   ↓
2. GitLab detects .gitlab-ci.yml
   ↓
3. Create Pipeline
   ↓
4. Assign jobs to Runners
   ↓
5. Runner pull Docker image
   ↓
6. Runner execute scripts
   ↓
7. Report success/failure
```

---

### **4.3. Job Artifacts & Caching**

**Artifacts:** Files generated by jobs (test reports, built images)
```yaml
artifacts:
  paths:
    - storage/logs/
  expire_in: 1 week
```

**Cache:** Speed up jobs (cache dependencies)
```yaml
cache:
  paths:
    - vendor/
    - node_modules/
```

**Difference:**
- **Cache:** For dependencies (vendor/, node_modules/) - speed up
- **Artifacts:** For build outputs (logs, reports) - pass to next stage

---

## 5. Creating .gitlab-ci.yml

**File location:** Root project (`C:\laragon\www\payment-gateway-simple-api-\.gitlab-ci.yml`)

### **Step 5.1: Basic Structure**

```yaml
# GitLab CI/CD Configuration for Laravel Payment Gateway API
# Author: [Your Name]
# Description: Automated testing, building, and deployment

# Use latest GitLab CI features
workflow:
  rules:
    - if: $CI_PIPELINE_SOURCE == "merge_request_event"
    - if: $CI_COMMIT_BRANCH == $CI_DEFAULT_BRANCH

# Define stages (order matters!)
stages:
  - test
  - build
  - deploy

# Global variables
variables:
  # Composer
  COMPOSER_CACHE_DIR: "$CI_PROJECT_DIR/.composer-cache"
  
  # Docker
  DOCKER_DRIVER: overlay2
  DOCKER_TLS_CERTDIR: "/certs"
  
  # App
  APP_NAME: payment-gateway-api
```

**Penjelasan:**

**workflow.rules:**
- Run pipeline ketika merge request dibuat
- Run pipeline ketika push ke default branch (main/master)

**stages:**
- Define urutan stages
- `test` → `build` → `deploy` (sequential)

**variables:**
- `COMPOSER_CACHE_DIR`: Cache composer dependencies
- `DOCKER_*`: Docker configuration
- `APP_NAME`: Project identifier

---

### **Step 5.2: Cache Configuration**

```yaml
# Cache strategy: Per branch
cache:
  key: ${CI_COMMIT_REF_SLUG}
  paths:
    - vendor/
    - .composer-cache/
```

**Penjelasan:**
- `key: ${CI_COMMIT_REF_SLUG}`: Separate cache per branch
- `paths`: What to cache (vendor/ = Composer packages)

**Why cache?**
- Without cache: `composer install` → 2-3 minutes every pipeline
- With cache: `composer install` → 10-30 seconds

---

## 6. Setting Up Variables & Secrets

**Go to:** GitLab Project → Settings → CI/CD → Variables

### **Required Variables:**

**For Testing:**
```
DB_CONNECTION = pgsql
DB_HOST = postgres
DB_PORT = 5432
DB_DATABASE = testing
DB_USERNAME = postgres
DB_PASSWORD = secret
```

**For Deployment (Railway):**
```
RAILWAY_TOKEN = your-railway-api-token
RAILWAY_PROJECT_ID = your-project-id
```

**For Deployment (VPS):**
```
SSH_PRIVATE_KEY = your-ssh-private-key
SERVER_HOST = your-server-ip
SERVER_USER = deploy
```

**How to add:**
1. Settings → CI/CD → Variables → Expand
2. Click "Add variable"
3. Key: `DB_PASSWORD`
4. Value: `secret`
5. ✅ Check "Mask variable" (hide in logs)
6. ✅ Check "Protect variable" (only protected branches)
7. Click "Add variable"

---

## 7. Testing Stage

### **Step 7.1: Test Job Configuration**

```yaml
# ==================================
# STAGE 1: TEST
# ==================================

# Job: Run PHPUnit/Pest tests
test:unit:
  stage: test
  image: php:8.4-alpine
  
  # Services: Database for testing
  services:
    - postgres:16-alpine
  
  # Environment variables for testing
  variables:
    DB_CONNECTION: pgsql
    DB_HOST: postgres
    DB_PORT: 5432
    DB_DATABASE: testing
    DB_USERNAME: postgres
    DB_PASSWORD: secret
    REDIS_HOST: redis
    REDIS_PORT: 6379
  
  # Commands before script
  before_script:
    # Install system dependencies
    - apk add --no-cache postgresql-dev libpng-dev libjpeg-turbo-dev
    - apk add --no-cache freetype-dev oniguruma-dev libzip-dev
    - apk add --no-cache zip unzip git curl autoconf g++ make
    
    # Install PHP extensions
    - docker-php-ext-configure gd --with-freetype --with-jpeg
    - docker-php-ext-install pdo pdo_pgsql pgsql gd zip
    
    # Install Composer
    - curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    
    # Install dependencies
    - composer install --prefer-dist --no-progress --no-interaction
    
    # Prepare Laravel
    - cp .env.example .env
    - php artisan key:generate
    - php artisan config:clear
  
  # Main test script
  script:
    - php artisan test --parallel --coverage --min=80
  
  # Save test results
  artifacts:
    when: always
    reports:
      junit: storage/logs/junit.xml
    paths:
      - storage/logs/
    expire_in: 1 week
  
  # Only run on specific branches
  only:
    - main
    - merge_requests
    - develop
```

**Penjelasan detail:**

**image: php:8.4-alpine**
- Base image untuk testing
- Alpine = lightweight (faster download)

**services: postgres:16-alpine**
- Start PostgreSQL container for testing
- Accessible via hostname `postgres` (automatic)

**before_script:**
1. Install system dependencies (libpng, postgresql-dev, etc)
2. Install PHP extensions (pdo_pgsql, gd, zip)
3. Install Composer
4. Install Laravel dependencies
5. Setup .env & generate key

**script:**
- Run tests dengan Pest/PHPUnit
- `--parallel`: Run tests parallel (faster)
- `--coverage`: Generate code coverage report
- `--min=80`: Fail jika coverage < 80%

**artifacts:**
- Save test results untuk review
- `junit.xml`: Test report format
- `expire_in: 1 week`: Auto-delete after 1 week

**only:**
- Run on `main` branch
- Run on merge requests
- Run on `develop` branch

---

### **Step 7.2: Code Quality Job (Optional)**

```yaml
# Job: Code quality check (PHPStan, Pint)
test:quality:
  stage: test
  image: php:8.4-alpine
  
  before_script:
    - apk add --no-cache git curl
    - curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    - composer install --prefer-dist --no-progress
  
  script:
    # Laravel Pint (code style)
    - ./vendor/bin/pint --test
    
    # PHPStan (static analysis) - if installed
    # - ./vendor/bin/phpstan analyze
  
  only:
    - main
    - merge_requests
  
  allow_failure: true  # Don't block pipeline if fails
```

**Penjelasan:**
- Check code style dengan Laravel Pint
- Run static analysis dengan PHPStan (optional)
- `allow_failure: true`: Pipeline tetap lanjut walau job ini fail

---

## 8. Build Stage

### **Step 8.1: Build Docker Image**

```yaml
# ==================================
# STAGE 2: BUILD
# ==================================

# Job: Build Docker image
build:docker:
  stage: build
  image: docker:24-dind
  
  services:
    - docker:24-dind
  
  before_script:
    # Login to GitLab Container Registry
    - echo $CI_REGISTRY_PASSWORD | docker login -u $CI_REGISTRY_USER --password-stdin $CI_REGISTRY
  
  script:
    # Build image
    - docker build -t $CI_REGISTRY_IMAGE:$CI_COMMIT_SHORT_SHA -f Dockerfile.dev .
    - docker build -t $CI_REGISTRY_IMAGE:latest -f Dockerfile.dev .
    
    # Push to registry
    - docker push $CI_REGISTRY_IMAGE:$CI_COMMIT_SHORT_SHA
    - docker push $CI_REGISTRY_IMAGE:latest
  
  only:
    - main
  
  # Only build if tests passed
  needs:
    - test:unit
```

**Penjelasan:**

**image: docker:24-dind**
- Docker-in-Docker image
- Can build Docker images inside GitLab CI

**services: docker:24-dind**
- Docker daemon for building images

**before_script:**
- Login to GitLab Container Registry
- `$CI_REGISTRY_PASSWORD`: Auto-provided by GitLab
- `$CI_REGISTRY_USER`: Auto-provided (usually gitlab-ci-token)

**script:**
- Build image dengan 2 tags:
  1. `$CI_COMMIT_SHORT_SHA`: Git commit hash (e.g., `abc123`)
  2. `latest`: Latest version
- Push both to registry

**needs: [test:unit]**
- Only run if `test:unit` job passed
- Dependency between jobs

---

### **Step 8.2: GitLab Container Registry**

**What is Container Registry?**
- Docker image storage (seperti Docker Hub)
- Built-in di GitLab (gratis!)
- Private by default

**Access registry:**
```
Registry URL: registry.gitlab.com/your-username/payment-gateway-api
Image: registry.gitlab.com/your-username/payment-gateway-api:latest
```

**View images:**
- GitLab Project → Packages & Registries → Container Registry

---

## 9. Deploy Stage

### **Option 1: Deploy to Railway**

```yaml
# ==================================
# STAGE 3: DEPLOY (Railway)
# ==================================

deploy:railway:
  stage: deploy
  image: alpine:latest
  
  before_script:
    - apk add --no-cache curl bash
    # Install Railway CLI
    - curl -fsSL https://railway.app/install.sh | sh
  
  script:
    # Deploy using Railway CLI
    - railway up --service $RAILWAY_SERVICE_NAME
  
  environment:
    name: production
    url: https://payment-gateway-api.up.railway.app
  
  only:
    - main
  
  needs:
    - build:docker
  
  # Manual trigger (safety)
  when: manual
```

**Penjelasan:**

**environment:**
- Name: Environment name (production, staging, etc)
- URL: Deployment URL

**when: manual**
- Require manual click untuk deploy
- Safety: Prevent accidental deploy
- Remove `when: manual` untuk auto-deploy

---

### **Option 2: Deploy to VPS (SSH)**

```yaml
# ==================================
# STAGE 3: DEPLOY (VPS via SSH)
# ==================================

deploy:vps:
  stage: deploy
  image: alpine:latest
  
  before_script:
    - apk add --no-cache openssh-client
    # Setup SSH
    - mkdir -p ~/.ssh
    - echo "$SSH_PRIVATE_KEY" > ~/.ssh/id_rsa
    - chmod 600 ~/.ssh/id_rsa
    - ssh-keyscan -H $SERVER_HOST >> ~/.ssh/known_hosts
  
  script:
    # SSH to server and deploy
    - |
      ssh $SERVER_USER@$SERVER_HOST << 'EOF'
        cd /var/www/payment-gateway-api
        
        # Pull latest code
        git pull origin main
        
        # Restart Docker containers
        docker-compose -f docker-compose.yml down
        docker-compose -f docker-compose.yml pull
        docker-compose -f docker-compose.yml up -d
        
        # Run migrations
        docker-compose exec -T app php artisan migrate --force
        
        # Clear cache
        docker-compose exec -T app php artisan cache:clear
        docker-compose exec -T app php artisan config:cache
        docker-compose exec -T app php artisan route:cache
        docker-compose exec -T app php artisan view:cache
      EOF
  
  environment:
    name: production
    url: https://api.yourdomain.com
  
  only:
    - main
  
  needs:
    - build:docker
  
  when: manual
```

**Penjelasan:**

**SSH_PRIVATE_KEY:**
- SSH key untuk connect ke server
- Store di GitLab CI/CD Variables (masked)

**script:**
- SSH ke server
- Pull latest code from Git
- Restart Docker containers
- Run migrations
- Clear & warm cache

---

### **Option 3: Deploy to DigitalOcean App Platform**

```yaml
# ==================================
# STAGE 3: DEPLOY (DigitalOcean)
# ==================================

deploy:digitalocean:
  stage: deploy
  image: alpine:latest
  
  before_script:
    - apk add --no-cache curl
    # Install doctl (DigitalOcean CLI)
    - curl -LO https://github.com/digitalocean/doctl/releases/download/v1.98.1/doctl-1.98.1-linux-amd64.tar.gz
    - tar xf doctl-1.98.1-linux-amd64.tar.gz
    - mv doctl /usr/local/bin
    # Authenticate
    - doctl auth init -t $DIGITALOCEAN_TOKEN
  
  script:
    # Trigger deployment
    - doctl apps create-deployment $DIGITALOCEAN_APP_ID
  
  environment:
    name: production
    url: https://payment-gateway-api-xxxxx.ondigitalocean.app
  
  only:
    - main
  
  needs:
    - build:docker
  
  when: manual
```

---

## 10. Monitoring & Debugging

### **10.1. View Pipeline Status**

**GitLab UI:**
1. Go to project
2. CI/CD → Pipelines
3. Click pipeline ID

**Pipeline view:**
```
Pipeline #123 - ✅ passed (5m 23s)
├─ Stage: test
│  ├─ ✅ test:unit (2m 45s)
│  └─ ✅ test:quality (1m 12s)
├─ Stage: build
│  └─ ✅ build:docker (1m 15s)
└─ Stage: deploy
   └─ 🔵 deploy:railway (manual)
```

---

### **10.2. View Job Logs**

**Click job name → See logs:**
```
Running with gitlab-runner 15.0.0 (git revision 12345)
  on shared-runner-1 abc123
Preparing the "docker" executor
...
$ composer install
Loading composer repositories with package information
Installing dependencies from lock file
...
✅ Job succeeded
```

**Log colors:**
- 🟢 Green = Success
- 🔴 Red = Error
- 🔵 Blue = Info

---

### **10.3. Download Artifacts**

**After job finished:**
1. Click job name
2. Right side → "Browse" button (artifacts)
3. Download files (test reports, logs)

---

### **10.4. Re-run Failed Jobs**

**If job fails:**
1. Click pipeline
2. Click failed job
3. Top right → "Retry" button

**Or retry entire pipeline:**
1. Pipelines list
2. Click "..." → Retry

---

## 11. Best Practices

### **11.1. Pipeline Performance**

**Optimize build time:**

```yaml
# ❌ Slow (no cache)
script:
  - composer install  # Download all packages every time

# ✅ Fast (with cache)
cache:
  paths:
    - vendor/
script:
  - composer install  # Use cached vendor/
```

**Tips:**
- Use `cache` for dependencies
- Use `artifacts` only for necessary files
- Use Alpine images (smaller, faster download)
- Run tests in parallel

---

### **11.2. Security**

**Never commit secrets:**
```yaml
# ❌ DON'T DO THIS
script:
  - echo "password123" > .env

# ✅ DO THIS
script:
  - echo "PASSWORD=$SECRET_PASSWORD" > .env
```

**Use GitLab CI/CD Variables:**
- Store all secrets in Variables
- Enable "Mask variable" (hide in logs)
- Enable "Protect variable" (only protected branches)

---

### **11.3. Branch Strategy**

**Recommended:**
```yaml
# Test all branches
test:unit:
  only:
    - branches

# Build only main
build:docker:
  only:
    - main

# Deploy only main (manual)
deploy:production:
  only:
    - main
  when: manual
```

---

### **11.4. Notifications**

**Setup Slack notifications:**

```yaml
notify:success:
  stage: .post  # Run after all stages
  script:
    - 'curl -X POST -H "Content-type: application/json" --data "{\"text\":\"✅ Pipeline passed!\"}" $SLACK_WEBHOOK_URL'
  only:
    - main
  when: on_success

notify:failure:
  stage: .post
  script:
    - 'curl -X POST -H "Content-type: application/json" --data "{\"text\":\"❌ Pipeline failed!\"}" $SLACK_WEBHOOK_URL'
  only:
    - main
  when: on_failure
```

---

## 12. Common Issues & Solutions

### **Issue 1: "composer install" timeout**

**Error:**
```
The "https://packagist.org/..." file could not be downloaded (HTTP/2 504)
```

**Solution:**
```yaml
script:
  - composer install --no-interaction --prefer-dist --optimize-autoloader --no-ansi --no-progress
```

Add `--no-progress` to reduce output.

---

### **Issue 2: Tests fail di CI tapi sukses di local**

**Common causes:**
1. Database not ready (PostgreSQL starting)
2. Environment variables different
3. Cache issues

**Solution:**
```yaml
before_script:
  # Wait for PostgreSQL
  - apt-get update && apt-get install -y postgresql-client
  - until pg_isready -h postgres -p 5432 -U postgres; do sleep 1; done
  
  # Clear cache
  - php artisan config:clear
  - php artisan cache:clear
```

---

### **Issue 3: "Cannot connect to Docker daemon"**

**Error:**
```
Cannot connect to the Docker daemon at unix:///var/run/docker.sock
```

**Solution:**
```yaml
build:docker:
  image: docker:24-dind
  services:
    - docker:24-dind  # ← Add this!
  variables:
    DOCKER_HOST: tcp://docker:2376
    DOCKER_TLS_CERTDIR: "/certs"
```

---

### **Issue 4: Pipeline stuck at "pending"**

**Cause:** No available runners

**Solution:**
1. Check Settings → CI/CD → Runners
2. Enable shared runners
3. Or setup self-hosted runner

---

### **Issue 5: "SSH: Permission denied"**

**Cause:** SSH key tidak valid

**Solution:**
```bash
# Generate new SSH key di local
ssh-keygen -t ed25519 -C "gitlab-ci"

# Copy private key
cat ~/.ssh/id_ed25519
# Paste ke GitLab Variables: SSH_PRIVATE_KEY

# Copy public key ke server
ssh-copy-id user@server-ip
```

---

## 13. Complete .gitlab-ci.yml Example

**File:** `.gitlab-ci.yml` (root project)

```yaml
# ============================================
# GitLab CI/CD - Laravel Payment Gateway API
# ============================================

workflow:
  rules:
    - if: $CI_PIPELINE_SOURCE == "merge_request_event"
    - if: $CI_COMMIT_BRANCH == $CI_DEFAULT_BRANCH

stages:
  - test
  - build
  - deploy

variables:
  COMPOSER_CACHE_DIR: "$CI_PROJECT_DIR/.composer-cache"
  DOCKER_DRIVER: overlay2
  DOCKER_TLS_CERTDIR: "/certs"
  APP_NAME: payment-gateway-api

cache:
  key: ${CI_COMMIT_REF_SLUG}
  paths:
    - vendor/
    - .composer-cache/

# ============================================
# STAGE 1: TEST
# ============================================

test:unit:
  stage: test
  image: php:8.4-alpine
  services:
    - postgres:16-alpine
  variables:
    DB_CONNECTION: pgsql
    DB_HOST: postgres
    DB_PORT: 5432
    DB_DATABASE: testing
    DB_USERNAME: postgres
    DB_PASSWORD: secret
  before_script:
    - apk add --no-cache postgresql-dev libpng-dev libjpeg-turbo-dev freetype-dev oniguruma-dev libzip-dev zip unzip git curl autoconf g++ make
    - docker-php-ext-configure gd --with-freetype --with-jpeg
    - docker-php-ext-install pdo pdo_pgsql pgsql gd zip
    - curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    - composer install --prefer-dist --no-progress --no-interaction
    - cp .env.example .env
    - php artisan key:generate
    - php artisan config:clear
  script:
    - php artisan test --parallel
  artifacts:
    when: always
    paths:
      - storage/logs/
    expire_in: 1 week
  only:
    - main
    - merge_requests
    - develop

test:quality:
  stage: test
  image: php:8.4-alpine
  before_script:
    - apk add --no-cache git curl
    - curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    - composer install --prefer-dist --no-progress
  script:
    - ./vendor/bin/pint --test
  only:
    - main
    - merge_requests
  allow_failure: true

# ============================================
# STAGE 2: BUILD
# ============================================

build:docker:
  stage: build
  image: docker:24-dind
  services:
    - docker:24-dind
  before_script:
    - echo $CI_REGISTRY_PASSWORD | docker login -u $CI_REGISTRY_USER --password-stdin $CI_REGISTRY
  script:
    - docker build -t $CI_REGISTRY_IMAGE:$CI_COMMIT_SHORT_SHA -f Dockerfile.dev .
    - docker build -t $CI_REGISTRY_IMAGE:latest -f Dockerfile.dev .
    - docker push $CI_REGISTRY_IMAGE:$CI_COMMIT_SHORT_SHA
    - docker push $CI_REGISTRY_IMAGE:latest
  only:
    - main
  needs:
    - test:unit

# ============================================
# STAGE 3: DEPLOY
# ============================================

deploy:production:
  stage: deploy
  image: alpine:latest
  before_script:
    - apk add --no-cache openssh-client
    - mkdir -p ~/.ssh
    - echo "$SSH_PRIVATE_KEY" > ~/.ssh/id_rsa
    - chmod 600 ~/.ssh/id_rsa
    - ssh-keyscan -H $SERVER_HOST >> ~/.ssh/known_hosts
  script:
    - |
      ssh $SERVER_USER@$SERVER_HOST << 'EOF'
        cd /var/www/payment-gateway-api
        git pull origin main
        docker-compose down
        docker-compose pull
        docker-compose up -d
        docker-compose exec -T app php artisan migrate --force
        docker-compose exec -T app php artisan cache:clear
        docker-compose exec -T app php artisan config:cache
        docker-compose exec -T app php artisan route:cache
      EOF
  environment:
    name: production
    url: https://api.yourdomain.com
  only:
    - main
  needs:
    - build:docker
  when: manual
```

---

## 14. Testing Your Pipeline

### **Step 1: Commit & Push .gitlab-ci.yml**

```bash
git add .gitlab-ci.yml
git commit -m "Add GitLab CI/CD pipeline"
git push origin main
```

### **Step 2: Monitor Pipeline**

1. Go to GitLab project
2. CI/CD → Pipelines
3. Should see new pipeline running

### **Step 3: Fix Errors**

If pipeline fails:
1. Click failed job
2. Read logs
3. Fix issue
4. Commit & push
5. Repeat

---

## 15. Next Steps

**After CI/CD working:**
1. ✅ **Setup production deployment** (Railway/VPS)
2. ✅ **Add monitoring** (Sentry for errors)
3. ✅ **Setup staging environment**
4. ✅ **Add automated backups**
5. ✅ **Performance monitoring** (New Relic/Datadog)

---

## 16. Checklist: Setup CI/CD

**Phase 1: Prerequisites**
- [ ] GitLab account & repository
- [ ] Code pushed to GitLab
- [ ] Runners available (check Settings → CI/CD → Runners)

**Phase 2: Configuration**
- [ ] Create `.gitlab-ci.yml`
- [ ] Add CI/CD Variables (DB credentials, secrets)
- [ ] Test pipeline locally (if possible)

**Phase 3: Testing Stage**
- [ ] Test job runs successfully
- [ ] Tests pass in CI
- [ ] Artifacts generated

**Phase 4: Build Stage**
- [ ] Docker image builds
- [ ] Image pushed to registry
- [ ] Can pull image from registry

**Phase 5: Deploy Stage**
- [ ] Deployment script works
- [ ] Application accessible
- [ ] Database migrations run
- [ ] Health check passes

**Phase 6: Monitoring**
- [ ] Pipeline badges added to README
- [ ] Notifications setup (Slack/Email)
- [ ] Logs monitored

---

## 17. Summary

**What you achieved:**
✅ **Automated testing** - Every push runs tests
✅ **Automated builds** - Docker images built automatically
✅ **Automated deploys** - Push to main = auto deploy
✅ **Quality assurance** - Code checked before merge
✅ **Fast feedback** - Know if code breaks within minutes

**Time saved:**
- Manual testing: 15 minutes → **Automated: 2 minutes**
- Manual build: 10 minutes → **Automated: 1 minute**
- Manual deploy: 20 minutes → **Automated: 2 minutes**

**Total:** ~45 minutes → **5 minutes** (9x faster!)

---

**Sekarang project kamu punya CI/CD yang proper!** 🎉

Push code → Auto test → Auto build → Auto deploy → Live in minutes!

**Questions?** Tanya section mana yang perlu dijelaskan lebih detail!
