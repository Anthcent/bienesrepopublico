# Pruebas ejecutadas

## Entorno de pruebas real (2026-08-07)

Se instaló XAMPP (PHP 8.2.12 + MariaDB 10.4.32) en `C:\xampp` y se ejecutó
un smoke test real de extremo a extremo contra el servidor embebido de PHP
(`php -S localhost:8000 -t public`) y una base `bienes_publicos` importada
desde `database/schema.sql` + `database/seed.sql`, con el admin creado vía
`database/create_admin.php`. Resultados:

### Bugs encontrados y corregidos durante esta corrida

1. **`CatalogRepository::search()`** asumía columna `activo` en todas las
   tablas de catálogo. `physical_states` y `movement_types` no la tienen →
   `PDOException: Column not found`. Corregido: la cláusula `activo = 1`
   ahora es condicional según la tabla. También se protegió
   `CatalogRepository::setActive()` para esas dos tablas (lanza un error
   de validación en vez de un `PDOException` sin capturar).
2. **Parámetros nombrados repetidos en la misma consulta**
   (`WHERE campo LIKE :t OR otro_campo LIKE :t`) — con `PDO::ATTR_EMULATE_PREPARES`
   en `false` (prepares nativos), MySQL/MariaDB **no permite** reutilizar el
   mismo marcador nombrado dos veces en una consulta → `PDOException:
   SQLSTATE[HY093]: Invalid parameter number`. Afectaba:
   `SearchService::global()`, `AssetRepository::search()`,
   `AssetRepository::availableForLoan()`, `AssetRepository::buildFilters()`
   (filtro `q` del inventario), `LoanRepository::paginate()` (filtro `q`) y
   `UserRepository::paginate()` (filtro `q`). Corregido usando marcadores
   `?` posicionales (cuando la consulta no mezcla con parámetros nombrados)
   o marcadores nombrados distintos por ocurrencia (`:q1`, `:q2`, `:q3`)
   cuando sí se mezclaban con otros filtros nombrados.

### Flujos verificados exitosamente tras las correcciones

- Login/logout, protección de rutas sin sesión (`AuthMiddleware` → redirect 302).
- Todas las páginas principales autenticadas devuelven 200: dashboard,
  inventario, préstamos, usuarios, auditoría, catálogos, reportes.
- Creación de catálogos (ubicación, responsable) vía API.
- **Incorporación de bien** end-to-end: crea el bien + movimiento
  `INCORPORACION` + registro de auditoría + documento HTML versionado +
  notificación de información incompleta (verificado leyendo la respuesta
  y `/api/notifications`).
- Validación de duplicados: número de bien repetido rechazado en
  `check-number` y en el `POST /api/assets`.
- **Préstamo**: registrar préstamo pasa el bien a `PRESTADO`; un segundo
  intento de préstamo sobre el mismo bien es rechazado por
  `LoanService::create()`.
- **Devolución con deterioro**: devolver con un estado físico peor al de
  salida y **sin** observación es rechazado (`LoanService::processReturn`);
  con observación se acepta, el préstamo pasa a `DEVUELTO`, el bien vuelve
  a `DISPONIBLE`, y se genera una notificación de deterioro real.
- **Desincorporación/readmisión**: ciclo completo verificado, con
  movimientos y auditoría asociados.
- Búsqueda global (`/api/search`), búsqueda de bienes y de bienes
  disponibles para préstamo, filtros `q` en inventario/préstamos/usuarios.
- Preview de documento generado y preview de reporte (plantilla "activos")
  con estimación de registros.

### Limitación restante

No se escribió todavía una suite **PHPUnit** automatizada (los casos de
abajo siguen siendo el plan recomendado a formalizar); lo ejecutado fue un
smoke test manual con `curl`, pero fue contra la base de datos real, no
simulado.

## Verificaciones estáticas realizadas manualmente

- Balance de llaves `{ }` verificado en los 100% de los archivos `.php` de
  `src/` (script de conteo, sin discrepancias).
- Verificación de apertura/cierre de `<?php` en todas las plantillas de
  `templates/` (discrepancias esperadas y explicadas: archivos PHP puros
  que terminan en modo PHP sin `?>` final, y archivos de componentes que
  son HTML puro sin lógica).
- Revisión manual de cada `Service` para confirmar que las operaciones
  críticas (incorporar, reasignar, desincorporar, readmitir, crear
  préstamo, devolver, extender, anular) están envueltas en
  `beginTransaction()`/`commit()`/`rollBack()`.
- Revisión manual de que ninguna consulta en `Repositories/` concatena
  variables directamente en el SQL (100% `PDO::prepare` con parámetros
  nombrados).

## Plan de pruebas recomendado (a ejecutar en un entorno con PHP)

### Smoke test manual (obligatorio antes de usar en producción)

1. Importar `schema.sql` + `seed.sql`, crear admin con `create_admin.php`.
2. Iniciar sesión con el admin.
3. Crear una ubicación, un responsable y verificar que aparecen en el wizard.
4. Incorporar un bien vía wizard → verificar que aparece en `/inventario`,
   que se generó un movimiento `INCORPORACION`, un registro de auditoría y
   un documento previsualizable.
5. Reasignar el bien → verificar `asset_movements` y auditoría.
6. Registrar un préstamo del bien → verificar que `disponibilidad` pasa a
   `PRESTADO` y que no puede desincorporarse mientras está prestado.
7. Registrar devolución con estado físico peor al de salida sin observación
   → debe rechazar la operación (regla de negocio en `LoanService`).
8. Registrar devolución con observación → debe aceptar, generar
   notificación de deterioro y volver el bien a `DISPONIBLE`.
9. Desincorporar el bien → verificar que ya no aparece como disponible para
   préstamo, y que readmitirlo lo vuelve a activar.
10. Verificar el centro de notificaciones y que un préstamo con vencimiento
    cercano genera alerta al entrar al dashboard o al módulo de préstamos.

### Casos por operación crítica (recomendado formalizar en PHPUnit)

Cada `Service` en `src/Services/` debe tener, como mínimo:

- Caso exitoso.
- Falta de permiso (vía `PermissionMiddleware`).
- Estado inválido (p. ej. prestar un bien ya prestado).
- Duplicado (número/serial de bien repetido).
- Rollback ante fallo a mitad de transacción (simulable forzando una
  excepción en un paso intermedio).
- Verificación de que se escribió auditoría.
- Verificación de que se generó notificación cuando aplica.
- Verificación de documento generado cuando aplica.

Ningún módulo debe marcarse "terminado" en `01_PLAN_MAESTRO/CHECKLIST_IMPLEMENTACION.md`
hasta que estos casos existan y pasen contra una base de datos real.
