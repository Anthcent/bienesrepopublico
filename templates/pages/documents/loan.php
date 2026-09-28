<?php
/** @var array $loan */
/** @var array $details */
/** @var array $user */
/** @var string $fecha */
use App\Core\View;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Acta de préstamo — <?= View::e($loan['codigo']) ?></title>
<link rel="stylesheet" href="/assets/css/tokens.css">
<link rel="stylesheet" href="/assets/css/app.css">
<link rel="stylesheet" href="/assets/css/print.css">
</head>
<body>
<div class="print-page">
  <div class="print-header">
    <div><h1>Acta de préstamo <?= View::e($loan['codigo']) ?></h1><?php View::component('print_identity', ['identity' => $identity]); ?></div>
    <small>Generado el <?= View::e($fecha) ?> por <?= View::e($user['nombre']) ?></small>
  </div>
  <div class="print-meta">
    <div><span>Prestatario</span><strong><?= View::e($loan['prestatario_nombre_snapshot']) ?></strong></div>
    <div><span>Dependencia</span><strong><?= View::e($loan['prestatario_dependencia_snapshot'] ?: '—') ?></strong></div>
    <div><span>Fecha de entrega</span><strong><?= View::e($loan['fecha_prestamo']) ?></strong></div>
    <div><span>Fecha prevista de devolución</span><strong><?= View::e($loan['fecha_vencimiento']) ?></strong></div>
  </div>
  <table class="print-table">
    <thead><tr><th>Bien</th><th>Descripción</th><th>Estado de salida</th></tr></thead>
    <tbody>
      <?php foreach ($details as $d): ?>
        <tr><td><?= View::e($d['numero_bien']) ?></td><td><?= View::e($d['descripcion']) ?></td><td><?= View::e($d['estado_salida_nombre']) ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <div class="no-print print-actions"><button onclick="window.print()" class="btn btn-primary">Imprimir / Guardar PDF</button></div>
</div>
</body>
</html>
