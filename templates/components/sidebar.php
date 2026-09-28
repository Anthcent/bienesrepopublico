<?php
/** @var array $actor */
/** @var string $initials */
/** @var array $identity */
use App\Core\View;
use App\Core\Request;
use App\Core\Csrf;

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$isActive = fn(string $prefix) => str_starts_with($path, $prefix) ? 'active' : '';
$isOpen = fn(string $prefix) => str_starts_with($path, $prefix) ? 'open' : '';
$roleCode = $actor['role_codigo'] ?? '';
$showLogo = ($identity['brand_mode'] ?? 'logo') === 'logo' && !empty($identity['logo_data_uri']);
?>
<aside class="sidebar" id="sidebar">
  <a class="brand" href="/dashboard">
    <div class="brand-mark <?= $showLogo ? 'has-logo' : 'has-initials' ?>" data-brand-mark>
      <?php if ($showLogo): ?>
        <img src="<?= View::e($identity['logo_data_uri']) ?>" alt="">
      <?php else: ?>
        <strong><?= View::e($identity['acronym'] ?: 'DEM') ?></strong>
      <?php endif; ?>
    </div>
    <div class="brand-copy">
      <span data-brand-organization><?= View::e($identity['organization_name']) ?></span>
      <strong data-brand-system><?= View::e($identity['system_name']) ?></strong>
    </div>
  </a>

  <button class="sidebar-toggle" id="sidebarToggle" aria-label="Contraer menú">
    <svg viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
  </button>

  <nav class="nav">
    <a class="nav-item <?= $isActive('/dashboard') ?>" href="/dashboard">
      <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M4 13h6V4H4v9Zm10 7h6V11h-6v9ZM4 20h6v-3H4v3Zm10-13h6V4h-6v3Z"/></svg></span>
      <span class="nav-label">Inicio</span>
    </a>

    <div class="nav-group <?= $isOpen('/inventario') ?>">
      <button class="nav-item nav-parent" type="button">
        <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="m21 8-9 5-9-5m18 0-9-5-9 5m18 0v8l-9 5-9-5V8m9 5v8"/></svg></span>
        <span class="nav-label">Inventario</span>
        <span class="nav-chevron"><svg viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></span>
      </button>
      <div class="nav-submenu">
        <a href="/inventario" class="sub-item">Bienes</a>
        <a href="/inventario/nuevo" class="sub-item">Incorporar</a>
        <a href="/inventario?estado_administrativo=DESINCORPORADO" class="sub-item">Desincorporados</a>
      </div>
    </div>

    <div class="nav-group <?= $isOpen('/catalogos') ?>">
      <button class="nav-item nav-parent" type="button">
        <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M3 6h18M6 3v6m12-6v6M5 12h4m2 0h8M5 17h7"/></svg></span>
        <span class="nav-label">Catálogos</span>
        <span class="nav-chevron"><svg viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></span>
      </button>
      <div class="nav-submenu">
        <a href="/catalogos/locations" class="sub-item">Ubicaciones</a>
        <a href="/catalogos/responsibles" class="sub-item">Responsables</a>
        <a href="/catalogos/categories" class="sub-item">Categorías</a>
        <a href="/catalogos/physical_states" class="sub-item">Estados físicos</a>
      </div>
    </div>

    <div class="nav-group <?= $isOpen('/prestamos') ?>">
      <button class="nav-item nav-parent" type="button">
        <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M8 7h8m-8 5h8m-8 5h5M5 3h14v18H5z"/></svg></span>
        <span class="nav-label">Préstamos</span>
        <span class="nav-chevron"><svg viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></span>
      </button>
      <div class="nav-submenu">
        <a href="/prestamos" class="sub-item">Activos</a>
        <a href="/prestamos?estado=VENCIDO" class="sub-item">Vencidos</a>
      </div>
    </div>

    <div class="nav-group <?= $isOpen('/verificacion') ?>">
      <button class="nav-item nav-parent" type="button">
        <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M9 12l2 2 4-4M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/></svg></span>
        <span class="nav-label">Verificación</span>
        <span class="nav-chevron"><svg viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></span>
      </button>
      <div class="nav-submenu">
        <a href="/verificacion" class="sub-item">Jornadas</a>
        <a href="#" class="sub-item" id="openVerificationWizard">Nueva jornada</a>
      </div>
    </div>

    <div class="nav-group <?= (str_starts_with($path, '/reportes') || str_starts_with($path, '/etiquetas')) ? 'open' : '' ?>">
      <button class="nav-item nav-parent" type="button">
        <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M4 19V9m5 10V5m5 14v-7m5 7V3"/></svg></span>
        <span class="nav-label">Reportes</span>
        <span class="nav-chevron"><svg viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></span>
      </button>
      <div class="nav-submenu">
        <a href="/reportes" class="sub-item">Generar reporte</a>
        <a href="/etiquetas" class="sub-item">Etiquetas de bienes</a>
      </div>
    </div>

    <div class="nav-separator"></div>

    <?php if ($roleCode === 'ADMIN'): ?>
    <a class="nav-item <?= $isActive('/usuarios') ?>" href="/usuarios">
      <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m7-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8 0 2 2 4-4"/></svg></span>
      <span class="nav-label">Usuarios</span>
    </a>
    <a class="nav-item <?= $isActive('/auditoria') ?>" href="/auditoria">
      <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Zm-3-10 2 2 4-4"/></svg></span>
      <span class="nav-label">Auditoría</span>
    </a>
    <a class="nav-item <?= $isActive('/configuracion') ?>" href="/configuracion">
      <span class="nav-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21H9.6v-.09A1.7 1.7 0 0 0 8.5 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H3V9.6h.09A1.7 1.7 0 0 0 4.6 8.5a1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V3h4v.09A1.7 1.7 0 0 0 15.5 4.6a1.7 1.7 0 0 0 1.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.4 9c.13.38.34.72.6 1 .29.28.67.42 1.1.4h.09v4h-.09A1.7 1.7 0 0 0 19.4 15Z"/></svg></span>
      <span class="nav-label">Configuración</span>
    </a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="mini-user-wrap">
      <button class="mini-user" id="miniUserButton">
        <span class="avatar"><?= View::e($initials) ?></span>
        <span class="mini-user-copy">
          <strong><?= View::e($actor['nombre']) ?></strong>
          <small><?= View::e($actor['role_nombre']) ?></small>
        </span>
        <span class="mini-user-menu mini-user-menu-icon"><svg viewBox="0 0 24 24" fill="currentColor" stroke="none"><circle cx="5" cy="12" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="19" cy="12" r="1.6"/></svg></span>
      </button>
      <div class="action-popover mini-user-popover" id="miniUserPopover">
        <form method="post" action="/logout" style="margin:0">
          <?= Csrf::field() ?>
          <button type="submit"><span><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg></span> Cerrar sesión</button>
        </form>
      </div>
    </div>
  </div>
</aside>
