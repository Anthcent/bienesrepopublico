<?php
/** @var array $settings */
/** @var array $backupStatus */
use App\Core\View;

$latest = $backupStatus['latest'];
$verified = $backupStatus['last_verified_restore'];
$stateLabels = [
    'healthy' => ['success', 'Respaldo vigente'],
    'stale' => ['warning', 'Respaldo atrasado'],
    'failed' => ['danger', 'Último intento falló'],
    'missing' => ['danger', 'Sin respaldo reportado'],
];
[$stateClass, $stateLabel] = $stateLabels[$backupStatus['state']];
$formatDate = static fn (?string $value): string => $value
    ? (new DateTimeImmutable($value))->format('d/m/Y H:i')
    : 'Sin registro';
$formatSize = static function ($bytes): string {
    if ($bytes === null) return 'No informado';
    $size = (float) $bytes;
    foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
        if ($size < 1024 || $unit === 'GB') return number_format($size, $unit === 'B' ? 0 : 1, ',', '.') . ' ' . $unit;
        $size /= 1024;
    }
    return 'No informado';
};
?>
<div class="page-heading">
  <div>
    <span class="eyebrow">CONFIGURACIÓN · PROTECCIÓN</span>
    <h1>Seguridad y respaldo</h1>
    <p>Gobierna el acceso a la aplicación y supervisa las copias verificables operadas por infraestructura.</p>
  </div>
  <div class="page-heading-actions">
    <a class="btn btn-ghost" href="/configuracion">Volver a configuración</a>
  </div>
</div>

<div class="identity-layout">
  <form class="panel identity-form" id="securitySettingsForm" novalidate>
    <div class="panel-header">
      <div><span class="panel-kicker">POLÍTICAS ACTIVAS</span><h3>Sesiones y acceso</h3></div>
      <span class="status success" id="securitySaveState">Protección activa</span>
    </div>
    <div class="identity-form-body">
      <div class="form-grid two">
        <label class="field">
          <span>Expirar por inactividad <b>*</b></span>
          <input name="idle_timeout_minutes" type="number" min="5" max="120" required value="<?= (int) $settings['idle_timeout_minutes'] ?>">
          <small>Entre 5 y 120 minutos sin actividad.</small>
          <small class="field-error" data-error-for="idle_timeout_minutes"></small>
        </label>
        <label class="field">
          <span>Duración máxima de sesión <b>*</b></span>
          <input name="absolute_session_hours" type="number" min="1" max="24" required value="<?= (int) $settings['absolute_session_hours'] ?>">
          <small>Obliga a autenticarse otra vez aunque exista actividad.</small>
          <small class="field-error" data-error-for="absolute_session_hours"></small>
        </label>
      </div>
      <div class="form-grid two">
        <label class="field">
          <span>Longitud mínima de contraseña <b>*</b></span>
          <input name="password_min_length" type="number" min="8" max="64" required value="<?= (int) $settings['password_min_length'] ?>">
          <small>Se aplica al crear nuevas cuentas.</small>
          <small class="field-error" data-error-for="password_min_length"></small>
        </label>
        <label class="field">
          <span>Intentos antes del bloqueo <b>*</b></span>
          <input name="max_login_attempts" type="number" min="3" max="10" required value="<?= (int) $settings['max_login_attempts'] ?>">
          <small>Siempre protege la cuenta; también la IP cuando infraestructura permite identificarla.</small>
          <small class="field-error" data-error-for="max_login_attempts"></small>
        </label>
      </div>
      <div class="form-grid two">
        <label class="field">
          <span>Duración del bloqueo <b>*</b></span>
          <input name="lockout_minutes" type="number" min="5" max="120" required value="<?= (int) $settings['lockout_minutes'] ?>">
          <small>Ventana durante la que se rechazan nuevos intentos.</small>
          <small class="field-error" data-error-for="lockout_minutes"></small>
        </label>
        <label class="field">
          <span>Alertar respaldo atrasado <b>*</b></span>
          <input name="backup_warning_hours" type="number" min="1" max="168" required value="<?= (int) $settings['backup_warning_hours'] ?>">
          <small>Horas máximas sin una copia exitosa reportada.</small>
          <small class="field-error" data-error-for="backup_warning_hours"></small>
        </label>
      </div>
    </div>
    <div class="identity-form-footer">
      <span>Las sesiones existentes adoptan estos límites en su siguiente solicitud. Los cambios quedan registrados en auditoría.</span>
      <button class="btn btn-primary" type="submit" id="saveSecuritySettings">Guardar políticas</button>
    </div>
  </form>

  <aside class="panel identity-preview">
    <div class="panel-header" style="padding:0 0 14px">
      <div><span class="panel-kicker">ESTADO OPERATIVO</span><h3>Respaldo y recuperación</h3></div>
      <span class="status <?= $stateClass ?>"><?= View::e($stateLabel) ?></span>
    </div>
    <div class="identity-preview-section">
      <small>ÚLTIMO RESULTADO</small>
      <h3><?= View::e($latest ? $formatDate($latest['completed_at']) : 'No informado') ?></h3>
      <p><?= $latest ? View::e($latest['status'] === 'success' ? $formatSize($latest['size_bytes']) . ' · ' . ($latest['storage_label'] ?: 'Destino externo') : ($latest['error_message'] ?: 'Fallo sin detalle')) : 'Infraestructura todavía no registró una copia.' ?></p>
    </div>
    <div class="identity-preview-section">
      <small>ÚLTIMA RECUPERACIÓN VERIFICADA</small>
      <h3><?= View::e($verified ? $formatDate($verified['restore_verified_at']) : 'Nunca verificada') ?></h3>
      <p>Una copia solo se considera recuperable después de restaurarla y validar su integridad fuera de producción.</p>
    </div>
    <div class="settings-guardrail">
      <strong>Administrado por infraestructura</strong>
      <p>Programación, cifrado, retención, almacenamiento y restauración no se ejecutan desde esta aplicación.</p>
      <a href="/BACKUP_RECOVERY.txt" target="_blank" rel="noopener">Ver procedimiento operativo</a>
    </div>
  </aside>
</div>

<script type="module">
  import { api } from '/assets/js/core/dom.js';
  import { showToast } from '/assets/js/core/toast.js';

  const form = document.getElementById('securitySettingsForm');
  const button = document.getElementById('saveSecuritySettings');
  const state = document.getElementById('securitySaveState');

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    form.querySelectorAll('.field-error').forEach((el) => { el.textContent = ''; });
    form.querySelectorAll('[aria-invalid="true"]').forEach((el) => el.removeAttribute('aria-invalid'));
    if (!form.reportValidity()) return;
    button.disabled = true;
    button.textContent = 'Guardando…';
    state.textContent = 'Aplicando políticas';
    state.className = 'status warning';
    try {
      const payload = Object.fromEntries(new FormData(form).entries());
      await api('/api/settings/security', { method: 'PUT', body: JSON.stringify(payload) });
      state.textContent = 'Políticas actualizadas';
      state.className = 'status success';
      showToast('Seguridad actualizada', 'Las políticas ya están activas.', 'success');
    } catch (error) {
      Object.entries(error.body?.errors || {}).forEach(([field, message]) => {
        const target = form.querySelector(`[data-error-for="${field}"]`);
        const input = form.elements[field];
        if (target) target.textContent = message;
        if (input) input.setAttribute('aria-invalid', 'true');
      });
      state.textContent = 'No se aplicaron los cambios';
      state.className = 'status danger';
      showToast('No se pudo guardar', error.message, 'error');
    } finally {
      button.disabled = false;
      button.textContent = 'Guardar políticas';
    }
  });
</script>
