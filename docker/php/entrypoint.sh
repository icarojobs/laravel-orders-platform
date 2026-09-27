#!/bin/sh
set -e

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force --no-interaction
    if [ "${RUN_SEEDERS:-false}" = "true" ]; then
        php artisan db:seed --force --no-interaction
    fi
fi

if [ "${APP_ENV:-production}" != "local" ] && [ "${APP_ENV:-production}" != "testing" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

exec "$@"
