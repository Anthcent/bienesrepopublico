<?php
/** @var array $items */
use App\Core\View;
?>
<div class="page-heading">
  <div>
    <span class="eyebrow">CATÁLOGOS</span>
    <h1>Estados físicos</h1>
    <p>Catálogo utilizado en incorporación, préstamos y devoluciones. El color se reutiliza en el wizard de incorporación.</p>
  </div>
  <div class="page-heading-actions">
    <button class="btn btn-primary" id="openStateCreate"><span class="btn-icon">＋</span> Nuevo estado</button>
  </div>
</div>

<div class="catalog-grid" id="statesGrid">
  <?php foreach ($items as $item): ?>
  <article class="catalog-card" data-id="<?= (int) $item['id'] ?>" data-item='<?= View::e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>'>
    <div class="catalog-card-head">
      <div class="catalog-card-icon" style="background:<?= View::e($item['color']) ?>22;color:<?= View::e($item['color']) ?>"><?= View::e(mb_strtoupper(mb_substr($item['nombre'], 0, 1))) ?></div>
      <div class="catalog-card-heading">
        <strong><?= View::e($item['nombre']) ?></strong>
        <span class="status <?= $item['es_negativo'] ? 'warning' : 'neutral-status' ?>"><?= $item['es_negativo'] ? 'Requiere observación' : 'Sin observaciones' ?></span>
      </div>
    </div>
    <div class="catalog-meta">
      <span><?= $item['es_negativo'] ? 'Estado que requiere observación en devoluciones' : 'Estado sin observaciones obligatorias' ?></span>
    </div>
    <div class="catalog-actions">
      <button class="btn btn-ghost btn-sm state-edit">Editar</button>
    </div>
  </article>
  <?php endforeach; ?>
</div>

<div class="modal-backdrop" id="stateModalBackdrop"></div>
<section class="modal" id="stateModal" role="dialog" aria-modal="true" style="width:min(420px,calc(100% - 30px))">
  <div class="modal-header">
    <div><span class="panel-kicker">ESTADO FÍSICO</span><h3 id="stateModalTitle">Nuevo estado</h3></div>
    <button class="icon-btn modal-close" data-modal-close="stateModal">×</button>
  </div>
  <div class="modal-body">
    <form id="stateForm">
      <label class="field" style="margin-bottom:12px">
        <span>Nombre</span>
        <input name="nombre" required>
      </label>
      <label class="field" style="margin-bottom:12px">
        <span>Código</span>
        <input name="codigo" placeholder="Ej. REGULAR" style="text-transform:uppercase" required>
      </label>
      <label class="field" style="margin-bottom:12px">
        <span>Orden</span>
        <input name="orden" type="number" min="0" value="0">
      </label>
      <div class="form-grid two">
        <label class="field">
          <span>Color</span>
          <input name="color" type="color" value="#50617a" style="padding:2px;height:42px">
        </label>
        <div class="field">
          <span>&nbsp;</span>
          <label style="display:flex;align-items:center;gap:8px;height:42px;font-weight:400;font-size:11px">
            <input name="es_negativo" type="checkbox" style="width:16px;height:16px">
            Requiere observación en devoluciones
          </label>
        </div>
      </div>
    </form>
  </div>
  <div class="modal-footer">
    <button class="btn btn-ghost" data-modal-close="stateModal">Cancelar</button>
    <button class="btn btn-primary" id="stateSave">Guardar</button>
  </div>
</section>

<script type="module">
  import { $, $$, api } from '/assets/js/core/dom.js';
  import { showToast } from '/assets/js/core/toast.js';
  import { openModal, closeModal, registerModal } from '/assets/js/core/modal.js';

  registerModal('stateModal', 'stateModalBackdrop');
  const form = $('#stateForm');
  let editingId = null;

  function cardHtml(item) {
    const negativo = !!Number(item.es_negativo);
    return `
    <article class="catalog-card" data-id="${item.id}" data-item='${JSON.stringify(item).replace(/'/g, '&#39;')}'>
      <div class="catalog-card-head">
        <div class="catalog-card-icon" style="background:${item.color}22;color:${item.color}">${(item.nombre || '').trim().slice(0, 1).toUpperCase()}</div>
        <div class="catalog-card-heading">
          <strong>${item.nombre}</strong>
          <span class="status ${negativo ? 'warning' : 'neutral-status'}">${negativo ? 'Requiere observación' : 'Sin observaciones'}</span>
        </div>
      </div>
      <div class="catalog-meta"><span>${negativo ? 'Estado que requiere observación en devoluciones' : 'Estado sin observaciones obligatorias'}</span></div>
      <div class="catalog-actions"><button class="btn btn-ghost btn-sm state-edit">Editar</button></div>
    </article>`;
  }

  function bindCard(el) {
    el.querySelector('.state-edit')?.addEventListener('click', () => openEdit(JSON.parse(el.dataset.item)));
  }

  function openCreate() {
    editingId = null;
    form.reset();
    form.elements.namedItem('color').value = '#50617a';
    $('#stateModalTitle').textContent = 'Nuevo estado';
    openModal('stateModal');
  }

  function openEdit(item) {
    editingId = item.id;
    form.reset();
    form.elements.namedItem('nombre').value = item.nombre;
    form.elements.namedItem('codigo').value = item.codigo;
    form.elements.namedItem('orden').value = item.orden ?? 0;
    form.elements.namedItem('color').value = item.color || '#50617a';
    form.elements.namedItem('es_negativo').checked = !!Number(item.es_negativo);
    $('#stateModalTitle').textContent = 'Editar estado';
    openModal('stateModal');
  }

  $('#openStateCreate')?.addEventListener('click', openCreate);
  $$('.catalog-card', $('#statesGrid')).forEach(bindCard);

  $('#stateSave')?.addEventListener('click', async () => {
    const payload = {
      nombre: form.elements.namedItem('nombre').value.trim(),
      codigo: form.elements.namedItem('codigo').value.trim().toUpperCase(),
      orden: Number(form.elements.namedItem('orden').value) || 0,
      color: form.elements.namedItem('color').value,
      es_negativo: form.elements.namedItem('es_negativo').checked,
    };
    if (!payload.nombre || !payload.codigo) {
      showToast('Faltan datos', 'Nombre y código son obligatorios.', 'warning');
      return;
    }
    try {
      let res;
      if (editingId) {
        res = await api(`/api/catalogs/physical_states/${editingId}`, { method: 'PUT', body: JSON.stringify(payload) });
      } else {
        res = await api('/api/catalogs/physical_states', { method: 'POST', body: JSON.stringify(payload) });
      }
      const grid = $('#statesGrid');
      const existing = grid.querySelector(`.catalog-card[data-id="${res.item.id}"]`);
      if (existing) existing.outerHTML = cardHtml(res.item);
      else grid.insertAdjacentHTML('beforeend', cardHtml(res.item));
      bindCard(grid.querySelector(`.catalog-card[data-id="${res.item.id}"]`));
      closeModal('stateModal');
      showToast('Estado guardado', `${res.item.nombre} se guardó correctamente.`, 'success');
    } catch (e) {
      showToast('No se pudo guardar', e.message, 'error');
    }
  });
</script>
