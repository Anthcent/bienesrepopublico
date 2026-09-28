<?php
/** @var array $campaign */
/** @var array $items */
/** @var array $campos */
/** @var array $user */
/** @var string $fecha */
use App\Core\View;

$labels = [
    'ubicacion' => 'Ubicación', 'responsable' => 'Responsable', 'estado_fisico' => 'Estado físico',
    'serial' => 'Serial', 'identificacion' => 'Identificación / etiqueta', 'fotografia' => 'Fotografía',
    'observaciones' => 'Observaciones',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Hoja de verificación — <?= View::e($campaign['codigo']) ?></title>
<link rel="stylesheet" href="/assets/css/tokens.css">
<link rel="stylesheet" href="/assets/css/app.css">
<link rel="stylesheet" href="/assets/css/print.css">
</head>
<body>
<div class="print-page" style="max-width:900px">
  <div class="print-header">
    <div><h1>JORNADA DE VERIFICACIÓN PATRIMONIAL</h1><?php View::component('print_identity', ['identity' => $identity]); ?></div>
    <small><?= View::e($campaign['codigo']) ?> · <?= View::e($fecha) ?></small>
  </div>
  <div class="print-meta">
    <div><span>Título</span><strong><?= View::e($campaign['titulo']) ?></strong></div>
    <div><span>Ubicación</span><strong><?= View::e($campaign['ubicacion_nombre'] ?? 'Todas') ?></strong></div>
    <div><span>Responsable</span><strong><?= View::e($campaign['responsable_nombre'] ?? 'Todos') ?></strong></div>
    <div><span>Verificador</span><strong>__________________</strong></div>
  </div>
  <p style="font-size:9px;background:#f3f3f3;padding:8px">Marque los recuadros y escriba solo cuando exista una diferencia respecto a lo indicado.</p>

  <?php foreach ($items as $item): ?>
  <section class="paper-item">
    <div class="paper-title">
      <strong><?= View::e($item['numero_bien_snapshot']) ?> · <?= View::e($item['descripcion_snapshot']) ?></strong>
      <span><?= View::e($item['serial_snapshot'] ?: 'Sin serial') ?></span>
    </div>
    <div class="paper-current">
      <span>Ubicación: <b><?= View::e($item['location_nombre_snapshot']) ?></b></span>
      <span>Responsable: <b><?= View::e($item['responsible_nombre_snapshot']) ?></b></span>
      <span>Estado: <b><?= View::e($item['physical_state_nombre_snapshot']) ?></b></span>
    </div>
    <div class="paper-grid">
      <div><b>Bien encontrado</b><label>☐ Sí</label><label>☐ No</label></div>
      <?php if (in_array('ubicacion', $campos, true)): ?>
      <div><b>Ubicación</b><label>☐ Correcta</label><label>☐ Cambió: ______________</label></div>
      <?php endif; ?>
      <?php if (in_array('responsable', $campos, true)): ?>
      <div><b>Responsable</b><label>☐ Correcto</label><label>☐ Cambió: ______________</label></div>
      <?php endif; ?>
      <?php if (in_array('estado_fisico', $campos, true)): ?>
      <div><b>Estado físico</b><label>☐ Bueno</label><label>☐ Regular</label><label>☐ Deteriorado</label><label>☐ Inservible</label></div>
      <?php endif; ?>
      <?php if (in_array('serial', $campos, true)): ?>
      <div><b>Serial</b><label>☐ Coincide</label><label>☐ Corregir: ______________</label></div>
      <?php endif; ?>
      <?php if (in_array('identificacion', $campos, true)): ?>
      <div><b>Identificación / etiqueta</b><label>☐ Correcta</label><label>☐ Falta / ilegible</label></div>
      <?php endif; ?>
      <?php if (in_array('fotografia', $campos, true)): ?>
      <div><b>Fotografía</b><label>☐ Correcta</label><label>☐ Requiere nueva</label></div>
      <?php endif; ?>
    </div>
    <?php if (in_array('observaciones', $campos, true)): ?>
    <div class="paper-notes"><b>Observaciones</b><span></span><span></span></div>
    <?php endif; ?>
  </section>
  <?php endforeach; ?>

  <p class="print-signature">Firma / iniciales del verificador: ____________________________________</p>
  <div class="no-print print-actions"><button onclick="window.print()" class="btn btn-primary">Imprimir / Guardar PDF</button></div>
</div>
</body>
</html>
