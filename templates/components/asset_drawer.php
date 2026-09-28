<div class="drawer-backdrop" id="assetBackdrop"></div>
<aside class="drawer asset-drawer" id="assetDrawer">
  <div class="drawer-header">
    <div><span class="panel-kicker">VISTA RÁPIDA</span><h3 data-drawer="numero">—</h3></div>
    <button class="icon-btn close-drawer" data-close="assetDrawer">×</button>
  </div>
  <div class="asset-hero">
    <div class="asset-photo-placeholder">
      <svg viewBox="0 0 24 24"><path d="m21 8-9 5-9-5m18 0-9-5-9 5m18 0v8l-9 5-9-5V8m9 5v8"/></svg>
    </div>
    <div>
      <h3 data-drawer="descripcion">—</h3>
      <p data-drawer="sub"></p>
    </div>
  </div>
  <div class="drawer-body">
    <div class="detail-grid">
      <div><span>Ubicación actual</span><strong data-drawer="ubicacion">—</strong></div>
      <div><span>Responsable</span><strong data-drawer="responsable">—</strong></div>
    </div>
    <div class="inline-section">
      <div class="inline-section-header"><strong>Acciones</strong></div>
      <div class="asset-action-grid">
        <button class="btn btn-primary" id="drawerLoanAction">↗ Prestar</button>
        <a class="btn btn-secondary" data-drawer="ficha" href="#">Abrir ficha</a>
      </div>
    </div>
  </div>
</aside>
