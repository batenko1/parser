FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    git zip icu-dev postgresql-dev supervisor autoconf make gcc g++ \
 && rm -rf /var/cache/apk/*

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN install-php-extensions pdo_pgsql pgsql intl opcache zip redis

WORKDIR /var/www

COPY composer.json composer.lock ./

RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY --chown=www-data:www-data . .

RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction --no-progress

RUN mkdir -p storage/logs storage/framework/views storage/framework/cache bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

RUN echo "* * * * * /usr/local/bin/php /var/www/artisan schedule:run >> /dev/null 2>&1" > /etc/crontabs/root \
 && chmod 600 /etc/crontabs/root \
 && mkdir -p /var/run/crond /var/log \
 && chown -R root:root /var/run/crond /var/log

COPY /_docker/supervisord.conf /etc/supervisord.conf

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
