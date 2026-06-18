# syntax=docker/dockerfile:1.7

###############################################################################
# Stage 1 — builder
# PHP + Composer + Node together: the Vite build invokes `php artisan
# wayfinder:generate` and Filament publishes assets, so both toolchains must be
# present. Produces: vendor/, public/ (compiled assets + static + storage link).
###############################################################################
FROM php:8.4-cli-bookworm AS builder

# PHP extensions (mlocati installer resolves apt deps automatically)
COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql gd intl zip bcmath exif pcntl redis

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Node.js 22 — glibc build matches the linux-x64-gnu optional deps in package.json
RUN apt-get update \
 && apt-get install -y --no-install-recommends curl ca-certificates gnupg unzip git \
 && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
 && apt-get install -y --no-install-recommends nodejs \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# PHP deps first for layer caching (scripts deferred — app not fully present yet)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# JS deps, cached on the lockfile
COPY package.json package-lock.json ./
RUN npm ci

# Full source
COPY . .

# Finalize autoloader + run the artisan-dependent steps (need vendor + a key)
RUN composer dump-autoload --optimize --no-dev \
 && cp .env.example .env \
 && php artisan key:generate --no-interaction \
 && php artisan package:discover --ansi \
 && php artisan filament:upgrade \
 && php artisan storage:link --relative

# Compile front-end assets (wayfinder generates TS helpers from artisan routes)
RUN npm run build

###############################################################################
# Stage 2 — app (PHP-FPM runtime)
###############################################################################
FROM php:8.4-fpm-alpine AS app

COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql gd intl zip bcmath exif pcntl opcache redis \
 && apk add --no-cache su-exec

WORKDIR /var/www/html

# Application source (no vendor/build/.env — see .dockerignore)
COPY . .
# Built artifacts from the builder stage
COPY --from=builder /app/vendor ./vendor
COPY --from=builder /app/public ./public

# PHP config + entrypoint
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh \
 && mkdir -p \
      storage/framework/cache/data \
      storage/framework/sessions \
      storage/framework/views \
      storage/logs \
      storage/app/public \
      storage/app/private \
      bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

###############################################################################
# Stage 3 — nginx (static + reverse proxy to php-fpm)
###############################################################################
FROM nginx:1.27-alpine AS nginx
# Public root (compiled assets, static files, /storage symlink) baked from app
COPY --from=app /var/www/html/public /var/www/html/public
