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
<title>Acta de devolución — <?= View::e($loan['codigo']) ?></title>
<link rel="stylesheet" href="/assets/css/tokens.css">
<link rel="stylesheet" href="/assets/css/app.css">
<link rel="stylesheet" href="/assets/css/print.css">
</head>
<body>
<div class="print-page">
  <div class="print-header">
    <div><h1>Acta de devolución <?= View::e($loan['codigo']) ?></h1><?php View::component('print_identity', ['identity' => $identity]); ?></div>
    <small>Generado el <?= View::e($fecha) ?> por <?= View::e($user['nombre']) ?></small>
  </div>
  <div class="print-meta">
    <div><span>Prestatario</span><strong><?= View::e($loan['prestatario_nombre_snapshot']) ?></strong></div>
    <div><span>Estado del préstamo</span><strong><?= View::e($loan['estado']) ?></strong></div>
  </div>
  <table class="print-table">
    <thead><tr><th>Bien</th><th>Estado devuelto</th><th>Fecha</th><th>Observación</th></tr></thead>
    <tbody>
      <?php foreach ($details as $d): ?>
        <tr>
          <td><?= View::e($d['numero_bien']) ?></td>
          <td><?= View::e($d['estado_devolucion_nombre'] ?? '—') ?></td>
          <td><?= View::e($d['fecha_devolucion'] ?? '—') ?></td>
          <td><?= View::e($d['observacion_devolucion'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <div class="no-print print-actions"><button onclick="window.print()" class="btn btn-primary">Imprimir / Guardar PDF</button></div>
</div>
</body>
</html>
