# syntax=docker/dockerfile:1.7

FROM php:8.5-cli-alpine AS base

COPY --from=ghcr.io/mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql sockets bcmath pcntl
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

FROM base AS test

COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-progress
COPY . .
CMD ["vendor/bin/phpunit"]

FROM base AS runtime

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --optimize-autoloader
COPY src ./src
COPY bin ./bin

USER www-data

HEALTHCHECK --interval=10s --timeout=3s --start-period=20s --retries=5 \
    CMD test $(( $(date +%s) - $(stat -c %Y /tmp/notification-service.heartbeat) )) -lt 30

CMD ["php", "bin/consume"]
