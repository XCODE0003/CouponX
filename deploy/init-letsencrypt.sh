#!/usr/bin/env bash
# Bootstrap Let's Encrypt certificates for the nginx container.
# Adapted from https://github.com/wmnnd/nginx-certbot (MIT).
#
# Usage (from the project root, after `docker compose up -d` for app/mysql/redis):
#   ./deploy/init-letsencrypt.sh
#   STAGING=1 ./deploy/init-letsencrypt.sh   # test against the LE staging CA
set -euo pipefail

DOMAIN="${DOMAIN:-couponx.deals}"
EMAIL="${EMAIL:-nikita150489@gmail.com}"
STAGING="${STAGING:-0}"
RSA_KEY_SIZE=4096

compose() { docker compose "$@"; }

cert_path="/etc/letsencrypt/live/${DOMAIN}"

echo "### Creating a dummy certificate for ${DOMAIN} ..."
compose run --rm --entrypoint "\
  sh -c 'mkdir -p ${cert_path} && \
    openssl req -x509 -nodes -newkey rsa:2048 -days 1 \
      -keyout ${cert_path}/privkey.pem \
      -out ${cert_path}/fullchain.pem \
      -subj \"/CN=localhost\"'" certbot

echo "### Starting nginx ..."
compose up -d nginx
sleep 3

echo "### Deleting the dummy certificate for ${DOMAIN} ..."
compose run --rm --entrypoint "\
  rm -Rf /etc/letsencrypt/live/${DOMAIN} \
         /etc/letsencrypt/archive/${DOMAIN} \
         /etc/letsencrypt/renewal/${DOMAIN}.conf" certbot

echo "### Requesting a Let's Encrypt certificate for ${DOMAIN} ..."
staging_arg=""
if [ "${STAGING}" != "0" ]; then staging_arg="--staging"; fi

compose run --rm --entrypoint "\
  certbot certonly --webroot -w /var/www/certbot \
    ${staging_arg} \
    --email ${EMAIL} \
    -d ${DOMAIN} \
    --rsa-key-size ${RSA_KEY_SIZE} \
    --agree-tos \
    --no-eff-email \
    --force-renewal" certbot

echo "### Reloading nginx ..."
compose exec nginx nginx -s reload

echo "### Done — https://${DOMAIN} should now be live."
