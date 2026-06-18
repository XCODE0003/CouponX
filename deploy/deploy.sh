#!/usr/bin/env bash
# Build and (re)deploy CouponX. Run from the project root on the server.
#   ./deploy/deploy.sh           # build + migrate (normal redeploy)
#   SEED=1 ./deploy/deploy.sh     # also run database seeders (first deploy only)
set -euo pipefail
cd "$(dirname "$0")/.."

if [ ! -f .env ]; then
  echo "No .env found — run ./deploy/bootstrap-env.sh first." >&2
  exit 1
fi

echo "### Building images ..."
docker compose build

echo "### Starting data + application services ..."
docker compose up -d mysql redis app queue scheduler

echo "### Waiting for MySQL to become healthy ..."
until [ "$(docker compose ps -q mysql | xargs -r docker inspect -f '{{.State.Health.Status}}' 2>/dev/null)" = "healthy" ]; do
  sleep 2
done

if [ "${SEED:-0}" = "1" ]; then
  echo "### Running migrations + seeders ..."
  docker compose exec -T app php artisan migrate --force --seed
else
  echo "### Running migrations ..."
  docker compose exec -T app php artisan migrate --force
fi

echo "### Optimizing application caches ..."
docker compose exec -T app php artisan optimize

echo "### Ensuring TLS / nginx is up ..."
if docker compose run --rm --entrypoint "test -f /etc/letsencrypt/live/couponx.deals/fullchain.pem" certbot 2>/dev/null; then
  docker compose up -d nginx certbot
  docker compose exec nginx nginx -s reload || true
else
  echo "No certificate yet — run ./deploy/init-letsencrypt.sh to obtain one."
fi

echo "### Deploy complete."
docker compose ps
