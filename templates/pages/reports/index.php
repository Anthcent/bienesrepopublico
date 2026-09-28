<?php
/** @var array $templates */
/** @var array $locations */
/** @var array $responsibles */
/** @var array $physicalStates */
/** @var array $customColumnGroups */
use App\Core\View;

$templateIcons = [
    'inventario_general' => '▥', 'activos' => '▦', 'prestados' => '↗', 'prestamos_vencidos' => '!',
    'desincorporados' => '↶', 'movimientos' => '⇄', 'por_ubicacion' => '⌂', 'por_responsable' => '♙',
    'por_estado_fisico' => '◫', 'verificacion' => '✓', 'personalizado' => '⚙',
];
$templateCategories = [
    'inventario_general' => 'General', 'activos' => 'Inventario', 'prestados' => 'Préstamos', 'prestamos_vencidos' => 'Préstamos',
    'desincorporados' => 'Inventario', 'movimientos' => 'Movimientos', 'por_ubicacion' => 'Inventario',
    'por_responsable' => 'Inventario', 'por_estado_fisico' => 'Inventario', 'verificacion' => 'Verificación',
    'personalizado' => 'Personalizado',
];
$templateSubtitles = [
    'inventario_general' => 'Listado completo de bienes', 'activos' => 'Solo activos y vigentes',
    'prestados' => 'Bienes actualmente prestados', 'prestamos_vencidos' => 'Con fechas vencidas',
    'desincorporados' => 'Retirados del inventario activo', 'movimientos' => 'Historial y trazabilidad',
    'por_ubicacion' => 'Agrupado por ubicación física', 'por_responsable' => 'Custodios y asignaciones',
    'por_estado_fisico' => 'Filtrado por estado físico', 'verificacion' => 'Hoja de jornadas con checks',
    'personalizado' => 'Elegís las columnas y filtros',
];
/**
 * Qué filtros honra de verdad cada plantilla en ReportService::rows() — antes
 * se mostraban los 7 campos siempre, sin importar la plantilla, aunque varias
 * los ignoran por completo (movimientos/prestamos_vencidos no filtran nada;
 * verificacion solo ubicación/responsable). Mostrar controles que no hacen
 * nada era buena parte de la confusión. `asset_id` (bien específico) y las
 * fechas relativas se suman a cualquier plantilla que ya soporte desde/hasta.
 */
$templateFilterFields = [
    'inventario_general' => ['location_id', 'responsible_id', 'estado_administrativo', 'disponibilidad', 'informacion_completa', 'desde', 'hasta', 'q', 'asset_id'],
    'activos' => ['location_id', 'responsible_id', 'disponibilidad', 'informacion_completa', 'desde', 'hasta', 'q', 'asset_id'],
    'prestados' => ['location_id', 'responsible_id', 'informacion_completa', 'desde', 'hasta', 'q', 'asset_id'],
    'prestamos_vencidos' => [],
    'desincorporados' => ['location_id', 'responsible_id', 'disponibilidad', 'informacion_completa', 'desde', 'hasta', 'q', 'asset_id'],
    'movimientos' => [],
    'por_ubicacion' => ['location_id', 'responsible_id', 'estado_administrativo', 'disponibilidad', 'informacion_completa', 'desde', 'hasta', 'q', 'asset_id'],
    'por_responsable' => ['location_id', 'responsible_id', 'estado_administrativo', 'disponibilidad', 'informacion_completa', 'desde', 'hasta', 'q', 'asset_id'],
    'por_estado_fisico' => ['location_id', 'responsible_id', 'estado_administrativo', 'disponibilidad', 'physical_state_id', 'informacion_completa', 'desde', 'hasta', 'q', 'asset_id'],
    'verificacion' => ['location_id', 'responsible_id'],
    'personalizado' => ['location_id', 'responsible_id', 'estado_administrativo', 'disponibilidad', 'informacion_completa', 'desde', 'hasta', 'q', 'asset_id'],
];
$categories = array_values(array_unique(array_values($templateCategories)));
$firstTemplate = array_key_first($templates);
?>
<link rel="stylesheet" href="/assets/css/wizard-incorporar.css">

<div class="page-heading">
  <div>
    <span class="eyebrow">REPORTES</span>
    <h1>Generar reporte</h1>
    <p>Un paso a la vez: elegí la plantilla, ajustá solo los filtros que aplican, y generá.</p>
  </div>
</div>

<div class="aw-layout">
  <section class="panel aw-card">
    <header class="wizard-progress">
      <button type="button" class="wizard-step active" data-rstep="1"><span>1</span><small>Plantilla</small></button>
      <div class="wizard-line"></div>
      <button type="button" class="wizard-step" data-rstep="2"><span>2</span><small>Filtros</small></button>
      <div class="wizard-line"></div>
      <button type="button" class="wizard-step" data-rstep="3"><span>3</span><small>Generar</small></button>
    </header>

    <div class="wizard-body">
      <!-- PASO 1: PLANTILLA -->
      <section class="wizard-panel active" data-rpanel="1">
        <div class="aw-panel-head">
          <div class="wizard-copy">
            <small class="aw-eyebrow">PASO 1 DE 3</small>
            <h4>¿Qué reporte necesitás?</h4>
            <p>Filtrá por categoría o buscá directamente. La plantilla elegida define qué filtros verás en el siguiente paso.</p>
          </div>
        </div>

        <div class="aw-type-toolbar">
          <label class="aw-searchbox">⌕ <input id="templateSearch" placeholder="Buscar plantilla…"></label>
        </div>

        <div class="chip-row" id="categoryChips" style="margin-bottom:10px">
          <button type="button" class="chip active" data-category="">Todas</button>
          <?php foreach ($categories as $cat): ?>
            <button type="button" class="chip" data-category="<?= View::e($cat) ?>"><?= View::e($cat) ?></button>
          <?php endforeach; ?>
        </div>

        <div class="type-grid" id="templateGrid">
          <?php foreach ($templates as $key => $label): ?>
            <button type="button" class="type-card <?= $key === $firstTemplate ? 'selected' : '' ?>" data-template="<?= View::e($key) ?>" data-category="<?= View::e($templateCategories[$key] ?? '') ?>" data-search="<?= View::e(mb_strtolower($label)) ?>">
              <i><?= $templateIcons[$key] ?? '▥' ?></i><strong><?= View::e($label) ?></strong><small><?= View::e($templateSubtitles[$key] ?? '') ?></small>
            </button>
          <?php endforeach; ?>
        </div>

        <div class="aw-hint" id="templatePreviewBox" style="white-space:normal;margin-top:12px;line-height:1.6">
          <strong id="templatePreviewTitle" style="display:block;margin-bottom:4px;color:var(--navy-900)">Esta plantilla incluye:</strong>
          <span id="templatePreviewCols"></span>
        </div>

        <div class="aw-other-create" id="customColumnsBox">
          <h3>Elegí las columnas de tu reporte</h3>
          <?php foreach ($customColumnGroups as $groupLabel => $columns): ?>
            <p class="field-help" style="margin:10px 0 6px;font-weight:800;color:var(--navy-900)"><?= View::e($groupLabel) ?></p>
            <div class="chip-row">
              <?php foreach ($columns as $colKey => $colLabel): ?>
                <label class="chip <?= $colKey === 'numero_bien' ? 'active' : '' ?>" style="cursor:pointer">
                  <input type="checkbox" class="hidden" data-column="<?= View::e($colKey) ?>" <?= $colKey === 'numero_bien' ? 'checked disabled' : '' ?> style="display:none">
                  <?= View::e($colLabel) ?>
                </label>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </section>

      <!-- PASO 2: FILTROS -->
      <section class="wizard-panel" data-rpanel="2">
        <div class="aw-panel-head">
          <div class="wizard-copy">
            <small class="aw-eyebrow">PASO 2 DE 3</small>
            <h4>Filtrá <span id="filterTemplateName"></span></h4>
            <p>Solo se muestran los filtros que esta plantilla realmente aplica. Es opcional: podés pasar de largo.</p>
          </div>
          <span class="aw-hint" id="noFiltersHint" style="display:none">Esta plantilla no usa filtros</span>
        </div>

        <div class="form-grid two">
          <label class="field" data-filter-field="location_id">
            <span>Ubicación</span>
            <select id="reportLocation"><option value="">Todas</option><?php foreach ($locations as $l): ?><option value="<?= (int) $l['id'] ?>"><?= View::e($l['nombre']) ?></option><?php endforeach; ?></select>
          </label>
          <label class="field" data-filter-field="responsible_id">
            <span>Responsable</span>
            <select id="reportResponsible"><option value="">Todos</option><?php foreach ($responsibles as $r): ?><option value="<?= (int) $r['id'] ?>"><?= View::e($r['nombre']) ?></option><?php endforeach; ?></select>
          </label>
          <label class="field" data-filter-field="estado_administrativo">
            <span>Estado administrativo</span>
            <select id="reportEstadoAdmin"><option value="">Todos</option><option value="ACTIVO">Activo</option><option value="DESINCORPORADO">Desincorporado</option></select>
          </label>
          <label class="field" data-filter-field="disponibilidad">
            <span>Disponibilidad</span>
            <select id="reportDisponibilidad"><option value="">Todas</option><option value="DISPONIBLE">Disponible</option><option value="PRESTADO">Prestado</option></select>
          </label>
          <label class="field" data-filter-field="physical_state_id">
            <span>Estado físico</span>
            <select id="reportPhysicalState"><option value="">Todos</option><?php foreach ($physicalStates as $ps): ?><option value="<?= (int) $ps['id'] ?>"><?= View::e($ps['nombre']) ?></option><?php endforeach; ?></select>
          </label>
          <label class="field" data-filter-field="desde"><span>Fecha desde</span><input type="date" id="reportDesde"></label>
          <label class="field" data-filter-field="hasta"><span>Fecha hasta</span><input type="date" id="reportHasta"></label>
          <label class="field wide" data-filter-field="q"><span>Búsqueda específica</span><input type="text" id="reportQuery" placeholder="Número de bien, serial o descripción…"></label>
        </div>

        <div class="field" data-filter-field="desde" style="margin-top:2px">
          <span>Período rápido</span>
          <div class="chip-row" id="datePresets">
            <button type="button" class="chip" data-days="7">Últimos 7 días</button>
            <button type="button" class="chip" data-days="30">Últimos 30 días</button>
            <button type="button" class="chip" data-preset="month">Este mes</button>
            <button type="button" class="chip" data-preset="year">Este año</button>
            <button type="button" class="chip" data-preset="clear">Todo</button>
          </div>
        </div>

        <div class="field" data-filter-field="asset_id" style="margin-top:12px">
          <span>Bien específico (opcional)</span>
          <label class="aw-searchbox" style="height:42px"><i style="font-style:normal">⌕</i> <input id="assetPickSearch" placeholder="Buscar por número, serial o descripción…" autocomplete="off"></label>
          <div id="assetPickResults"></div>
          <div id="assetPickSelected"></div>
        </div>

        <label class="field" data-filter-field="informacion_completa" style="flex-direction:row;align-items:center;gap:8px;margin-top:14px">
          <input type="checkbox" id="reportSinFoto" style="width:16px;height:16px;flex:none">
          <span style="font-size:11px">Solo bienes con información incompleta (sin fotografía)</span>
        </label>

        <div class="aw-inline-actions" style="justify-content:flex-start;margin-top:14px">
          <button class="btn btn-ghost btn-sm" type="button" id="clearFilters">Limpiar filtros</button>
        </div>
      </section>

      <!-- PASO 3: GENERAR -->
      <section class="wizard-panel" data-rpanel="3">
        <div class="aw-panel-head">
          <div class="wizard-copy">
            <small class="aw-eyebrow">PASO 3 DE 3</small>
            <h4>Revisá y generá</h4>
            <p>La vista previa y el reporte final usan exactamente estos mismos filtros.</p>
          </div>
        </div>

        <article class="review-card">
          <div class="review-icon">◫</div>
          <div class="review-main">
            <h3 id="reviewTemplateName">—</h3>
            <p id="reviewEstimate">Calculando…</p>
            <div class="review-grid" id="reviewFilterTags"></div>
          </div>
        </article>

        <div class="aw-inline-actions" style="justify-content:flex-start;margin-top:16px">
          <button class="btn btn-secondary" type="button" id="previewBtn">◫ Vista previa</button>
          <button class="btn btn-primary" type="button" id="generateBtn">⬇ Generar reporte</button>
        </div>
      </section>
    </div>

    <footer class="modal-footer wizard-footer">
      <button class="btn btn-ghost" type="button" id="rBack" style="visibility:hidden">← Atrás</button>
      <div><button class="btn btn-primary" type="button" id="rNext">Continuar →</button></div>
    </footer>
  </section>

  <aside class="panel aw-summary">
    <div class="panel-header compact">
      <div><span class="panel-kicker">RESUMEN</span><h3>Este reporte</h3></div>
    </div>
    <div class="aw-summary-body">
      <div class="aw-summary-list">
        <div class="aw-summary-row"><span>Plantilla</span><strong id="sideTemplate"><?= View::e($templates[$firstTemplate]) ?></strong></div>
        <div class="aw-summary-row"><span>Filtros activos</span><strong id="sideFilterCount">0</strong></div>
        <div class="aw-summary-row"><span>Registros estimados</span><strong id="sideRegistros">—</strong></div>
      </div>
    </div>
  </aside>
</div>

<div class="modal-backdrop" id="reportPreviewBackdrop"></div>
<section class="modal" id="reportPreviewModal" role="dialog" aria-modal="true" style="width:min(920px,calc(100vw - 32px));height:min(88vh,calc(100dvh - 32px))">
  <div class="modal-header">
    <div><span class="panel-kicker">PREVISUALIZACIÓN</span><h3 id="modalPreviewTitle">Vista previa del reporte</h3></div>
    <button class="icon-btn modal-close" data-modal-close="reportPreviewModal">×</button>
  </div>
  <div class="report-preview-toolbar">
    <div class="report-preview-tabs">
      <button type="button" class="chip active" data-tab="documento">Documento</button>
      <button type="button" class="chip" data-tab="datos">Datos</button>
      <button type="button" class="chip" data-tab="resumen">Resumen</button>
    </div>
    <button class="btn btn-secondary" id="refreshPreviewBtn" style="height:34px">↻ Actualizar vista</button>
  </div>
  <div class="modal-body report-preview-modal-body">
    <div class="report-preview-panel" data-panel="documento">
      <iframe class="report-preview-frame" id="reportPreviewFrame" src="about:blank"></iframe>
    </div>
    <div class="report-preview-panel" data-panel="datos" style="display:none">
      <div class="report-data-wrap">
        <table class="smart-table" id="reportDataTable"><thead><tr></tr></thead><tbody></tbody></table>
      </div>
    </div>
    <div class="report-preview-panel" data-panel="resumen" style="display:none">
      <div class="report-data-wrap" id="reportSummaryPanel"></div>
    </div>
  </div>
  <div class="modal-footer">
    <button class="btn btn-ghost" id="modalPrintBtn">⎙ Versión impresión</button>
    <a class="btn btn-primary" id="modalDownloadBtn" href="#" target="_blank">⬇ Generar reporte</a>
  </div>
</section>

<script type="module">
  import { $, $$, api, debounce } from '/assets/js/core/dom.js';
  import { showToast } from '/assets/js/core/toast.js';
  import { registerModal, openModal, closeModal } from '/assets/js/core/modal.js';

  registerModal('reportPreviewModal', 'reportPreviewBackdrop');
  let modalOpen = false;

  const templates = <?= json_encode($templates, JSON_UNESCAPED_UNICODE) ?>;
  const templateFields = <?= json_encode($templateFilterFields, JSON_UNESCAPED_UNICODE) ?>;
  const FIELD_LABELS = {
    location_id: 'Ubicación', responsible_id: 'Responsable', estado_administrativo: 'Estado administrativo',
    disponibilidad: 'Disponibilidad', physical_state_id: 'Estado físico', desde: 'Desde', hasta: 'Hasta',
    q: 'Búsqueda', informacion_completa: 'Sin fotografía', asset_id: 'Bien específico',
  };
  let selectedTemplate = <?= json_encode($firstTemplate) ?>;
  let selectedAssetId = null;
  let selectedAssetLabel = '';
  let rstep = 1;
  const maxRStep = 3;

  const fields = {
    location_id: $('#reportLocation'),
    responsible_id: $('#reportResponsible'),
    estado_administrativo: $('#reportEstadoAdmin'),
    disponibilidad: $('#reportDisponibilidad'),
    physical_state_id: $('#reportPhysicalState'),
    desde: $('#reportDesde'),
    hasta: $('#reportHasta'),
    q: $('#reportQuery'),
  };

  /** Solo se leen (y se muestran) los campos que la plantilla actual realmente usa. */
  function applicableFields() {
    return templateFields[selectedTemplate] || [];
  }

  function syncFieldVisibility() {
    const applicable = applicableFields();
    $$('[data-filter-field]').forEach((el) => {
      el.classList.toggle('hidden', !applicable.includes(el.dataset.filterField));
    });
    $('#noFiltersHint').style.display = applicable.length === 0 ? '' : 'none';
    $('#customColumnsBox').classList.toggle('show', selectedTemplate === 'personalizado');
  }

  function currentFilters() {
    const applicable = applicableFields();
    const filters = {};
    Object.entries(fields).forEach(([key, el]) => {
      if (applicable.includes(key) && el.value) filters[key] = el.value;
    });
    if (applicable.includes('informacion_completa') && $('#reportSinFoto').checked) filters.informacion_completa = '0';
    if (applicable.includes('asset_id') && selectedAssetId) filters.asset_id = selectedAssetId;
    if (selectedTemplate === 'personalizado') {
      const cols = $$('#customColumnsBox [data-column]').filter((c) => c.checked).map((c) => c.dataset.column);
      if (cols.length) filters.columns = cols.join(',');
    }
    return filters;
  }

  function buildParams() {
    return new URLSearchParams({ template: selectedTemplate, ...currentFilters() });
  }

  function labelOf(select) {
    const opt = select.options[select.selectedIndex];
    return opt ? opt.textContent : '';
  }

  function updateSidebar(filters) {
    const tagEntries = Object.entries(filters).filter(([key]) => key !== 'columns');
    $('#sideFilterCount').textContent = tagEntries.length;
    $('#sideTemplate').textContent = templates[selectedTemplate];
    $('#reviewTemplateName').textContent = templates[selectedTemplate];
    $('#filterTemplateName').textContent = '· ' + templates[selectedTemplate];

    const tagsBox = $('#reviewFilterTags');
    tagsBox.innerHTML = tagEntries.map(([key, value]) => {
      let display = value;
      if (key === 'location_id') display = labelOf(fields.location_id);
      else if (key === 'responsible_id') display = labelOf(fields.responsible_id);
      else if (key === 'physical_state_id') display = labelOf(fields.physical_state_id);
      else if (key === 'informacion_completa') display = 'Sí';
      else if (key === 'asset_id') display = selectedAssetLabel || value;
      return `<div><span>${FIELD_LABELS[key] || key}</span><strong>${display}</strong></div>`;
    }).join('') || '<div><span>Filtros</span><strong>Ninguno — reporte completo</strong></div>';
  }

  async function updateEstimate() {
    const filters = currentFilters();
    updateSidebar(filters);
    try {
      const res = await api('/api/reports/estimate?' + buildParams().toString());
      const n = res.estimate.registros;
      $('#sideRegistros').textContent = n.toLocaleString('es-CO');
      $('#reviewEstimate').textContent = n === 0
        ? 'Ningún registro coincide con los filtros actuales.'
        : `${n.toLocaleString('es-CO')} registro${n === 1 ? '' : 's'} · ${res.estimate.ubicaciones} ubicación(es) · ${res.estimate.responsables} responsable(s)`;
    } catch (e) {
      $('#sideRegistros').textContent = '—';
      $('#reviewEstimate').textContent = 'No se pudo calcular: ' + e.message;
    }
    $('#modalDownloadBtn').href = '/reportes/preview?' + buildParams().toString();
  }

  function refreshDocumentTab() {
    $('#reportPreviewFrame').src = '/reportes/preview?' + buildParams().toString();
  }

  const FRIENDLY_COLUMNS = {
    numero_bien: 'Número', serial: 'Serial', descripcion: 'Descripción', estado_administrativo: 'Estado',
    disponibilidad: 'Disponibilidad', ubicacion_nombre: 'Ubicación', responsable_nombre: 'Responsable',
    estado_fisico_nombre: 'Estado físico', created_at: 'Fecha', fecha_vencimiento: 'Vence', estado: 'Estado',
    jornada: 'Jornada', resultado: 'Resultado', tipo_movimiento: 'Tipo de movimiento', motivo: 'Motivo',
  };

  /**
   * Qué columnas trae realmente cada plantilla fija (calcado del SELECT de
   * cada caso en `ReportService::rows()`), para poder mostrar "esto es lo
   * que se genera" en el paso 1 sin tener que avanzar todo el flujo.
   * "personalizado" no tiene lista fija: las columnas las elige el usuario.
   */
  const TEMPLATE_COLUMNS = {
    inventario_general: ['numero_bien', 'serial', 'descripcion', 'ubicacion_nombre', 'responsable_nombre', 'estado_fisico_nombre', 'created_at'],
    activos: ['numero_bien', 'serial', 'descripcion', 'ubicacion_nombre', 'responsable_nombre', 'estado_fisico_nombre', 'created_at'],
    prestados: ['numero_bien', 'serial', 'descripcion', 'ubicacion_nombre', 'responsable_nombre', 'estado_fisico_nombre', 'created_at'],
    prestamos_vencidos: ['numero_bien', 'responsable_nombre', 'fecha_vencimiento', 'estado', 'created_at'],
    desincorporados: ['numero_bien', 'serial', 'descripcion', 'ubicacion_nombre', 'responsable_nombre', 'estado_fisico_nombre', 'created_at'],
    movimientos: ['numero_bien', 'tipo_movimiento', 'motivo', 'responsable_nombre', 'created_at'],
    por_ubicacion: ['numero_bien', 'serial', 'descripcion', 'ubicacion_nombre', 'responsable_nombre', 'estado_fisico_nombre', 'created_at'],
    por_responsable: ['numero_bien', 'serial', 'descripcion', 'ubicacion_nombre', 'responsable_nombre', 'estado_fisico_nombre', 'created_at'],
    por_estado_fisico: ['numero_bien', 'serial', 'descripcion', 'ubicacion_nombre', 'responsable_nombre', 'estado_fisico_nombre', 'created_at'],
    verificacion: ['numero_bien', 'jornada', 'resultado', 'ubicacion_nombre', 'responsable_nombre', 'created_at'],
  };

  async function fetchRows() {
    const res = await api('/api/reports/rows?' + buildParams().toString());
    return res.rows || [];
  }

  function renderDataTab(rows) {
    const table = $('#reportDataTable');
    if (rows.length === 0) {
      table.querySelector('thead tr').innerHTML = '';
      table.querySelector('tbody').innerHTML = '<tr><td class="no-results">Sin registros para mostrar.</td></tr>';
      return;
    }
    const cols = Object.keys(rows[0]);
    table.querySelector('thead tr').innerHTML = cols.map((c) => `<th>${FRIENDLY_COLUMNS[c] || c}</th>`).join('');
    table.querySelector('tbody').innerHTML = rows.map((row) => `<tr>${cols.map((c) => `<td>${row[c] ?? '—'}</td>`).join('')}</tr>`).join('');
  }

  function renderSummaryTab(rows) {
    const panel = $('#reportSummaryPanel');
    if (rows.length === 0 || !('ubicacion_nombre' in rows[0])) {
      panel.innerHTML = '<p class="field-help">Esta plantilla no agrupa por ubicación.</p>';
      return;
    }
    const counts = {};
    rows.forEach((r) => { const k = r.ubicacion_nombre || 'Sin ubicación'; counts[k] = (counts[k] || 0) + 1; });
    const sorted = Object.entries(counts).sort((a, b) => b[1] - a[1]).slice(0, 8);
    panel.innerHTML = '<div class="aw-summary-list">' + sorted.map(([name, n]) =>
      `<div class="aw-summary-row"><span>${name}</span><strong>${n}</strong></div>`
    ).join('') + `<div class="aw-summary-row"><span>Muestra analizada</span><strong>${rows.length} registro(s)</strong></div></div>`;
  }

  let activeTab = 'documento';
  async function refreshActiveTab() {
    if (activeTab === 'documento') refreshDocumentTab();
    else {
      const rows = await fetchRows();
      if (activeTab === 'datos') renderDataTab(rows);
      else renderSummaryTab(rows);
    }
  }

  function openPreview() {
    $('#modalPreviewTitle').textContent = 'Vista previa · ' + templates[selectedTemplate];
    modalOpen = true;
    openModal('reportPreviewModal');
    refreshActiveTab();
  }

  document.querySelector('[data-modal-close="reportPreviewModal"]')?.addEventListener('click', () => { modalOpen = false; });
  $('#reportPreviewBackdrop')?.addEventListener('click', () => { modalOpen = false; });

  const debouncedRefresh = debounce(() => { updateEstimate(); if (modalOpen) refreshActiveTab(); }, 350);

  // ---- Paso 1: plantilla ----
  function updateTemplatePreview() {
    const box = $('#templatePreviewBox');
    const cols = TEMPLATE_COLUMNS[selectedTemplate];
    if (!cols) {
      // "personalizado": ya tiene su propio picker de columnas debajo, no hace falta duplicar la vista previa.
      box.classList.add('hidden');
      return;
    }
    box.classList.remove('hidden');
    $('#templatePreviewCols').textContent = cols.map((c) => FRIENDLY_COLUMNS[c] || c).join(' · ');
  }

  function selectTemplate(key) {
    selectedTemplate = key;
    $$('.type-card', $('#templateGrid')).forEach((b) => b.classList.toggle('selected', b.dataset.template === key));
    syncFieldVisibility();
    updateTemplatePreview();
    debouncedRefresh();
  }
  $$('.type-card', $('#templateGrid')).forEach((btn) => btn.addEventListener('click', () => selectTemplate(btn.dataset.template)));

  $('#templateSearch')?.addEventListener('input', () => {
    const q = $('#templateSearch').value.toLowerCase().trim();
    $$('.type-card', $('#templateGrid')).forEach((btn) => {
      btn.style.display = (!q || btn.dataset.search.includes(q)) ? '' : 'none';
    });
  });

  $('#categoryChips')?.addEventListener('click', (e) => {
    const chip = e.target.closest('.chip');
    if (!chip) return;
    $$('#categoryChips .chip').forEach((c) => c.classList.remove('active'));
    chip.classList.add('active');
    const cat = chip.dataset.category;
    $$('.type-card', $('#templateGrid')).forEach((btn) => {
      btn.style.display = (!cat || btn.dataset.category === cat) ? '' : 'none';
    });
  });

  // ---- Columnas del reporte personalizado ----
  $('#customColumnsBox')?.addEventListener('click', (e) => {
    const label = e.target.closest('.chip');
    if (!label) return;
    const checkbox = $('[data-column]', label);
    if (!checkbox || checkbox.disabled) return;
    checkbox.checked = !checkbox.checked;
    label.classList.toggle('active', checkbox.checked);
    debouncedRefresh();
  });

  // ---- Paso 2: filtros ----
  Object.values(fields).forEach((el) => el.addEventListener('input', debouncedRefresh));
  $('#reportSinFoto')?.addEventListener('change', debouncedRefresh);

  // Fechas relativas: calculan desde/hasta automáticamente en vez de que el
  // usuario tenga que abrir dos selectores de fecha y hacer la cuenta.
  function isoDate(d) { return d.toISOString().slice(0, 10); }
  $('#datePresets')?.addEventListener('click', (e) => {
    const btn = e.target.closest('.chip');
    if (!btn) return;
    $$('#datePresets .chip').forEach((c) => c.classList.remove('active'));
    btn.classList.add('active');
    const today = new Date();
    if (btn.dataset.days) {
      const from = new Date(today.getTime() - Number(btn.dataset.days) * 86400000);
      fields.desde.value = isoDate(from);
      fields.hasta.value = isoDate(today);
    } else if (btn.dataset.preset === 'month') {
      fields.desde.value = isoDate(new Date(today.getFullYear(), today.getMonth(), 1));
      fields.hasta.value = isoDate(today);
    } else if (btn.dataset.preset === 'year') {
      fields.desde.value = isoDate(new Date(today.getFullYear(), 0, 1));
      fields.hasta.value = isoDate(today);
    } else if (btn.dataset.preset === 'clear') {
      fields.desde.value = '';
      fields.hasta.value = '';
    }
    debouncedRefresh();
  });
  // Si el usuario toca las fechas a mano, ningún preset queda "activo".
  fields.desde.addEventListener('input', () => $$('#datePresets .chip').forEach((c) => c.classList.remove('active')));
  fields.hasta.addEventListener('input', () => $$('#datePresets .chip').forEach((c) => c.classList.remove('active')));

  // ---- Bien específico: búsqueda en vivo (mismo endpoint que el buscador global de bienes) ----
  const assetPickSearch = $('#assetPickSearch');
  const assetPickResults = $('#assetPickResults');
  function renderAssetPicked() {
    $('#assetPickSelected').innerHTML = selectedAssetId ? `
      <div class="selected-asset" data-id="${selectedAssetId}">
        <span class="asset-thumb blue">▦</span>
        <div><strong>${selectedAssetLabel}</strong><small>Bien seleccionado</small></div>
        <button type="button" class="icon-action" id="assetPickRemove">✕</button>
      </div>` : '';
    $('#assetPickRemove')?.addEventListener('click', () => {
      selectedAssetId = null;
      selectedAssetLabel = '';
      renderAssetPicked();
      debouncedRefresh();
    });
  }
  assetPickSearch?.addEventListener('input', debounce(async () => {
    const q = assetPickSearch.value.trim();
    if (q.length < 2) { assetPickResults.innerHTML = ''; return; }
    const res = await api('/api/assets/search?q=' + encodeURIComponent(q));
    assetPickResults.innerHTML = (res.items || []).map((a) => `
      <button type="button" class="search-result" data-pick-asset="${a.id}" data-numero="${a.numero_bien}" data-desc="${a.descripcion}">
        <span class="result-icon blue">▦</span>
        <span><strong>${a.numero_bien}</strong><small>${a.descripcion}</small></span>
      </button>
    `).join('') || '<div class="no-results">Sin bienes con ese criterio.</div>';
    $$('[data-pick-asset]', assetPickResults).forEach((btn) => btn.addEventListener('click', () => {
      selectedAssetId = btn.dataset.pickAsset;
      selectedAssetLabel = `${btn.dataset.numero} · ${btn.dataset.desc}`;
      assetPickSearch.value = '';
      assetPickResults.innerHTML = '';
      renderAssetPicked();
      debouncedRefresh();
    }));
  }, 300));

  $('#clearFilters')?.addEventListener('click', () => {
    Object.values(fields).forEach((el) => { el.value = ''; });
    $('#reportSinFoto').checked = false;
    $$('#datePresets .chip').forEach((c) => c.classList.remove('active'));
    selectedAssetId = null;
    selectedAssetLabel = '';
    renderAssetPicked();
    debouncedRefresh();
    showToast('Filtros limpiados', 'La configuración volvió a los valores por defecto.', 'success');
  });

  // ---- Paso 3: generar ----
  $$('.report-preview-tabs .chip').forEach((btn) => btn.addEventListener('click', () => {
    $$('.report-preview-tabs .chip').forEach((b) => b.classList.remove('active'));
    btn.classList.add('active');
    activeTab = btn.dataset.tab;
    $$('.report-preview-panel').forEach((p) => { p.style.display = p.dataset.panel === activeTab ? '' : 'none'; });
    refreshActiveTab();
  }));
  $('#refreshPreviewBtn')?.addEventListener('click', () => { refreshActiveTab(); showToast('Vista actualizada', 'La previsualización se regeneró con los filtros actuales.', 'success'); });
  $('#previewBtn')?.addEventListener('click', openPreview);
  $('#generateBtn')?.addEventListener('click', () => window.open('/reportes/preview?' + buildParams().toString(), '_blank'));
  $('#modalPrintBtn')?.addEventListener('click', () => {
    if (activeTab !== 'documento') { $('.report-preview-tabs .chip[data-tab="documento"]').click(); }
    setTimeout(() => $('#reportPreviewFrame').contentWindow?.print(), 200);
  });

  // ---- Navegación de pasos ----
  function renderRStep() {
    $$('.wizard-step').forEach((el) => {
      const n = Number(el.dataset.rstep);
      el.classList.toggle('active', n === rstep);
      el.classList.toggle('done', n < rstep);
    });
    $$('.wizard-panel').forEach((el) => el.classList.toggle('active', Number(el.dataset.rpanel) === rstep));
    $('#rBack').style.visibility = rstep === 1 ? 'hidden' : 'visible';
    $('#rNext').classList.toggle('hidden', rstep === maxRStep);
  }
  $('#rNext').addEventListener('click', () => { if (rstep < maxRStep) { rstep++; renderRStep(); } });
  $('#rBack').addEventListener('click', () => { if (rstep > 1) { rstep--; renderRStep(); } });
  $$('.wizard-step').forEach((btn) => btn.addEventListener('click', () => {
    const n = Number(btn.dataset.rstep);
    if (n < rstep) { rstep = n; renderRStep(); }
  }));

  syncFieldVisibility();
  updateTemplatePreview();
  updateEstimate();
</script>
