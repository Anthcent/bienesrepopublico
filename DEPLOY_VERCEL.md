# Despliegue en Vercel

Este proyecto fue preparado para ejecutarse en Vercel mediante un servicio de contenedor.

## Requisito obligatorio

La aplicación usa PostgreSQL. Configure en Vercel la variable de entorno:

- `DATABASE_URL=postgresql://usuario:clave@host:5432/base?sslmode=require`

Variables recomendadas:

- `APP_ENV=production`
- `COOKIE_SECURE=1`
- `ENABLE_DEMO_DATA=0`

No habilite `ENABLE_DEMO_DATA=1` en producción.

## Archivos de Vercel

- `vercel.json`: declara el servicio de contenedor y enruta todo el tráfico al backend PHP.
- `Dockerfile.vercel`: instala PHP 8.3 y `pdo_pgsql`.
- `docker-entrypoint.sh`: ejecuta las migraciones y escucha en el puerto que Vercel entrega mediante `$PORT`.

## Sesiones

Las sesiones se guardan en PostgreSQL (`app_sessions`) para que el login no dependa del filesystem efímero del contenedor.
La tabla se crea automáticamente por `database/migrate.php`.

## Despliegue

Con Vercel CLI autenticada, desde la raíz del proyecto:

```bash
vercel
```

Para producción:

```bash
vercel --prod
```

Antes de arrancar el servidor, el contenedor ejecuta `php database/migrate.php`.
