<?php
/** @var array $campaign */
/** @var array $items */
/** @var array $counters */
/** @var array $campos */
/** @var array $documents */
use App\Core\View;

$estadoBadge = ['GENERADA' => 'info', 'EN_VERIFICACION' => 'info', 'EN_CAPTURA' => 'warning', 'COMPLETADA' => 'success', 'CANCELADA' => 'neutral-status'];
$estadoLabel = ['GENERADA' => 'Generada', 'EN_VERIFICACION' => 'En verificación', 'EN_CAPTURA' => 'En captura', 'COMPLETADA' => 'Completada', 'CANCELADA' => 'Cancelada'];
$itemBadge = ['PENDIENTE' => 'neutral-status', 'VERIFICADO_SIN_CAMBIOS' => 'success', 'VERIFICADO_CON_CAMBIOS' => 'warning', 'NO_ENCONTRADO' => 'danger', 'REQUIERE_REVISION' => 'danger'];
$itemLabel = ['PENDIENTE' => 'Pendiente', 'VERIFICADO_SIN_CAMBIOS' => 'Sin cambios', 'VERIFICADO_CON_CAMBIOS' => 'Con cambios', 'NO_ENCONTRADO' => 'No encontrado', 'REQUIERE_REVISION' => 'Requiere revisión'];
$pct = $counters['total'] > 0 ? round(($counters['revisados'] / $counters['total']) * 100) : 0;
$canOperate = in_array($campaign['estado'], ['GENERADA', 'EN_VERIFICACION', 'EN_CAPTURA'], true);
$sheetDoc = null;
foreach ($documents as $d) { if ($d['tipo'] === 'verificacion_hoja') { $sheetDoc = $d; break; } }
?>
<div class="page-heading">
  <div>
    <span class="eyebrow">JORNADA <?= View::e($campaign['codigo']) ?></span>
    <h1><?= View::e($campaign['titulo']) ?></h1>
    <p><span class="status <?= $estadoBadge[$campaign['estado']] ?? 'neutral-status' ?>"><?= $estadoLabel[$campaign['estado']] ?? $campaign['estado'] ?></span> · <?= (int) $campaign['cantidad_bienes'] ?> bienes<?= $campaign['ubicacion_nombre'] ? ' · ' . View::e($campaign['ubicacion_nombre']) : '' ?></p>
  </div>
  <div class="page-heading-actions">
    <a class="btn btn-ghost" href="/verificacion">← Jornadas</a>
    <?php if ($sheetDoc): ?><button class="btn btn-ghost" data-doc-preview="<?= (int) $sheetDoc['id'] ?>">▤ Ver/imprimir hoja</button><?php endif; ?>
    <?php if ($canOperate): ?><a class="btn btn-primary" href="/verificacion/<?= (int) $campaign['id'] ?>/capturar">Continuar captura</a><?php endif; ?>
  </div>
</div>

<div class="mini-metrics">
  <article><span>Total</span><strong><?= (int) $counters['total'] ?></strong></article>
  <article><span>Revisados</span><strong><?= (int) $counters['revisados'] ?></strong></article>
  <article class="warn"><span>Con cambios</span><strong><?= (int) $counters['con_cambios'] ?></strong></article>
  <article class="danger"><span>No encontrados</span><strong><?= (int) $counters['no_encontrados'] ?></strong></article>
</div>

<?php if ($canOperate): ?>
<section class="panel" style="margin-bottom:14px;padding:16px 18px">
  <div style="display:flex;justify-content:space-between;font-size:10.5px;color:var(--text-secondary);margin-bottom:8px"><span><?= (int) $counters['revisados'] ?> / <?= (int) $counters['total'] ?></span><span><?= $pct ?>% completado</span></div>
  <div class="progress-bar large"><b style="width:<?= $pct ?>%"></b></div>
</section>
<?php endif; ?>

<section class="panel" style="margin-bottom:14px">
  <div class="panel-header"><div><span class="panel-kicker">BIENES</span><h3>Detalle de la jornada</h3></div></div>
  <div class="inventory-table-wrap active-view">
    <table class="smart-table">
      <thead><tr><th>Bien</th><th>Ubicación (hoja)</th><th>Responsable (hoja)</th><th>Estado</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
          <td><strong><?= View::e($item['numero_bien_snapshot']) ?></strong><span><?= View::e($item['descripcion_snapshot']) ?></span></td>
          <td><?= View::e($item['location_nombre_snapshot']) ?></td>
          <td><?= View::e($item['responsible_nombre_snapshot']) ?></td>
          <td><span class="status <?= $itemBadge[$item['estado_item']] ?? 'neutral-status' ?>"><?= $itemLabel[$item['estado_item']] ?? $item['estado_item'] ?></span></td>
          <td class="row-actions">
            <?php if ($item['estado_item'] === 'PENDIENTE' && $canOperate): ?>
              <a class="icon-action" href="/verificacion/<?= (int) $campaign['id'] ?>/capturar?item=<?= (int) $item['id'] ?>">Capturar</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<?php if (!empty($documents)): ?>
<section class="panel" style="margin-bottom:14px">
  <div class="panel-header"><div><span class="panel-kicker">DOCUMENTOS</span><h3>Hoja e informes generados</h3></div></div>
  <div style="padding:0 18px 18px;display:flex;flex-direction:column;gap:8px">
    <?php foreach ($documents as $doc): ?>
    <div class="document-mini">
      <span>📄</span>
      <div><strong><?= $doc['tipo'] === 'verificacion_hoja' ? 'Hoja de campo' : 'Cierre de jornada' ?></strong><small>Versión <?= (int) $doc['version'] ?> · <?= View::e($doc['usuario_nombre']) ?></small></div>
      <a href="#" data-doc-preview="<?= (int) $doc['id'] ?>">Previsualizar</a>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($canOperate): ?>
<section class="panel">
  <div class="panel-header"><div><span class="panel-kicker">CIERRE</span><h3>Finalizar jornada</h3></div></div>
  <div style="padding:0 18px 18px">
    <?php if ((int) $counters['pendientes'] > 0): ?>
      <label class="field"><span>Observaciones de cierre (obligatorio si cierras con pendientes)</span><textarea id="closeObservations"></textarea></label>
      <label class="field" style="margin-top:8px;flex-direction:row;align-items:center;gap:8px"><input type="checkbox" id="closeForce"> Cerrar con <?= (int) $counters['pendientes'] ?> bien(es) pendiente(s)</label>
    <?php endif; ?>
    <div style="display:flex;gap:8px;margin-top:12px">
      <button class="btn btn-primary" id="btnComplete">Cerrar jornada</button>
      <button class="btn btn-danger-soft" id="btnCancelCampaign">Cancelar jornada</button>
    </div>
  </div>
</section>
<?php endif; ?>

<script type="module">
  import { api } from '/assets/js/core/dom.js';
  import { showToast } from '/assets/js/core/toast.js';
  import { confirmAction } from '/assets/js/core/modal.js';
  import { initDocumentPreview } from '/assets/js/modules/documentPreview.js';
  import { refreshPageContent } from '/assets/js/core/pageRefresh.js';

  const campaignId = <?= (int) $campaign['id'] ?>;

  async function refreshDetail() {
    await refreshPageContent(() => { bindPage(); initDocumentPreview(); });
  }

  function bindPage() {
    document.getElementById('btnComplete')?.addEventListener('click', () => {
      const force = document.getElementById('closeForce')?.checked || false;
      const observaciones = document.getElementById('closeObservations')?.value || null;
      confirmAction({
        title: 'Cerrar jornada',
        message: force ? 'Vas a cerrar la jornada con bienes pendientes. Esto queda registrado en auditoría.' : '¿Confirmas el cierre de esta jornada de verificación?',
        confirmLabel: 'Cerrar jornada',
        onConfirm: async () => {
          try {
            await api(`/api/verification/${campaignId}/complete`, { method: 'POST', body: JSON.stringify({ force, observaciones }) });
            showToast('Jornada cerrada', 'Se generó el informe de cierre correctamente.', 'success');
            await refreshDetail();
          } catch (e) {
            showToast('No se pudo cerrar la jornada', e.message, 'error');
          }
        },
      });
    });

    document.getElementById('btnCancelCampaign')?.addEventListener('click', () => {
      confirmAction({
        title: 'Cancelar jornada',
        message: 'La jornada quedará cancelada y no podrá seguir capturándose. Esta acción queda registrada en auditoría.',
        confirmLabel: 'Cancelar jornada',
        danger: true,
        onConfirm: async () => {
          try {
            await api(`/api/verification/${campaignId}/cancel`, { method: 'POST' });
            showToast('Jornada cancelada', '', 'success');
            await refreshDetail();
          } catch (e) {
            showToast('No se pudo cancelar', e.message, 'error');
          }
        },
      });
    });
  }

  bindPage();
</script>
