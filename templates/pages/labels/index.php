<?php
/** @var array $recentAssets */
use App\Core\View;

$fieldOptions = [
    'descripcion' => 'Descripción',
    'ubicacion' => 'Ubicación',
    'responsable' => 'Responsable',
    'categoria' => 'Categoría',
    'marca' => 'Marca / modelo',
    'serial' => 'Serial',
    'estado_fisico' => 'Estado físico',
];
$defaultFields = ['descripcion', 'ubicacion', 'responsable'];
?>
<link rel="stylesheet" href="/assets/css/wizard-incorporar.css">
<link rel="stylesheet" href="/assets/css/print.css">

<div class="page-heading">
  <div>
    <span class="eyebrow">REPORTES</span>
    <h1>Etiquetas de bienes</h1>
    <p>Buscá los bienes, elegí qué información va en la etiqueta, y mirá la vista previa antes de generar.</p>
  </div>
</div>

<div class="aw-layout">
  <section class="panel aw-card">
    <div class="wizard-body">
      <div class="aw-panel-head">
        <div class="wizard-copy">
          <h4>1. Elegí los bienes</h4>
          <p>Tocá un bien para agregarlo o quitarlo — los que ya están en la etiquetada quedan marcados en la misma lista.</p>
        </div>
        <span class="aw-hint" id="labelCountHint">0 etiqueta(s)</span>
      </div>

      <label class="aw-searchbox" style="height:42px;margin-bottom:10px"><i style="font-style:normal">⌕</i> <input id="labelAssetSearch" placeholder="Buscar por número, serial o descripción…" autocomplete="off"></label>
      <div class="aw-smart-options" style="border:1px solid var(--border);border-radius:9px;max-height:230px" id="labelAssetResults">
        <?php foreach ($recentAssets as $a): ?>
          <button type="button" class="search-result" data-add="<?= (int) $a['id'] ?>" data-numero="<?= View::e($a['numero_bien']) ?>" data-desc="<?= View::e($a['descripcion']) ?>">
            <span class="result-icon blue">▦</span>
            <span><strong><?= View::e($a['numero_bien']) ?></strong><small><?= View::e($a['descripcion']) ?></small></span>
            <span class="result-check">✓</span>
          </button>
        <?php endforeach; ?>
      </div>

      <div class="inline-section" style="margin-top:14px">
        <div class="inline-section-header"><strong>Bienes a etiquetar</strong></div>
        <div id="labelSelectedList"><p class="field-help">Todavía no agregaste ningún bien.</p></div>
      </div>

      <div class="aw-panel-head" style="margin-top:20px">
        <div class="wizard-copy">
          <h4>2. Qué va en la etiqueta</h4>
          <p>El número de bien siempre se incluye. El resto es opcional — armá tu configuración y guardala como plantilla para la próxima vez.</p>
        </div>
      </div>

      <div style="margin-bottom:6px">
        <span class="field-label">Código QR</span>
        <div class="chip-row" id="labelQrChip">
          <label class="chip active" style="cursor:pointer">
            <input type="checkbox" class="hidden" id="labelQrToggle" checked style="display:none">
            Incluir QR (abre la ficha del bien al escanear)
          </label>
        </div>
      </div>

      <span class="field-label">Datos adicionales</span>
      <div class="chip-row" id="labelFieldChips">
        <?php foreach ($fieldOptions as $key => $label): ?>
          <label class="chip <?= in_array($key, $defaultFields, true) ? 'active' : '' ?>" style="cursor:pointer">
            <input type="checkbox" class="hidden" data-field="<?= View::e($key) ?>" <?= in_array($key, $defaultFields, true) ? 'checked' : '' ?> style="display:none">
            <?= View::e($label) ?>
          </label>
        <?php endforeach; ?>
      </div>

      <div class="form-grid two" style="margin-top:16px">
        <label class="field">
          <span>Logo (opcional)</span>
          <input type="file" id="labelLogoInput" accept="image/*">
        </label>
        <label class="field">
          <span>Posición del logo</span>
          <select id="labelLogoPosition">
            <option value="inline">Junto al texto (chico)</option>
            <option value="top">Arriba de la etiqueta (ancho completo)</option>
          </select>
        </label>
      </div>
      <div id="labelLogoPreviewRow" class="hidden" style="margin:2px 0 10px">
        <span class="field-help">Logo cargado — </span>
        <button type="button" class="text-button" id="labelLogoRemove">quitar</button>
      </div>
      <label class="field">
        <span>Texto adicional (opcional)</span>
        <input type="text" id="labelExtraText" placeholder="Ej. Alcaldía de… / Propiedad institucional" maxlength="60">
      </label>

      <div class="aw-panel-head" style="margin-top:20px">
        <div class="wizard-copy">
          <h4>3. Plantillas</h4>
          <p>Guardá esta configuración (QR, campos, logo, texto) para reutilizarla sin volver a armarla.</p>
        </div>
      </div>
      <div class="field">
        <span>Cargar plantilla guardada</span>
        <div style="display:flex;gap:8px;align-items:center">
          <select id="labelTemplateSelect" style="flex:1;min-width:0"><option value="">Sin plantilla</option></select>
          <button class="btn btn-ghost btn-sm" type="button" id="labelTemplateSave" style="flex:none">Guardar como plantilla</button>
          <button class="btn btn-ghost btn-sm" type="button" id="labelTemplateDelete" style="flex:none">Eliminar</button>
        </div>
      </div>
    </div>

    <footer class="modal-footer wizard-footer">
      <span class="field-help" id="labelCountHintFooter">0 etiqueta(s) para generar</span>
      <div><button class="btn btn-primary" type="button" id="generateLabelsBtn">⬇ Generar etiquetas</button></div>
    </footer>
  </section>

  <aside class="panel aw-summary">
    <div class="panel-header compact">
      <div><span class="panel-kicker">VISTA PREVIA</span><h3>Así se va a ver</h3></div>
    </div>
    <div class="aw-summary-body">
      <div class="labels-grid" style="grid-template-columns:1fr">
        <article class="label-card" id="labelPreviewCard">
          <img id="labelPreviewLogoTop" class="label-logo-top hidden">
          <div class="label-body-row">
            <div class="label-qr" id="labelPreviewQr"></div>
            <div class="label-info">
              <img id="labelPreviewLogo" class="label-logo-inline hidden">
              <strong class="label-numero" id="previewNumero">BP-0001</strong>
              <span class="label-desc" id="previewDesc">Descripción de ejemplo</span>
              <div class="label-meta" id="previewMeta"></div>
              <span class="label-desc label-extra hidden" id="previewExtra"></span>
            </div>
          </div>
        </article>
      </div>
      <div class="aw-summary-help">Esta vista previa usa el primer bien seleccionado (o datos de ejemplo si todavía no elegiste ninguno) y se actualiza sola con cada cambio.</div>
    </div>
  </aside>
</div>

<script src="/assets/js/vendor/qrcode.js"></script>
<script type="module">
  import { $, $$, api, debounce } from '/assets/js/core/dom.js';
  import { showToast } from '/assets/js/core/toast.js';
  import { confirmAction } from '/assets/js/core/modal.js';

  const TEMPLATES_KEY = 'bp_label_templates';
  const selected = new Map(); // id -> { numero_bien, descripcion, ubicacion_nombre, responsable_nombre, categoria_nombre, marca_nombre, modelo_nombre, serial, estado_fisico_nombre, qty }
  let logoDataUrl = null;

  // ---- Selección de bienes: un mismo botón agrega/quita, marcado en la lista ----
  function isSelected(id) { return selected.has(id); }

  function syncResultButtonStates(container) {
    $$('[data-add]', container).forEach((btn) => {
      btn.classList.toggle('selected', isSelected(Number(btn.dataset.add)));
    });
  }

  function bindAssetButtons(container) {
    $$('[data-add]', container).forEach((btn) => btn.addEventListener('click', async () => {
      const id = Number(btn.dataset.add);
      if (selected.has(id)) {
        selected.delete(id);
      } else {
        const res = await api('/api/assets/search?q=' + encodeURIComponent(btn.dataset.numero));
        const full = (res.items || []).find((a) => a.id === id) || {};
        selected.set(id, { ...full, numero_bien: btn.dataset.numero, descripcion: btn.dataset.desc, qty: 1 });
      }
      syncResultButtonStates(container);
      renderSelected();
      updatePreview();
    }));
  }

  function renderSelected() {
    const box = $('#labelSelectedList');
    const entries = [...selected.entries()];
    box.innerHTML = entries.map(([id, a]) => `
      <div class="selected-asset" data-id="${id}">
        <span class="asset-thumb blue">▦</span>
        <div><strong>${a.numero_bien} · ${a.descripcion}</strong><small>Disponible para etiquetar</small></div>
        <label style="display:flex;align-items:center;gap:6px;font-size:10px;color:#687991">Copias
          <input type="number" min="1" max="50" value="${a.qty}" data-qty="${id}" style="width:52px;height:30px;border:1px solid #c8d4e5;border-radius:6px;text-align:center">
        </label>
        <button type="button" class="icon-action" data-remove="${id}">✕</button>
      </div>
    `).join('') || '<p class="field-help">Todavía no agregaste ningún bien.</p>';

    $$('[data-remove]', box).forEach((btn) => btn.addEventListener('click', () => {
      selected.delete(Number(btn.dataset.remove));
      syncResultButtonStates($('#labelAssetResults'));
      renderSelected();
      updatePreview();
    }));
    $$('[data-qty]', box).forEach((input) => input.addEventListener('input', () => {
      const id = Number(input.dataset.qty);
      const qty = Math.max(1, Math.min(50, Number(input.value) || 1));
      selected.get(id).qty = qty;
      updateCountHint();
    }));
    updateCountHint();
  }

  function updateCountHint() {
    const total = [...selected.values()].reduce((sum, a) => sum + a.qty, 0);
    const text = `${total} etiqueta${total === 1 ? '' : 's'} para generar`;
    $('#labelCountHint').textContent = `${total} etiqueta${total === 1 ? '' : 's'}`;
    $('#labelCountHintFooter').textContent = text;
  }

  bindAssetButtons($('#labelAssetResults'));

  const search = $('#labelAssetSearch');
  const results = $('#labelAssetResults');
  search?.addEventListener('input', debounce(async () => {
    const q = search.value.trim();
    if (q.length < 2) return;
    const res = await api('/api/assets/search?q=' + encodeURIComponent(q));
    results.innerHTML = (res.items || []).map((a) => `
      <button type="button" class="search-result" data-add="${a.id}" data-numero="${a.numero_bien}" data-desc="${a.descripcion}">
        <span class="result-icon blue">▦</span>
        <span><strong>${a.numero_bien}</strong><small>${a.descripcion}</small></span>
        <span class="result-check">✓</span>
      </button>
    `).join('') || '<div class="no-results">Sin bienes con ese criterio.</div>';
    bindAssetButtons(results);
    syncResultButtonStates(results);
  }, 300));

  // ---- Qué información va en la etiqueta ----
  function activeFields() {
    return $$('[data-field]', $('#labelFieldChips')).filter((c) => c.checked).map((c) => c.dataset.field);
  }
  $('#labelFieldChips')?.addEventListener('click', (e) => {
    const label = e.target.closest('.chip');
    if (!label) return;
    const checkbox = $('[data-field]', label);
    checkbox.checked = !checkbox.checked;
    label.classList.toggle('active', checkbox.checked);
    updatePreview();
  });

  $('#labelQrChip')?.addEventListener('click', (e) => {
    const label = e.target.closest('.chip');
    if (!label) return;
    const checkbox = $('#labelQrToggle');
    checkbox.checked = !checkbox.checked;
    label.classList.toggle('active', checkbox.checked);
    updatePreview();
  });

  // ---- Logo ----
  function applyLogo(dataUrl) {
    logoDataUrl = dataUrl;
    $('#labelLogoPreviewRow').classList.toggle('hidden', !dataUrl);
    updatePreview();
  }
  $('#labelLogoInput')?.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = () => applyLogo(reader.result);
    reader.readAsDataURL(file);
  });
  $('#labelLogoRemove')?.addEventListener('click', () => {
    $('#labelLogoInput').value = '';
    applyLogo(null);
  });
  $('#labelLogoPosition')?.addEventListener('change', updatePreview);
  $('#labelExtraText')?.addEventListener('input', debounce(updatePreview, 200));

  // ---- Vista previa ----
  const FIELD_MAP = {
    descripcion: (a) => a.descripcion,
    ubicacion: (a) => a.ubicacion_nombre,
    responsable: (a) => a.responsable_nombre,
    categoria: (a) => a.categoria_nombre,
    marca: (a) => [a.marca_nombre, a.modelo_nombre].filter(Boolean).join(' '),
    serial: (a) => a.serial ? ('Serial: ' + a.serial) : null,
    estado_fisico: (a) => a.estado_fisico_nombre,
  };

  function updatePreview() {
    const sample = selected.size ? [...selected.values()][0] : {
      numero_bien: 'BP-0001', descripcion: 'Descripción de ejemplo', ubicacion_nombre: 'Almacén Central',
      responsable_nombre: 'Responsable de ejemplo', categoria_nombre: 'Categoría', marca_nombre: 'Marca', modelo_nombre: 'Modelo',
      serial: 'SN-000000', estado_fisico_nombre: 'Bueno',
    };
    $('#previewNumero').textContent = sample.numero_bien;
    const showDesc = activeFields().includes('descripcion');
    $('#previewDesc').textContent = showDesc ? sample.descripcion : '';
    $('#previewDesc').classList.toggle('hidden', !showDesc);

    const metaParts = activeFields().filter((f) => f !== 'descripcion').map((f) => FIELD_MAP[f](sample)).filter(Boolean);
    $('#previewMeta').innerHTML = metaParts.map((t) => `<span>${t}</span>`).join('');

    const extra = $('#labelExtraText').value.trim();
    $('#previewExtra').textContent = extra;
    $('#previewExtra').classList.toggle('hidden', !extra);

    const logoTop = $('#labelPreviewLogoTop');
    const logoInline = $('#labelPreviewLogo');
    const logoPos = $('#labelLogoPosition').value;
    logoTop.classList.add('hidden');
    logoInline.classList.add('hidden');
    if (logoDataUrl) {
      const target = logoPos === 'top' ? logoTop : logoInline;
      target.src = logoDataUrl;
      target.classList.remove('hidden');
    }

    const qrBox = $('#labelPreviewQr');
    const includeQr = $('#labelQrToggle').checked;
    qrBox.classList.toggle('hidden', !includeQr);
    if (includeQr) {
      const qr = qrcode(0, 'M');
      qr.addData(location.origin + '/inventario/' + (selected.size ? [...selected.keys()][0] : '0'));
      qr.make();
      qrBox.innerHTML = qr.createSvgTag(3, 0);
    }
  }

  // ---- Plantillas (QR, campos, logo, posición y texto — no la selección de bienes) ----
  function readTemplates() {
    try { return JSON.parse(localStorage.getItem(TEMPLATES_KEY) || '{}'); } catch (e) { return {}; }
  }
  function writeTemplates(templates) {
    localStorage.setItem(TEMPLATES_KEY, JSON.stringify(templates));
  }
  function refreshTemplateSelect() {
    const templates = readTemplates();
    const select = $('#labelTemplateSelect');
    const current = select.value;
    select.innerHTML = '<option value="">Sin plantilla</option>' +
      Object.keys(templates).map((name) => `<option value="${name}">${name}</option>`).join('');
    if (templates[current]) select.value = current;
  }
  function currentConfig() {
    return {
      qr: $('#labelQrToggle').checked,
      fields: activeFields(),
      logo: logoDataUrl,
      logoPosition: $('#labelLogoPosition').value,
      extraText: $('#labelExtraText').value.trim(),
    };
  }
  function applyConfig(cfg) {
    $('#labelQrToggle').checked = cfg.qr !== false;
    $('#labelQrChip .chip')?.classList.toggle('active', cfg.qr !== false);
    $$('[data-field]', $('#labelFieldChips')).forEach((c) => {
      c.checked = (cfg.fields || []).includes(c.dataset.field);
      c.closest('.chip').classList.toggle('active', c.checked);
    });
    applyLogo(cfg.logo || null);
    $('#labelLogoPosition').value = cfg.logoPosition || 'inline';
    $('#labelExtraText').value = cfg.extraText || '';
    updatePreview();
  }

  $('#labelTemplateSave')?.addEventListener('click', () => {
    const name = (prompt('Nombre de la plantilla:') || '').trim();
    if (!name) return;
    const templates = readTemplates();
    templates[name] = currentConfig();
    writeTemplates(templates);
    refreshTemplateSelect();
    $('#labelTemplateSelect').value = name;
    showToast('Plantilla guardada', `"${name}" quedó disponible para la próxima vez.`, 'success');
  });

  $('#labelTemplateSelect')?.addEventListener('change', () => {
    const name = $('#labelTemplateSelect').value;
    if (!name) return;
    const templates = readTemplates();
    if (templates[name]) applyConfig(templates[name]);
  });

  $('#labelTemplateDelete')?.addEventListener('click', () => {
    const name = $('#labelTemplateSelect').value;
    if (!name) { showToast('Elegí una plantilla', 'Seleccioná primero la plantilla que querés eliminar.', 'warning'); return; }
    confirmAction({
      title: 'Eliminar plantilla',
      message: `¿Confirmás eliminar la plantilla "${name}"? Esta acción no se puede deshacer.`,
      confirmLabel: 'Eliminar',
      danger: true,
      onConfirm: () => {
        const templates = readTemplates();
        delete templates[name];
        writeTemplates(templates);
        refreshTemplateSelect();
        showToast('Plantilla eliminada', `"${name}" se quitó de tus plantillas guardadas.`, 'success');
      },
    });
  });

  $('#generateLabelsBtn')?.addEventListener('click', () => {
    if (selected.size === 0) {
      showToast('Agregá al menos un bien', 'Buscá y seleccioná los bienes que querés etiquetar.', 'warning');
      return;
    }
    const ids = [];
    selected.forEach((a, id) => { for (let i = 0; i < a.qty; i++) ids.push(id); });
    if (logoDataUrl) localStorage.setItem('bp_label_logo', logoDataUrl);
    else localStorage.removeItem('bp_label_logo');
    const params = new URLSearchParams({
      ids: ids.join(','),
      fields: activeFields().join(','),
      text: $('#labelExtraText').value.trim(),
      qr: $('#labelQrToggle').checked ? '1' : '0',
      logoPos: $('#labelLogoPosition').value,
    });
    window.open('/etiquetas/imprimir?' + params.toString(), '_blank');
  });

  refreshTemplateSelect();
  updatePreview();
</script>
