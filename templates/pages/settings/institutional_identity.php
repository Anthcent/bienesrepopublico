<?php
/** @var array $identity */
use App\Core\View;

$logo = $identity['logo_data_uri'] ?? '';
$acronym = $identity['acronym'] ?: 'DEM';
$brandMode = $identity['brand_mode'] ?? 'initials';
$showLogo = $brandMode === 'logo' && $logo;
?>
<div class="page-heading">
  <div>
    <span class="eyebrow">CONFIGURACIÓN · IDENTIDAD</span>
    <h1>Identidad institucional</h1>
    <p>Define la marca y los datos oficiales que identifican al sistema, sus reportes y sus documentos nuevos.</p>
  </div>
  <div class="page-heading-actions">
    <a class="btn btn-ghost" href="/configuracion">Volver a configuración</a>
  </div>
</div>

<div class="identity-layout">
  <form class="panel identity-form" id="institutionalIdentityForm" novalidate>
    <div class="panel-header">
      <div><span class="panel-kicker">DATOS OFICIALES</span><h3>Información institucional</h3></div>
      <span class="status success" id="identitySaveState">Configuración activa</span>
    </div>

    <div class="identity-form-body">
      <div class="form-grid two">
        <label class="field">
          <span>Nombre del sistema <b>*</b></span>
          <input name="system_name" required minlength="3" maxlength="160" value="<?= View::e($identity['system_name']) ?>" placeholder="Sistema de Bienes Públicos">
          <small class="field-error" data-error-for="system_name"></small>
        </label>
        <label class="field">
          <span>Nombre del organismo <b>*</b></span>
          <input name="organization_name" required minlength="3" maxlength="200" value="<?= View::e($identity['organization_name']) ?>" placeholder="Nombre oficial del organismo">
          <small class="field-error" data-error-for="organization_name"></small>
        </label>
      </div>

      <div class="form-grid two">
        <label class="field">
          <span>Siglas</span>
          <input name="acronym" maxlength="30" value="<?= View::e($identity['acronym']) ?>" placeholder="DEM">
          <small class="field-error" data-error-for="acronym"></small>
        </label>
        <label class="field">
          <span>Identificación fiscal</span>
          <input name="tax_id" maxlength="40" value="<?= View::e($identity['tax_id']) ?>" placeholder="RIF, NIT o equivalente">
          <small class="field-error" data-error-for="tax_id"></small>
        </label>
      </div>

      <label class="field">
        <span>Dirección institucional</span>
        <textarea name="address" maxlength="500" rows="2" placeholder="Dirección completa del organismo"><?= View::e($identity['address']) ?></textarea>
        <small class="field-error" data-error-for="address"></small>
      </label>

      <div class="form-grid two">
        <label class="field">
          <span>Teléfono</span>
          <input name="phone" type="tel" maxlength="50" value="<?= View::e($identity['phone']) ?>" placeholder="+58 000 0000000">
          <small class="field-error" data-error-for="phone"></small>
        </label>
        <label class="field">
          <span>Correo institucional</span>
          <input name="email" type="email" maxlength="160" value="<?= View::e($identity['email']) ?>" placeholder="contacto@organismo.gob">
          <small class="field-error" data-error-for="email"></small>
        </label>
      </div>

      <label class="field">
        <span>Sitio web</span>
        <input name="website" type="url" maxlength="255" value="<?= View::e($identity['website']) ?>" placeholder="https://www.organismo.gob">
        <small class="field-error" data-error-for="website"></small>
      </label>

      <fieldset class="brand-mode-field">
        <legend>Presentación de la marca</legend>
        <div class="brand-mode-options">
          <label class="brand-mode-option">
            <input type="radio" name="brand_mode" value="logo" <?= $brandMode === 'logo' ? 'checked' : '' ?>>
            <span class="brand-mode-symbol image-symbol"><svg viewBox="0 0 24 24"><path d="M4 5h16v14H4zM4 15l4-4 3 3 2-2 7 7M15 9h.01"/></svg></span>
            <span><strong>Usar logotipo</strong><small>Muestra la imagen cargada en navegación, acceso y documentos.</small></span>
          </label>
          <label class="brand-mode-option">
            <input type="radio" name="brand_mode" value="initials" <?= $brandMode === 'initials' ? 'checked' : '' ?>>
            <span class="brand-mode-symbol initials-symbol" data-mode-initials-preview><?= View::e($acronym) ?></span>
            <span><strong>Usar siglas</strong><small>Presenta las iniciales en grande con tratamiento tipográfico.</small></span>
          </label>
        </div>
        <small class="field-error" data-error-for="brand_mode"></small>
      </fieldset>

      <div class="identity-logo-field" data-logo-upload>
        <div>
          <strong>Logotipo institucional</strong>
          <span>PNG, JPG o WebP, hasta 512 KB. La imagen se conserva aunque elijas mostrar siglas.</span>
        </div>
        <div class="identity-logo-actions">
          <label class="btn btn-ghost btn-sm" for="identityLogoFile">Seleccionar imagen</label>
          <input id="identityLogoFile" type="file" accept="image/png,image/jpeg,image/webp" hidden>
          <button class="btn btn-ghost btn-sm" type="button" id="removeIdentityLogo" <?= $logo ? '' : 'hidden' ?>>Quitar</button>
        </div>
        <input type="hidden" name="logo_data_uri" id="identityLogoData" value="<?= View::e($logo) ?>">
        <small class="field-error" data-error-for="logo_data_uri"></small>
      </div>
    </div>

    <div class="identity-form-footer">
      <span>Los documentos ya emitidos conservarán la identidad con la que fueron generados.</span>
      <button class="btn btn-primary" type="submit" id="saveInstitutionalIdentity">Guardar cambios</button>
    </div>
  </form>

  <aside class="identity-preview panel">
    <span class="panel-kicker">VISTA PREVIA ANTES DE GUARDAR</span>

    <div class="identity-preview-section">
      <small>NAVEGACIÓN PRINCIPAL</small>
      <div class="identity-preview-nav">
        <div class="identity-preview-nav-logo" data-identity-logo-preview>
          <?php if ($showLogo): ?><img src="<?= View::e($logo) ?>" alt=""><?php else: ?><strong><?= View::e($acronym) ?></strong><?php endif; ?>
        </div>
        <div>
          <span data-identity-preview-organization><?= View::e($identity['organization_name']) ?></span>
          <strong data-identity-preview-system><?= View::e($identity['system_name']) ?></strong>
        </div>
      </div>
    </div>

    <div class="identity-preview-section">
      <small>PANTALLA DE ACCESO</small>
      <div class="identity-preview-login">
        <span data-identity-preview-organization><?= View::e($identity['organization_name']) ?></span>
        <h2 data-identity-preview-system><?= View::e($identity['system_name']) ?></h2>
        <p>Inventario, préstamos, movimientos y jornadas de verificación patrimonial en un solo lugar, con trazabilidad completa.</p>
        <div class="identity-preview-login-logo" data-identity-logo-preview>
          <?php if ($showLogo): ?><img src="<?= View::e($logo) ?>" alt=""><?php else: ?><strong><?= View::e($acronym) ?></strong><?php endif; ?>
        </div>
        <div class="identity-preview-login-features">
          <span>Trazabilidad</span>
          <span>Auditoría</span>
          <span>Verificación</span>
        </div>
      </div>
    </div>

    <div class="identity-preview-details" id="identityPreviewDetails">
      <span data-preview-detail="tax_id"><?= View::e($identity['tax_id'] ?: 'Identificación fiscal no configurada') ?></span>
      <span data-preview-detail="email"><?= View::e($identity['email'] ?: 'Correo institucional no configurado') ?></span>
      <span data-preview-detail="phone"><?= View::e($identity['phone'] ?: 'Teléfono no configurado') ?></span>
    </div>
    <p>Esta identidad se aplicará en la navegación, el acceso, los reportes, las etiquetas y los documentos que se generen desde ahora.</p>
  </aside>
</div>

<script type="module">
  import { api } from '/assets/js/core/dom.js';
  import { showToast } from '/assets/js/core/toast.js';

  const form = document.getElementById('institutionalIdentityForm');
  const fileInput = document.getElementById('identityLogoFile');
  const logoData = document.getElementById('identityLogoData');
  const removeLogo = document.getElementById('removeIdentityLogo');
  const saveButton = document.getElementById('saveInstitutionalIdentity');
  const saveState = document.getElementById('identitySaveState');

  function renderLogo(dataUri, acronym = form.acronym.value.trim() || 'SBP') {
    const safeAcronym = acronym.replace(/[&<>"']/g, '');
    const useLogo = form.brand_mode.value === 'logo' && !!dataUri;
    document.querySelectorAll('[data-identity-logo-preview]').forEach((preview) => {
      preview.innerHTML = useLogo ? `<img src="${dataUri}" alt="">` : `<strong>${safeAcronym}</strong>`;
      preview.classList.toggle('has-logo', useLogo);
    });
    document.querySelector('[data-mode-initials-preview]').textContent = safeAcronym;
    document.querySelector('[data-logo-upload]').classList.toggle('is-muted', form.brand_mode.value === 'initials');
    removeLogo.hidden = !dataUri;
  }

  function updatePreview() {
    document.querySelectorAll('[data-identity-preview-organization]').forEach((el) => {
      el.textContent = form.organization_name.value || 'Nombre del organismo';
    });
    document.querySelectorAll('[data-identity-preview-system]').forEach((el) => {
      el.textContent = form.system_name.value || 'Nombre del sistema';
    });
    const detailFallbacks = {
      tax_id: 'Identificación fiscal no configurada',
      email: 'Correo institucional no configurado',
      phone: 'Teléfono no configurado',
    };
    Object.entries(detailFallbacks).forEach(([field, fallback]) => {
      document.querySelector(`[data-preview-detail="${field}"]`).textContent = form[field].value || fallback;
    });
    renderLogo(logoData.value);
  }

  function clearErrors() {
    form.querySelectorAll('.field-error').forEach((el) => { el.textContent = ''; });
    form.querySelectorAll('[aria-invalid="true"]').forEach((el) => el.removeAttribute('aria-invalid'));
  }

  function showErrors(errors = {}) {
    Object.entries(errors).forEach(([field, message]) => {
      const target = form.querySelector(`[data-error-for="${field}"]`);
      const input = form.elements[field];
      if (target) target.textContent = message;
      if (input) input.setAttribute('aria-invalid', 'true');
    });
  }

  form.addEventListener('input', updatePreview);
  fileInput.addEventListener('change', () => {
    const file = fileInput.files?.[0];
    if (!file) return;
    if (!['image/png', 'image/jpeg', 'image/webp'].includes(file.type) || file.size > 512 * 1024) {
      fileInput.value = '';
      showToast('Logotipo no válido', 'Usa una imagen PNG, JPG o WebP de hasta 512 KB.', 'error');
      return;
    }
    const reader = new FileReader();
    reader.addEventListener('load', () => {
      logoData.value = String(reader.result || '');
      form.querySelector('[name="brand_mode"][value="logo"]').checked = true;
      renderLogo(logoData.value);
    });
    reader.readAsDataURL(file);
  });
  removeLogo.addEventListener('click', () => {
    fileInput.value = '';
    logoData.value = '';
    form.querySelector('[name="brand_mode"][value="initials"]').checked = true;
    renderLogo('');
  });
  updatePreview();

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    clearErrors();
    if (!form.reportValidity()) return;
    saveButton.disabled = true;
    saveButton.textContent = 'Guardando…';
    saveState.textContent = 'Guardando cambios';
    saveState.className = 'status warning';

    try {
      const payload = Object.fromEntries(new FormData(form).entries());
      const response = await api('/api/settings/institutional-identity', { method: 'PUT', body: JSON.stringify(payload) });
      const identity = response.identity;
      try {
        document.cookie = "bp_identity=" + encodeURIComponent(JSON.stringify(identity)) + "; path=/; max-age=31536000; SameSite=Lax; Secure";
      } catch (e) {}
      logoData.value = identity.logo_data_uri || '';
      document.querySelector('[data-brand-system]')?.replaceChildren(document.createTextNode(identity.system_name));
      document.querySelector('[data-brand-organization]')?.replaceChildren(document.createTextNode(identity.organization_name));
      const brandMark = document.querySelector('[data-brand-mark]');
      if (brandMark) {
        if (identity.brand_mode === 'logo' && identity.logo_data_uri) {
          brandMark.className = 'brand-mark has-logo';
          const image = document.createElement('img');
          image.src = identity.logo_data_uri;
          image.alt = '';
          brandMark.replaceChildren(image);
        } else {
          brandMark.className = 'brand-mark has-initials';
          const initials = document.createElement('strong');
          initials.textContent = identity.acronym || 'DEM';
          brandMark.replaceChildren(initials);
        }
      }
      document.title = identity.system_name;
      saveState.textContent = 'Cambios guardados';
      saveState.className = 'status success';
      showToast('Identidad actualizada', 'Los cambios ya están activos en el sistema.', 'success');
    } catch (error) {
      showErrors(error.body?.errors);
      saveState.textContent = 'No se guardaron los cambios';
      saveState.className = 'status danger';
      showToast('No se pudo guardar', error.message, 'error');
    } finally {
      saveButton.disabled = false;
      saveButton.textContent = 'Guardar cambios';
    }
  });
</script>
