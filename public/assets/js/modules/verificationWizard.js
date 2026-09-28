import { $, $$, api, escapeHtml } from '../core/dom.js';
import { openModal, closeModal, registerModal } from '../core/modal.js';
import { showToast } from '../core/toast.js';

/** Wizard de creación de Jornada de Verificación Patrimonial (Plan V5 §21). */
export function initVerificationWizard() {
  const modal = $('#verificationWizard');
  if (!modal) return;
  registerModal('verificationWizard', 'verificationWizardBackdrop');

  let step = 1;
  const maxStep = 4;
  let candidates = [];
  let excluded = new Set();

  function render() {
    $$('.wizard-step', modal).forEach((el) => el.classList.toggle('active', Number(el.dataset.vstep) <= step));
    $$('.wizard-panel', modal).forEach((el) => el.classList.toggle('active', Number(el.dataset.vpanel) === step));
    $('#vBack', modal).style.visibility = step === 1 ? 'hidden' : 'visible';
    $('#vNext', modal).textContent = step === maxStep ? 'Generar jornada' : 'Continuar';
    if (step === 4) fillReview();
  }

  function openWizard() {
    step = 1;
    excluded = new Set();
    candidates = [];
    $('#vTitulo', modal).value = '';
    $('#vDescripcion', modal).value = '';
    $('#vFechaProgramada', modal).value = '';
    $$('.v-campo', modal).forEach((c) => { c.checked = true; });
    render();
    fetchCandidates();
    openModal('verificationWizard');
  }
  window.__openVerificationWizard = openWizard;
  $('#openVerificationWizard')?.addEventListener('click', (e) => { e.preventDefault(); openWizard(); });
  $$('[data-action="newVerification"]').forEach((btn) => btn.addEventListener('click', (e) => { e.preventDefault(); openWizard(); }));

  function scopeFilters() {
    return {
      location_id: $('#vScopeLocation', modal).value,
      responsible_id: $('#vScopeResponsible', modal).value,
      category_id: $('#vScopeCategory', modal).value,
      disponibilidad: $('#vScopeAvailability', modal).value,
      informacion_completa: $('#vScopeIncomplete', modal).checked ? 'incompleta' : '',
    };
  }

  async function fetchCandidates() {
    const params = new URLSearchParams(scopeFilters());
    try {
      const res = await api('/api/verification/candidates?' + params.toString());
      candidates = res.items || [];
      excluded = new Set();
      $('#vCandidateCount', modal).textContent = `${res.total} bien(es) encontrado(s)`;
      renderCandidateList();
    } catch (e) {
      $('#vCandidateCount', modal).textContent = 'No fue posible calcular el alcance.';
    }
  }

  ['vScopeLocation', 'vScopeResponsible', 'vScopeCategory', 'vScopeAvailability', 'vScopeIncomplete'].forEach((id) => {
    $('#' + id, modal)?.addEventListener('change', fetchCandidates);
  });

  function renderCandidateList() {
    const list = $('#vCandidateList', modal);
    if (!list) return;
    if (candidates.length === 0) {
      list.innerHTML = '<div class="candidate-row"><div><strong>Sin bienes para este alcance</strong><small>Ajusta los filtros del paso 1.</small></div></div>';
    } else {
      list.innerHTML = candidates.map((a) => `
        <label class="candidate-row">
          <input type="checkbox" class="v-candidate" value="${a.id}" ${excluded.has(String(a.id)) ? '' : 'checked'}>
          <div><strong>${escapeHtml(a.numero_bien)} · ${escapeHtml(a.descripcion)}</strong><small>${escapeHtml(a.ubicacion_nombre)} · ${escapeHtml(a.responsable_nombre)}</small></div>
        </label>
      `).join('');
      $$('.v-candidate', list).forEach((cb) => cb.addEventListener('change', () => {
        if (cb.checked) excluded.delete(cb.value); else excluded.add(cb.value);
        updateSelectedCount();
      }));
    }
    updateSelectedCount();
  }

  function selectedIds() {
    return candidates.map((a) => String(a.id)).filter((id) => !excluded.has(id));
  }

  function updateSelectedCount() {
    $('#vSelectedCount', modal).textContent = `${selectedIds().length} de ${candidates.length} bienes seleccionados`;
  }

  function selectedCampos() {
    return $$('.v-campo:checked', modal).map((c) => c.value);
  }

  function fillReview() {
    $('#vReviewTitulo', modal).textContent = $('#vTitulo', modal).value || 'Sin título';
    $('#vReviewCount', modal).textContent = selectedIds().length;
    $('#vReviewCampos', modal).textContent = selectedCampos().map((c) => c.replace('_', ' ')).join(', ') || '—';
  }

  $('#vNext')?.addEventListener('click', async () => {
    if (step === 1 && candidates.length === 0) {
      showToast('Sin bienes en el alcance', 'Ajusta los filtros del paso 1 antes de continuar.', 'warning');
      return;
    }
    if (step === 2 && selectedIds().length === 0) {
      showToast('Selecciona al menos un bien', 'Marca al menos un bien para incluir en la jornada.', 'warning');
      return;
    }
    if (step < maxStep) {
      step++;
      render();
      return;
    }
    await submit();
  });
  $('#vBack')?.addEventListener('click', () => { if (step > 1) { step--; render(); } });

  async function submit() {
    const titulo = $('#vTitulo', modal).value.trim();
    if (!titulo) {
      showToast('Falta el título', 'Escribe un título para identificar la jornada.', 'warning');
      step = 4; render();
      return;
    }
    try {
      const res = await api('/api/verification', {
        method: 'POST',
        body: JSON.stringify({
          titulo,
          descripcion: $('#vDescripcion', modal).value || null,
          fecha_programada: $('#vFechaProgramada', modal).value || null,
          asset_ids: selectedIds(),
          campos: selectedCampos(),
        }),
      });
      closeModal('verificationWizard');
      showToast('Jornada creada', `${res.data.campaign.codigo} está lista con ${res.data.campaign.cantidad_bienes} bien(es).`, 'success', {
        label: 'Ver jornada',
        onClick: () => { window.location.href = '/verificacion/' + res.data.campaign.id; },
      });
      setTimeout(() => { window.location.href = '/verificacion/' + res.data.campaign.id; }, 1200);
    } catch (e) {
      showToast('No se pudo crear la jornada', e.message, 'error');
    }
  }
}
