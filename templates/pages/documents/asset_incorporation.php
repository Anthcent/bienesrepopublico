<?php
/** @var array $asset */
/** @var array $user */
/** @var string $fecha */
/** @var array $identity */
use App\Core\View;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Nota de incorporación — <?= View::e($asset['numero_bien']) ?></title>
<link rel="stylesheet" href="/assets/css/tokens.css">
<link rel="stylesheet" href="/assets/css/app.css">
<link rel="stylesheet" href="/assets/css/print.css">
</head>
<body>
<div class="print-page">
  <div class="print-header">
    <div><h1>Nota de incorporación</h1><?php View::component('print_identity', ['identity' => $identity]); ?></div>
    <small>Generado el <?= View::e($fecha) ?> por <?= View::e($user['nombre']) ?></small>
  </div>
  <div class="print-meta">
    <div><span>Número de bien</span><strong><?= View::e($asset['numero_bien']) ?></strong></div>
    <div><span>Serial</span><strong><?= View::e($asset['serial'] ?: 'Sin serial') ?></strong></div>
    <div><span>Descripción</span><strong><?= View::e($asset['descripcion']) ?></strong></div>
    <div><span>Estado físico</span><strong><?= View::e($asset['estado_fisico_nombre']) ?></strong></div>
    <div><span>Ubicación</span><strong><?= View::e($asset['ubicacion_nombre']) ?></strong></div>
    <div><span>Responsable</span><strong><?= View::e($asset['responsable_nombre']) ?></strong></div>
  </div>
  <p>Se deja constancia de la incorporación del bien descrito anteriormente al inventario de <?= View::e($identity['organization_name']) ?>, administrado mediante <?= View::e($identity['system_name']) ?>, quedando en estado activo y disponible bajo la custodia del responsable indicado.</p>
  <div class="no-print print-actions"><button onclick="window.print()" class="btn btn-primary">Imprimir / Guardar PDF</button></div>
</div>
</body>
</html>
