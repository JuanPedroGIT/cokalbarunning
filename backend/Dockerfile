FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache \
    postgresql-dev libzip-dev zip unzip git icu-dev curl \
    oniguruma-dev libpng-dev libjpeg-turbo-dev freetype-dev libwebp-dev \
    $PHPIZE_DEPS

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
    pdo_pgsql pgsql zip intl opcache gd exif

RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/backend

# ── DEV ──
FROM base AS dev
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-interaction --no-scripts
COPY . .
RUN composer dump-autoload --optimize
RUN mkdir -p public/uploads var && chmod -R 777 public/uploads var
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENV APP_ENV=dev
EXPOSE 9000
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]

# ── PROD ──
FROM base AS prod
ENV APP_ENV=prod APP_DEBUG=0
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-interaction --no-scripts
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative \
    && mkdir -p public/uploads var && chmod -R 777 public/uploads var
RUN APP_ENV=prod php bin/console cache:warmup
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
EXPOSE 9000
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
