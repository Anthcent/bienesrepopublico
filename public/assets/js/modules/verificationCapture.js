import { $, $$, api } from '../core/dom.js';
import { showToast } from '../core/toast.js';

/** Pantalla de captura secuencial de la Jornada de Verificación (Plan V5 §24-25). */
(function initVerificationCapture() {
  const main = $('#captureMain');
  if (!main) return;

  const itemId = main.dataset.itemId;
  const campaignId = main.dataset.campaignId;
  const changeForm = $('#changeForm');
  const conflictBanner = $('#conflictBanner');
  let pendingForceChanges = null;

  function goTo(nextItemId) {
    if (nextItemId) {
      window.location.href = `/verificacion/${campaignId}/capturar?item=${nextItemId}`;
    } else {
      window.location.href = `/verificacion/${campaignId}`;
    }
  }

  $('#btnSame')?.addEventListener('click', async () => {
    try {
      const res = await api(`/api/verification/items/${itemId}/no-changes`, { method: 'POST' });
      showToast('Sin cambios', 'Bien confirmado, avanzando al siguiente.', 'success');
      setTimeout(() => goTo(res.data.next?.id), 500);
    } catch (e) {
      showToast('No se pudo confirmar', e.message, 'error');
    }
  });

  $('#btnMissing')?.addEventListener('click', async () => {
    try {
      const res = await api(`/api/verification/items/${itemId}/not-found`, { method: 'POST', body: JSON.stringify({ observacion: null }) });
      showToast('Marcado como no encontrado', 'Requiere revisión posterior.', 'warning');
      setTimeout(() => goTo(res.data.next?.id), 500);
    } catch (e) {
      showToast('No se pudo registrar', e.message, 'error');
    }
  });

  $('#btnChanges')?.addEventListener('click', () => {
    changeForm.classList.add('show');
    conflictBanner.classList.add('hidden');
  });
  $('#cancelChanges')?.addEventListener('click', () => {
    changeForm.classList.remove('show');
    pendingForceChanges = null;
  });

  $$('.change-toggle').forEach((toggle) => {
    toggle.addEventListener('change', () => {
      const field = toggle.dataset.field;
      const dynamicField = document.querySelector(`[data-dynamic="${field}"]`);
      dynamicField?.classList.toggle('hidden', !toggle.checked);
    });
  });

  function buildChangesPayload(force) {
    const changes = {};
    $$('.change-toggle').forEach((t) => { changes[t.dataset.field] = t.checked; });

    return {
      changes,
      ubicacion_observada_id: changes.ubicacion ? $('#fUbicacion')?.value : null,
      responsable_observado_id: changes.responsable ? $('#fResponsable')?.value : null,
      estado_fisico_observado_id: changes.estado_fisico ? $('#fEstado')?.value : null,
      serial_observado: changes.serial ? $('#fSerial')?.value : null,
      observacion_captura: $('#fObservacion')?.value || null,
      force: !!force,
    };
  }

  function renderConflict(conflicts) {
    const labels = { ubicacion: 'Ubicación', responsable: 'Responsable', estado_fisico: 'Estado físico', serial: 'Serial' };
    const rows = Object.entries(conflicts).map(([field, v]) => `
      <tr><td>${labels[field] || field}</td><td>Hoja: <b>${v.hoja}</b></td><td>Actual en sistema: <b>${v.actual}</b></td></tr>
    `).join('');
    conflictBanner.innerHTML = `
      <strong>⚠ El dato actual del sistema cambió desde que se generó la hoja.</strong>
      <table>${rows}</table>
      <div style="margin-top:10px;display:flex;gap:8px">
        <button class="btn btn-ghost" id="conflictReview">Revisar de nuevo</button>
        <button class="btn btn-danger-soft" id="conflictForce">Aplicar el valor capturado de todas formas</button>
      </div>
    `;
    conflictBanner.classList.remove('hidden');
    $('#conflictReview', conflictBanner)?.addEventListener('click', () => conflictBanner.classList.add('hidden'));
    $('#conflictForce', conflictBanner)?.addEventListener('click', () => submitChanges(true));
  }

  async function submitChanges(force = false) {
    const payload = buildChangesPayload(force);
    const anyChange = Object.values(payload.changes).some(Boolean);
    if (!anyChange) {
      showToast('Selecciona al menos un cambio', 'Marca qué cambió antes de guardar.', 'warning');
      return;
    }
    try {
      const res = await api(`/api/verification/items/${itemId}/changes`, { method: 'POST', body: JSON.stringify(payload) });
      showToast('Cambios registrados', 'Avanzando al siguiente bien.', 'success');
      setTimeout(() => goTo(res.data.next?.id), 500);
    } catch (e) {
      if (e.body?.errors?.conflicts) {
        renderConflict(e.body.errors.conflicts);
      } else {
        showToast('No se pudo guardar', e.message, 'error');
      }
    }
  }

  $('#saveChanges')?.addEventListener('click', () => submitChanges(false));

  document.addEventListener('keydown', (e) => {
    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) return;
    const key = e.key.toLowerCase();
    if (key === 's') $('#btnSame')?.click();
    else if (key === 'c') $('#btnChanges')?.click();
    else if (key === 'n') $('#btnMissing')?.click();
  });
})();
