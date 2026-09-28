# Migraciones realizadas

No hay un sistema previo que migrar (ver `DECISIONES_TECNICAS.md`), por lo
que no existen "migraciones" en el sentido de transformar datos existentes.
Este documento registra en su lugar la evolución del esquema `database/schema.sql`
a medida que el sistema crezca, para que futuras modificaciones queden
documentadas aquí (nunca se elimina una migración anterior de esta lista).

## v1 — Esquema inicial

Fecha: 2026-08-07

Creación de todas las tablas base del núcleo funcional (M01–M12):

- `roles`, `permissions`, `role_permissions`, `users`, `user_preferences`, `saved_views`
- `locations`, `responsibles`, `categories`, `brands`, `models`, `physical_states`, `movement_types`
- `assets`
- `asset_movements`
- `loans`, `loan_details`, `loan_extensions`
- `documents`
- `audit_log`
- `notifications`

Datos semilla (`database/seed.sql`): roles (ADMIN/OPERATIVO/CONSULTA),
permisos y su asignación por rol, catálogo de estados físicos, tipos de
movimiento y categorías de bien. **Sin bienes, préstamos ni movimientos de
ejemplo** (se evitó deliberadamente poblar datos ficticios de negocio, tal
como indica `00_LEER_PRIMERO/INSTRUCCIONES_IA.md`).

## v2 — Jornada de Verificación Patrimonial

Fecha: 2026-08-07 (sesión Plan Maestro V5)
Archivo: `database/migrations/001_verification_campaigns.sql`

Motivo: el Plan Maestro V5 introduce el módulo "Jornada de Verificación
Patrimonial" (§17-30), inexistente en el esquema v1.

Tablas nuevas:
- `verification_campaigns` — la jornada (código `JV-{año}-{secuencial}`,
  alcance, campos a verificar en `campos_json`, estado, contador `cantidad_bienes`).
  Los contadores de progreso (revisados/con cambios/no encontrados/pendientes)
  **no se duplican como columnas**: se calculan siempre con `COUNT(estado_item)`
  agrupado sobre `verification_campaign_items`, igual que ya hace
  `LoanRepository::counters()` para préstamos — evita que un contador
  quede desincronizado del estado real de los items.
- `verification_campaign_items` — snapshot inmutable de cada bien al
  generar la jornada + resultado de la captura posterior.

Otros cambios:
- Nuevo `movement_types.codigo = 'VERIFICACION'`: se usa solo cuando la
  captura corrige estado físico o serial **sin** tocar ubicación/responsable.
  Si la captura sí cambia ubicación o responsable, se reutiliza
  `AssetMovementService::reassign()` (genera un movimiento `REASIGNACION`
  real, con las mismas validaciones que la reasignación manual) para no
  duplicar la lógica de transición de estado del bien.
- Nuevos permisos `verification.view` / `verification.manage`, asignados
  a ADMIN y OPERATIVO (gestión) y CONSULTA (solo vista), siguiendo el
  mismo criterio ya usado para `asset.view`/`report.generate`.

Sin backfill: no había datos previos de verificación que migrar.

## v3 — Color por estado físico

Fecha: 2026-08-09
Archivo: `database/migrations/002_physical_states_color.sql`

Motivo: el catálogo "Estados físicos" era de solo lectura (sin alta/edición
en la UI); se habilitó edición completa y se agregó un color por estado,
reutilizado en las tarjetas del paso "Asignación y estado" del wizard de
incorporación (antes usaban un ícono fijo ✓/! sin distinción visual por
estado).

Cambios:
- `physical_states.color VARCHAR(20) NULL` (hex, ej. `#147d4a`).
- `CatalogRepository::TABLES['physical_states']` ahora incluye `color`.
- `templates/pages/catalogs/physical_states.php` pasó de tarjetas estáticas
  a un catálogo editable (modal crear/editar, reutilizando las rutas
  genéricas `POST`/`PUT /api/catalogs/{table}` que ya existían). Sigue sin
  permitir activar/desactivar (`CatalogRepository::setActive()` lo rechaza
  explícitamente para `physical_states`/`movement_types`; no se tocó esa
  restricción).

Backfill: se asignó un color por defecto a los 4 estados semilla
(Bueno=verde éxito, Regular=ámbar advertencia, Deteriorado=naranja marca,
Inservible=rojo peligro — mismos tokens que ya usan los badges `.status`
del resto del sistema) y un gris neutro a cualquier fila sin color.

## v5 — Migración de motor: MySQL → PostgreSQL

Fecha: 2026-08-09
Archivos: `database/schema.postgres.sql`, `database/seed.postgres.sql`, `database/migrate.php`

Motivo: preparar el despliegue en Hexper Ops/Dokploy, cuyo PostgreSQL
automático inyecta `DATABASE_URL` (no ofrece MySQL). `src/Core/Database.php`
pasó a conectar únicamente por `pdo_pgsql`.

`database/schema.postgres.sql` es el esquema completo consolidado (incluye
lo de v1, v2, v3 y v4 ya integrado, no como migraciones separadas) para que
un despliegue nuevo se levante con una sola importación. `database/migrate.php`
lo aplica junto con `seed.postgres.sql` de forma idempotente en cada arranque
del contenedor (`docker-entrypoint.sh`), antes de aceptar tráfico.

Cambios de dialecto relevantes (documentados aquí porque afectan cómo se
debe escribir SQL nuevo en este proyecto de ahora en adelante):
- `AUTO_INCREMENT` → `SERIAL`/`BIGSERIAL`.
- `TINYINT(1)` (booleanos) → `SMALLINT` (se mantuvo, en vez de `BOOLEAN`,
  para no tener que auditar cada comparación `= 1`/`= 0` del código PHP
  existente ni el manejo de tipos que hace PDO al leer la columna).
- `ENUM(...)` → `VARCHAR` + `CHECK (col IN (...))`.
- `ON UPDATE CURRENT_TIMESTAMP` → trigger `set_updated_at()` (no existe
  equivalente declarativo en PostgreSQL).
- Comillas dobles para literales de texto (`= "ACTIVO"`) → comillas simples
  (en PostgreSQL las dobles son identificadores, no strings).
- `SUM(condición_booleana)` → `SUM(CASE WHEN condición THEN 1 ELSE 0 END)`
  (PostgreSQL no convierte booleano a entero implícitamente en `SUM`).
- `CURDATE()`/`DATE_ADD(CURDATE(), INTERVAL n DAY)` → `CURRENT_DATE`/
  `CURRENT_DATE + make_interval(days => n)`.
- `INSERT ... ON DUPLICATE KEY UPDATE` → `INSERT ... ON CONFLICT (...) DO UPDATE SET ... EXCLUDED...`.
- `PDO::lastInsertId()` (requiere nombre de secuencia en PostgreSQL) →
  todos los `INSERT` que necesitaban el id generado se reescribieron con
  `RETURNING id` + `fetchColumn()`.
- `categories.nombre` ganó una restricción `UNIQUE` que no tenía en MySQL:
  el `seed.sql` original dependía de `ON DUPLICATE KEY UPDATE` para no
  duplicar categorías al reimportar, pero sin una clave única en `nombre`
  eso nunca se disparaba ni en MySQL; como el seed ahora se reaplica en
  cada despliegue (ver arriba), se agregó la restricción para que sea
  realmente idempotente.

`database/schema.sql`, `database/seed.sql` y `database/migrations/001-003`
(dialecto MySQL) se dejan intactos como historial — ya no los usa la
aplicación.

## v6 — Cuentas de prueba (una por rol) en el arranque del contenedor

Fecha: 2026-08-09
Archivos: `src/Config/demo_credentials.php`, `database/bootstrap_demo_users.php`,
`templates/pages/auth/login.php` (bloque `auth-devbox`)

Motivo: en un despliegue gestionado por Dokploy no siempre hay acceso a una
shell dentro del contenedor, así que no se puede depender de ejecutar
`database/create_admin.php` a mano después del primer despliegue (paso 3 de
"Instalación" arriba, pensado para desarrollo local). Además, el equipo pidió
que las credenciales de prueba fueran fijas y visibles directamente en la
pantalla de login, con botón de copiado/autorrelleno, para agilizar las
pruebas de este despliegue con los 3 roles del sistema.

`src/Config/demo_credentials.php` es la fuente única de esas credenciales
(un registro por rol: ADMIN/OPERATIVO/CONSULTA). La usan dos lugares que antes
podían desincronizarse si se editaban por separado:

- `database/bootstrap_demo_users.php` — crea/actualiza los 3 usuarios reales
  en la base de datos (`ON CONFLICT (email) DO UPDATE`, así que reafirma la
  contraseña conocida en cada arranque). Corre en `docker-entrypoint.sh`
  después de `migrate.php`.
- `templates/pages/auth/login.php` — la caja "Credenciales de prueba" del
  login, con copiado al portapapeles por campo y un botón "Rellenar
  formulario con estos datos" por rol, más una nota fija indicando que estas
  cuentas se eliminarán al terminar las pruebas.

**Importante — esto es intencionalmente temporal.** Antes de un despliegue
real hay que quitar los tres: `src/Config/demo_credentials.php`,
`database/bootstrap_demo_users.php` (y su llamada en `docker-entrypoint.sh`)
y el bloque `auth-devbox` de `login.php`; y crear el/los administradores
reales con `database/create_admin.php` o un flujo equivalente que no exponga
la contraseña en la interfaz.

## v7 — Datos de muestra para evaluar el sistema

Fecha: 2026-08-09
Archivo: `database/seed_sample_data.php`

Motivo: se pidió explícitamente un conjunto pequeño de datos de prueba
(pocos, sencillos) para poder evaluar el comportamiento del sistema en este
despliegue sin arrancar de una base vacía. Antes de esto, `seed.postgres.sql`
seguía a propósito sin bienes/préstamos de ejemplo (ver v1, motivo original:
evitar datos ficticios de negocio en el esquema base) — este script separado
respeta esa decisión (no toca `seed.postgres.sql`) y solo añade los datos de
muestra en el contenedor de este despliegue de pruebas.

Carga, en una sola transacción: 2 ubicaciones, 2 responsables, 1 marca con 2
modelos, 4 bienes (`BP-0001`..`BP-0004` — uno disponible, uno prestado, uno
en estado "Regular", uno desincorporado) y 1 préstamo activo (`PR-DEMO-0001`)
sobre el bien prestado. Usa al usuario ADMIN de prueba (`demo_credentials.php`)
como creador, por eso corre en `docker-entrypoint.sh` **después** de
`bootstrap_demo_users.php`.

Idempotencia: se guarda contra `SELECT COUNT(*) FROM assets` — si ya hay
algún bien (de muestra o real), no hace nada. Esto significa que en cuanto
se cargue el primer bien real, el script queda inerte para siempre sin
necesidad de quitarlo explícitamente, aunque igual se recomienda remover la
llamada en `docker-entrypoint.sh` antes de producción (ver v6).

## v8 — Dos bugs de la migración a PostgreSQL que rompían la búsqueda

Fecha: 2026-08-09

Al probar la migración a PostgreSQL (v5) aparecieron dos regresiones reales
que no se habían detectado antes por falta de un Postgres para probar en
ese momento:

1. **Buscador global (barra superior) totalmente caído.**
   `SearchService::global()` tenía `CONCAT("Versión ", version)` para armar
   el subtítulo de los documentos. En MySQL las comillas dobles alrededor de
   un literal funcionan como string; en PostgreSQL son un identificador (de
   columna), así que esa consulta lanzaba una excepción de PDO en **cada**
   búsqueda. `SearchController::global()` no la capturaba, así que
   `GET /api/search` fallaba siempre, y `search.js` mostraba "No fue posible
   buscar en este momento." para cualquier término. Arreglado: comillas
   simples en el `CONCAT`, y `SearchController` ahora captura la excepción
   (por si vuelve a pasar algo similar) en vez de dejar caer toda la
   búsqueda con un error sin manejar.

2. **Casi ninguna búsqueda por texto encontraba resultados.**
   Todo el código usaba `LIKE`, que en MySQL (con la collation utf8mb4 por
   defecto de este proyecto) es case-insensitive, pero en PostgreSQL es
   case-sensitive. Buscar "laptop" ya no encontraba "Laptop". Se cambiaron
   todos los `LIKE` de búsquedas por texto de usuario (buscador global,
   inventario, préstamos, catálogos, usuarios, auditoría, jornadas de
   verificación, reportes) a `ILIKE`.

3. **El buscador de la barra superior dejaba de responder por completo en
   páginas con modales que hicieron un refresco en vivo.** No es un bug de
   PostgreSQL sino uno preexistente en `modal.js`: `initModals()` ataba los
   listeners de cierre (botón "×"/"Cancelar" y clic en el backdrop)
   directamente a los nodos del DOM, una sola vez, al cargar la página. Un
   refresco en vivo (`refreshPageContent()`, p. ej. tras reasignar un bien)
   reemplaza esos nodos por otros nuevos sin listeners: el modal quedaba sin
   forma de cerrarse, y su backdrop —a pantalla completa y por encima de la
   barra superior— seguía capturando todos los clics de la página,
   incluyendo el buscador (parecía que ni dejaba escribir). `drawer.js` ya
   había resuelto el mismo problema con delegación de eventos a nivel
   `document`; se aplicó el mismo patrón en `modal.js`.

## Cómo agregar una migración futura

1. Crear `database/migrations/NNN_descripcion.sql` con los `ALTER TABLE`
   necesarios (no editar `schema.sql` retroactivamente en producción).
2. Documentar aquí: fecha, motivo, tablas afectadas, y si requiere
   backfill de datos.
3. Si cambia el significado de una columna ya usada por un `Repository`,
   actualizar el repositorio y el servicio correspondiente en el mismo commit.
