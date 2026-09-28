<?php
/** @var array $items */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var array $filters */
/** @var array $counters */
/** @var array $physicalStates */
use App\Core\View;

$thumbColors = ['blue', 'amber', 'violet', 'mint'];
$deterioradoState = null;
foreach ($physicalStates as $state) {
    if ($state['codigo'] === 'DETERIORADO') {
        $deterioradoState = $state;
        break;
    }
}
?>
<div class="page-heading">
  <div>
    <span class="eyebrow">INVENTARIO</span>
    <h1>Bienes públicos</h1>
    <p>Consulta, filtra y gestiona el inventario completo del patrimonio.</p>
  </div>
  <div class="page-heading-actions">
    <a class="btn btn-primary" href="/inventario/nuevo"><span>＋</span> Incorporar bien</a>
  </div>
</div>

<section class="inventory-panel panel">
  <div class="panel-header">
    <div><span class="panel-kicker">RESULTADOS</span><h3><?= number_format($total, 0, ',', '.') ?> bienes encontrados</h3></div>
    <div class="view-switcher" aria-label="Cambiar vista">
      <button class="active" data-view="table" title="Tabla"><svg viewBox="0 0 24 24"><path d="M4 5h16v14H4zM4 10h16M9 5v14"/></svg></button>
      <button data-view="cards" title="Tarjetas"><svg viewBox="0 0 24 24"><path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z"/></svg></button>
      <button data-view="compact" title="Compacta"><svg viewBox="0 0 24 24"><path d="M5 6h14M5 12h14M5 18h14"/></svg></button>
    </div>
  </div>

  <div class="smart-search">
    <div class="search-box">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input id="inventorySearch" placeholder="Buscar número, serial, descripción…">
    </div>
    <button class="filter-btn" id="filterButton">
      <svg viewBox="0 0 24 24"><path d="M4 5h16M7 12h10M10 19h4"/></svg>
      Filtros
      <?php $activeFilters = count(array_filter($filters)); ?>
      <?php if ($activeFilters > 0): ?><span class="filter-count"><?= $activeFilters ?></span><?php endif; ?>
    </button>
  </div>

  <div class="filter-chips">
    <a class="chip <?= empty($filters['disponibilidad']) && empty($filters['estado_administrativo']) ? 'active' : '' ?>" href="/inventario">Todos</a>
    <a class="chip <?= $filters['estado_administrativo'] === 'ACTIVO' ? 'active' : '' ?>" href="/inventario?estado_administrativo=ACTIVO">Activos</a>
    <a class="chip <?= $filters['disponibilidad'] === 'PRESTADO' ? 'active' : '' ?>" href="/inventario?disponibilidad=PRESTADO">Prestados</a>
    <?php if ($deterioradoState): ?>
    <a class="chip <?= (string) $filters['physical_state_id'] === (string) $deterioradoState['id'] ? 'active' : '' ?>" href="/inventario?physical_state_id=<?= (int) $deterioradoState['id'] ?>">Deteriorados</a>
    <?php endif; ?>
  </div>

  <?php if (empty($items)): ?>
    <?php View::component('empty_state', ['title' => 'No hay bienes con esos criterios', 'message' => 'Ajusta los filtros o incorpora un nuevo bien al inventario.']); ?>
  <?php else: ?>

  <div class="inventory-table-wrap active-view" data-view-panel="table">
    <table class="smart-table">
      <thead><tr><th>Bien</th><th>Ubicación</th><th>Responsable</th><th>Estado</th><th>Disponibilidad</th><th></th></tr></thead>
      <tbody id="assetTableBody">
        <?php foreach ($items as $i => $asset):
          $searchKey = mb_strtolower(implode(' ', [$asset['numero_bien'], $asset['serial'], $asset['descripcion'], $asset['ubicacion_nombre'], $asset['responsable_nombre'], $asset['estado_administrativo'], $asset['disponibilidad']]));
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
    <?php foreach ($items as $i => $asset): ?>
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
    <?php foreach ($items as $i => $asset): ?>
    <button class="compact-row asset-view" data-asset='<?= View::e(json_encode($asset, JSON_UNESCAPED_UNICODE)) ?>'>
      <span class="asset-thumb <?= $thumbColors[$i % 4] ?>">▦</span>
      <span class="compact-main"><strong><?= View::e($asset['numero_bien']) ?> · <?= View::e($asset['descripcion']) ?></strong><small><?= View::e($asset['ubicacion_nombre']) ?> · <?= View::e($asset['responsable_nombre']) ?></small></span>
      <span class="status <?= $asset['disponibilidad'] === 'DISPONIBLE' ? 'info' : 'warning' ?>"><?= View::e($asset['disponibilidad']) ?></span>
    </button>
    <?php endforeach; ?>
  </div>

  <?php endif; ?>

  <?php View::component('pagination', ['total' => $total, 'page' => $page, 'perPage' => $perPage, 'baseUrl' => '/inventario']); ?>
</section>
