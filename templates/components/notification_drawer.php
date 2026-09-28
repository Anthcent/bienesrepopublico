<div class="drawer-backdrop" id="notificationBackdrop"></div>
<aside class="drawer notification-drawer" id="notificationDrawer">
  <div class="drawer-header">
    <div><span class="panel-kicker">CENTRO DE AVISOS</span><h3>Notificaciones</h3></div>
    <button class="icon-btn close-drawer" data-close="notificationDrawer">×</button>
  </div>
  <div class="notification-tabs" id="notificationTabs">
    <button class="active" data-tab="nuevas">Nuevas <span id="notifTabCountNuevas" style="display:none">0</span></button>
    <button data-tab="prestamos">Préstamos <span id="notifTabCountPrestamos" style="display:none">0</span></button>
    <button data-tab="sistema">Sistema <span id="notifTabCountSistema" style="display:none">0</span></button>
  </div>
  <div class="drawer-body">
    <div id="notificationList">
      <div class="empty-notifications">Cargando notificaciones…</div>
    </div>
  </div>
  <div class="drawer-footer single">
    <button class="btn btn-ghost" id="markAllReadBtn">Marcar todas como leídas</button>
  </div>
</aside>
