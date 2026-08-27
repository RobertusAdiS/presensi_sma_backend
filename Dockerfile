# syntax=docker/dockerfile:1

# ============================================================
# Composer dependency stage
# ============================================================

FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-progress


# ============================================================
# Production PHP + Nginx
# ============================================================

FROM php:8.2-fpm-bookworm

WORKDIR /var/www/html


# ============================================================
# System dependencies
# ============================================================

RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        curl \
        unzip \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libonig-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        intl \
        zip \
        bcmath \
        gd \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*


# ============================================================
# PHP configuration
# ============================================================

COPY docker/php.ini \
    /usr/local/etc/php/conf.d/production.ini


# ============================================================
# Nginx
# ============================================================

COPY docker/nginx.conf \
    /etc/nginx/sites-available/default


# ============================================================
# Supervisor
# ============================================================

COPY docker/supervisord.conf \
    /etc/supervisor/conf.d/supervisord.conf


# ============================================================
# Composer dependencies
# ============================================================

COPY --from=vendor /app/vendor ./vendor


# ============================================================
# Laravel source
# ============================================================

COPY . .


# ============================================================
# Laravel permissions
# ============================================================

RUN mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data \
        storage \
        bootstrap/cache


# ============================================================
# Laravel package discovery
# ============================================================

RUN php artisan package:discover --ansi


# ============================================================
# Railway provides PORT dynamically.
#
# 8080 is only the container's default exposed port.
# The application must ultimately listen on $PORT.
# ============================================================

EXPOSE 8080


# ============================================================
# Container startup
# ============================================================

ENTRYPOINT ["/var/www/html/docker/start.sh"]
