#!/bin/sh
set -e

APP_ENV="${APP_ENV:-production}"
ENABLE_DEMO_DATA="${ENABLE_DEMO_DATA:-0}"

if [ "$APP_ENV" = "production" ] && [ "$ENABLE_DEMO_DATA" = "1" ]; then
  echo "ENABLE_DEMO_DATA no puede activarse en producción." >&2
  exit 1
fi

if [ "$APP_ENV" = "production" ] && [ -n "${TRUSTED_PROXIES:-}" ]; then
  php database/validate_security_env.php
fi

# Aplica esquema + datos semilla (idempotente) antes de aceptar tráfico.
# Requiere DATABASE_URL (o DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASS).
php database/migrate.php

if [ "$ENABLE_DEMO_DATA" = "1" ]; then
  php database/bootstrap_demo_users.php
  php database/seed_sample_data.php
fi

PORT="${PORT:-8080}"
exec php -S "0.0.0.0:${PORT}" -t public
