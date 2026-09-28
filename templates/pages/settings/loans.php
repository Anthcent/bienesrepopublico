<?php
/** @var array $settings */
?>
<div class="page-heading">
  <div>
    <span class="eyebrow">CONFIGURACIÓN · OPERACIÓN</span>
    <h1>Préstamos y alertas</h1>
    <p>Define los plazos habituales, cuándo anticipar vencimientos y qué evidencia exigir al devolver un bien.</p>
  </div>
  <div class="page-heading-actions">
    <a class="btn btn-ghost" href="/configuracion">Volver a configuración</a>
  </div>
</div>

<div class="identity-layout">
  <form class="panel identity-form" id="loanSettingsForm" novalidate>
    <div class="panel-header">
      <div><span class="panel-kicker">REGLAS OPERATIVAS</span><h3>Comportamiento predeterminado</h3></div>
      <span class="status success" id="loanSettingsState">Configuración activa</span>
    </div>

    <div class="identity-form-body">
      <div class="form-grid two">
        <label class="field">
          <span>Plazo predeterminado <b>*</b></span>
          <input name="default_loan_days" type="number" required min="1" max="365" value="<?= (int) $settings['default_loan_days'] ?>">
          <small>Días que se suman a la fecha de entrega al crear un préstamo.</small>
          <small class="field-error" data-error-for="default_loan_days"></small>
        </label>
        <label class="field">
          <span>Anticipación de alertas <b>*</b></span>
          <input name="due_alert_days" type="number" required min="0" max="90" value="<?= (int) $settings['due_alert_days'] ?>">
          <small>Usa 0 para avisar únicamente el día del vencimiento.</small>
          <small class="field-error" data-error-for="due_alert_days"></small>
        </label>
      </div>

      <fieldset class="brand-mode-field">
        <legend>Regla ante deterioro</legend>
        <div class="brand-mode-options">
          <label class="brand-mode-option">
            <input type="radio" name="require_return_observation" value="1" <?= $settings['require_return_observation'] ? 'checked' : '' ?>>
            <span class="brand-mode-symbol initials-symbol">!</span>
            <span><strong>Exigir observación</strong><small>Bloquea la devolución hasta explicar por qué empeoró el estado físico.</small></span>
          </label>
          <label class="brand-mode-option">
            <input type="radio" name="require_return_observation" value="0" <?= !$settings['require_return_observation'] ? 'checked' : '' ?>>
            <span class="brand-mode-symbol image-symbol">✓</span>
            <span><strong>Observación opcional</strong><small>Registra el deterioro y genera la alerta aunque no se escriba un detalle.</small></span>
          </label>
        </div>
        <small class="field-error" data-error-for="require_return_observation"></small>
      </fieldset>
    </div>

    <div class="identity-form-footer">
      <span>Los cambios se aplican a nuevas operaciones y al siguiente cálculo de alertas. Las fechas ya registradas no se modifican.</span>
      <button class="btn btn-primary" type="submit" id="saveLoanSettings">Guardar cambios</button>
    </div>
  </form>

  <aside class="panel identity-preview">
    <span class="panel-kicker">EFECTO EN EL SISTEMA</span>
    <div class="identity-preview-section">
      <small>NUEVO PRÉSTAMO</small>
      <h3><span data-preview-default><?= (int) $settings['default_loan_days'] ?></span> días de plazo</h3>
      <p>El sistema propondrá esta fecha y también la calculará si otro cliente no envía un vencimiento.</p>
    </div>
    <div class="identity-preview-section">
      <small>SEGUIMIENTO</small>
      <h3><span data-preview-alert><?= (int) $settings['due_alert_days'] ?></span> días de anticipación</h3>
      <p>Panel, contadores y notificaciones utilizarán la misma ventana temporal.</p>
    </div>
    <p>La validación de devoluciones ocurre en el servidor para que la regla no dependa de la interfaz utilizada.</p>
  </aside>
</div>

<script type="module">
  import { api } from '/assets/js/core/dom.js';
  import { showToast } from '/assets/js/core/toast.js';

  const form = document.getElementById('loanSettingsForm');
  const saveButton = document.getElementById('saveLoanSettings');
  const saveState = document.getElementById('loanSettingsState');

  form.addEventListener('input', () => {
    document.querySelector('[data-preview-default]').textContent = form.default_loan_days.value || '0';
    document.querySelector('[data-preview-alert]').textContent = form.due_alert_days.value || '0';
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    form.querySelectorAll('.field-error').forEach((el) => { el.textContent = ''; });
    form.querySelectorAll('[aria-invalid="true"]').forEach((el) => el.removeAttribute('aria-invalid'));
    if (!form.reportValidity()) return;

    saveButton.disabled = true;
    saveButton.textContent = 'Guardando…';
    saveState.textContent = 'Guardando cambios';
    saveState.className = 'status warning';

    try {
      const payload = Object.fromEntries(new FormData(form).entries());
      await api('/api/settings/loans', { method: 'PUT', body: JSON.stringify(payload) });
      saveState.textContent = 'Cambios guardados';
      saveState.className = 'status success';
      showToast('Configuración actualizada', 'Las nuevas reglas ya están activas.', 'success');
    } catch (error) {
      Object.entries(error.body?.errors || {}).forEach(([field, message]) => {
        const target = form.querySelector(`[data-error-for="${field}"]`);
        const input = form.elements[field];
        if (target) target.textContent = message;
        if (input) input.setAttribute('aria-invalid', 'true');
      });
      saveState.textContent = 'No se guardaron los cambios';
      saveState.className = 'status danger';
      showToast('No se pudo guardar', error.message, 'error');
    } finally {
      saveButton.disabled = false;
      saveButton.textContent = 'Guardar cambios';
    }
  });
</script>
