# ================================================
# Multi-stage Dockerfile untuk Laravel Production
# ================================================
# 
# Stage 1: Composer dependencies (build stage)
# Stage 2: Runtime (final image)
#
# Analogi: 
# - Stage 1 = Dapur persiapan (install tools, compile)
# - Stage 2 = Restoran (hanya serve makanan jadi)
# ================================================

# ========== STAGE 1: Composer Dependencies ==========
FROM composer:2.7 AS composer-stage

# Copy hanya composer files dulu untuk leverage Docker cache
COPY composer.json composer.lock /app/

WORKDIR /app

# Install dependencies (production only, no dev packages)
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --optimize-autoloader

# Copy seluruh source code
COPY . /app

# Generate optimized autoloader
RUN composer dump-autoload --optimize --no-dev


# ========== STAGE 2: Runtime Image ==========
FROM php:8.2-fpm-alpine

# Install system dependencies & PHP extensions
# Alpine Linux = lightweight Linux distro (ukuran kecil)
RUN apk add --no-cache \
    nginx \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    zip \
    unzip \
    git \
    curl \
    supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        gd \
        pcntl \
        bcmath

# Install Redis extension (untuk queue & cache)
RUN pecl install redis && docker-php-ext-enable redis

# Set working directory
WORKDIR /var/www/html

# Copy vendor dari stage 1 (hasil composer install)
COPY --from=composer-stage /app/vendor /var/www/html/vendor

# Copy application code
COPY . /var/www/html

# Copy configuration files
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf

# Set permissions
# www-data = user untuk PHP-FPM & nginx
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create nginx pid directory
RUN mkdir -p /run/nginx

# Create supervisor log directory
RUN mkdir -p /var/log/supervisor

# Expose port 80
EXPOSE 80

# Health check untuk monitoring
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s \
    CMD curl -f http://localhost/api/health || exit 1

# Start supervisor (manage nginx + php-fpm + queue worker)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
