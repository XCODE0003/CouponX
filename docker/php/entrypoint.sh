#!/bin/sh
# Container entrypoint shared by the app (php-fpm), queue and scheduler services.
# Runs as root: prepares the (possibly empty) storage volume, warms framework
# caches, then drops privileges to www-data for the actual process.
set -e

cd /var/www/html

# Recreate the storage skeleton — the named volume mounted here may be empty.
mkdir -p \
  storage/app/public \
  storage/app/private \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

# Warm config/route/view caches with the runtime environment.
# Guarded so a transient failure never blocks container start.
su-exec www-data php artisan config:cache 2>/dev/null || true
su-exec www-data php artisan route:cache  2>/dev/null || true
su-exec www-data php artisan view:cache   2>/dev/null || true

case "$1" in
  php-fpm)
    # php-fpm master stays root so it can drop workers to www-data per pool config
    exec "$@"
    ;;
  *)
    exec su-exec www-data "$@"
    ;;
esac
