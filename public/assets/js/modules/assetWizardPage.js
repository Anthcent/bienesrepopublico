import { $, $$, api, debounce, escapeHtml } from '../core/dom.js';
import { showToast } from '../core/toast.js';
import { initSmartSelect } from '../core/smartSelect.js';

/**
 * Wizard de incorporación de bienes — página dedicada en `/inventario/nuevo`
 * (reemplaza el modal anterior). Replica el mockup `wizard_incorporar_bienes_v2`:
 * 4 pasos, selects con búsqueda interna y creación rápida de catálogos sin
 * salir del wizard. El contrato con el backend no cambió: se sigue enviando
 * el mismo payload a POST /api/assets que ya validaba AssetService.
 */
const DRAFT_KEY = 'bp_asset_wizard_draft';
const form = $('#assetWizardForm');
if (form) {
  form.addEventListener('submit', (e) => e.preventDefault());

  let step = 1;
  const maxStep = 4;
  let selectedCategoryLabel = $('.type-card.selected strong', form)?.textContent || '—';
  let selectedBrandLabel = null;
  let selectedModelLabel = null;
  let selectedLocationLabel = $('#locationTrigger')?.textContent || '—';
  let selectedResponsibleLabel = $('#responsibleTrigger')?.textContent || '—';
  let selectedStateLabel = $('.state-card.selected strong')?.textContent || '—';

  function renderStep() {
    $$('.wizard-step').forEach((el) => {
      const n = Number(el.dataset.step);
      el.classList.toggle('active', n === step);
      el.classList.toggle('done', n < step);
    });
    $$('.wizard-panel', form).forEach((el) => el.classList.toggle('active', Number(el.dataset.wizardPanel) === step));
    $('#wizardBack').style.visibility = step === 1 ? 'hidden' : 'visible';
    $('#wizardNextBtn').textContent = step === maxStep ? 'Incorporar bien' : 'Continuar →';
    $('#wizardTopContinue').textContent = step === maxStep ? 'Incorporar bien' : 'Continuar →';
    $('#wizardSubmitSimilar').classList.toggle('hidden', step !== maxStep);
    const pct = step * 25;
    $('#progressText').textContent = pct + '%';
    $('#progressBar').style.width = pct + '%';
    syncSummary();
  }

  function syncSummary() {
    const numero = $('#assetNumberFull').value.trim();
    const serial = $('#assetSerial').value.trim();
    const descripcion = $('#assetDescription').value.trim();
    const color = $('#colorInput').value.trim();

    $('#summaryNumber').textContent = numero || '—';
    $('#summarySerial').textContent = serial || '—';
    $('#summaryDescription').textContent = descripcion || 'Sin descripción';
    $('#summaryType').textContent = selectedCategoryLabel || '—';
    $('#summaryBrand').textContent = selectedBrandLabel || 'Sin especificar';
    $('#summaryModel').textContent = selectedModelLabel || 'Sin especificar';
    $('#summaryColor').textContent = color || '—';
    $('#summaryLocation').textContent = selectedLocationLabel || '—';
    $('#summaryResponsible').textContent = selectedResponsibleLabel || '—';
    $('#summaryState').textContent = selectedStateLabel || '—';

    const review = $('#wizardReview');
    if (review) {
      review.querySelector('[data-review="descripcion"]').textContent = descripcion || 'Sin descripción';
      review.querySelector('[data-review="numero"]').textContent = (numero || '—') + ' · ' + (serial || 'Sin serial');
      review.querySelector('[data-review="tipo"]').textContent = selectedCategoryLabel || '—';
      review.querySelector('[data-review="marca"]').textContent = selectedBrandLabel || 'Sin especificar';
      review.querySelector('[data-review="modelo"]').textContent = selectedModelLabel || 'Sin especificar';
      review.querySelector('[data-review="color"]').textContent = color || '—';
      review.querySelector('[data-review="ubicacion"]').textContent = selectedLocationLabel || '—';
      review.querySelector('[data-review="responsable"]').textContent = selectedResponsibleLabel || '—';
      review.querySelector('[data-review="estado"]').textContent = selectedStateLabel || '—';
    }
  }

  function next() {
    if (step < maxStep) { step++; renderStep(); return; }
    submit({ registerSimilar: false });
  }
  function back() { if (step > 1) { step--; renderStep(); } }
  $('#wizardNextBtn').addEventListener('click', next);
  $('#wizardTopContinue').addEventListener('click', next);
  $('#wizardBack').addEventListener('click', back);
  $$('.wizard-step').forEach((btn) => btn.addEventListener('click', () => {
    const n = Number(btn.dataset.step);
    if (n < step) { step = n; renderStep(); }
  }));

  // ---- Paso 1: tipo de bien ----
  function selectTypeCard(card) {
    $$('.type-card', form).forEach((c) => c.classList.remove('selected'));
    card.classList.add('selected');
    if (card.id === 'typeCardOther') {
      $('#otherCreate').classList.add('show');
      return;
    }
    $('#otherCreate').classList.remove('show');
    $('#categoryIdInput').value = card.dataset.categoryId;
    selectedCategoryLabel = card.querySelector('strong').textContent;
    syncSummary();
  }
  $$('.type-card', form).forEach((card) => card.addEventListener('click', () => selectTypeCard(card)));

  $('#typeSearch').addEventListener('input', (e) => {
    const q = e.target.value.toLowerCase().trim();
    $$('.type-card', form).forEach((c) => {
      if (c.id === 'typeCardOther') return;
      c.style.display = !q || c.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });

  $('#quickAddType').addEventListener('click', () => {
    selectTypeCard($('#typeCardOther'));
    $('#otherName').focus();
  });

  $('#cancelOther').addEventListener('click', () => {
    $('#otherCreate').classList.remove('show');
    $$('.type-card', form)[0]?.click();
  });
  $('#saveOther').addEventListener('click', async () => {
    const nombre = $('#otherName').value.trim();
    const tipoBien = $('#otherFamily').value.trim();
    if (!nombre) { showToast('Falta el nombre', 'Escribe el nombre del nuevo tipo.', 'warning'); return; }
    try {
      const res = await api('/api/catalogs/categories', { method: 'POST', body: JSON.stringify({ nombre, tipo_bien: tipoBien }) });
      const card = document.createElement('button');
      card.type = 'button';
      card.className = 'type-card';
      card.dataset.categoryId = res.item.id;
      card.innerHTML = `<i>▦</i><strong>${escapeHtml(res.item.nombre)}</strong><small>${escapeHtml(res.item.tipo_bien || '')}</small>`;
      card.addEventListener('click', () => selectTypeCard(card));
      $('#typeCardOther').insertAdjacentElement('beforebegin', card);
      $('#otherName').value = '';
      $('#otherFamily').value = '';
      selectTypeCard(card);
      showToast('Tipo creado', `${res.item.nombre} quedó seleccionado.`, 'success');
    } catch (e) {
      showToast('No se pudo crear el tipo', e.message, 'error');
    }
  });

  // ---- Smart selects (buscador interno + creación rápida) ----
  const brandSelect = initSmartSelect('brand', {
    onSelect: (value, label) => {
      $('#brandIdInput').value = value;
      selectedBrandLabel = label;
      onBrandChanged(value);
      syncSummary();
    },
  });
  const modelSelect = initSmartSelect('model', {
    onSelect: (value, label) => {
      $('#modelIdInput').value = value;
      selectedModelLabel = label;
      syncSummary();
    },
  });
  const locationSelect = initSmartSelect('location', {
    onSelect: (value, label) => {
      $('#locationIdInput').value = value;
      selectedLocationLabel = label;
      syncSummary();
    },
  });
  const responsibleSelect = initSmartSelect('responsible', {
    onSelect: (value, label) => {
      $('#responsibleIdInput').value = value;
      selectedResponsibleLabel = label;
      syncSummary();
    },
  });

  async function onBrandChanged(brandId) {
    modelSelect.clearOptions();
    $('#modelIdInput').value = '';
    selectedModelLabel = null;
    const modelTrigger = $('#modelTrigger');
    const modelEmpty = $('#modelEmpty');
    const createModelBtn = $('[data-create-model]');
    if (!brandId) {
      modelTrigger.textContent = 'Selecciona una marca primero';
      modelEmpty.textContent = 'Selecciona una marca para ver sus modelos.';
      createModelBtn.disabled = true;
      return;
    }
    modelTrigger.textContent = 'Sin especificar';
    modelEmpty.textContent = 'Este bien no tiene modelos registrados aún.';
    createModelBtn.disabled = false;
    try {
      const res = await api(`/api/catalogs/brands/${brandId}/models`);
      res.items.forEach((m) => modelSelect.addOption(m.id, m.nombre, 'Modelo existente', { select: false }));
    } catch (e) {
      showToast('No se pudieron cargar los modelos', e.message, 'error');
    }
  }

  // ---- Crear marca / modelo / ubicación / responsable sin salir del wizard ----
  $('[data-create-brand]').addEventListener('click', () => {
    $('[data-smart-select="brand"] .aw-smart-menu').classList.remove('show');
    $('#brandCreate').classList.add('show');
  });
  $('#closeBrand').addEventListener('click', () => $('#brandCreate').classList.remove('show'));
  $('#saveBrand').addEventListener('click', async () => {
    const nombre = $('#newBrandName').value.trim();
    if (!nombre) { showToast('Falta la marca', 'Escribe el nombre.', 'warning'); return; }
    try {
      const res = await api('/api/catalogs/brands', { method: 'POST', body: JSON.stringify({ nombre }) });
      brandSelect.addOption(res.item.id, res.item.nombre, 'Marca existente');
      $('#newBrandName').value = '';
      $('#brandCreate').classList.remove('show');
      showToast('Marca creada', `${res.item.nombre} quedó seleccionada.`, 'success');
    } catch (e) {
      showToast('No se pudo crear la marca', e.message, 'error');
    }
  });

  $('[data-create-model]').addEventListener('click', () => {
    if ($('[data-create-model]').disabled) return;
    $('[data-smart-select="model"] .aw-smart-menu').classList.remove('show');
    $('#modelCreate').classList.add('show');
  });
  $('#closeModel').addEventListener('click', () => $('#modelCreate').classList.remove('show'));
  $('#saveModel').addEventListener('click', async () => {
    const nombre = $('#newModelName').value.trim();
    const brandId = $('#brandIdInput').value;
    if (!brandId) { showToast('Falta la marca', 'Selecciona primero una marca.', 'warning'); return; }
    if (!nombre) { showToast('Falta el modelo', 'Escribe el nombre.', 'warning'); return; }
    try {
      const res = await api('/api/catalogs/models', { method: 'POST', body: JSON.stringify({ brand_id: brandId, nombre }) });
      modelSelect.addOption(res.item.id, res.item.nombre, 'Modelo existente');
      $('#newModelName').value = '';
      $('#modelCreate').classList.remove('show');
      showToast('Modelo creado', `${res.item.nombre} quedó seleccionado.`, 'success');
    } catch (e) {
      showToast('No se pudo crear el modelo', e.message, 'error');
    }
  });

  $('[data-create-location]').addEventListener('click', () => {
    $('[data-smart-select="location"] .aw-smart-menu').classList.remove('show');
    $('#locationCreate').classList.add('show');
  });
  $('#closeLocation').addEventListener('click', () => $('#locationCreate').classList.remove('show'));
  $('#saveLocation').addEventListener('click', async () => {
    const nombre = $('#newLocationName').value.trim();
    const pisoZona = $('#newLocationZone').value.trim();
    if (!nombre) { showToast('Falta la ubicación', 'Escribe el nombre.', 'warning'); return; }
    try {
      const res = await api('/api/catalogs/locations', { method: 'POST', body: JSON.stringify({ nombre, piso_zona: pisoZona }) });
      locationSelect.addOption(res.item.id, res.item.nombre, res.item.piso_zona || 'Sin piso/zona registrada');
      $('#newLocationName').value = '';
      $('#newLocationZone').value = '';
      $('#locationCreate').classList.remove('show');
      showToast('Ubicación creada', `${res.item.nombre} quedó seleccionada.`, 'success');
    } catch (e) {
      showToast('No se pudo crear la ubicación', e.message, 'error');
    }
  });

  $('[data-create-responsible]').addEventListener('click', () => {
    $('[data-smart-select="responsible"] .aw-smart-menu').classList.remove('show');
    $('#responsibleCreate').classList.add('show');
  });
  $('#closeResponsible').addEventListener('click', () => $('#responsibleCreate').classList.remove('show'));
  $('#saveResponsible').addEventListener('click', async () => {
    const nombre = $('#newResponsibleName').value.trim();
    const cargo = $('#newResponsibleCargo').value.trim();
    const dependencia = $('#newResponsibleDependencia').value.trim();
    if (!nombre) { showToast('Falta el responsable', 'Escribe el nombre.', 'warning'); return; }
    try {
      const res = await api('/api/catalogs/responsibles', { method: 'POST', body: JSON.stringify({ nombre, cargo, dependencia }) });
      const sub = [cargo, dependencia].filter(Boolean).join(' · ') || 'Sin cargo/dependencia registrada';
      responsibleSelect.addOption(res.item.id, res.item.nombre, sub);
      $('#newResponsibleName').value = '';
      $('#newResponsibleCargo').value = '';
      $('#newResponsibleDependencia').value = '';
      $('#responsibleCreate').classList.remove('show');
      showToast('Responsable creado', `${res.item.nombre} quedó seleccionado.`, 'success');
    } catch (e) {
      showToast('No se pudo crear el responsable', e.message, 'error');
    }
  });

  // ---- Color: busca coincidencia contra las sugerencias mientras se
  // escribe y muestra una vista previa del color real (no un punto gris
  // genérico), tanto al escribir como al guardar una sugerencia nueva. ----
  const DIACRITICS = new RegExp('[̀-ͯ]', 'g');
  const normalizeColor = (str) => (str || '').normalize('NFD').replace(DIACRITICS, '').toLowerCase().trim();

  /** Nombres en español que CSS no reconoce de forma nativa (solo entiende inglés). No se muestran como chips: solo alimentan la vista previa/búsqueda. */
  const EXTRA_COLOR_NAMES = {
    turquesa: '#30d5c8', celeste: '#87ceeb', vinotinto: '#722f37', vino: '#722f37',
    fucsia: '#e91e8c', lila: '#c8a2c8', violeta: '#7f00ff', cian: '#00bcd4',
    magenta: '#e91e63', coral: '#ff7f50', salmon: '#fa8072', caqui: '#c3b091',
    oliva: '#6b8e23', bronce: '#cd7f32', cobre: '#b87333', dorado: '#c9a227',
    crema: '#fff5d7', hueso: '#f0ead6', perla: '#eae0c8', aguamarina: '#7fffd4',
    mostaza: '#d4ac0d', vainilla: '#f3e5ab', chocolate: '#7b3f00', turquoise: '#30d5c8',
    rosa: '#e88ab0', rosado: '#e88ab0', purpura: '#800080', indigo: '#4b0082',
  };

  function resolveColorHex(value) {
    const q = normalizeColor(value);
    if (!q) return null;
    const swatch = $$('.aw-swatch').find((s) => normalizeColor(s.dataset.color) === q);
    if (swatch) return swatch.dataset.hex;
    if (EXTRA_COLOR_NAMES[q]) return EXTRA_COLOR_NAMES[q];
    if (CSS.supports('color', value.trim())) return value.trim();
    return null;
  }

  function updateColorPreview() {
    const hex = resolveColorHex($('#colorInput').value);
    $('#colorPreview').style.background = hex || '#fff';

    const q = normalizeColor($('#colorInput').value);
    $$('.aw-swatch').forEach((s) => s.classList.toggle('matched', !!q && normalizeColor(s.dataset.color).includes(q)));
  }

  function bindSwatch(s) {
    s.addEventListener('click', (e) => {
      if (e.target.closest('[data-remove]')) return;
      $$('.aw-swatch').forEach((x) => x.classList.remove('active'));
      s.classList.add('active');
      $('#colorInput').value = s.dataset.color;
      updateColorPreview();
      syncSummary();
    });
    $('[data-remove]', s)?.addEventListener('click', (e) => {
      e.stopPropagation();
      const wasActive = s.classList.contains('active');
      s.remove();
      if (wasActive) { $('#colorInput').value = ''; updateColorPreview(); syncSummary(); }
    });
  }
  $$('.aw-swatch').forEach(bindSwatch);

  $('#colorInput').addEventListener('input', () => {
    $$('.aw-swatch').forEach((x) => x.classList.remove('active'));
    updateColorPreview();
    syncSummary();
  });

  $('#saveColor').addEventListener('click', () => {
    const value = $('#colorInput').value.trim();
    if (!value) { showToast('Falta el color', 'Escribe un color.', 'warning'); return; }
    const exists = $$('.aw-swatch').some((s) => normalizeColor(s.dataset.color) === normalizeColor(value));
    if (!exists) {
      const hex = resolveColorHex(value) || '#d4d4d4';
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'aw-swatch active';
      b.dataset.color = value;
      b.dataset.hex = hex;
      b.innerHTML = `<span class="aw-swatch-dot" style="background:${escapeHtml(hex)}"></span>${escapeHtml(value)}<span class="aw-swatch-remove" data-remove title="Quitar">×</span>`;
      bindSwatch(b);
      $$('.aw-swatch').forEach((x) => { if (x !== b) x.classList.remove('active'); });
      $('#swatches').appendChild(b);
      showToast('Color guardado', `${value} quedó como sugerencia.`, 'success');
    } else {
      showToast('Color existente', `${value} ya está en sugerencias.`, 'info');
    }
    updateColorPreview();
    syncSummary();
  });

  updateColorPreview();

  // ---- Datos adicionales ----
  $('#optionalToggle').addEventListener('click', () => $('#optionalToggle').closest('.aw-optional').classList.toggle('open'));

  // ---- Paso 3: estado físico ----
  $$('.state-card').forEach((c) => c.addEventListener('click', () => {
    $$('.state-card').forEach((x) => x.classList.remove('selected'));
    c.classList.add('selected');
    $('#physicalStateIdInput').value = c.dataset.stateId;
    selectedStateLabel = c.querySelector('strong').textContent;
    syncSummary();
  }));

  // ---- Paso 2: número de bien — prefijo fijo "BP-", solo dígitos, con
  // botón para sugerir uno disponible al azar sin salir del campo ----
  const numberInput = $('#assetNumber');
  const numberFull = $('#assetNumberFull');
  const numberHelp = $('#assetNumberHelp');

  function syncNumberFull() {
    numberFull.value = numberInput.value ? 'BP-' + numberInput.value : '';
  }

  const checkNumberAvailability = debounce(async () => {
    const value = numberFull.value;
    if (!value) { numberHelp.textContent = ''; return; }
    const res = await api('/api/assets/check-number?numero_bien=' + encodeURIComponent(value));
    numberHelp.textContent = res.disponible ? '✓ Disponible' : '✕ Ya existe un bien con ese número';
    numberHelp.className = 'field-help ' + (res.disponible ? 'success-text' : 'danger-text');
  }, 350);

  numberInput.addEventListener('input', () => {
    numberInput.value = numberInput.value.replace(/\D/g, '');
    syncNumberFull();
    checkNumberAvailability();
    syncSummary();
  });

  $('#assetNumberRandom').addEventListener('click', async () => {
    const btn = $('#assetNumberRandom');
    btn.disabled = true;
    try {
      for (let attempt = 0; attempt < 15; attempt++) {
        const candidate = String(Math.floor(1000 + Math.random() * 9000));
        const res = await api('/api/assets/check-number?numero_bien=' + encodeURIComponent('BP-' + candidate));
        if (res.disponible) {
          numberInput.value = candidate;
          syncNumberFull();
          numberHelp.textContent = '✓ Disponible';
          numberHelp.className = 'field-help success-text';
          syncSummary();
          return;
        }
      }
      showToast('No se encontró un número libre', 'Intenta de nuevo o escribe uno manualmente.', 'warning');
    } catch (e) {
      showToast('No se pudo generar el número', e.message, 'error');
    } finally {
      btn.disabled = false;
    }
  });

  const serialInput = $('#assetSerial');
  const serialHelp = $('#assetSerialHelp');
  serialInput.addEventListener('input', debounce(async () => {
    const value = serialInput.value.trim();
    if (!value) { serialHelp.textContent = 'Se verifica duplicidad automáticamente.'; return; }
    const res = await api('/api/assets/check-serial?serial=' + encodeURIComponent(value));
    serialHelp.textContent = res.disponible ? '✓ Disponible' : '✕ Ya existe un bien con ese serial';
    serialHelp.className = 'field-help ' + (res.disponible ? 'success-text' : 'danger-text');
  }, 350));
  serialInput.addEventListener('input', syncSummary);
  $('#assetDescription').addEventListener('input', syncSummary);

  // ---- Borrador ----
  function readDraft() {
    try { return JSON.parse(localStorage.getItem(DRAFT_KEY) || 'null'); } catch (e) { return null; }
  }
  function clearDraft() {
    localStorage.removeItem(DRAFT_KEY);
    $('#wizardDraftBox').classList.add('hidden');
  }
  function currentPayload() {
    return Object.fromEntries(new FormData(form).entries());
  }
  function saveDraft() {
    localStorage.setItem(DRAFT_KEY, JSON.stringify({ payload: currentPayload(), step, savedAt: Date.now() }));
    showToast('Borrador guardado', 'Puedes continuarlo la próxima vez que abras "Incorporar bien".', 'info');
    setTimeout(() => { window.location.href = '/inventario'; }, 900);
  }
  $('#wizardDraftBtn').addEventListener('click', saveDraft);
  $('#wizardSaveDraftTop').addEventListener('click', saveDraft);

  async function prefill(payload, { skipIdentity = false } = {}) {
    if (!skipIdentity) {
      if (payload.numero_bien) { numberInput.value = payload.numero_bien.replace(/^BP-/, '').replace(/\D/g, ''); syncNumberFull(); }
      if (payload.serial) $('#assetSerial').value = payload.serial;
    }
    if (payload.descripcion) $('#assetDescription').value = payload.descripcion;
    if (payload.material) form.elements.namedItem('material').value = payload.material;
    if (payload.color) { $('#colorInput').value = payload.color; }
    if (payload.category_id) {
      const card = $(`.type-card[data-category-id="${payload.category_id}"]`);
      if (card) selectTypeCard(card);
    }
    if (payload.physical_state_id) {
      const card = $(`.state-card[data-state-id="${payload.physical_state_id}"]`);
      if (card) card.click();
    }
    if (payload.location_id) locationSelect.selectByValue(payload.location_id);
    if (payload.responsible_id) responsibleSelect.selectByValue(payload.responsible_id);
    if (payload.brand_id) {
      brandSelect.selectByValue(payload.brand_id);
      await onBrandChanged(payload.brand_id);
      if (payload.model_id) modelSelect.selectByValue(payload.model_id);
    }
    syncSummary();
  }

  function checkDraft() {
    const draft = readDraft();
    const box = $('#wizardDraftBox');
    if (!draft) { box.classList.add('hidden'); return; }
    $('#wizardDraftMeta').textContent = `${draft.payload?.descripcion || 'Sin descripción'} · guardado ${new Date(draft.savedAt).toLocaleString('es-VE')}`;
    box.classList.remove('hidden');
  }
  $('#wizardDraftRestore').addEventListener('click', async () => {
    const draft = readDraft();
    if (!draft) return;
    await prefill(draft.payload);
    step = draft.step || 1;
    renderStep();
    $('#wizardDraftBox').classList.add('hidden');
  });
  $('#wizardDraftDiscard').addEventListener('click', clearDraft);

  // ---- Envío final ----
  async function submit({ registerSimilar = false } = {}) {
    const payload = currentPayload();
    try {
      const res = await api('/api/assets', { method: 'POST', body: JSON.stringify(payload) });
      clearDraft();

      if (registerSimilar) {
        showToast('Bien incorporado correctamente', `${res.asset.numero_bien} fue registrado. Continúa con el siguiente similar.`, 'success');
        form.reset();
        numberInput.value = '';
        numberFull.value = '';
        $('#assetSerial').value = '';
        numberHelp.textContent = '';
        serialHelp.textContent = 'Se verifica duplicidad automáticamente.';
        step = 2;
        renderStep();
        return;
      }

      showToast('Bien incorporado correctamente', `${res.asset.numero_bien} fue registrado como activo y disponible.`, 'success');
      setTimeout(() => { window.location.href = '/inventario/' + res.asset.id; }, 900);
    } catch (e) {
      showToast('No se pudo incorporar el bien', e.message, 'error');
    }
  }
  $('#wizardSubmitSimilar').addEventListener('click', () => submit({ registerSimilar: true }));

  renderStep();
  checkDraft();
}
