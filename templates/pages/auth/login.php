<?php
/** @var ?string $error */
/** @var string $next */
/** @var array $identity */
use App\Core\View;
use App\Core\Csrf;

$config = require dirname(__DIR__, 3) . '/src/Config/config.php';
$demoCredentials = $config['app']['demo_enabled']
    ? require dirname(__DIR__, 3) . '/src/Config/demo_credentials.php'
    : [];
$showLogo = ($identity['brand_mode'] ?? 'logo') === 'logo' && !empty($identity['logo_data_uri']);
?>
<div class="auth-shell">
  <div class="auth-visual">
    <div class="auth-visual-grid"></div>
    <div class="auth-visual-shape s1"></div>
    <div class="auth-visual-shape s2"></div>
    <div class="auth-visual-shape s3"></div>

    <div class="auth-visual-main">
      <div class="auth-visual-top">
        <span class="eyebrow"><?= View::e($identity['organization_name']) ?></span>
        <h1><?= View::e($identity['system_name']) ?></h1>
        <p>Inventario, préstamos, movimientos y jornadas de verificación patrimonial en un solo lugar, con trazabilidad completa.</p>
        <div class="auth-identity-showcase <?= $showLogo ? 'has-logo' : 'has-initials' ?>">
          <?php if ($showLogo): ?>
            <img src="<?= View::e($identity['logo_data_uri']) ?>" alt="Logotipo de <?= View::e($identity['organization_name']) ?>">
          <?php else: ?>
            <strong><?= View::e($identity['acronym'] ?: 'SBP') ?></strong>
          <?php endif; ?>
        </div>
      </div>

      <ul class="auth-highlights">
        <li><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z"/></svg></i><span>Trazabilidad de cada bien y movimiento</span></li>
        <li><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 17H4v-2a4 4 0 014-4h1m5-6a4 4 0 110 8 4 4 0 010-8zm5 14v-2a4 4 0 00-3-3.87"/></svg></i><span>Auditoría automática de operaciones</span></li>
        <li><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4M21 12v6a2 2 0 01-2 2H5a2 2 0 012-2h11"/></svg></i><span>Verificación patrimonial organizada</span></li>
      </ul>
    </div>

    <div class="auth-visual-bottom">© <?= date('Y') ?> <?= View::e($identity['organization_name']) ?> — Acceso restringido a personal autorizado.</div>
  </div>

  <div class="auth-form-panel">
    <div class="auth-mobile-brand">
      <?php if ($showLogo): ?>
        <img src="<?= View::e($identity['logo_data_uri']) ?>" alt="Logotipo de <?= View::e($identity['organization_name']) ?>">
      <?php else: ?>
        <strong><?= View::e($identity['acronym'] ?: 'SBP') ?></strong>
      <?php endif; ?>
      <span><?= View::e($identity['system_name']) ?></span>
    </div>
    <div class="auth-card">
      <div class="auth-card-kicker">
        <span><svg viewBox="0 0 24 24"><path d="M7 10V7a5 5 0 0 1 10 0v3M5 10h14v11H5zM12 14v3"/></svg></span>
        Acceso seguro
      </div>
      <div class="auth-card-header">
        <h2>Iniciar sesión</h2>
        <p>Ingresa con tu correo institucional para continuar.</p>
      </div>

      <?php if ($error): ?>
        <div class="auth-error"><?= View::e($error) ?></div>
      <?php endif; ?>

      <form method="post" action="/login" id="loginForm">
        <?= Csrf::field() ?>
        <input type="hidden" name="next" value="<?= View::e($next) ?>">
        <div class="field">
          <span>Correo electrónico</span>
          <div class="auth-input-wrap">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16v12H4zM4 7l8 6 8-6"/></svg>
            <input type="email" name="email" id="loginEmail" required autofocus placeholder="usuario@bienespublicos.local">
          </div>
        </div>
        <div class="field">
          <span>Contraseña</span>
          <div class="auth-input-wrap">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 10V7a5 5 0 0 1 10 0v3M5 10h14v11H5z"/></svg>
            <input type="password" name="password" id="loginPassword" required placeholder="••••••••">
            <button type="button" class="auth-password-toggle" id="toggleLoginPassword" aria-label="Mostrar contraseña" aria-pressed="false">
              <svg viewBox="0 0 24 24"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg>
            </button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary wide">Iniciar sesión</button>
      </form>

      <div class="auth-security-note">
        <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Zm-3-10 2 2 4-4"/></svg>
        <span>Sesión protegida y acceso registrado en auditoría.</span>
      </div>

      <?php if ($demoCredentials): ?>
      <details class="auth-devbox">
        <summary class="auth-devbox-head">
          <span><strong>Credenciales de prueba</strong><small>Solo en este entorno</small></span>
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
        </summary>

        <div class="auth-devbox-body">

          <?php foreach ($demoCredentials as $cred): ?>
          <div class="auth-devgroup">
            <div class="auth-devgroup-label"><?= View::e($cred['label']) ?></div>
            <div class="auth-devrow" data-copy="<?= View::e($cred['email']) ?>" data-fill="loginEmail" title="Clic para copiar">
              <span class="k">Correo</span>
              <code><?= View::e($cred['email']) ?></code>
              <button type="button" tabindex="-1"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a1 1 0 01-1-1V4a1 1 0 011 1h10a1 1 0 011 1v1"/></svg></button>
            </div>
            <div class="auth-devrow" data-copy="<?= View::e($cred['password']) ?>" data-fill="loginPassword" title="Clic para copiar">
              <span class="k">Contraseña</span>
              <code><?= View::e($cred['password']) ?></code>
              <button type="button" tabindex="-1"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a1 1 0 01-1-1V4a1 1 0 011-1h10a1 1 0 011 1v1"/></svg></button>
            </div>
            <button type="button" class="auth-devbox-fill" data-fill-email="<?= View::e($cred['email']) ?>" data-fill-password="<?= View::e($cred['password']) ?>">Usar estas credenciales</button>
          </div>
          <?php endforeach; ?>

          <p class="auth-devbox-note">Cuentas exclusivas para validar este despliegue; deben retirarse antes de producción.</p>
        </div>
      </details>
      <?php endif; ?>
    </div>
  </div>
</div>

<script type="module">
  function flashCopied(row) {
    const code = row.querySelector('code');
    const original = code.textContent;
    code.textContent = 'Copiado ✓';
    setTimeout(() => { code.textContent = original; }, 900);
  }

  document.querySelectorAll('.auth-devrow').forEach((row) => {
    row.addEventListener('click', async () => {
      const value = row.dataset.copy;
      const targetId = row.dataset.fill;
      const target = document.getElementById(targetId);
      if (target) target.value = value;
      try {
        await navigator.clipboard.writeText(value);
        flashCopied(row);
      } catch (e) {
        // Portapapeles no disponible (p. ej. sin HTTPS); el valor ya quedó en el campo.
      }
    });
  });

  document.querySelectorAll('.auth-devbox-fill').forEach((btn) => {
    btn.addEventListener('click', () => {
      document.getElementById('loginEmail').value = btn.dataset.fillEmail;
      document.getElementById('loginPassword').value = btn.dataset.fillPassword;
    });
  });

  document.getElementById('toggleLoginPassword')?.addEventListener('click', (event) => {
    const button = event.currentTarget;
    const input = document.getElementById('loginPassword');
    const showing = input.type === 'text';
    input.type = showing ? 'password' : 'text';
    button.setAttribute('aria-pressed', String(!showing));
    button.setAttribute('aria-label', showing ? 'Mostrar contraseña' : 'Ocultar contraseña');
  });
</script>
