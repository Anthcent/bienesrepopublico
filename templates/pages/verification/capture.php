<?php
/** @var array $campaign */
/** @var ?array $item */
/** @var array $items */
/** @var array $counters */
/** @var array $campos */
/** @var array $locations */
/** @var array $responsibles */
/** @var array $physicalStates */
use App\Core\View;

$pct = $counters['total'] > 0 ? round(($counters['revisados'] / $counters['total']) * 100) : 0;
$position = 0;
foreach ($items as $i => $it) { if ($item && (int) $it['id'] === (int) $item['id']) { $position = $i + 1; break; } }
?>
<div class="page-heading">
  <div>
    <span class="eyebrow">CAPTURA DE CAMPO</span>
    <h1><?= View::e($campaign['codigo']) ?> · <?= View::e($campaign['titulo']) ?></h1>
    <p>Procesa un bien por vez y avanza automáticamente.</p>
  </div>
  <div class="page-heading-actions">
    <a class="btn btn-ghost" href="/verificacion/<?= (int) $campaign['id'] ?>">← Jornada</a>
  </div>
</div>

<?php if (!$item): ?>
  <?php View::component('empty_state', ['title' => '¡Captura completa!', 'message' => 'Ya no quedan bienes pendientes por capturar en esta jornada.']); ?>
  <div style="text-align:center;margin-top:12px"><a class="btn btn-primary" href="/verificacion/<?= (int) $campaign['id'] ?>">Ir a la jornada</a></div>
<?php else: ?>
<div class="capture-grid">
  <aside class="panel queue-panel">
    <div class="panel-header compact"><div><span class="panel-kicker">PROGRESO</span><h3>Bienes</h3></div><strong><?= (int) $counters['revisados'] ?>/<?= (int) $counters['total'] ?></strong></div>
    <div class="progress-bar"><b style="width:<?= $pct ?>%"></b></div>
    <div class="queue-list">
      <?php foreach ($items as $qi):
        $cls = $qi['estado_item'] === 'VERIFICADO_SIN_CAMBIOS' ? 'q-done' : ($qi['estado_item'] === 'VERIFICADO_CON_CAMBIOS' ? 'q-changed' : ($qi['estado_item'] === 'NO_ENCONTRADO' ? 'q-missing' : ''));
        $icon = $qi['estado_item'] === 'VERIFICADO_SIN_CAMBIOS' ? '✓' : ($qi['estado_item'] === 'VERIFICADO_CON_CAMBIOS' ? '!' : ($qi['estado_item'] === 'NO_ENCONTRADO' ? '?' : '·'));
      ?>
      <a class="<?= $cls ?> <?= $item && (int) $qi['id'] === (int) $item['id'] ? 'active' : '' ?>" href="/verificacion/<?= (int) $campaign['id'] ?>/capturar?item=<?= (int) $qi['id'] ?>">
        <span class="q-icon"><?= $icon ?></span>
        <span><strong><?= View::e($qi['numero_bien_snapshot']) ?></strong><small><?= View::e($qi['descripcion_snapshot']) ?></small></span>
      </a>
      <?php endforeach; ?>
    </div>
  </aside>

  <section class="panel capture-main" id="captureMain" data-item-id="<?= (int) $item['id'] ?>" data-campaign-id="<?= (int) $campaign['id'] ?>">
    <div class="capture-main-header">
      <div>
        <div class="asset-thumb large blue">▦</div>
        <div>
          <small>BIEN <?= $position ?> DE <?= count($items) ?></small>
          <h2><?= View::e($item['numero_bien_snapshot']) ?> · <?= View::e($item['descripcion_snapshot']) ?></h2>
          <p><?= View::e($item['location_nombre_snapshot']) ?> · <?= View::e($item['responsible_nombre_snapshot']) ?></p>
        </div>
      </div>
    </div>

    <div class="snapshot-grid">
      <div><span>Ubicación registrada</span><strong><?= View::e($item['location_nombre_snapshot']) ?></strong></div>
      <div><span>Responsable</span><strong><?= View::e($item['responsible_nombre_snapshot']) ?></strong></div>
      <div><span>Estado físico</span><strong><?= View::e($item['physical_state_nombre_snapshot']) ?></strong></div>
      <div><span>Serial</span><strong><?= View::e($item['serial_snapshot'] ?: 'Sin serial') ?></strong></div>
    </div>

    <div id="conflictBanner" class="conflict-banner hidden"></div>

    <div class="capture-actions-primary">
      <button class="cap-same" id="btnSame"><i>✓</i><span><strong>Sin cambios</strong><small>Todo coincide con la hoja</small></span></button>
      <button class="cap-changes" id="btnChanges"><i>✎</i><span><strong>Registrar cambios</strong><small>Mostrar solo lo que cambió</small></span></button>
      <button class="cap-missing" id="btnMissing"><i>?</i><span><strong>No encontrado</strong><small>Requiere revisión posterior</small></span></button>
    </div>

    <div class="change-form" id="changeForm">
      <h4>¿Qué cambió?</h4>
      <div class="change-chips">
        <?php if (in_array('ubicacion', $campos, true)): ?><label><input type="checkbox" class="change-toggle" data-field="ubicacion"> Ubicación</label><?php endif; ?>
        <?php if (in_array('responsable', $campos, true)): ?><label><input type="checkbox" class="change-toggle" data-field="responsable"> Responsable</label><?php endif; ?>
        <?php if (in_array('estado_fisico', $campos, true)): ?><label><input type="checkbox" class="change-toggle" data-field="estado_fisico"> Estado físico</label><?php endif; ?>
        <?php if (in_array('serial', $campos, true)): ?><label><input type="checkbox" class="change-toggle" data-field="serial"> Serial</label><?php endif; ?>
      </div>
      <div class="dynamic-fields">
        <label class="field hidden" data-dynamic="ubicacion"><span>Nueva ubicación</span>
          <select id="fUbicacion"><?php foreach ($locations as $l): ?><option value="<?= (int) $l['id'] ?>"><?= View::e($l['nombre']) ?></option><?php endforeach; ?></select>
        </label>
        <label class="field hidden" data-dynamic="responsable"><span>Nuevo responsable</span>
          <select id="fResponsable"><?php foreach ($responsibles as $r): ?><option value="<?= (int) $r['id'] ?>"><?= View::e($r['nombre']) ?></option><?php endforeach; ?></select>
        </label>
        <label class="field hidden" data-dynamic="estado_fisico"><span>Estado observado</span>
          <select id="fEstado"><?php foreach ($physicalStates as $s): ?><option value="<?= (int) $s['id'] ?>"><?= View::e($s['nombre']) ?></option><?php endforeach; ?></select>
        </label>
        <label class="field hidden" data-dynamic="serial"><span>Serial observado</span><input id="fSerial"></label>
      </div>
      <label class="field"><span>Observación</span><textarea id="fObservacion" placeholder="Solo si hace falta aclarar algo..."></textarea></label>
      <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:10px">
        <button class="btn btn-ghost" id="cancelChanges">Cancelar</button>
        <button class="btn btn-primary" id="saveChanges">Guardar y siguiente →</button>
      </div>
    </div>

    <div class="key-help"><span><kbd>S</kbd> Sin cambios</span><span><kbd>C</kbd> Cambios</span><span><kbd>N</kbd> No encontrado</span></div>
  </section>
</div>
<?php endif; ?>

<script type="module" src="/assets/js/modules/verificationCapture.js"></script>
