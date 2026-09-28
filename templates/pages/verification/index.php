<?php
/** @var array $items */
/** @var array $countersByCampaign */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var array $filters */
use App\Core\View;

$estadoBadge = ['BORRADOR' => 'neutral-status', 'GENERADA' => 'info', 'EN_VERIFICACION' => 'info', 'EN_CAPTURA' => 'warning', 'COMPLETADA' => 'success', 'CANCELADA' => 'neutral-status'];
$estadoLabel = ['BORRADOR' => 'Borrador', 'GENERADA' => 'Generada', 'EN_VERIFICACION' => 'En verificación', 'EN_CAPTURA' => 'En captura', 'COMPLETADA' => 'Completada', 'CANCELADA' => 'Cancelada'];
?>
<div class="page-heading">
  <div>
    <span class="eyebrow">VERIFICACIÓN PATRIMONIAL</span>
    <h1>Jornadas de actualización</h1>
    <p>Imprime, verifica físicamente y actualiza solo lo que cambió.</p>
  </div>
  <div class="page-heading-actions">
    <button class="btn btn-primary" id="openVerificationWizardBtn"><span>＋</span> Nueva jornada</button>
  </div>
</div>

<?php if (empty($items)): ?>
  <?php View::component('empty_state', ['title' => 'Todavía no hay jornadas', 'message' => 'Crea la primera jornada de verificación patrimonial para tu inventario.']); ?>
<?php else: ?>
<div class="campaign-list">
  <?php foreach ($items as $c): $counters = $countersByCampaign[$c['id']] ?? ['total' => 0, 'revisados' => 0, 'con_cambios' => 0, 'no_encontrados' => 0, 'pendientes' => 0];
    $pct = $counters['total'] > 0 ? round(($counters['revisados'] / $counters['total']) * 100) : 0;
  ?>
  <a class="campaign-card <?= in_array($c['estado'], ['EN_CAPTURA', 'EN_VERIFICACION'], true) ? 'featured' : '' ?>" href="/verificacion/<?= (int) $c['id'] ?>">
    <div class="campaign-top">
      <div><span class="status <?= $estadoBadge[$c['estado']] ?? 'neutral-status' ?>"><?= $estadoLabel[$c['estado']] ?? $c['estado'] ?></span> <strong><?= View::e($c['codigo']) ?></strong></div>
    </div>
    <h2><?= View::e($c['titulo']) ?></h2>
    <p><?= (int) $c['cantidad_bienes'] ?> bienes<?= $c['ubicacion_nombre'] ? ' · ' . View::e($c['ubicacion_nombre']) : '' ?></p>
    <?php if (!in_array($c['estado'], ['COMPLETADA', 'CANCELADA'], true)): ?>
    <div class="progress-bar large" style="margin-top:12px"><b style="width:<?= $pct ?>%"></b></div>
    <?php endif; ?>
    <div class="campaign-stats">
      <div><strong><?= (int) $counters['revisados'] ?></strong><span>Revisados</span></div>
      <div><strong><?= (int) $counters['con_cambios'] ?></strong><span>Con cambios</span></div>
      <div><strong><?= (int) $counters['no_encontrados'] ?></strong><span>No encontrados</span></div>
      <div><strong><?= (int) $counters['pendientes'] ?></strong><span>Pendientes</span></div>
    </div>
  </a>
  <?php endforeach; ?>
</div>
<?php View::component('pagination', ['total' => $total, 'page' => $page, 'perPage' => $perPage, 'baseUrl' => '/verificacion']); ?>
<?php endif; ?>

<script type="module">
  document.getElementById('openVerificationWizardBtn')?.addEventListener('click', () => window.__openVerificationWizard?.());
</script>
