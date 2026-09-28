<?php
/** @var array $campaign */
/** @var array $counters */
/** @var array $user */
/** @var string $fecha */
use App\Core\View;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Cierre de jornada — <?= View::e($campaign['codigo']) ?></title>
<link rel="stylesheet" href="/assets/css/tokens.css">
<link rel="stylesheet" href="/assets/css/app.css">
<link rel="stylesheet" href="/assets/css/print.css">
</head>
<body>
<div class="print-page">
  <div class="print-header">
    <div><h1>CIERRE DE JORNADA DE VERIFICACIÓN</h1><?php View::component('print_identity', ['identity' => $identity]); ?></div>
    <small><?= View::e($campaign['codigo']) ?> · <?= View::e($fecha) ?></small>
  </div>
  <div class="print-meta">
    <div><span>Título</span><strong><?= View::e($campaign['titulo']) ?></strong></div>
    <div><span>Cerrado por</span><strong><?= View::e($user['nombre']) ?></strong></div>
    <div><span>Total de bienes</span><strong><?= (int) $counters['total'] ?></strong></div>
    <div><span>Revisados</span><strong><?= (int) $counters['revisados'] ?></strong></div>
    <div><span>Con cambios</span><strong><?= (int) $counters['con_cambios'] ?></strong></div>
    <div><span>No encontrados</span><strong><?= (int) $counters['no_encontrados'] ?></strong></div>
    <div><span>Pendientes al cierre</span><strong><?= (int) $counters['pendientes'] ?></strong></div>
  </div>
  <?php if (!empty($campaign['observaciones'])): ?>
  <p><strong>Observaciones de cierre:</strong> <?= View::e($campaign['observaciones']) ?></p>
  <?php endif; ?>
  <div class="no-print print-actions"><button onclick="window.print()" class="btn btn-primary">Imprimir / Guardar PDF</button></div>
</div>
</body>
</html>
