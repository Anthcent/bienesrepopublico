# Sistema de Bienes Públicos — Implementación (carpeta `adolfo`)

Implementación construida a partir de las especificaciones en
`PAQUETE_TRABAJO_IA_BIENES_PUBLICOS/` (Plan Maestro V4, mockup y checklist),
adaptada a **PHP + PostgreSQL** (sin framework, arquitectura por capas propia)
con **Tailwind-free CSS a medida** (mismos tokens/patrones del mockup) y
**JavaScript modular** (ES modules, sin dependencias de build).

> El proyecto se construyó originalmente sobre MySQL. `database/schema.sql`,
> `database/seed.sql` y `database/migrations/` son ese historial (ver
> `MIGRACIONES_REALIZADAS.md`) y ya no los usa la aplicación: la conexión
> (`src/Core/Database.php`) solo habla PostgreSQL. El esquema vigente es
> `database/schema.postgres.sql` + `database/seed.postgres.sql`.

## Requisitos

- PHP 8.1+ con extensión `pdo_pgsql`
- PostgreSQL 14+
- Servidor web (Apache con `mod_rewrite`, o el servidor embebido de PHP)

No requiere Composer ni Node — el autoload es un mapeador PSR-4 mínimo
(`src/Support/autoload.php`) y el CSS/JS son archivos estáticos servidos
directamente.

## Instalación (local)

1. Crear la base de datos y aplicar el esquema + datos semilla (ambos son
   idempotentes, se pueden ejecutar varias veces sin duplicar nada):

   ```bash
   createdb bienes_publicos
   php database/migrate.php
   ```

   `migrate.php` lee la conexión de `DATABASE_URL` (formato
   `postgres://usuario:clave@host:puerto/nombre_bd`) o, si no está
   definida, de las variables `DB_HOST`/`DB_PORT`/`DB_NAME`/`DB_USER`/`DB_PASS`
   (ver `src/Config/config.php`).

2. Crear el usuario administrador (genera un hash bcrypt real; nunca se
   fabrica un hash a mano en el SQL semilla):

   ```bash
   php database/create_admin.php admin@tudominio.local "UnaContraseñaSegura123"
   ```

3. Levantar el servidor apuntando a `public/` como document root:

   ```bash
   php -S localhost:8000 -t public
   ```

   O con Apache: apuntar el VirtualHost/alias a `adolfo/public`.

4. Ingresar con el correo y contraseña creados en el paso 2.

## Despliegue en contenedor (Dokploy / Hexper Ops)

El `Dockerfile` de la raíz empaqueta la app con el servidor embebido de PHP
escuchando en `0.0.0.0:8080` (mismo servidor que en desarrollo, solo cambia
el puerto/bind). `docker-entrypoint.sh` corre, en cada arranque y antes de
aceptar tráfico:

1. `database/migrate.php` — esquema + datos semilla (idempotente).
2. `database/bootstrap_demo_users.php` — crea/actualiza 3 usuarios de
   prueba, uno por rol (ver "Credenciales de prueba" abajo).
3. `database/seed_sample_data.php` — carga un puñado de datos de muestra
   (ver "Datos de muestra" abajo).

Variables de entorno esperadas en tiempo de ejecución (configurarlas en el
administrador de despliegue, nunca en el repositorio):

- `DATABASE_URL` — inyectada automáticamente si se habilita el PostgreSQL
  automático de Hexper Ops/Dokploy.
- Alternativamente `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`.

### Credenciales de prueba (temporales — quitar antes de producción real)

Mientras dura esta etapa de pruebas del despliegue, `docker-entrypoint.sh`
crea automáticamente un usuario por rol (ADMIN, OPERATIVO, CONSULTA) con
contraseña fija — definidos en `src/Config/demo_credentials.php` — y la
pantalla de login (`templates/pages/auth/login.php`, bloque `auth-devbox`)
los muestra en una caja "Credenciales de prueba" con copiado al portapapeles
y un botón de autorrelleno por rol, para no tener que pedirlas por otro
canal.

Antes de un despliegue real (no de pruebas) hay que quitar los tres:
`src/Config/demo_credentials.php`, `database/bootstrap_demo_users.php` (y su
llamada en `docker-entrypoint.sh`) y el bloque `auth-devbox` de `login.php`;
y crear el/los administradores reales con `database/create_admin.php`
(paso 2 de "Instalación" arriba) desde un canal que no exponga la
contraseña en la interfaz.

### Datos de muestra (temporales — quitar antes de producción real)

Para poder evaluar el comportamiento del sistema sin arrancar de una base
totalmente vacía, `database/seed_sample_data.php` carga un conjunto
pequeño y fijo de datos de prueba en el primer arranque:

- 2 ubicaciones, 2 responsables, 1 marca con 2 modelos.
- 4 bienes (`BP-0001`..`BP-0004`) cubriendo distintos casos: uno
  disponible en buen estado, uno prestado (ver punto siguiente), uno con
  estado físico "Regular" y uno desincorporado por deterioro.
- 1 préstamo activo (`PR-DEMO-0001`) sobre el bien prestado, vence en 7 días.

Es idempotente por construcción: si ya existe algún bien en la base (los
de muestra o reales), el script no hace nada — así que solo siembra datos
en el primer arranque, nunca duplica ni pisa datos ya cargados.

Antes de un despliegue real, quita la llamada a `seed_sample_data.php` en
`docker-entrypoint.sh` (y, si quieres, borra los 4 bienes y el préstamo de
muestra desde la propia aplicación).

Build y prueba local:

```bash
docker build -t bienes-publicos .
docker run --rm -p 8080:8080 -e DATABASE_URL="postgres://usuario:clave@host:5432/bienes_publicos" bienes-publicos
```

## Estructura

```text
adolfo/
  database/            esquema SQL + datos semilla (catálogos, roles, admin)
  public/               document root (front controller, CSS, JS)
  src/
    Config/             configuración
    Core/                Database, Router, Request/Response, Auth, Session, View
    Middleware/          AuthMiddleware, PermissionMiddleware
    Repositories/        acceso a datos (PDO, prepared statements)
    Services/            reglas de negocio y transacciones
    Commands/            DTOs de entrada (CreateAssetCommand, CreateLoanCommand, …)
    Controllers/         rutas delgadas (delegan a Services)
  routes/                web.php (páginas) y api.php (JSON)
  templates/
    layout/              shells (app autenticado, auth, impresión)
    components/           sidebar, topbar, drawers, modales, wizard, paginación
    pages/                una carpeta por módulo
```

## Arquitectura

```text
Route → Command/DTO → Service (reglas + transacción) → Repository → PDO/MySQL
```

Todas las operaciones críticas (incorporación, reasignación, desincorporación,
readmisión, préstamo múltiple, devolución total/parcial, extensión) son
transacciones atómicas (`beginTransaction`/`commit`/`rollBack`) que además
registran auditoría y, cuando aplica, generan un documento y notificaciones.

## Documentos entregables de este build

- `DECISIONES_TECNICAS.md`
- `MIGRACIONES_REALIZADAS.md`
- `CAMBIOS_UX.md`
- `PRUEBAS_EJECUTADAS.md`
- `PENDIENTES.md`
