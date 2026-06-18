#!/usr/bin/env bash
# Generate a production .env from .env.docker with fresh secrets.
# Safe to run only once — refuses to overwrite an existing .env.
set -euo pipefail
cd "$(dirname "$0")/.."

if [ -f .env ]; then
  echo ".env already exists — not overwriting. Edit it by hand if needed." >&2
  exit 1
fi

cp .env.docker .env

DB_PW="$(openssl rand -hex 24)"
DB_ROOT_PW="$(openssl rand -hex 24)"
APP_KEY="base64:$(openssl rand -base64 32)"

# Portable in-place edit (GNU + BSD sed)
sed_i() { sed --version >/dev/null 2>&1 && sed -i "$@" || sed -i '' "$@"; }

sed_i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|"            .env
sed_i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PW}|"      .env
sed_i "s|^DB_ROOT_PASSWORD=.*|DB_ROOT_PASSWORD=${DB_ROOT_PW}|" .env

echo "Wrote .env with a fresh APP_KEY and database passwords."
echo "Keep this file private — it is the source of truth for container secrets."
