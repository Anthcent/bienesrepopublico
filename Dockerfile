# Sistema de Bienes Públicos — imagen de despliegue.
# Aplicación PHP plana (sin Composer, sin build de frontend): el propio
# servidor embebido de PHP sirve public/ como document root, igual que en
# desarrollo (`php -S localhost:8000 -t public`), pero escuchando en
# 0.0.0.0:8080 dentro del contenedor.
FROM php:8.3-cli-alpine

# pdo_pgsql: extensión para conectar a PostgreSQL.
# curl: usado únicamente por el HEALTHCHECK.
RUN apk add --no-cache postgresql-libs curl \
    && apk add --no-cache --virtual .build-deps postgresql-dev \
    && docker-php-ext-install pdo_pgsql \
    && apk del .build-deps

# Usuario sin privilegios para ejecutar la aplicación.
RUN addgroup -S app && adduser -S app -G app

WORKDIR /var/www/html
COPY --chown=app:app . .
RUN chmod +x docker-entrypoint.sh

USER app

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD curl -fsS http://127.0.0.1:8080/ || exit 1

ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]
