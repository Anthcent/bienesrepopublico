<?php
/** @var array $asset */
/** @var array $movement */
/** @var array $user */
/** @var string $fecha */
use App\Core\View;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Nota de movimiento — <?= View::e($asset['numero_bien']) ?></title>
<link rel="stylesheet" href="/assets/css/tokens.css">
<link rel="stylesheet" href="/assets/css/app.css">
<link rel="stylesheet" href="/assets/css/print.css">
</head>
<body>
<div class="print-page">
  <div class="print-header">
    <div><h1>Nota de movimiento</h1><?php View::component('print_identity', ['identity' => $identity]); ?></div>
    <small>Generado el <?= View::e($fecha) ?> por <?= View::e($user['nombre']) ?></small>
  </div>
  <div class="print-meta">
    <div><span>Número de bien</span><strong><?= View::e($asset['numero_bien']) ?></strong></div>
    <div><span>Descripción</span><strong><?= View::e($asset['descripcion']) ?></strong></div>
    <div><span>Ubicación actual</span><strong><?= View::e($asset['ubicacion_nombre']) ?></strong></div>
    <div><span>Responsable actual</span><strong><?= View::e($asset['responsable_nombre']) ?></strong></div>
  </div>
  <p>Se deja constancia del movimiento administrativo registrado para el bien indicado. Referencia de movimiento #<?= (int) $movement['id'] ?>.</p>
  <div class="no-print print-actions"><button onclick="window.print()" class="btn btn-primary">Imprimir / Guardar PDF</button></div>
</div>
</body>
</html>
