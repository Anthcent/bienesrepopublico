<?php
/** @var array $loan */
/** @var array $extensions */
/** @var array $documents */
/** @var array $physicalStates */
use App\Core\View;

$badge = ['ACTIVO' => 'info', 'PARCIALMENTE_DEVUELTO' => 'warning', 'DEVUELTO' => 'success', 'VENCIDO' => 'danger', 'ANULADO' => 'neutral-status'][$loan['estado']] ?? 'neutral-status';
$canOperate = in_array($loan['estado'], ['ACTIVO', 'PARCIALMENTE_DEVUELTO', 'VENCIDO'], true);
$canCancel = in_array($loan['estado'], ['ACTIVO', 'PARCIALMENTE_DEVUELTO'], true);

$fechaPrestamo = new DateTimeImmutable($loan['fecha_prestamo']);
$fechaVencimiento = new DateTimeImmutable($loan['fecha_vencimiento']);
$today = new DateTimeImmutable('today');
$diasRestantes = (int) $today->diff($fechaVencimiento)->format('%r%a');
$totalDias = max(1, $fechaPrestamo->diff($fechaVencimiento)->days);
$diasTranscurridos = max(0, min($totalDias, $fechaPrestamo->diff($today)->days));
$progresoVigencia = (int) round($diasTranscurridos / $totalDias * 100);

$totalBienes = count($loan['detalles']);
$bienesDevueltos = count(array_filter($loan['detalles'], fn ($d) => $d['fecha_devolucion'] !== null));

$docIcons = [
    'prestamo' => ['blue', '<path d="M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h6"/>'],
    'devolucion' => ['mint', '<path d="M9 11 6 8l3-3"/><path d="M6 8h9a5 5 0 0 1 5 5v1"/>'],
    'default' => ['blue', '<path d="M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"/><path d="M14 2v6h6"/>'],
];
?>
<div class="page-heading">
  <div>
    <span class="eyebrow">PRÉSTAMO</span>
    <h1><?= View::e($loan['codigo']) ?></h1>
    <p>Prestado a <?= View::e($loan['prestatario_nombre_snapshot']) ?><?php if ($loan['prestatario_dependencia_snapshot']): ?> · <?= View::e($loan['prestatario_dependencia_snapshot']) ?><?php endif; ?></p>
  </div>
  <div class="page-heading-actions">
    <span class="status <?= $badge ?>" style="height:42px;padding:0 16px"><?= View::e($loan['estado']) ?></span>
    <a class="btn btn-ghost" href="/prestamos">← Volver</a>
  </div>
</div>

<div class="content-grid">
  <section class="panel">
    <div class="panel-header"><div><span class="panel-kicker">DETALLE</span><h3>Bienes del préstamo</h3></div></div>
    <?php if ($loan['motivo'] || $loan['observaciones']): ?>
    <div class="suggestion-box" style="margin:0 18px 14px">
      <div class="suggestion-icon">i</div>
      <div>
        <?php if ($loan['motivo']): ?><strong><?= View::e($loan['motivo']) ?></strong><?php endif; ?>
        <?php if ($loan['observaciones']): ?><p><?= View::e($loan['observaciones']) ?></p><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
    <div class="loan-asset-list" id="loanDetailsBody">
      <?php foreach ($loan['detalles'] as $detail): ?>
      <div class="loan-asset-row" data-detail-id="<?= (int) $detail['id'] ?>">
        <div class="loan-asset-top">
          <div class="asset-cell">
            <div class="asset-thumb blue"><span>▦</span></div>
            <div><strong><?= View::e($detail['numero_bien']) ?></strong><span><?= View::e($detail['descripcion']) ?></span></div>
          </div>
          <div class="loan-asset-status">
            <span class="status neutral-status">Salió: <?= View::e($detail['estado_salida_nombre']) ?></span>
            <?php if ($detail['fecha_devolucion']): ?>
              <span class="status success">Devuelto <?= (new DateTimeImmutable($detail['fecha_devolucion']))->format('d/m/Y') ?></span>
            <?php else: ?>
              <span class="status warning">Pendiente</span>
            <?php endif; ?>
          </div>
          <?php if (!$detail['fecha_devolucion'] && $canOperate): ?>
            <label class="check-row">
              <input type="checkbox" class="return-check" value="<?= (int) $detail['id'] ?>">
              Devolver este bien
            </label>
          <?php endif; ?>
        </div>
        <?php if (!$detail['fecha_devolucion'] && $canOperate): ?>
          <div class="return-fields" hidden>
            <div class="form-grid two">
              <label class="field">
                <span>Estado al devolver</span>
                <select class="return-state" data-detail="<?= (int) $detail['id'] ?>">
                  <?php foreach ($physicalStates as $state): ?>
                    <option value="<?= (int) $state['id'] ?>"><?= View::e($state['nombre']) ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label class="field">
                <span>URL de imagen del daño (opcional)</span>
                <input type="url" class="return-image" data-detail="<?= (int) $detail['id'] ?>" placeholder="https://…">
              </label>
            </div>
            <label class="field">
              <span>Observación<?= $loanSettings['require_return_observation'] ? ' (obligatoria si el estado empeora)' : ' (opcional)' ?></span>
              <textarea class="return-observation" data-detail="<?= (int) $detail['id'] ?>" placeholder="Detalle del estado con el que se devuelve el bien…"></textarea>
            </label>
          </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if ($canOperate): ?>
    <div class="inline-section" style="padding:16px 18px">
      <p class="field-help">Marca cada bien que se está devolviendo, su estado y — si corresponde — la observación e imagen de ese bien en particular.</p>
      <button class="btn btn-primary" id="btnReturn" style="margin-top:10px">Registrar devolución seleccionada</button>
    </div>
    <?php endif; ?>

    <div class="inline-section" style="padding:0 18px 18px">
      <div class="inline-section-header"><strong>Documentos</strong></div>
      <?php foreach ($documents as $doc):
        [$docColor, $docPath] = $docIcons[$doc['tipo']] ?? $docIcons['default'];
      ?>
        <div class="document-mini">
          <span class="result-icon <?= $docColor ?>"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?= $docPath ?></svg></span>
          <div><strong><?= View::e(ucfirst($doc['tipo'])) ?></strong><small>Versión <?= (int) $doc['version'] ?></small></div>
          <a href="#" data-doc-preview="<?= (int) $doc['id'] ?>">Previsualizar</a>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($loan['creado_por_nombre']): ?>
    <div class="panel-footer">
      <span>Registrado por <?= View::e($loan['creado_por_nombre']) ?> el <?= (new DateTimeImmutable($loan['created_at']))->format('d/m/Y H:i') ?></span>
    </div>
    <?php endif; ?>
  </section>

  <aside class="right-column">
    <section class="panel">
      <div class="panel-header compact"><div><span class="panel-kicker">PRESTATARIO</span><h3>Datos de contacto</h3></div></div>
      <div style="display:flex;gap:12px;align-items:center;padding:0 18px 14px">
        <div class="asset-thumb blue large"><?= View::e(mb_strtoupper(mb_substr($loan['prestatario_nombre_snapshot'], 0, 1))) ?></div>
        <div style="min-width:0">
          <strong style="display:block;font-size:12px;color:var(--navy-900)"><?= View::e($loan['prestatario_nombre_snapshot']) ?></strong>
          <?php if ($loan['prestatario_cargo_snapshot'] || $loan['prestatario_dependencia_snapshot']): ?>
            <span style="display:block;font-size:9.5px;color:var(--text-muted);margin-top:2px"><?= View::e(trim(($loan['prestatario_cargo_snapshot'] ?: '') . (($loan['prestatario_cargo_snapshot'] && $loan['prestatario_dependencia_snapshot']) ? ' · ' : '') . ($loan['prestatario_dependencia_snapshot'] ?: ''))) ?></span>
          <?php endif; ?>
        </div>
      </div>
      <?php if ($loan['responsable_cedula'] || $loan['responsable_telefono'] || $loan['responsable_email']): ?>
      <div class="detail-grid" style="padding:0 18px 18px">
        <?php if ($loan['responsable_cedula']): ?><div><span>Cédula</span><strong><?= View::e($loan['responsable_cedula']) ?></strong></div><?php endif; ?>
        <?php if ($loan['responsable_telefono']): ?><div><span>Teléfono</span><strong><?= View::e($loan['responsable_telefono']) ?></strong></div><?php endif; ?>
        <?php if ($loan['responsable_email']): ?><div style="grid-column:1/-1"><span>Correo</span><strong><?= View::e($loan['responsable_email']) ?></strong></div><?php endif; ?>
      </div>
      <?php else: ?>
      <p class="field-help" style="padding:0 18px 18px">Sin datos de contacto adicionales registrados para este prestatario.</p>
      <?php endif; ?>
    </section>

    <section class="panel">
      <div class="panel-header compact"><div><span class="panel-kicker">RESUMEN</span><h3>De un vistazo</h3></div></div>
      <div class="detail-grid" style="padding:0 18px 18px">
        <div><span>Bienes</span><strong><?= $bienesDevueltos ?> de <?= $totalBienes ?> devuelto<?= $totalBienes === 1 ? '' : 's' ?></strong></div>
        <div>
          <span>Estado</span>
          <?php if ($loan['estado'] === 'DEVUELTO' || $loan['estado'] === 'ANULADO'): ?>
            <strong>Cerrado</strong>
          <?php elseif ($diasRestantes < 0): ?>
            <strong style="color:var(--danger)">Vencido hace <?= abs($diasRestantes) ?> día<?= abs($diasRestantes) === 1 ? '' : 's' ?></strong>
          <?php elseif ($diasRestantes === 0): ?>
            <strong style="color:var(--warning)">Vence hoy</strong>
          <?php else: ?>
            <strong>Vence en <?= $diasRestantes ?> día<?= $diasRestantes === 1 ? '' : 's' ?></strong>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section class="panel">
      <div class="panel-header compact"><div><span class="panel-kicker">FECHAS</span><h3>Vigencia</h3></div></div>
      <div class="detail-grid" style="padding:0 18px 18px">
        <div><span>Entrega</span><strong><?= $fechaPrestamo->format('d/m/Y') ?></strong></div>
        <div><span>Vence</span><strong><?= $fechaVencimiento->format('d/m/Y') ?></strong></div>
      </div>
      <?php if ($canOperate): ?>
      <div style="padding:0 18px 18px">
        <div class="progress-top" style="display:flex;justify-content:space-between;font-size:9px;color:var(--text-muted);margin-bottom:6px">
          <span>Tiempo transcurrido</span><strong style="color:var(--navy-900)"><?= min(100, $progresoVigencia) ?>%</strong>
        </div>
        <div class="progress-bar"><b style="width:<?= min(100, $progresoVigencia) ?>%<?= $progresoVigencia >= 100 ? ';background:linear-gradient(90deg,var(--danger),#d9534f)' : '' ?>"></b></div>
      </div>
      <div class="inline-section" style="padding:0 18px 18px">
        <label class="field"><span>Nueva fecha de vencimiento</span><input type="date" id="extendDate"></label>
        <button class="btn btn-secondary" id="btnExtend" style="margin-top:8px;width:100%">Extender préstamo</button>
        <?php if ($canCancel): ?><button class="btn btn-danger-soft" id="btnCancel" style="margin-top:8px;width:100%">Anular préstamo</button><?php endif; ?>
      </div>
      <?php endif; ?>
    </section>

    <?php if (!empty($extensions)): ?>
    <section class="panel">
      <div class="panel-header compact"><div><span class="panel-kicker">HISTORIAL</span><h3>Extensiones</h3></div></div>
      <div class="activity-list">
        <?php foreach ($extensions as $ext): ?>
        <div class="activity-item">
          <span class="activity-icon orange">↻</span>
          <div><strong><?= (new DateTimeImmutable($ext['fecha_anterior']))->format('d/m/Y') ?> → <?= (new DateTimeImmutable($ext['fecha_nueva']))->format('d/m/Y') ?></strong><span><?= View::e($ext['usuario_nombre']) ?></span></div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>
  </aside>
</div>

<script type="module">
  import { api } from '/assets/js/core/dom.js';
  import { showToast } from '/assets/js/core/toast.js';
  import { confirmAction } from '/assets/js/core/modal.js';
  import { initDocumentPreview } from '/assets/js/modules/documentPreview.js';
  import { refreshPageContent } from '/assets/js/core/pageRefresh.js';

  const loanId = <?= (int) $loan['id'] ?>;

  async function refreshDetail() {
    await refreshPageContent(() => { bindPage(); initDocumentPreview(); });
  }

  function bindPage() {
    document.querySelectorAll('.return-check').forEach((checkbox) => {
      const fields = checkbox.closest('.loan-asset-row')?.querySelector('.return-fields');
      if (!fields) return;
      fields.hidden = !checkbox.checked;
      checkbox.addEventListener('change', () => { fields.hidden = !checkbox.checked; });
    });

    document.getElementById('btnReturn')?.addEventListener('click', async () => {
      const returns = [...document.querySelectorAll('.return-check:checked')].map((cb) => ({
        detail_id: cb.value,
        estado_devolucion_id: document.querySelector(`.return-state[data-detail="${cb.value}"]`).value,
        observacion: document.querySelector(`.return-observation[data-detail="${cb.value}"]`).value,
        imagen_url: document.querySelector(`.return-image[data-detail="${cb.value}"]`).value || null,
      }));
      if (returns.length === 0) {
        showToast('Selecciona al menos un bien', 'Marca la casilla de los bienes que se están devolviendo.', 'warning');
        return;
      }
      try {
        await api(`/api/loans/${loanId}/return`, { method: 'POST', body: JSON.stringify({ returns }) });
        showToast('Devolución registrada', 'El estado del préstamo se actualizó correctamente.', 'success');
        await refreshDetail();
      } catch (e) {
        showToast('No se pudo registrar la devolución', e.message, 'error');
      }
    });

    document.getElementById('btnExtend')?.addEventListener('click', async () => {
      const fecha = document.getElementById('extendDate').value;
      if (!fecha) { showToast('Fecha requerida', 'Selecciona la nueva fecha de vencimiento.', 'warning'); return; }
      try {
        await api(`/api/loans/${loanId}/extend`, { method: 'POST', body: JSON.stringify({ fecha_nueva: fecha }) });
        showToast('Préstamo extendido', 'La fecha de vencimiento fue actualizada.', 'success');
        await refreshDetail();
      } catch (e) {
        showToast('No se pudo extender', e.message, 'error');
      }
    });

    document.getElementById('btnCancel')?.addEventListener('click', () => {
      confirmAction({
        title: 'Anular préstamo',
        message: 'Los bienes pendientes de devolución volverán a estar disponibles. Esta acción queda registrada en auditoría.',
        confirmLabel: 'Anular préstamo',
        danger: true,
        onConfirm: async () => {
          try {
            await api(`/api/loans/${loanId}/cancel`, { method: 'POST' });
            showToast('Préstamo anulado', 'El préstamo fue anulado correctamente.', 'success');
            await refreshDetail();
          } catch (e) {
            showToast('No se pudo anular', e.message, 'error');
          }
        },
      });
    });
  }

  bindPage();
</script>
