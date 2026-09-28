<?php
/** @var array $actor */
/** @var string $initials */
use App\Core\View;
use App\Core\Csrf;
?>
<header class="topbar">
  <button class="mobile-menu" id="mobileMenu" aria-label="Abrir menú">
    <svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
  </button>

  <div class="global-search-wrap">
    <svg class="search-icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
    <input id="globalSearch" type="search" placeholder="Buscar bien, préstamo, persona o ubicación…" autocomplete="off">
    <kbd>Ctrl K</kbd>
    <div class="search-popover" id="searchPopover"></div>
  </div>

  <div class="topbar-actions">
    <div class="quick-action-wrap">
      <button class="quick-action-btn" id="quickActionButton">
        <span>＋</span>
        <span class="quick-action-label">Acción rápida</span>
      </button>

      <div class="action-popover" id="actionPopover">
        <a href="/inventario/nuevo" onclick="document.getElementById('actionPopover').classList.remove('show')"><span>＋</span> Incorporar bien</a>
        <button type="button" data-action="loan"><span>↗</span> Registrar préstamo</button>
        <a href="/prestamos"><span>✓</span> Registrar devolución</a>
        <a href="/reportes"><span>▥</span> Generar reporte</a>
      </div>
    </div>

    <button class="icon-btn dock-toggle-button" id="dockToggleButton" type="button" aria-pressed="true" title="Mostrar u ocultar la barra de acciones rápidas" aria-label="Mostrar u ocultar la barra de acciones rápidas">
      <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 15h18"/></svg>
    </button>

    <button class="icon-btn notification-button" id="notificationButton" aria-label="Notificaciones">
      <svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
      <span class="notification-count" style="display:none">0</span>
    </button>

    <div class="top-user-wrap">
      <button class="top-user" id="topUserButton">
        <span class="avatar"><?= View::e($initials) ?></span>
        <span class="top-user-copy">
          <strong><?= View::e($actor['nombre']) ?></strong>
          <small><?= View::e($actor['role_nombre']) ?></small>
        </span>
        <span class="top-user-chevron"><svg viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></span>
      </button>
      <div class="action-popover top-user-popover" id="topUserPopover">
        <a href="/dashboard"><span><svg viewBox="0 0 24 24"><path d="M4 11.5 12 4l8 7.5"/><path d="M6 10v10h12V10"/><path d="M10 20v-6h4v6"/></svg></span> Panel principal</a>
        <form method="post" action="/logout" style="margin:0">
          <?= Csrf::field() ?>
          <button type="submit"><span><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg></span> Cerrar sesión</button>
        </form>
      </div>
    </div>
  </div>
</header>
