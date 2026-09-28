# syntax=docker/dockerfile:1.7

FROM php:8.5-fpm-alpine AS base

COPY --from=ghcr.io/mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions pdo_pgsql redis pcntl bcmath sockets intl zip opcache \
    && apk add --no-cache fcgi \
    && echo "pm.status_path = /status" >> /usr/local/etc/php-fpm.d/zz-docker.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/zz-pool.conf /usr/local/etc/php-fpm.d/zz-pool.conf

WORKDIR /var/www/html

FROM base AS vendor

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

FROM base AS assets

RUN apk add --no-cache nodejs npm
COPY --from=vendor /var/www/html/vendor ./vendor
COPY package.json package-lock.json .npmrc ./
RUN npm ci --no-audit --no-fund
COPY . .
RUN composer dump-autoload --no-dev --no-scripts \
    && cp .env.example .env && php artisan key:generate \
    && npm run build

FROM base AS app

COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /var/www/html/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /var/www/html/public/build ./public/build
RUN composer dump-autoload --no-dev --optimize --no-scripts \
    && rm -f .env && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

FROM app AS dev

RUN install-php-extensions pcov \
    && composer install --no-scripts --no-interaction --prefer-dist \
    && composer dump-autoload --optimize \
    && touch .env \
    && chown -R www-data:www-data vendor

FROM nginx:1.29-alpine AS web

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public
