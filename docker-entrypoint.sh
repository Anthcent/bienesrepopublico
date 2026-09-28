#!/bin/sh
set -e

# Si existe una base de datos externa configurada (Neon, Supabase, etc.), la usamos
if [ -n "${DATABASE_URL:-}" ] || [ -n "${POSTGRES_URL:-}" ]; then
  echo "[entrypoint] Base de datos externa detectada. Ejecutando migraciones..."
  php database/migrate.php
  if [ "${ENABLE_DEMO_DATA:-0}" = "1" ]; then
    php database/bootstrap_demo_users.php
    php database/seed_sample_data.php
  fi
else
  echo "[entrypoint] Sin base de datos externa. Preparando PostgreSQL embebido..."
  PGDATA="/tmp/pgdata"
  
  if [ ! -d "$PGDATA" ]; then
    echo "[entrypoint] Copiando base de datos precargada a /tmp/pgdata..."
    cp -a /var/lib/postgresql/template "$PGDATA"
  fi

  rm -f "$PGDATA/postmaster.pid"

  echo "[entrypoint] Arrancando PostgreSQL..."
  pg_ctl -D "$PGDATA" -l /tmp/postgres.log -o "-k /tmp -c listen_addresses='127.0.0.1' -c port=5432 -c shared_buffers=32MB -c max_connections=20" start

  READY=0
  for i in $(seq 1 50); do
    if pg_isready -h 127.0.0.1 -p 5432 -U postgres -q; then
      READY=1
      echo "[entrypoint] PostgreSQL embebido listo y aceptando conexiones."
      break
    fi
    sleep 0.1
  done

  if [ "$READY" -ne 1 ]; then
    echo "[entrypoint] ERROR: PostgreSQL embebido no pudo iniciar:" >&2
    cat /tmp/postgres.log >&2 || true
    exit 1
  fi
fi

PORT="${PORT:-8080}"
echo "[entrypoint] Iniciando servidor web PHP en 0.0.0.0:${PORT}..."
exec php -S "0.0.0.0:${PORT}" -t public public/index.php
