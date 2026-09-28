# Decisiones técnicas

## Contexto de partida

El paquete `PAQUETE_TRABAJO_IA_BIENES_PUBLICOS/00_LEER_PRIMERO/README_PRIMERO.md`
está redactado para un escenario de **refactor** de un sistema Flask + Jinja2
existente. En este caso no existe un repositorio real previo: solo el
paquete de especificación. Por lo tanto:

- No se generó `AUDITORIA_ESTADO_ACTUAL.md` (no hay sistema previo que
  auditar); este documento cumple ese rol de "punto de partida" en su lugar.
- Se siguió como **fuente de verdad de reglas y arquitectura** el
  `PLAN_MAESTRO_V4.md`, y como **fuente de verdad visual** el mockup
  (`02_MOCKUP/`), tal como indica la regla de autoridad del paquete.
- El usuario pidió explícitamente **PHP + SQL** para el backend en lugar de
  Flask. Esa instrucción explícita del usuario tiene prioridad sobre la
  sugerencia por defecto de "mantener Flask" del paquete (que es una
  recomendación, no un requisito funcional).

## Backend: PHP sin framework, arquitectura por capas

Se optó por PHP "vanilla" con una arquitectura explícita en capas en vez de
un framework grande (Laravel/Symfony), porque:

- El Plan Maestro exige explícitamente rutas delgadas que delegan a
  `Service → Repository → DB` (§19), algo que se puede lograr sin framework.
- Evita una dependencia de Composer/paquetes externos para que el sistema
  corra en cualquier hosting PHP compartido típico (frecuente en el sector
  público) sin `composer install`.
- El autoload es un mapeador PSR-4 mínimo (`src/Support/autoload.php`).

Capas: `routes/*.php` → `Controllers` (delgados) → `Services` (reglas +
transacciones) → `Repositories` (SQL con `PDO` y prepared statements) → MySQL.

## Base de datos: MySQL + PDO, prepared statements en el 100% de las consultas

Ninguna consulta concatena entrada de usuario en el SQL. Todas usan
parámetros nombrados vía `PDO::prepare()`/`execute()`. Esto es la mitigación
estándar contra inyección SQL, que es explícitamente el riesgo que el
usuario nombró ("sqli php" → se interpretó como "SQL + PHP", y se construyó
con la premisa de que el SQL debe ser seguro por diseño).

## Frontend: sin framework SPA, JS modular con ES modules nativos

Siguiendo la prohibición explícita de `README_PRIMERO.md` §5 ("No migres a
React/Vue por defecto"), el frontend es HTML renderizado en servidor (PHP)
+ CSS propio (mismos tokens que el mockup) + JavaScript modular con
`<script type="module">` y `import/export` nativos del navegador (sin
bundler). Los componentes JS (`sidebar.js`, `drawer.js`, `modal.js`,
`toast.js`, `search.js`, `viewSwitcher.js`, `assetWizard.js`, `loanWizard.js`,
`notifications.js`) están separados por responsabilidad, según la
"Obligación de modularidad" del paquete.

## Documentos (M09): HTML versionado e imprimible en vez de un generador de PDF

No se agregó una librería de generación de PDF en servidor (p. ej. Dompdf)
para no introducir una dependencia de Composer en un proyecto que
deliberadamente no la requiere. En su lugar, `DocumentService` genera y
versiona documentos como HTML completo (con la hoja de estilos de
impresión `print.css`), previsualizables en una pestaña nueva e imprimibles
o exportables a PDF con "Guardar como PDF" del navegador. Esto cumple el
flujo funcional (generar → versionar → previsualizar → descargar/imprimir)
sin la dependencia. Ver `PENDIENTES.md` para la ruta de mejora con Dompdf.

## Notificaciones: sin cron obligatorio

`NotificationService::syncLoanAlertsThrottled()` se invoca en el dashboard y
en el módulo de préstamos, con throttling por sesión
(`config['loans']['throttle_sync_segundos']`), tal como exige el paquete.
Las claves únicas (`clave_unica`, columna `UNIQUE`) garantizan idempotencia
vía `INSERT ... ON DUPLICATE KEY UPDATE`.

## Seguridad de la contraseña administrador

El SQL semilla (`database/seed.sql`) **no** contiene un hash de contraseña
fabricado a mano. Se agregó `database/create_admin.php`, un script CLI que
usa `password_hash()` de PHP (bcrypt) para crear/actualizar el usuario
administrador con un hash válido y verificable.
