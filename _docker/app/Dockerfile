FROM php:8.4-fpm-alpine

# Минимум зависимостей
RUN apk add --no-cache \
    git zip icu-dev postgresql-dev autoconf make gcc g++ \
 && rm -rf /var/cache/apk/*

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Только необходимые расширения
RUN install-php-extensions pdo_pgsql pgsql intl opcache zip

WORKDIR /var/www

COPY composer.json composer.lock ./

# Первый install для кеша
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY --chown=www-data:www-data . .

# Второй install для генерации автолоадера
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction --no-progress

RUN mkdir -p storage/logs storage/framework/views storage/framework/cache bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache
