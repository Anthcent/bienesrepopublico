<?php
/** @var array $items */
/** @var string $baseUrl */
/** @var array $fields */
/** @var string $extraText */
/** @var bool $includeQr */
/** @var string $logoPosition */
/** @var array $identity */
use App\Core\View;

$fieldGetters = [
    'descripcion' => fn ($a) => $a['descripcion'],
    'ubicacion' => fn ($a) => $a['ubicacion_nombre'],
    'responsable' => fn ($a) => $a['responsable_nombre'],
    'categoria' => fn ($a) => $a['categoria_nombre'],
    'marca' => fn ($a) => trim(($a['marca_nombre'] ?? '') . ' ' . ($a['modelo_nombre'] ?? '')),
    'serial' => fn ($a) => $a['serial'] ? 'Serial: ' . $a['serial'] : null,
    'estado_fisico' => fn ($a) => $a['estado_fisico_nombre'],
];
$showDescripcion = in_array('descripcion', $fields, true);
$metaFields = array_values(array_diff($fields, ['descripcion']));
$institutionalLogo = ($identity['brand_mode'] ?? 'logo') === 'logo' ? ($identity['logo_data_uri'] ?? null) : null;
?>
<div class="print-header">
  <div>
    <h1>Etiquetas de bienes</h1>
    <?php View::component('print_identity', ['identity' => $identity]); ?>
  </div>
  <small>Generado el <?= View::e(date('d/m/Y H:i')) ?></small>
</div>

<?php if (empty($items)): ?>
  <p>No se encontraron bienes para generar etiquetas.</p>
<?php else: ?>
<div class="labels-grid">
  <?php foreach ($items as $i => $asset): ?>
    <article class="label-card">
      <img class="label-logo-top hidden" data-label-logo-top>
      <div class="label-body-row">
        <?php if ($includeQr): ?>
          <div class="label-qr" data-qr-url="<?= View::e($baseUrl . '/inventario/' . (int) $asset['id']) ?>" id="qr-<?= $i ?>"></div>
        <?php endif; ?>
        <div class="label-info">
          <img class="label-logo-inline hidden" data-label-logo-inline>
          <strong class="label-numero"><?= View::e($asset['numero_bien']) ?></strong>
          <?php if ($showDescripcion): ?><span class="label-desc"><?= View::e($asset['descripcion']) ?></span><?php endif; ?>
          <div class="label-meta">
            <?php foreach ($metaFields as $f): ?>
              <?php $value = ($fieldGetters[$f] ?? fn () => null)($asset); ?>
              <?php if ($value): ?><span><?= View::e($value) ?></span><?php endif; ?>
            <?php endforeach; ?>
          </div>
          <?php if ($extraText !== ''): ?><span class="label-desc label-extra"><?= View::e($extraText) ?></span><?php endif; ?>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<script src="/assets/js/vendor/qrcode.js"></script>
<script type="module">
  document.querySelectorAll('.label-qr').forEach((el) => {
    const qr = qrcode(0, 'M');
    qr.addData(el.dataset.qrUrl);
    qr.make();
    el.innerHTML = qr.createSvgTag(3, 0);
  });

  const logo = localStorage.getItem('bp_label_logo') || <?= json_encode($institutionalLogo) ?>;
  if (logo) {
    const selector = <?= json_encode($logoPosition === 'top' ? '[data-label-logo-top]' : '[data-label-logo-inline]') ?>;
    document.querySelectorAll(selector).forEach((img) => {
      img.src = logo;
      img.classList.remove('hidden');
    });
  }
</script>
