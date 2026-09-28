<?php
/**
 * Plantilla compartida por los catálogos simples (M04). Cada archivo
 * catalogs/{table}.php sólo define título/campos y hace include de esta
 * base, evitando duplicar el layout de tarjetas.
 * @var array $items
 * @var array $stats
 * @var string $table
 * @var string $title
 * @var string $subtitle
 * @var array $fields  [ ['name'=>'nombre','label'=>'Nombre','type'=>'text'], ... ]
 */
use App\Core\View;

$statLabels = [
    'total' => 'bienes',
    'activos' => 'activos',
    'prestados' => 'prestados',
    'bienes' => 'bienes',
    'prestamos' => 'préstamos',
];
?>
<?php
$activeCount = count(array_filter($items, fn ($i) => (bool) ($i['activo'] ?? 1)));
$inactiveCount = count($items) - $activeCount;
?>
<div class="page-heading">
  <div>
    <span class="eyebrow">CATÁLOGOS</span>
    <h1><?= View::e($title) ?></h1>
    <p><?= View::e($subtitle) ?></p>
  </div>
  <div class="page-heading-actions">
    <button class="btn btn-primary" id="openCatalogCreate"><span class="btn-icon">＋</span> Nuevo registro</button>
  </div>
</div>

<?php if (empty($items)): ?>
  <?php View::component('empty_state', ['title' => 'Sin registros todavía', 'message' => 'Crea el primer registro de este catálogo.']); ?>
<?php else: ?>
<section class="panel catalog-panel">
  <div class="panel-header">
    <div>
      <span class="panel-kicker">RESULTADOS</span>
      <h3 id="catalogResultsCount"><?= count($items) ?> registro<?= count($items) === 1 ? '' : 's' ?> · <?= $activeCount ?> activo<?= $activeCount === 1 ? '' : 's' ?><?= $inactiveCount ? ' · ' . $inactiveCount . ' inactivo' . ($inactiveCount === 1 ? '' : 's') : '' ?></h3>
    </div>
    <div class="view-switcher" aria-label="Cambiar vista">
      <button class="active" data-view="cards" title="Tarjetas"><svg viewBox="0 0 24 24"><path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z"/></svg></button>
      <button data-view="compact" title="Lista"><svg viewBox="0 0 24 24"><path d="M5 6h14M5 12h14M5 18h14"/></svg></button>
    </div>
  </div>

  <div class="smart-search">
    <div class="search-box">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input id="catalogSearch" placeholder="Buscar por nombre…">
    </div>
  </div>

  <div class="filter-chips">
    <button class="chip active" data-filter="all">Todos</button>
    <button class="chip" data-filter="active">Activos</button>
    <button class="chip" data-filter="inactive">Inactivos</button>
  </div>

  <div class="catalog-grid active-view" data-view-panel="cards">
    <?php foreach ($items as $item):
      $isActive = (bool) ($item['activo'] ?? 1);
      $metaFields = array_values(array_filter($fields, fn ($f) => !in_array($f['name'], ['nombre', 'imagen_url'], true) && !empty($item[$f['name']])));
      $itemStats = $stats[$item['id']] ?? null;
      $searchKey = mb_strtolower($item['nombre']);
    ?>
    <article class="catalog-card<?= $isActive ? '' : ' is-inactive' ?>" data-id="<?= (int) $item['id'] ?>" data-search="<?= View::e($searchKey) ?>" data-status="<?= $isActive ? 'active' : 'inactive' ?>">
      <?php if (!empty($item['imagen_url'])): ?>
        <img class="catalog-card-image" src="<?= View::e($item['imagen_url']) ?>" alt="<?= View::e($item['nombre']) ?>">
      <?php endif; ?>
      <div class="catalog-card-head">
        <div class="catalog-card-icon"><?= View::e(mb_strtoupper(mb_substr($item['nombre'], 0, 1))) ?></div>
        <div class="catalog-card-heading">
          <strong><?= View::e($item['nombre']) ?></strong>
          <span class="status <?= $isActive ? 'success' : 'neutral-status' ?>"><?= $isActive ? 'Activo' : 'Inactivo' ?></span>
        </div>
      </div>
      <?php if ($metaFields): ?>
        <div class="catalog-meta">
          <?php foreach ($metaFields as $field): ?>
            <span><em><?= View::e($field['label']) ?></em><?= View::e($item[$field['name']]) ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php if ($itemStats): ?>
        <div class="catalog-stats">
          <?php foreach ($itemStats as $k => $v): ?>
            <span><strong><?= (int) $v ?></strong><?= View::e($statLabels[$k] ?? $k) ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <div class="catalog-actions">
        <button class="btn btn-ghost btn-sm catalog-edit" data-item='<?= View::e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'>Editar</button>
        <button class="btn btn-ghost btn-sm catalog-toggle" data-id="<?= (int) $item['id'] ?>" data-active="<?= (int) $isActive ?>">
          <?= $isActive ? 'Desactivar' : 'Activar' ?>
        </button>
      </div>
    </article>
    <?php endforeach; ?>
  </div>

  <div class="catalog-compact-list" data-view-panel="compact">
    <?php foreach ($items as $item):
      $isActive = (bool) ($item['activo'] ?? 1);
      $metaFields = array_values(array_filter($fields, fn ($f) => !in_array($f['name'], ['nombre', 'imagen_url'], true) && !empty($item[$f['name']])));
      $searchKey = mb_strtolower($item['nombre']);
    ?>
    <div class="catalog-compact-row" data-id="<?= (int) $item['id'] ?>" data-search="<?= View::e($searchKey) ?>" data-status="<?= $isActive ? 'active' : 'inactive' ?>">
      <div class="catalog-card-icon"><?= View::e(mb_strtoupper(mb_substr($item['nombre'], 0, 1))) ?></div>
      <span class="compact-main">
        <strong><?= View::e($item['nombre']) ?></strong>
        <small><?= $metaFields ? View::e(implode(' · ', array_map(fn ($f) => $item[$f['name']], $metaFields))) : ($isActive ? 'Activo' : 'Inactivo') ?></small>
      </span>
      <span class="status <?= $isActive ? 'success' : 'neutral-status' ?>"><?= $isActive ? 'Activo' : 'Inactivo' ?></span>
      <div class="catalog-actions">
        <button class="btn btn-ghost btn-sm catalog-edit" data-item='<?= View::e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'>Editar</button>
        <button class="btn btn-ghost btn-sm catalog-toggle" data-id="<?= (int) $item['id'] ?>" data-active="<?= (int) $isActive ?>">
          <?= $isActive ? 'Desactivar' : 'Activar' ?>
        </button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <p class="no-results hidden-row" id="catalogNoResults">No encontramos registros con esos criterios.</p>

  <?php if (!empty($paginated)): ?>
    <?php View::component('pagination', ['total' => $total, 'page' => $page, 'perPage' => $perPage, 'baseUrl' => '/catalogos/' . $table]); ?>
  <?php endif; ?>
</section>
<?php endif; ?>

<div class="modal-backdrop" id="catalogModalBackdrop"></div>
<section class="modal" id="catalogModal" role="dialog" aria-modal="true" style="width:min(440px,calc(100% - 30px))">
  <div class="modal-header">
    <div><span class="panel-kicker">CATÁLOGO</span><h3 id="catalogModalTitle">Nuevo registro</h3></div>
    <button class="icon-btn modal-close" data-modal-close="catalogModal">×</button>
  </div>
  <div class="modal-body">
    <form id="catalogForm">
      <?php foreach ($fields as $field): ?>
        <label class="field" style="margin-bottom:12px">
          <span><?= View::e($field['label']) ?></span>
          <input name="<?= View::e($field['name']) ?>" type="<?= View::e($field['type'] ?? 'text') ?>">
        </label>
      <?php endforeach; ?>
    </form>
  </div>
  <div class="modal-footer">
    <button class="btn btn-ghost" data-modal-close="catalogModal">Cancelar</button>
    <button class="btn btn-primary" id="catalogSave">Guardar</button>
  </div>
</section>

<script type="module">
  import { $, $$, api, escapeHtml } from '/assets/js/core/dom.js';
  import { showToast } from '/assets/js/core/toast.js';
  import { openModal, closeModal, registerModal } from '/assets/js/core/modal.js';

  registerModal('catalogModal', 'catalogModalBackdrop');
  const table = <?= json_encode($table) ?>;
  const fieldDefs = <?= json_encode(array_map(fn ($f) => ['name' => $f['name'], 'label' => $f['label']], $fields), JSON_UNESCAPED_UNICODE) ?>;
  let editingId = null;

  const grid = $('.catalog-grid');
  const compactList = $('.catalog-compact-list');

  function metaFieldsOf(item) {
    return fieldDefs.filter((f) => !['nombre', 'imagen_url'].includes(f.name) && item[f.name]);
  }

  function initial(name) {
    return (name || '').trim().slice(0, 1).toUpperCase();
  }

  function cardHtml(item) {
    const active = item.activo === undefined ? true : !!Number(item.activo);
    const meta = metaFieldsOf(item);
    return `
    <article class="catalog-card${active ? '' : ' is-inactive'}" data-id="${item.id}" data-search="${escapeHtml((item.nombre || '').toLowerCase())}" data-status="${active ? 'active' : 'inactive'}">
      ${item.imagen_url ? `<img class="catalog-card-image" src="${escapeHtml(item.imagen_url)}" alt="${escapeHtml(item.nombre)}">` : ''}
      <div class="catalog-card-head">
        <div class="catalog-card-icon">${escapeHtml(initial(item.nombre))}</div>
        <div class="catalog-card-heading">
          <strong>${escapeHtml(item.nombre)}</strong>
          <span class="status ${active ? 'success' : 'neutral-status'}">${active ? 'Activo' : 'Inactivo'}</span>
        </div>
      </div>
      ${meta.length ? `<div class="catalog-meta">${meta.map((f) => `<span><em>${escapeHtml(f.label)}</em>${escapeHtml(item[f.name])}</span>`).join('')}</div>` : ''}
      <div class="catalog-actions">
        <button class="btn btn-ghost btn-sm catalog-edit" data-item='${JSON.stringify(item).replace(/'/g, '&#39;')}'>Editar</button>
        <button class="btn btn-ghost btn-sm catalog-toggle" data-id="${item.id}" data-active="${active ? 1 : 0}">${active ? 'Desactivar' : 'Activar'}</button>
      </div>
    </article>`;
  }

  function compactHtml(item) {
    const active = item.activo === undefined ? true : !!Number(item.activo);
    const meta = metaFieldsOf(item);
    const sub = meta.length ? meta.map((f) => item[f.name]).join(' · ') : (active ? 'Activo' : 'Inactivo');
    return `
    <div class="catalog-compact-row" data-id="${item.id}" data-search="${escapeHtml((item.nombre || '').toLowerCase())}" data-status="${active ? 'active' : 'inactive'}">
      <div class="catalog-card-icon">${escapeHtml(initial(item.nombre))}</div>
      <span class="compact-main"><strong>${escapeHtml(item.nombre)}</strong><small>${escapeHtml(sub)}</small></span>
      <span class="status ${active ? 'success' : 'neutral-status'}">${active ? 'Activo' : 'Inactivo'}</span>
      <div class="catalog-actions">
        <button class="btn btn-ghost btn-sm catalog-edit" data-item='${JSON.stringify(item).replace(/'/g, '&#39;')}'>Editar</button>
        <button class="btn btn-ghost btn-sm catalog-toggle" data-id="${item.id}" data-active="${active ? 1 : 0}">${active ? 'Desactivar' : 'Activar'}</button>
      </div>
    </div>`;
  }

  function bindRow(el) {
    el.querySelector('.catalog-edit')?.addEventListener('click', () => openEdit(el.querySelector('.catalog-edit').dataset.item));
    el.querySelector('.catalog-toggle')?.addEventListener('click', () => toggleItem(el.querySelector('.catalog-toggle')));
  }

  function updateResultsCount() {
    const cards = $$('.catalog-card', grid);
    const active = cards.filter((c) => c.dataset.status === 'active').length;
    const inactive = cards.length - active;
    let text = `${cards.length} registro${cards.length === 1 ? '' : 's'} · ${active} activo${active === 1 ? '' : 's'}`;
    if (inactive) text += ` · ${inactive} inactivo${inactive === 1 ? '' : 's'}`;
    const el = $('#catalogResultsCount');
    if (el) el.textContent = text;
  }

  function upsertRow(item) {
    const existingCard = grid?.querySelector(`.catalog-card[data-id="${item.id}"]`);
    const existingCompact = compactList?.querySelector(`.catalog-compact-row[data-id="${item.id}"]`);
    if (existingCard) existingCard.outerHTML = cardHtml(item);
    else grid?.insertAdjacentHTML('beforeend', cardHtml(item));
    if (existingCompact) existingCompact.outerHTML = compactHtml(item);
    else compactList?.insertAdjacentHTML('beforeend', compactHtml(item));

    const newCard = grid?.querySelector(`.catalog-card[data-id="${item.id}"]`);
    const newCompact = compactList?.querySelector(`.catalog-compact-row[data-id="${item.id}"]`);
    if (newCard) bindRow(newCard);
    if (newCompact) bindRow(newCompact);
    updateResultsCount();
    applyFilters();
  }

  function openEdit(itemJson) {
    const data = JSON.parse(itemJson);
    editingId = data.id;
    const form = document.getElementById('catalogForm');
    form.reset();
    Object.keys(data).forEach((key) => {
      const input = form.querySelector(`[name="${key}"]`);
      if (input) input.value = data[key] ?? '';
    });
    document.getElementById('catalogModalTitle').textContent = 'Editar registro';
    openModal('catalogModal');
  }

  async function toggleItem(btn) {
    const activo = btn.dataset.active === '1' ? 0 : 1;
    try {
      const res = await api(`/api/catalogs/${table}/${btn.dataset.id}/toggle`, { method: 'POST', body: JSON.stringify({ activo: !!activo }) });
      upsertRow(res.item);
      showToast(activo ? 'Registro activado' : 'Registro desactivado', 'El cambio ya está reflejado en la lista.', 'success');
    } catch (e) {
      showToast('No se pudo cambiar el estado', e.message, 'error');
    }
  }

  document.getElementById('openCatalogCreate')?.addEventListener('click', () => {
    editingId = null;
    document.getElementById('catalogForm').reset();
    document.getElementById('catalogModalTitle').textContent = 'Nuevo registro';
    openModal('catalogModal');
  });

  $$('.catalog-edit').forEach((btn) => btn.addEventListener('click', () => openEdit(btn.dataset.item)));
  $$('.catalog-toggle').forEach((btn) => btn.addEventListener('click', () => toggleItem(btn)));

  const searchInput = document.getElementById('catalogSearch');
  const noResults = document.getElementById('catalogNoResults');
  let activeFilter = 'all';

  function applyFilters() {
    const q = (searchInput?.value || '').toLowerCase().trim();
    let visible = 0;
    document.querySelectorAll('.catalog-card[data-search], .catalog-compact-row[data-search]').forEach((el) => {
      const matchesSearch = !q || el.dataset.search.includes(q);
      const matchesFilter = activeFilter === 'all' || el.dataset.status === activeFilter;
      const match = matchesSearch && matchesFilter;
      el.classList.toggle('hidden-row', !match);
      if (match && el.closest('[data-view-panel="cards"]')) visible++;
    });
    noResults?.classList.toggle('hidden-row', visible !== 0);
  }

  searchInput?.addEventListener('input', applyFilters);

  document.querySelectorAll('.filter-chips .chip').forEach((chip) => {
    chip.addEventListener('click', () => {
      document.querySelectorAll('.filter-chips .chip').forEach((c) => c.classList.remove('active'));
      chip.classList.add('active');
      activeFilter = chip.dataset.filter;
      applyFilters();
    });
  });

  document.getElementById('catalogSave')?.addEventListener('click', async () => {
    const form = document.getElementById('catalogForm');
    const payload = Object.fromEntries(new FormData(form).entries());
    try {
      let res;
      if (editingId) {
        res = await api(`/api/catalogs/${table}/${editingId}`, { method: 'PUT', body: JSON.stringify(payload) });
      } else {
        res = await api(`/api/catalogs/${table}`, { method: 'POST', body: JSON.stringify(payload) });
      }
      closeModal('catalogModal');
      upsertRow(res.item);
      showToast('Catálogo actualizado', 'El registro se guardó correctamente.', 'success');
    } catch (e) {
      showToast('No se pudo guardar', e.message, 'error');
    }
  });
</script>
