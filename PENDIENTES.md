# Pendientes

Este build entrega el **núcleo funcional completo** (M01–M14 del Plan
Maestro) con arquitectura por capas, transacciones atómicas, auditoría y
notificaciones persistentes reales. Lo que sigue no estaba terminado al
cierre de esta sesión.

## Bloqueante antes de producción

- [x] **Smoke test manual end-to-end** ejecutado el 2026-08-07 contra
      PHP 8.2 + MariaDB 10.4 reales (XAMPP). Encontró y corrigió 2 bugs
      reales de SQL (ver `PRUEBAS_EJECUTADAS.md`). Todos los flujos
      críticos (incorporación, préstamo, devolución con deterioro,
      desincorporación/readmisión, búsquedas) verificados exitosamente.
- [ ] **Suite PHPUnit automatizada** — el smoke test fue manual con
      `curl`; falta formalizar los casos por servicio listados abajo.
- [ ] Cambiar la contraseña del administrador y revisar `src/Config/config.php`
      para producción (mover a variables de entorno reales, no valores por defecto).
- [ ] Servir la aplicación por HTTPS y activar `cookie_secure` en `Session::start()`.

## Funcional — corto plazo

- [ ] Pantalla de cambio de contraseña propia (M01) — hoy solo existe login/logout.
- [ ] Expiración de sesión configurable (hoy usa la sesión nativa de PHP sin timeout propio).
- [ ] Modelos (M04, submódulo de "Marcas/Modelos") — el catálogo `models`
      existe en BD y `CatalogRepository::modelsByBrand()`/`createModel()`
      están listos, pero falta la página `catalogs/models.php` y el
      `<select>` dependiente de marca en el wizard (paso 2).
- [ ] Filtros avanzados con "vistas guardadas" (`saved_views` ya existe en
      BD, falta exponerlo en UI).
- [ ] Wizard de incorporación: "Guardar borrador", "Guardar y registrar
      similar", "Guardar y generar etiqueta" (hoy solo hace "Incorporar bien").
- [ ] Imagen real del bien (`imagen_url` existe en el modelo; falta subida
      de archivos — hoy solo acepta una URL).
- [ ] Reportes: faltan las plantillas "Activos por estado físico completo
      con gráfico" y exportación a Excel/CSV (hoy: HTML imprimible/"Guardar como PDF").

## Técnico

- [ ] **Generación de PDF real en servidor.** Hoy los documentos son HTML
      versionado e imprimible (ver `DECISIONES_TECNICAS.md`). Para PDF
      nativo en servidor, agregar `dompdf/dompdf` vía Composer y adaptar
      `DocumentService` para renderizar a PDF además de HTML.
- [ ] **Suite de pruebas automatizadas (PHPUnit).** No existe todavía;
      ver el plan de casos por servicio en `PRUEBAS_EJECUTADAS.md`.
- [ ] **Rate limiting / bloqueo de fuerza bruta en login** (hoy no hay
      límite de intentos).
- [ ] **Migraciones versionadas** (hoy solo hay `schema.sql` monolítico;
      ver `MIGRACIONES_REALIZADAS.md` para el formato a seguir en adelante).
- [ ] Accesibilidad WCAG AA: revisar contraste de `.status.warning` sobre
      fondo claro y agregar `aria-live` al `toast-stack` para lectores de pantalla.
- [ ] QA responsive en los breakpoints del checklist (1920/1440/1280/1024/768/390px)
      — el CSS ya trae los `@media queries` del mockup, pero falta la
      verificación visual real en cada uno.

## Fuera de alcance de esta sesión (documentado, no iniciado)

- Integraciones externas (correo transaccional para notificaciones,
  SSO/LDAP institucional).
- Internacionalización (el sistema está en español fijo, como el mockup).
- Backups automatizados de base de datos.
