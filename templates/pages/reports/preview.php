<?php
/** @var string $template */
/** @var string $templateName */
/** @var array $rows */
/** @var string $fecha */
/** @var array $identity */
use App\Core\View;
use App\Core\Auth;

$actor = Auth::user();
$columns = $rows ? array_keys($rows[0]) : [];
// En "personalizado" las columnas ya vienen elegidas a propósito por el
// usuario (incluye estado_administrativo/disponibilidad si las pidió) — la
// lista de ocultas es solo para las plantillas fijas, que siempre traen esos
// campos internos aunque no se muestren.
$hidden = $template === 'personalizado'
    ? []
    : ['id', 'category_id', 'brand_id', 'model_id', 'location_id', 'responsible_id', 'physical_state_id', 'usuario_creador_id', 'estado_administrativo', 'disponibilidad'];
$columns = array_values(array_diff($columns, $hidden));
?>
<div class="print-header">
  <div>
    <h1><?= View::e($templateName) ?></h1>
    <?php View::component('print_identity', ['identity' => $identity]); ?>
  </div>
  <small>Generado el <?= View::e($fecha) ?> por <?= View::e($actor['nombre']) ?></small>
</div>

<div class="print-meta">
  <div><span>Total de registros</span><strong><?= count($rows) ?></strong></div>
  <div><span>Plantilla</span><strong><?= View::e($templateName) ?></strong></div>
</div>

<?php if (empty($rows)): ?>
  <p>No se encontraron registros con los filtros seleccionados.</p>
<?php else: ?>
<table class="print-table">
  <thead><tr><?php foreach ($columns as $col): ?><th><?= View::e(ucfirst(str_replace('_', ' ', $col))) ?></th><?php endforeach; ?></tr></thead>
  <tbody>
    <?php foreach ($rows as $row): ?>
      <tr><?php foreach ($columns as $col): ?><td><?= View::e((string) ($row[$col] ?? '')) ?></td><?php endforeach; ?></tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
