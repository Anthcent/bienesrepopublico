# Auditoría de estado actual vs. Plan Maestro V5

**Fecha:** 2026-08-07
**Alcance:** Comparación del sistema real (`adolfo/`, PHP + MySQL) contra `PAQUETE_V5_SISTEMA_BIENES_PUBLICOS` (Plan Maestro V5, Guía Responsive, Mockup V5).
**Nota de arquitectura:** el Plan V5 fue redactado en términos de "Flask + Jinja2 + Tailwind" pero el sistema real es **PHP puro (sin framework) + MySQL**, arquitectura por capas Ruta→Controller→Service→Repository. Por instrucción explícita del usuario, **no se cambia el stack**: todo lo de V5 se adapta a PHP/JS vanilla tal como ya está construido. El mockup V5 (`02_MOCKUP_COMPLETO/`) es HTML/CSS/JS estático de referencia visual — sus datos son ficticios y su JS no tiene lógica productiva.

---

## 1. Módulos encontrados (inventario real)

### Rutas
- `routes/web.php`: login/logout, dashboard, inventario (index/show), préstamos (index/show), catálogos genéricos, usuarios, reportes (index/preview), auditoría, documentos (preview/download).
- `routes/api.php`: búsqueda global, bienes (search/check-number/check-serial/check-similarity/store/update), movimientos (reasignar/desincorporar/readmitir), catálogos (search/models-by-brand/store/update/toggle), préstamos (store/return/extend/cancel), notificaciones (index/read/read-all/resolve), reportes (estimate), usuarios (store/update/toggle).
- **No existe ninguna ruta de verificación/jornada.**

### Repositories / Services / Controllers
Arquitectura limpia y consistente ya implementada:
`AssetRepository/Service/Controller`, `MovementRepository` + `AssetMovementService/MovementController`, `LoanRepository/Service/Controller`, `NotificationRepository/Service/Controller`, `DocumentRepository/Service/Controller`, `AuditRepository/Service/Controller`, `CatalogRepository/Controller` (genérico multi-tabla), `UserRepository/Service*/Controller`, `ReportService/Controller`, `SearchService/Controller`, `PermissionService` + `PermissionMiddleware`.

Patrón repetido en toda operación de negocio: `Command` (DTO) → `Service` (transacción PDO + `ServiceResult`) → `Repository` (PDO) → efectos secundarios (movimiento, documento, auditoría, notificación).

### Templates
- Layouts: `app.php` (shell autenticado), `auth.php`, `print.php`.
- Componentes globales ya montados una sola vez en el shell: `sidebar`, `topbar`, `filter_drawer`, `notification_drawer`, `asset_drawer`, `asset_wizard` (wizard de incorporación de 4 pasos, ya funcional), `loan_modal`, `document_preview_modal`, `modal_confirm` (reemplaza `confirm()`), `toast_stack`, `empty_state`, `pagination`.
- Páginas por módulo: dashboard, assets (index/show con pestañas), loans (index/show), catalogs (genérico + 5 wrappers), users, audit, reports (index/preview), documents (4 plantillas fuente), auth/login, errores 403/404.

### JS
- `core/`: `dom.js` (helpers+fetch wrapper), `drawer.js` (registro genérico), `modal.js` (registro genérico + `confirmAction`), `search.js` (**Ctrl/Cmd+K ya implementado**, debounce 320ms, agrupado por categoría), `sidebar.js` (colapsar/expandir persistido en localStorage), `toast.js` (**sistema de toasts ya implementado**, máx. 3 visibles), `viewSwitcher.js` (tabla/tarjetas/compacta, persistido en localStorage).
- `modules/`: `assetWizard.js`, `loanWizard.js`, `notifications.js` (polling 60s), `inventory.js` (filtro local + drawer quick view), `documentPreview.js` (iframe + imprimir).
- **No existe `window.alert()` ni `window.confirm()` en el código productivo** — ya se sigue la regla prohibida del plan V5.
- **No existe componente "dock de operaciones"** — lo más cercano es el popover "Acción rápida" del topbar (4 acciones fijas) y el grid de acciones contextuales en la ficha del bien.

### CSS
- `tokens.css`: paleta navy/orange, variables `--sidebar-w-expanded: 258px` / `--sidebar-w-collapsed: 74px`, radios, sombras — **ya existen variables de sidebar**, pero **no existe `--sidebar-width` unificada usada por `margin-inline-start`** tal como pide la Guía Responsive (hoy son dos variables separadas expandido/colapsado referenciadas directamente).
- `app.css` (473 líneas): shell, topbar, `.page{padding:30px 30px 55px;max-width:1600px;margin:auto}` (contenedor **fijo por padding**, no usa la fórmula `width:min(100% - 32px, 1500px)` de la guía), botones, tabla inteligente, vistas tabla/tarjetas/compacta, drawers/modales, wizard, loan modal, toasts, catálogos, login.
  - Breakpoints actuales: `1180px`, `900px`, `640px` — **incompletos frente a los 10 breakpoints obligatorios de la guía** (1920/1600/1440/1366/1280/1024/768/430/390/360).
- `print.css` (17 líneas): básico, sin la estructura de hoja de verificación con checks que pide V5 (`paper-item`, `paper-grid`, `break-inside:avoid`, etc. — eso está en el mockup, no en el sistema real).

### Base de datos (`database/schema.sql`)
Tablas existentes relevantes: `roles`, `permissions`, `role_permissions`, `users`, `user_preferences` (**declarada pero sin uso en código** — la vista/densidad viven en localStorage), `saved_views` (**declarada pero sin uso en código, sin Repository, sin ruta, sin UI**), `locations`, `responsibles`, `categories`, `brands`, `models`, `physical_states`, `movement_types`, `assets`, `asset_movements` (columna `anulado` declarada pero nunca usada), `loans`, `loan_details`, `loan_extensions`, `documents` (columna `estado` ENUM VIGENTE/ANULADO declarada pero nunca puesta en ANULADO), `audit_log`, `notifications` (con `clave_unica` UNIQUE para upsert idempotente).

**Confirmado por grep exhaustivo: cero ocurrencias de "verifica"/"jornada" en todo el repo.** El módulo de Jornada de Verificación Patrimonial no existe en ningún nivel (BD, backend, frontend).

### Permisos
RBAC simple por código de permiso string. Roles: `ADMIN`, `OPERATIVO`, `CONSULTA`. 16 permisos en `role_permissions`. Validación server-side real vía `PermissionMiddleware::require()` en cada ruta sensible — **no es solo cosmético**, de verdad bloquea. La UI solo oculta Usuarios/Auditoría a no-ADMIN en el sidebar; el resto de botones se muestran siempre y el backend es quien rechaza.

### Estados del bien
**Ya separados correctamente en 3 dimensiones independientes**, tal como exige el Plan V5 §11:
- Administrativo: `assets.estado_administrativo` ENUM(ACTIVO, DESINCORPORADO)
- Disponibilidad: `assets.disponibilidad` ENUM(DISPONIBLE, PRESTADO)
- Físico: `assets.physical_state_id` → catálogo `physical_states` (BUENO/REGULAR/DETERIORADO/INSERVIBLE, con `orden` para detectar empeoramiento)

Las reglas de negocio (no puede prestarse si no está ACTIVO+DISPONIBLE; no se desincorpora si está PRESTADO; readmisión solo desde DESINCORPORADO) ya están implementadas en `AssetMovementService`/`LoanService`. **Este punto no requiere cambios de modelo, solo debe conservarse.**

---

## 2. Qué se conserva tal cual (bien implementado, no tocar sin necesidad)

1. Arquitectura por capas y patrón Command→Service transaccional→Repository→(movimiento+documento+auditoría+notificación).
2. Separación de estados del bien (administrativo/disponibilidad/físico) y sus reglas de negocio.
3. Dominio de préstamos completo (múltiple, devolución total/parcial, extensión, cancelación, detección de empeoramiento físico con observación obligatoria).
4. Sistema de notificaciones persistentes en BD con idempotencia (`clave_unica`) y sync throttled — ya cumple la prohibición de `alert()`/`confirm()`/dependencia de cron.
5. Sistema de auditoría humano+JSON en cada operación.
6. Documentos HTML versionados con preview en iframe/modal (no obliga a descargar).
7. Wizard de incorporación de 4 pasos, con validación async de duplicados y detección de similares — ya no es "formulario viejo por pasos", cumple la prohibición explícita.
8. Componentes globales reutilizables ya montados una sola vez (drawer/modal genéricos, toast) — el patrón correcto a replicar para Verificación.
9. Permisos server-side reales por ruta.
10. Búsqueda global con Ctrl/Cmd+K y debounce ya implementada (aunque incompleta, ver hallazgos).

---

## 3. Bugs / huecos responsive detectados

- `.page` usa `max-width:1600px; margin:auto` con padding fijo `30px`, no la fórmula `width:min(100% - 32px, 1500px)` de la guía → en viewports angostos (360–390px) el padding fijo puede no dejar suficiente margen y en anchos intermedios el comportamiento difiere del estándar pedido.
- Solo 3 breakpoints (1180/900/640) contra los 10 exigidos — falta verificar/ajustar específicamente 1920, 1600, 1366, 1280, 1024, 430, 390, 360.
- No hay dock de operaciones — por tanto ninguna de sus reglas responsive (`position:fixed;left:50%;transform:translateX(-50%);width:min(calc(100% - 32px),720px)`, padding inferior reservado en el contenido) aplica todavía; hay que verificar que `.main{padding-bottom:...}` exista cuando se agregue el dock para no tapar contenido (el mockup ya reserva `padding-bottom:104px` en `.main`).
- `print.css` real es mínimo (17 líneas); el mockup trae toda la maquetación de hoja imprimible con `break-inside:avoid` y ocultamiento de sidebar/topbar/dock — eso falta construirlo en el sistema real para cualquier documento, no solo verificación.
- No se ha verificado (ni en esta sesión ni en la anterior) el render real en navegador en ninguna resolución — todo el trabajo previo fue validado por HTML/CSS servido vía curl, no visualmente.

---

## 4. Diferencias funcionales contra V5 (qué falta / qué completar)

| Área | Estado actual | Gap vs. V5 |
|---|---|---|
| Dock de operaciones | No existe | Construir componente global fijo inferior-centro con buscador + "Nueva operación" + alertas, siguiendo patrón `registerDrawer`/`registerModal` existente |
| Búsqueda global | Ctrl+K funcional, pero `GROUP_LINKS` en `search.js` solo resuelve navegación para bienes y préstamos | Completar enlaces para responsables/ubicaciones/usuarios/documentos; agregar jornadas cuando exista el módulo |
| Inventario | Tabla/tarjetas/compacta, chips, filtros drawer, quick view — ya implementado | Falta exponer "vistas guardadas" (tabla `saved_views` existe, sin Repository/ruta/UI); confirmar "última verificación" cuando exista el módulo de verificación |
| Incorporación | Wizard de 4 pasos con duplicados/similares | Falta "guardar borrador" y "guardar y registrar similar" (mencionados también en `PENDIENTES.md` previo) |
| Movimientos | Reasignar/desincorporar/readmitir con auditoría+documento+transacción | Cumple; sin diff visual "antes → después" explícito en UI en todos los casos — verificar en `assets/show.php` |
| Préstamos | Dominio completo | Cumple casi en su totalidad; revisar que devolución permita observación/imagen por bien individual (pendiente ya documentado en sesión anterior) |
| Notificaciones | Persistentes, con throttle, toast+drawer | Cumple |
| Documentos | Cards, preview, versiones, imprimir | Cumple a nivel funcional; falta CSS de impresión robusto (ver §3) |
| Reportes | 10 plantillas con estimación en vivo, incluida "Verificación/actualización" (agregada 2026-08-07, lee `verification_campaign_items`) | Cumple |
| Catálogos | CRUD genérico multi-tabla | Cumple |
| Usuarios | CRUD con vista tabla/tarjetas | Cumple |
| Auditoría | Feed cronológico con búsqueda | Cumple |
| **Jornada de Verificación Patrimonial** | **Construida y verificada end-to-end (2026-08-07): BD migrada en la base real, backend, wizard, hoja imprimible, captura, cierre transaccional con documento+auditoría** | Falta: enlazar en `search.js` (`GROUP_LINKS`/`GROUP_LABELS`) para que el buscador global (Ctrl/K) resuelva jornadas; falta ver en navegador real (solo validado por curl) |

---

## 5. Riesgos identificados

1. **Ninguno de los cambios anteriores debe tocar el modelo de `assets`** (ya cumple la separación de estados) — el nuevo módulo de verificación debe *leer y proponer actualizaciones* a `assets`/`locations`/`responsibles`/`physical_states`, nunca duplicar su lógica de transición de estado (reusar `AssetMovementService` donde corresponda, p.ej. si una jornada cambia la ubicación de un bien, eso debería generar un movimiento real vía el servicio existente, no un UPDATE directo).
2. **`user_preferences` y `saved_views` están "dormidas"** — cualquier feature nueva que toque preferencias/vistas debe decidir explícitamente si las activa (y migrar de localStorage) o las deja así; no activarlas a medias.
3. **No hay Composer ni PHPUnit** — cualquier dependencia nueva (ej. Dompdf) o suite de pruebas automatizada requiere introducir tooling nuevo; debe hacerse de forma aislada y documentada, sin romper el autoload manual existente.
4. **Cambiar `.page`/contenedor global y breakpoints afecta a TODAS las páginas simultáneamente** — es un cambio transversal de alto impacto visual; debe hacerse primero y validarse en el navegador real antes de construir nada nuevo encima (evita tener que rehacer CSS de un dock/módulo nuevo si el contenedor base cambia después).
5. **El dock de operaciones se solapa en el espacio inferior con el toast-stack** (ambos "position:fixed" en la zona baja) — el mockup ya resuelve esto (`toast-stack{bottom:86px}` cuando hay dock); replicar ese ajuste, no solo copiar el toast tal cual.
6. **Módulo de Verificación es grande**: modelos nuevos, snapshot, wizard de 5 pasos, hoja imprimible, captura secuencial, detección de conflictos (snapshot vs. BD vs. capturado), cierre transaccional. Es el mayor riesgo de regresión indirecta porque su "capturar cambios" puede terminar escribiendo en `assets`/`asset_movements` — debe construirse reusando `AssetMovementService`/`DocumentService`/`AuditService`/`NotificationService` existentes en vez de reimplementar transiciones de estado.
7. Sin acceso a navegador real en esta sesión salvo que el usuario lo habilite — la validación visual/responsive seguirá dependiendo de HTML/CSS servido + razonamiento, o de capturas que el usuario comparta.

---

## 6. Orden de refactor propuesto (adaptado del Plan V5 §32 a lo que realmente falta)

1. ~~Auditoría~~ (este documento).
2. ~~**Base responsive**~~ y ~~**Dock de operaciones**~~: ya existen (`templates/components/operation_dock.php`, `public/assets/js/core/dock.js`) — pendiente confirmar visualmente en navegador si cumplen al 100% la Guía Responsive (no reauditado en esta pasada).
3. ~~**Búsqueda global**~~ — cerrado 2026-08-07: `SearchService::global()` agrega grupo `jornadas` (tabla `verification_campaigns`), y `search.js` resuelve `GROUP_LABELS`/`GROUP_LINKS`/íconos para ese grupo. Probado con `/api/search?q=JV` contra datos reales.
4. **Huecos menores de inventario/incorporación/movimientos/préstamos** documentados en la tabla §4 (borrador, registro similar, devolución por bien individual) — siguen pendientes.
5. ~~**Jornada de Verificación Patrimonial**~~ — construida y verificada end-to-end el 2026-08-07 (BD real migrada, backend, wizard, hoja imprimible, captura, cierre transaccional con documento+auditoría; probado con curl autenticado: crear candidatos, cerrar jornada, documentos `verificacion_hoja`/`verificacion_cierre` generados, `audit_log` con `verification.create/capture/complete`).
6. ~~**Reportes: plantilla de verificación**~~ — agregada el 2026-08-07 (`ReportService::TEMPLATES['verificacion']`, lee `verification_campaign_items`), probada con estimate+preview reales.
7. Responsive final + accesibilidad + pruebas integrales de todo lo anterior — sigue pendiente, incluyendo confirmación visual real en navegador de todo lo construido en esta fase V5.

Cada fase se trabajará siguiendo el ciclo de la §"Cómo trabajar por fase" de `INSTRUCCIONES_IA_V5.md`: explicar cambio → listar archivos → backend → frontend → migración si aplica → pruebas → ejecutar pruebas → revisar roles → revisar responsive → revisar accesibilidad → informar resultado.
