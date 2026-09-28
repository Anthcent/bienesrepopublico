# Cambios UX respecto al mockup

## Auditoría de paridad 2026-08-07 (tercera pasada — topbar, alertas, feature-card)

- **Bug real confirmado por CSS**: el botón "Cerrar sesión" que agregué al
  topbar en la ronda anterior ensanchó `.topbar-actions`, y el popover de
  "Acción rápida" usaba un offset fijo (`right: 145px`) calculado para el
  layout original del mockup — quedó desalineado. Corregido: el
  quick-action y el menú de usuario ahora tienen su propio contenedor
  `position: relative` (igual que ya hacía el buscador global), así que el
  popover siempre se posiciona `right: 0` respecto a su propio botón, sin
  importar cuántos elementos haya alrededor.
- **Menú de usuario real**: el avatar del topbar no abría nada (solo
  visual). Ahora es un botón con popover propio (Panel principal / Cerrar
  sesión), consistente con el ícono de flecha que ya tenía.
- **Logout accesible en todos los tamaños de pantalla**: a <640px el
  mockup oculta `.top-user`, lo que dejaba el sistema sin forma de cerrar
  sesión en móvil. Se agregó el mismo menú (con logout) al pie del sidebar,
  que sigue siendo accesible en cualquier ancho vía el drawer móvil.
- **Centro de notificaciones sin pestañas**: el mockup tiene pestañas
  "Nuevas / Préstamos / Sistema" con contador; mi versión solo tenía una
  lista plana. Agregadas las 3 pestañas con filtrado en cliente por tipo de
  notificación (`loan_alert`/`return_damage` → Préstamos; el resto →
  Sistema).
- **Card azul del dashboard sin elemento gráfico**: el mockup usa formas
  CSS decorativas (edificio/laptop/caja) en el lado derecho; se había
  omitido por completo. A solicitud del usuario, se agregó una marca de
  agua tipográfica "DEM" en su lugar (mismo tratamiento visual: semi-
  transparente, alineado a la derecha, `overflow:hidden` para que nunca
  desborde la card en ningún tamaño de pantalla).

## Auditoría de paridad 2026-08-07 (segunda pasada)

El usuario reportó que el dashboard y otras secciones no seguían fielmente
la guía. Auditoría módulo por módulo contra `02_MOCKUP` y `PLAN_MAESTRO_V4`,
con estos hallazgos y correcciones:

- **Bug real de IDs duplicados**: `id="quickLoan"` e `id="openAssetWizard"`
  existían dos veces en el DOM (topbar + página), por lo que
  `querySelector` enganchaba el elemento equivocado y los botones visibles
  del dashboard no respondían al clic. Corregido usando `data-action` en el
  popover del topbar.
- **Dashboard → "Bienes recientes"**: le faltaban view switcher, buscador
  instantáneo y filter chips que sí tiene la sección de inventario completa.
  Reconstruido para incluir las 3 vistas (tabla/tarjetas/compacta).
- **Inventario**: faltaba el chip "Deteriorados" (solo había 3 de 4 chips
  del mockup). Agregado, resuelto server-side por `physical_state_id`.
- **Ficha del bien** (`/inventario/{id}`): no tenía pestañas reales (todo en
  un solo scroll largo), no mostraba foto, no mostraba "préstamo activo" en
  el header, y no existía la pestaña "Préstamos" que exige el Plan Maestro
  §22. Reconstruida con pestañas (Resumen/Movimientos/Préstamos/Documentos/
  Historial), header con foto/placeholder y enlace directo al préstamo
  activo, y detalle técnico (antes/después) expandible en Historial —
  cumple M11 (auditoría técnica) que antes solo mostraba el resumen humano.
- **Preview de documentos**: el Plan Maestro §21 exige "Preview en modal.
  No descargar solo para revisar." — se abría en pestaña nueva. Corregido:
  ahora hay un modal global con iframe (`document_preview_modal`), botones
  de descargar/imprimir, reutilizado desde la ficha del bien y desde la
  ficha del préstamo.
- **Usuarios**: Plan Maestro M25 exige vistas "Tarjetas | Tabla"; solo había
  tabla. Agregada vista de tarjetas con avatar y buscador instantáneo propio.
- **Préstamos (listado)**: no tenía buscador aunque el backend ya lo
  soportaba (`filters['q']`); agregado. También se agregó un indicador de
  días para vencer/vencido junto a la fecha de vencimiento en la tabla.
- **Catálogo de ubicaciones**: Plan Maestro §23 pide cards "con imagen"; el
  campo `imagen_url` existía en BD pero no era editable ni se mostraba.
  Agregado al formulario y a la card.
- **Búsqueda global**: los resultados de todos los grupos usaban el mismo
  ícono azul; ahora cada grupo (bienes/préstamos/responsables/ubicaciones/
  usuarios/documentos) tiene su propio color e ícono, como en el mockup.
- **Íconos de despliegue** (`⌄`, `⋯` como texto) reemplazados por SVG
  lineales consistentes con el resto del sistema.

Todo verificado con un smoke test real (login + recorrido de todas las
páginas + revisión de errores PHP + revisión de IDs duplicados) contra el
servidor PHP/MySQL real, no solo revisión de código.

El mockup (`02_MOCKUP/`) se usó como **especificación visual y de
interacción**, no como código productivo (tal como indica su propio
`README_MOCKUP.md`). Reutilizado explícitamente permitido por
`INSTRUCCIONES_IA.md`: estructura de layout, tokens de color, sidebar,
drawers, toasts, wizard, view switcher, quick actions, cards, estados.

## Lo que se conservó tal cual

- Paleta y tokens (`navy-900`, `orange-700`, estados success/warning/danger, etc.)
- Estructura de sidebar (colapsable, grupos desplegables, tooltip vía título)
- Topbar con búsqueda global (Ctrl/Cmd+K), acción rápida, notificaciones
- Patrón de drawer (filtros, notificaciones, vista rápida de bien)
- Patrón de modal + wizard de 4 pasos para incorporación
- Toast en esquina inferior derecha, máximo 3 visibles, sin `alert()`/`confirm()`
- View switcher tabla/tarjetas/compacta con persistencia en `localStorage`

## Lo que se corrigió o cambió respecto al mockup

- **Datos**: el mockup trae datos ficticios ("María González", "BP-00001",
  totales inventados). Ninguno de esos datos se llevó al sistema real; todas
  las vistas leen de MySQL y muestran estados vacíos reales cuando no hay
  datos (`empty_state` component).
- **JS de simulación → lógica real**: el `app.js` del mockup simula
  interacciones con `setTimeout` y arreglos en memoria. Se reemplazó
  íntegramente por llamadas a la API real (`/api/...`) con validación de
  servidor (número/serial únicos, disponibilidad de bienes, reglas de
  estado del bien, etc.).
- **Wizard de préstamo**: el mockup permite un solo bien de ejemplo
  precargado. El sistema real soporta selección de **uno o varios bienes**
  mediante búsqueda instantánea de bienes disponibles (M08, préstamo
  múltiple), como exige el Plan Maestro §14.
- **Confirmaciones críticas**: se añadió un modal de confirmación propio
  (`#confirmModal`, `confirmAction()` en `core/modal.js`) para
  desincorporar, readmitir y anular préstamos — el mockup no modelaba estas
  acciones destructivas.
- **Ficha completa del bien**: el mockup solo mostraba un drawer de "vista
  rápida". Se agregó la página `/inventario/{id}` con resumen, movimientos,
  documentos y auditoría, enlazada desde "Abrir ficha completa".

## Pendiente de refinar (ver `PENDIENTES.md`)

- Filtros avanzados del drawer todavía aplican por navegación de formulario
  (GET), no aún con "vistas guardadas" persistidas por usuario.
- El wizard de incorporación no ofrece aún "Guardar borrador" persistente
  ni "Guardar y generar etiqueta" (quedaron fuera del alcance de este build).
