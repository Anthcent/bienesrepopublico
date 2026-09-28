<?php
/** @var array $actor */
/** @var array $assetCounters */
/** @var array $loanCounters */
/** @var array $dueSoonLoans */
/** @var array $recentAssets */
/** @var array $recentMovements */
use App\Core\View;

$firstName = explode(' ', trim($actor['nombre']))[0] ?? $actor['nombre'];
$totalActivos = (int) ($assetCounters['activos'] ?? 0);
$disponibles = (int) ($assetCounters['disponibles'] ?? 0);
$prestados = (int) ($assetCounters['prestados'] ?? 0);
$incompletos = (int) ($assetCounters['incompletos'] ?? 0);
$pctDisponibles = $totalActivos > 0 ? round($disponibles / $totalActivos * 100, 1) : 0;
$thumbColors = ['blue', 'amber', 'violet', 'mint'];
?>
<div class="page-heading">
  <div>
    <span class="eyebrow">PANEL PRINCIPAL</span>
    <h1>Buenos días, <?= View::e($firstName) ?></h1>
    <p>Este es el estado actual del patrimonio y las tareas que requieren atención.</p>
  </div>
  <div class="page-heading-actions">
    <a class="btn btn-primary" href="/inventario/nuevo">
      <span>＋</span> Incorporar bien
    </a>
  </div>
</div>

<section class="overview-grid">
  <article class="feature-card">
    <div class="feature-content">
      <span class="feature-kicker">Resumen general</span>
      <h2><?= number_format($totalActivos, 0, ',', '.') ?></h2>
      <p>Bienes activos</p>
      <div class="feature-stats"><span>Inventario administrado por el sistema</span></div>
      <a class="feature-link" href="/inventario?estado_administrativo=ACTIVO">Ver inventario activo →</a>
    </div>
    <div class="feature-visual" aria-hidden="true">
      <span class="feature-visual-mark">DEM</span>
    </div>
  </article>

  <article class="metric-card mint">
    <div class="metric-icon"><svg viewBox="0 0 24 24"><path d="m21 8-9 5-9-5m18 0-9-5-9 5m18 0v8l-9 5-9-5V8m9 5v8"/></svg></div>
    <div>
      <span>Disponibles</span>
      <strong><?= number_format($disponibles, 0, ',', '.') ?></strong>
      <small><?= $pctDisponibles ?>% del inventario activo</small>
    </div>
  </article>

  <article class="metric-card amber">
    <div class="metric-icon"><svg viewBox="0 0 24 24"><path d="M8 7h8m-8 5h8m-8 5h5M5 3h14v18H5z"/></svg></div>
    <div>
      <span>Prestados</span>
      <strong><?= number_format($prestados, 0, ',', '.') ?></strong>
      <small><?= (int) ($loanCounters['proximos'] ?? 0) ?> próximos a vencer</small>
    </div>
  </article>

  <article class="metric-card violet">
    <div class="metric-icon"><svg viewBox="0 0 24 24"><path d="M9 14 4 9l5-5m-5 5h10a6 6 0 0 1 0 12h-1"/></svg></div>
    <div>
      <span>Vencidos</span>
      <strong><?= (int) ($loanCounters['vencidos'] ?? 0) ?></strong>
      <small>Requieren gestión inmediata</small>
    </div>
  </article>
</section>

<?php if ($incompletos > 0 || !empty($dueSoonLoans)): ?>
<section class="attention-row">
  <?php if (!empty($dueSoonLoans)): ?>
  <div class="attention-card warning">
    <div class="attention-icon">!</div>
    <div>
      <strong><?= count($dueSoonLoans) ?> préstamo(s) próximos a vencer</strong>
      <span>Revísalos para evitar retrasos en la devolución.</span>
    </div>
    <a href="/prestamos">Ver préstamos</a>
  </div>
  <?php endif; ?>
  <?php if ($incompletos > 0): ?>
  <div class="attention-card neutral">
    <div class="attention-icon">i</div>
    <div>
      <strong><?= $incompletos ?> bien(es) con información incompleta</strong>
      <span>Faltan fotografías o características secundarias.</span>
    </div>
    <a href="/inventario">Completar datos</a>
  </div>
  <?php endif; ?>
</section>
<?php endif; ?>

<div class="content-grid">
  <section class="inventory-panel panel">
    <div class="panel-header">
      <div>
        <span class="panel-kicker">INVENTARIO</span>
        <h3>Bienes recientes</h3>
      </div>
      <div class="view-switcher" aria-label="Cambiar vista">
        <button class="active" data-view="table" title="Tabla"><svg viewBox="0 0 24 24"><path d="M4 5h16v14H4zM4 10h16M9 5v14"/></svg></button>
        <button data-view="cards" title="Tarjetas"><svg viewBox="0 0 24 24"><path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z"/></svg></button>
        <button data-view="compact" title="Compacta"><svg viewBox="0 0 24 24"><path d="M5 6h14M5 12h14M5 18h14"/></svg></button>
      </div>
    </div>

    <?php if (empty($recentAssets)): ?>
      <?php View::component('empty_state', ['title' => 'Aún no hay bienes registrados', 'message' => 'Incorpora el primer bien para comenzar a construir el inventario.']); ?>
    <?php else: ?>

    <div class="smart-search">
      <div class="search-box">
        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <input id="inventorySearch" placeholder="Buscar número, serial, descripción, ubicación o responsable…">
      </div>
      <button class="filter-btn" id="filterButton">
        <svg viewBox="0 0 24 24"><path d="M4 5h16M7 12h10M10 19h4"/></svg>
        Filtros
      </button>
    </div>

    <div class="filter-chips">
      <button class="chip active" data-filter="all">Todos</button>
      <button class="chip" data-filter="activo">Activos</button>
      <button class="chip" data-filter="prestado">Prestados</button>
      <button class="chip" data-filter="deteriorado">Deteriorados</button>
    </div>

    <div class="inventory-table-wrap active-view" data-view-panel="table">
      <table class="smart-table">
        <thead><tr><th>Bien</th><th>Ubicación</th><th>Responsable</th><th>Estado</th><th>Disponibilidad</th><th></th></tr></thead>
        <tbody id="assetTableBody">
          <?php foreach ($recentAssets as $i => $asset):
            $searchKey = mb_strtolower(implode(' ', [$asset['numero_bien'], $asset['serial'], $asset['descripcion'], $asset['ubicacion_nombre'], $asset['responsable_nombre'], $asset['estado_administrativo'], $asset['disponibilidad'], $asset['estado_fisico_codigo']]));
          ?>
          <tr data-search="<?= View::e($searchKey) ?>">
            <td>
              <div class="asset-cell">
                <div class="asset-thumb <?= $thumbColors[$i % 4] ?>"><span>▦</span></div>
                <div><strong><?= View::e($asset['numero_bien']) ?></strong><span><?= View::e($asset['descripcion']) ?></span><small><?= View::e($asset['serial'] ?: 'Sin serial') ?></small></div>
              </div>
            </td>
            <td><strong><?= View::e($asset['ubicacion_nombre']) ?></strong></td>
            <td><?= View::e($asset['responsable_nombre']) ?></td>
            <td><span class="status <?= $asset['estado_administrativo'] === 'ACTIVO' ? 'success' : 'neutral-status' ?>">● <?= View::e($asset['estado_administrativo']) ?></span></td>
            <td><span class="status <?= $asset['disponibilidad'] === 'DISPONIBLE' ? 'info' : 'warning' ?>"><?= View::e($asset['disponibilidad']) ?></span></td>
            <td class="row-actions">
              <button class="icon-action asset-view" data-asset='<?= View::e(json_encode($asset, JSON_UNESCAPED_UNICODE)) ?>' aria-label="Vista rápida">
                <svg viewBox="0 0 24 24"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg>
              </button>
              <a class="icon-action" href="/inventario/<?= (int) $asset['id'] ?>">Ficha</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="asset-card-grid" data-view-panel="cards">
      <?php foreach ($recentAssets as $i => $asset): ?>
      <article class="asset-card">
        <div class="asset-card-top">
          <div class="asset-thumb <?= $thumbColors[$i % 4] ?> large">▦</div>
          <span class="status <?= $asset['estado_administrativo'] === 'ACTIVO' ? 'success' : 'neutral-status' ?>">● <?= View::e($asset['estado_administrativo']) ?></span>
        </div>
        <strong><?= View::e($asset['numero_bien']) ?></strong>
        <h4><?= View::e($asset['descripcion']) ?></h4>
        <div class="asset-meta"><span><?= View::e($asset['ubicacion_nombre']) ?></span><span><?= View::e($asset['responsable_nombre']) ?></span></div>
        <div class="asset-card-actions">
          <button class="asset-view" data-asset='<?= View::e(json_encode($asset, JSON_UNESCAPED_UNICODE)) ?>'>Vista rápida</button>
          <a href="/inventario/<?= (int) $asset['id'] ?>">Ficha →</a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>

    <div class="compact-list" data-view-panel="compact">
      <?php foreach ($recentAssets as $i => $asset): ?>
      <button class="compact-row asset-view" data-asset='<?= View::e(json_encode($asset, JSON_UNESCAPED_UNICODE)) ?>'>
        <span class="asset-thumb <?= $thumbColors[$i % 4] ?>">▦</span>
        <span class="compact-main"><strong><?= View::e($asset['numero_bien']) ?> · <?= View::e($asset['descripcion']) ?></strong><small><?= View::e($asset['ubicacion_nombre']) ?> · <?= View::e($asset['responsable_nombre']) ?></small></span>
        <span class="status <?= $asset['disponibilidad'] === 'DISPONIBLE' ? 'info' : 'warning' ?>"><?= View::e($asset['disponibilidad']) ?></span>
      </button>
      <?php endforeach; ?>
    </div>

    <div class="widget-pagination" id="assetWidgetPager" style="display:none">
      <button type="button" id="assetPagerPrev" aria-label="Anterior"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg></button>
      <span id="assetPagerLabel">1 / 1</span>
      <button type="button" id="assetPagerNext" aria-label="Siguiente"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg></button>
    </div>

    <?php endif; ?>

    <div class="panel-footer">
      <span>Mostrando <?= count($recentAssets) ?> de <?= number_format((int) ($assetCounters['total'] ?? 0), 0, ',', '.') ?> bienes</span>
      <a href="/inventario">Ver inventario completo →</a>
    </div>
  </section>

  <aside class="right-column">
    <section class="quick-access panel">
      <div class="panel-header compact">
        <div><span class="panel-kicker orange-text">ACCESOS RÁPIDOS</span><h3>Acciones frecuentes</h3></div>
      </div>
      <div class="quick-grid">
        <a class="quick-tile primary" href="/inventario/nuevo">
          <span class="quick-icon">＋</span><strong>Incorporar bien</strong><small>Registro asistido</small>
        </a>
        <button class="quick-tile" id="quickLoan">
          <span class="quick-icon">↗</span><strong>Registrar préstamo</strong><small>Bienes disponibles</small>
        </button>
        <a class="quick-tile" href="/prestamos">
          <span class="quick-icon">✓</span><strong>Registrar devolución</strong><small>Préstamos activos</small>
        </a>
        <a class="quick-tile" href="/reportes">
          <span class="quick-icon">▥</span><strong>Generar reporte</strong><small>Plantillas rápidas</small>
        </a>
      </div>
    </section>

    <section class="loans-panel panel">
      <div class="panel-header compact">
        <div><span class="panel-kicker">PRÉSTAMOS</span><h3>Requieren atención</h3></div>
        <a class="text-button" href="/prestamos">Ver todos</a>
      </div>
      <?php if (empty($dueSoonLoans)): ?>
        <div class="empty-notifications">No hay préstamos próximos a vencer.</div>
      <?php endif; ?>
      <?php foreach ($dueSoonLoans as $loan):
        $due = new DateTimeImmutable($loan['fecha_vencimiento']);
        $diff = (int) (new DateTimeImmutable('today'))->diff($due)->format('%r%a');
        $urgent = $diff < 0;
      ?>
      <a class="loan-item <?= $urgent ? 'urgent' : '' ?>" href="/prestamos/<?= (int) $loan['id'] ?>">
        <div class="loan-date <?= $diff > 1 ? 'neutral' : '' ?>"><strong><?= abs($diff) ?></strong><span>día(s)</span></div>
        <div class="loan-info">
          <strong><?= View::e($loan['codigo']) ?></strong>
          <span>Prestado a <?= View::e($loan['prestatario_nombre_snapshot']) ?></span>
          <small><?= $urgent ? 'Vencido' : 'Vence el ' . $due->format('d/m/Y') ?></small>
        </div>
        <span class="status <?= $urgent ? 'danger' : 'warning' ?>"><?= $urgent ? 'Vencido' : 'Próximo' ?></span>
      </a>
      <?php endforeach; ?>
    </section>

    <section class="activity-panel panel">
      <div class="panel-header compact">
        <div><span class="panel-kicker">ACTIVIDAD</span><h3>Últimos movimientos</h3></div>
        <a class="text-button" href="/auditoria">Ver historial</a>
      </div>
      <div class="activity-list">
        <?php if (empty($recentMovements)): ?>
          <div class="empty-notifications">Sin movimientos registrados todavía.</div>
        <?php endif; ?>
        <?php foreach ($recentMovements as $movement): ?>
        <div class="activity-item">
          <span class="activity-icon blue">⇄</span>
          <div><strong><?= View::e($movement['tipo_nombre']) ?></strong><span><?= View::e($movement['numero_bien']) ?> · <?= View::e($movement['usuario_nombre']) ?></span></div>
          <small><?= (new DateTimeImmutable($movement['created_at']))->format('H:i') ?></small>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
  </aside>
</div>

<script type="module">
  import { initViewSwitcher } from '/assets/js/core/viewSwitcher.js';
  import { initInventoryPanel } from '/assets/js/modules/inventory.js';
  import { bindLoanWizardTrigger } from '/assets/js/modules/loanWizard.js';

  /**
   * "Bienes recientes" solo trae un puñado de registros (no hay más páginas
   * que pedirle al servidor), pero mostrarlos todos apilados hace scrollear
   * de más — sobre todo en la vista de tarjetas. Se paginan en el cliente,
   * por tanda, sobre lo que ya está en el DOM.
   */
  function initAssetPager() {
    const PAGE_SIZE = 3;
    const pager = document.getElementById('assetWidgetPager');
    if (!pager) return;
    const prevBtn = document.getElementById('assetPagerPrev');
    const nextBtn = document.getElementById('assetPagerNext');
    const label = document.getElementById('assetPagerLabel');
    let page = 1;

    function itemsFor(view) {
      if (view === 'table') return [...document.querySelectorAll('#assetTableBody tr[data-search]')];
      if (view === 'cards') return [...document.querySelectorAll('.asset-card-grid .asset-card')];
      return [...document.querySelectorAll('.compact-list .compact-row')];
    }
    function activeView() {
      return document.querySelector('.view-switcher button.active')?.dataset.view || 'table';
    }

    function render() {
      const items = itemsFor(activeView());
      const visible = items.filter((el) => !el.classList.contains('hidden-row'));
      const totalPages = Math.max(1, Math.ceil(visible.length / PAGE_SIZE));
      if (page > totalPages) page = totalPages;
      let count = 0;
      items.forEach((el) => {
        if (el.classList.contains('hidden-row')) { el.classList.add('page-hidden'); return; }
        count++;
        el.classList.toggle('page-hidden', count <= (page - 1) * PAGE_SIZE || count > page * PAGE_SIZE);
      });
      label.textContent = `${page} / ${totalPages}`;
      prevBtn.disabled = page <= 1;
      nextBtn.disabled = page >= totalPages;
      pager.style.display = totalPages > 1 ? 'flex' : 'none';
    }

    prevBtn.addEventListener('click', () => { page = Math.max(1, page - 1); render(); });
    nextBtn.addEventListener('click', () => { page += 1; render(); });
    document.querySelectorAll('.view-switcher button').forEach((b) => b.addEventListener('click', () => { page = 1; render(); }));
    document.getElementById('inventorySearch')?.addEventListener('input', () => { page = 1; setTimeout(render, 320); });
    document.querySelectorAll('.filter-chips .chip').forEach((c) => c.addEventListener('click', () => { page = 1; setTimeout(render, 20); }));

    render();
  }

  /**
   * Se reata tras cada refresco en vivo disparado por los wizards globales
   * (Incorporar bien/Registrar préstamo) — ver `window.__pageBindPage` en
   * core/pageRefresh.js. No se llama aquí en la carga inicial: `app.js` ya
   * inicializa `initViewSwitcher`/`initInventoryPanel`/`bindLoanWizardTrigger`
   * una vez, y `initAssetPager()` ya se llama más abajo en esta carga.
   */
  function bindPage() {
    initViewSwitcher();
    initInventoryPanel();
    bindLoanWizardTrigger();
    initAssetPager();
  }
  window.__pageBindPage = bindPage;

  initAssetPager();
</script>
