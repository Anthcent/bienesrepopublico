<?php
/** @var array $asset */
/** @var array $movements */
/** @var array $documents */
/** @var array $history */
/** @var array $loans */
/** @var ?array $activeLoan */
/** @var array $locations */
/** @var array $responsibles */
/** @var array $historyLookups */
use App\Core\View;

$antiguedadDias = (new DateTimeImmutable())->diff(new DateTimeImmutable($asset['created_at']))->days;
$totalMovimientos = count($movements);
$totalPrestamos = count($loans);
$totalDocumentos = count($documents);
$responsableIniciales = mb_strtoupper(mb_substr($asset['responsable_nombre'], 0, 1));
$tieneContacto = !empty($asset['responsable_cedula']) || !empty($asset['responsable_telefono']) || !empty($asset['responsable_email']);
$docIconPath = '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/>';
$historyActionLabels = [
    'asset.incorporate' => 'Registro inicial del bien',
    'asset.update' => 'Actualización de información',
    'asset.reassign' => 'Cambio de ubicación o responsable',
    'asset.decommission' => 'Retiro del bien del inventario',
    'asset.readmit' => 'Reingreso del bien al inventario',
    'verification.correct' => 'Corrección durante una revisión física',
];
$historyFieldLabels = [
    'numero_bien' => 'Número de bien',
    'serial' => 'Serial',
    'descripcion' => 'Descripción',
    'category_id' => 'Categoría',
    'category_name' => 'Categoría',
    'brand_id' => 'Marca',
    'brand_name' => 'Marca',
    'model_id' => 'Modelo',
    'model_name' => 'Modelo',
    'color' => 'Color',
    'material' => 'Material',
    'imagen_url' => 'Imagen del bien',
    'location_id' => 'Ubicación',
    'location_name' => 'Ubicación',
    'responsible_id' => 'Responsable',
    'responsible_name' => 'Responsable',
    'physical_state_id' => 'Estado físico',
    'physical_state_name' => 'Estado físico',
    'estado_administrativo' => 'Estado administrativo',
    'disponibilidad' => 'Disponibilidad',
    'informacion_completa' => 'Información completa',
    'motivo' => 'Motivo',
    'observaciones' => 'Observaciones',
];
$historyValue = static function (string $field, mixed $value) use ($historyLookups): string {
    if ($field === 'imagen_url') {
        return $value ? 'Imagen registrada' : 'Sin imagen';
    }
    if ($field === 'informacion_completa') {
        return $value ? 'Sí' : 'No';
    }
    if (isset($historyLookups[$field]) && $value !== null && $value !== '') {
        $name = $historyLookups[$field][(int) $value] ?? null;
        return $name ? $name . ' (nombre actual)' : 'Registro anterior no disponible';
    }
    $knownValues = [
        'ACTIVO' => 'Activo',
        'DESINCORPORADO' => 'Desincorporado',
        'DISPONIBLE' => 'Disponible',
        'PRESTADO' => 'Prestado',
    ];
    if ($value === null || $value === '') {
        return 'No informado';
    }
    return $knownValues[(string) $value] ?? (string) $value;
};
$historyChanges = static function (array $event) use ($historyFieldLabels, $historyValue): array {
    $before = json_decode((string) ($event['valores_anteriores_json'] ?? ''), true);
    $after = json_decode((string) ($event['valores_nuevos_json'] ?? ''), true);
    $before = is_array($before) ? $before : [];
    $after = is_array($after) ? $after : [];
    $nameSnapshots = [
        'category_id' => 'category_name',
        'brand_id' => 'brand_name',
        'model_id' => 'model_name',
        'location_id' => 'location_name',
        'responsible_id' => 'responsible_name',
        'physical_state_id' => 'physical_state_name',
    ];
    $beforeNames = [];
    $afterNames = [];
    foreach ($nameSnapshots as $idField => $nameField) {
        if (array_key_exists($nameField, $before)) {
            $beforeNames[$idField] = $historyValue($nameField, $before[$nameField]);
            unset($before[$nameField]);
        }
        if (array_key_exists($nameField, $after)) {
            $afterNames[$idField] = $historyValue($nameField, $after[$nameField]);
            unset($after[$nameField]);
        }
    }
    $changes = [];

    foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $field) {
        $rawPrevious = $before[$field] ?? null;
        $rawCurrent = $after[$field] ?? null;
        if ($before && (string) $rawPrevious === (string) $rawCurrent) {
            continue;
        }
        $previous = $beforeNames[$field] ?? $historyValue($field, $rawPrevious);
        $current = $afterNames[$field] ?? $historyValue($field, $rawCurrent);
        if ($before && $previous === $current && $rawPrevious !== $rawCurrent) {
            $current .= ' (otro registro con el mismo nombre)';
        }
        $changes[] = [
            'label' => $historyFieldLabels[$field] ?? 'Información adicional',
            'before' => $previous,
            'after' => $current,
            'initial' => !$before,
            'context' => !array_key_exists($field, $before) && in_array($field, ['motivo', 'observaciones'], true),
        ];
    }
    return $changes;
};
?>
<div class="page-heading">
  <div>
    <span class="eyebrow">FICHA DEL BIEN</span>
    <h1><?= View::e($asset['numero_bien']) ?> · <?= View::e($asset['descripcion']) ?></h1>
    <p><?= View::e($asset['serial'] ?: 'Sin serial') ?><?php if ($asset['marca_nombre']): ?> · <?= View::e($asset['marca_nombre']) ?> <?= View::e($asset['modelo_nombre'] ?? '') ?><?php endif; ?></p>
  </div>
  <div class="page-heading-actions">
    <a class="btn btn-ghost" href="/inventario">← Volver al inventario</a>
  </div>
</div>

<section class="panel" style="margin-bottom:14px">
  <div class="asset-hero">
    <div class="asset-photo-placeholder">
      <?php if (!empty($asset['imagen_url'])): ?>
        <img src="<?= View::e($asset['imagen_url']) ?>" alt="<?= View::e($asset['descripcion']) ?>">
      <?php else: ?>
        <svg viewBox="0 0 24 24"><path d="m21 8-9 5-9-5m18 0-9-5-9 5m18 0v8l-9 5-9-5V8m9 5v8"/></svg>
      <?php endif; ?>
    </div>
    <div>
      <h3><?= View::e($asset['descripcion']) ?></h3>
      <p><?= View::e($asset['numero_bien']) ?> · <?= View::e($asset['serial'] ?: 'Sin serial') ?></p>
      <div class="asset-status-row">
        <span class="status <?= $asset['estado_administrativo'] === 'ACTIVO' ? 'success' : 'neutral-status' ?>">● <?= View::e($asset['estado_administrativo']) ?></span>
        <span class="status <?= $asset['disponibilidad'] === 'DISPONIBLE' ? 'info' : 'warning' ?>"><?= View::e($asset['disponibilidad']) ?></span>
        <span class="status neutral-status"><?= View::e($asset['estado_fisico_nombre']) ?></span>
        <?php if ($activeLoan): ?>
          <a class="status danger" href="/prestamos/<?= (int) $activeLoan['id'] ?>">Préstamo activo: <?= View::e($activeLoan['codigo']) ?></a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<div class="content-grid">
  <section class="panel">
    <div class="notification-tabs" id="assetTabs" style="padding:14px 18px 0">
      <button class="active" data-tab="resumen">Resumen</button>
      <button data-tab="movimientos">Movimientos <?= count($movements) ? '<span>' . count($movements) . '</span>' : '' ?></button>
      <button data-tab="prestamos">Préstamos <?= count($loans) ? '<span>' . count($loans) . '</span>' : '' ?></button>
      <button data-tab="documentos">Documentos <?= count($documents) ? '<span>' . count($documents) . '</span>' : '' ?></button>
      <button data-tab="historial">Historial <?= count($history) ? '<span>' . count($history) . '</span>' : '' ?></button>
    </div>

    <div class="tab-panel active" data-tab-panel="resumen" style="padding:18px">
      <div class="detail-grid">
        <div><span>Ubicación actual</span><strong><?= View::e($asset['ubicacion_nombre']) ?><?= $asset['ubicacion_piso_zona'] ? ' · ' . View::e($asset['ubicacion_piso_zona']) : '' ?></strong></div>
        <div><span>Responsable</span><strong><?= View::e($asset['responsable_nombre']) ?><?= $asset['responsable_cargo'] ? ' · ' . View::e($asset['responsable_cargo']) : '' ?></strong></div>
        <div><span>Categoría</span><strong><?= View::e($asset['categoria_nombre'] ?? '—') ?></strong></div>
        <div><span>Color</span><strong><?= View::e($asset['color'] ?? '—') ?></strong></div>
        <div><span>Material</span><strong><?= View::e($asset['material'] ?? '—') ?></strong></div>
        <div><span>Creado</span><strong><?= (new DateTimeImmutable($asset['created_at']))->format('d/m/Y') ?></strong></div>
        <div><span>Última actualización</span><strong><?= (new DateTimeImmutable($asset['updated_at']))->format('d/m/Y H:i') ?></strong></div>
      </div>
    </div>

    <div class="tab-panel" data-tab-panel="movimientos" style="padding:8px 18px 18px">
      <?php if (empty($movements)): ?>
        <?php View::component('empty_state', ['title' => 'Sin movimientos', 'message' => 'Este bien todavía no registra movimientos administrativos.']); ?>
      <?php else: ?>
        <?php foreach ($movements as $m): ?>
        <div class="activity-item">
          <span class="activity-icon violet">⇄</span>
          <div><strong><?= View::e($m['tipo_nombre']) ?></strong><span><?= View::e($m['motivo'] ?? '') ?> · <?= View::e($m['usuario_nombre']) ?></span></div>
          <small><?= (new DateTimeImmutable($m['created_at']))->format('d/m/Y H:i') ?></small>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="tab-panel" data-tab-panel="prestamos" style="padding:8px 18px 18px">
      <?php if (empty($loans)): ?>
        <?php View::component('empty_state', ['title' => 'Sin préstamos', 'message' => 'Este bien todavía no ha sido prestado.']); ?>
      <?php else: ?>
        <?php foreach ($loans as $loan): ?>
        <a class="loan-item" href="/prestamos/<?= (int) $loan['id'] ?>">
          <div class="loan-date neutral"><strong><?= (new DateTimeImmutable($loan['fecha_prestamo']))->format('d/m') ?></strong><span><?= (new DateTimeImmutable($loan['fecha_prestamo']))->format('Y') ?></span></div>
          <div class="loan-info">
            <strong><?= View::e($loan['codigo']) ?></strong>
            <span>Prestado a <?= View::e($loan['prestatario_nombre_snapshot']) ?></span>
            <small>Salió en estado: <?= View::e($loan['estado_salida_nombre']) ?></small>
          </div>
          <?php $badge = ['ACTIVO' => 'info', 'PARCIALMENTE_DEVUELTO' => 'warning', 'DEVUELTO' => 'success', 'VENCIDO' => 'danger', 'ANULADO' => 'neutral-status'][$loan['estado']] ?? 'neutral-status'; ?>
          <span class="status <?= $badge ?>"><?= View::e($loan['estado']) ?></span>
        </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="tab-panel" data-tab-panel="documentos" style="padding:8px 18px 18px">
      <?php if (empty($documents)): ?>
        <?php View::component('empty_state', ['title' => 'Sin documentos', 'message' => 'Aún no se ha generado ningún documento para este bien.']); ?>
      <?php else: ?>
        <?php foreach ($documents as $doc): ?>
        <div class="document-mini">
          <span class="asset-thumb blue"><svg viewBox="0 0 24 24"><?= $docIconPath ?></svg></span>
          <div><strong><?= View::e(ucfirst($doc['tipo'])) ?></strong><small>Versión <?= (int) $doc['version'] ?> · <?= (new DateTimeImmutable($doc['created_at']))->format('d/m/Y') ?> · <?= View::e($doc['usuario_nombre']) ?></small></div>
          <a href="#" data-doc-preview="<?= (int) $doc['id'] ?>">Previsualizar</a>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="tab-panel asset-history" data-tab-panel="historial">
      <?php if (empty($history)): ?>
        <?php View::component('empty_state', ['title' => 'Sin historial', 'message' => 'No hay registros de auditoría para este bien.']); ?>
      <?php else: ?>
        <div class="asset-history-intro">
          <strong>Historial de cambios del bien</strong>
          <span>Aquí se muestran cambios de datos y situación administrativa. Los préstamos y devoluciones se consultan en su pestaña.</span>
        </div>
        <?php foreach ($history as $h):
          $eventDate = new DateTimeImmutable($h['created_at']);
          $changes = $historyChanges($h);
          $initial = !empty($changes) && $changes[0]['initial'];
        ?>
        <article class="asset-history-event">
          <div class="asset-history-marker"><span></span></div>
          <div class="asset-history-card">
            <div class="asset-history-heading">
              <div>
                <span class="asset-history-kind"><?= View::e($historyActionLabels[$h['accion']] ?? 'Actividad del bien') ?></span>
                <strong><?= View::e($h['resumen_humano']) ?></strong>
              </div>
              <time datetime="<?= View::e($eventDate->format(DATE_ATOM)) ?>"><?= $eventDate->format('d/m/Y') ?><small><?= $eventDate->format('H:i') ?></small></time>
            </div>
            <div class="asset-history-meta">
              <span><b>Realizado por</b> <?= View::e($h['usuario_nombre'] ?? 'Proceso automático') ?></span>
              <span><b>Momento exacto</b> <?= $eventDate->format('d/m/Y H:i:s') ?></span>
            </div>
            <?php if ($changes): ?>
            <details class="asset-history-details">
              <summary><?= $initial ? 'Ver información registrada' : 'Ver ' . count($changes) . ' cambio' . (count($changes) === 1 ? '' : 's') ?></summary>
              <?php if ($initial): ?>
              <div class="asset-history-initial">
                <?php foreach ($changes as $change): ?>
                  <div><span><?= View::e($change['label']) ?></span><strong><?= View::e($change['after']) ?></strong></div>
                <?php endforeach; ?>
              </div>
              <?php else: ?>
              <div class="asset-history-change-list">
                <?php foreach ($changes as $change): ?>
                <?php if ($change['context']): ?>
                <div class="asset-history-context">
                  <strong><?= View::e($change['label']) ?></strong>
                  <span><?= View::e($change['after']) ?></span>
                </div>
                <?php else: ?>
                <div class="asset-history-change">
                  <strong><?= View::e($change['label']) ?></strong>
                  <span class="history-old"><small>Valor anterior</small><?= View::e($change['before']) ?></span>
                  <span class="history-arrow" aria-hidden="true">→</span>
                  <span class="history-new"><small>Nuevo valor</small><?= View::e($change['after']) ?></span>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
            </details>
            <?php else: ?>
              <p class="asset-history-no-changes">Este evento no modificó información general del bien.</p>
            <?php endif; ?>
          </div>
        </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="panel-footer">
      <span>Registrado por <?= View::e($asset['creado_por_nombre'] ?? 'Sistema') ?> el <?= (new DateTimeImmutable($asset['created_at']))->format('d/m/Y \a \l\a\s H:i') ?></span>
    </div>
  </section>

  <aside class="right-column">
    <section class="panel">
      <div class="panel-header compact"><div><span class="panel-kicker">ACCIONES</span><h3>Gestionar bien</h3></div></div>
      <div class="asset-action-grid" style="padding:0 18px 18px" id="assetActions" data-asset-id="<?= (int) $asset['id'] ?>">
        <?php if ($asset['estado_administrativo'] === 'ACTIVO' && $asset['disponibilidad'] === 'DISPONIBLE'): ?>
          <button class="btn btn-primary" id="btnLoan">↗ Prestar</button>
          <button class="btn btn-secondary" id="btnReassign">⇄ Reasignar</button>
          <button class="btn btn-danger-soft" id="btnDecommission">Desincorporar</button>
        <?php elseif ($asset['estado_administrativo'] === 'ACTIVO' && $asset['disponibilidad'] === 'PRESTADO'): ?>
          <a class="btn btn-secondary wide" href="<?= $activeLoan ? '/prestamos/' . (int) $activeLoan['id'] : '/prestamos' ?>">Ver préstamo activo</a>
        <?php else: ?>
          <button class="btn btn-primary wide" id="btnReadmit">Readmitir bien</button>
        <?php endif; ?>
      </div>
    </section>

    <section class="panel">
      <div class="panel-header compact"><div><span class="panel-kicker">RESPONSABLE</span><h3>Datos de contacto</h3></div></div>
      <div style="padding:0 18px 18px;display:flex;flex-direction:column;gap:12px">
        <div style="display:flex;align-items:center;gap:10px">
          <span class="asset-thumb blue large"><?= View::e($responsableIniciales) ?></span>
          <div>
            <strong style="font-size:11px;display:block"><?= View::e($asset['responsable_nombre']) ?></strong>
            <small style="color:var(--text-muted)"><?= View::e($asset['responsable_cargo'] ?: 'Sin cargo registrado') ?></small>
          </div>
        </div>
        <?php if ($tieneContacto): ?>
          <div class="detail-grid">
            <?php if (!empty($asset['responsable_cedula'])): ?>
              <div><span>Cédula</span><strong><?= View::e($asset['responsable_cedula']) ?></strong></div>
            <?php endif; ?>
            <?php if (!empty($asset['responsable_telefono'])): ?>
              <div><span>Teléfono</span><strong><?= View::e($asset['responsable_telefono']) ?></strong></div>
            <?php endif; ?>
            <?php if (!empty($asset['responsable_email'])): ?>
              <div><span>Email</span><strong><?= View::e($asset['responsable_email']) ?></strong></div>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <p style="font-size:9.5px;color:var(--text-muted);margin:0">Sin datos de contacto adicionales registrados para este responsable.</p>
        <?php endif; ?>
      </div>
    </section>

    <section class="panel">
      <div class="panel-header compact"><div><span class="panel-kicker">RESUMEN</span><h3>De un vistazo</h3></div></div>
      <div class="detail-grid" style="padding:0 18px 18px">
        <div><span>Antigüedad en el sistema</span><strong><?= $antiguedadDias ?> día<?= $antiguedadDias === 1 ? '' : 's' ?></strong></div>
        <div><span>Movimientos registrados</span><strong><?= $totalMovimientos ?></strong></div>
        <div><span>Préstamos históricos</span><strong><?= $totalPrestamos ?></strong></div>
        <div><span>Documentos generados</span><strong><?= $totalDocumentos ?></strong></div>
      </div>
    </section>
  </aside>
</div>

<div class="modal-backdrop" id="reassignModalBackdrop"></div>
<section class="modal" id="reassignModal" role="dialog" aria-modal="true" style="width:min(460px,calc(100% - 30px))">
  <div class="modal-header">
    <div><span class="panel-kicker">MOVIMIENTO</span><h3>Reasignar bien</h3></div>
    <button class="icon-btn modal-close" data-modal-close="reassignModal">×</button>
  </div>
  <div class="modal-body">
    <label class="field">
      <span>Nueva ubicación</span>
      <select id="reassignLocation">
        <?php foreach ($locations as $location): ?>
          <option value="<?= (int) $location['id'] ?>" <?= (int) $location['id'] === (int) $asset['location_id'] ? 'selected' : '' ?>><?= View::e($location['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="field">
      <span>Nuevo responsable</span>
      <select id="reassignResponsible">
        <?php foreach ($responsibles as $responsible): ?>
          <option value="<?= (int) $responsible['id'] ?>" <?= (int) $responsible['id'] === (int) $asset['responsible_id'] ? 'selected' : '' ?>><?= View::e($responsible['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="field"><span>Motivo</span><input id="reassignMotivo" placeholder="Opcional"></label>
  </div>
  <div class="modal-footer">
    <button class="btn btn-ghost" data-modal-close="reassignModal">Cancelar</button>
    <button class="btn btn-primary" id="confirmReassign">Reasignar</button>
  </div>
</section>

<script type="module">
  import { api } from '/assets/js/core/dom.js';
  import { showToast } from '/assets/js/core/toast.js';
  import { confirmAction, openModal, registerModal } from '/assets/js/core/modal.js';
  import { initDocumentPreview } from '/assets/js/modules/documentPreview.js';
  import { refreshPageContent } from '/assets/js/core/pageRefresh.js';

  async function refreshDetail() {
    await refreshPageContent(() => { bindPage(); initDocumentPreview(); });
  }

  function bindPage() {
    const assetId = document.getElementById('assetActions')?.dataset.assetId;

    document.querySelectorAll('#assetTabs button').forEach((btn) => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('#assetTabs button').forEach((b) => b.classList.toggle('active', b === btn));
        document.querySelectorAll('[data-tab-panel]').forEach((panel) => {
          panel.classList.toggle('active', panel.dataset.tabPanel === btn.dataset.tab);
        });
      });
    });

    registerModal('reassignModal', 'reassignModalBackdrop');
    document.getElementById('btnReassign')?.addEventListener('click', () => openModal('reassignModal'));
    document.getElementById('confirmReassign')?.addEventListener('click', async () => {
      try {
        await api(`/api/assets/${assetId}/reasignar`, {
          method: 'POST',
          body: JSON.stringify({
            location_id: document.getElementById('reassignLocation').value,
            responsible_id: document.getElementById('reassignResponsible').value,
            motivo: document.getElementById('reassignMotivo').value,
          }),
        });
        showToast('Bien reasignado', 'La ubicación y responsable se actualizaron correctamente.', 'success');
        await refreshDetail();
      } catch (e) {
        showToast('No se pudo reasignar', e.message, 'error');
      }
    });

    document.getElementById('btnLoan')?.addEventListener('click', () => {
      window.__openLoanModal?.(<?= json_encode(['id' => (int) $asset['id'], 'numero_bien' => $asset['numero_bien'], 'descripcion' => $asset['descripcion']]) ?>);
    });

    document.getElementById('btnDecommission')?.addEventListener('click', () => {
      confirmAction({
        title: 'Desincorporar bien',
        message: '¿Confirmas la desincorporación de <?= View::e($asset['numero_bien']) ?>? Esta acción queda registrada en auditoría.',
        confirmLabel: 'Desincorporar',
        danger: true,
        onConfirm: async () => {
          try {
            await api(`/api/assets/${assetId}/desincorporar`, { method: 'POST', body: JSON.stringify({ motivo: 'Desincorporación solicitada desde la ficha del bien' }) });
            showToast('Bien desincorporado', 'El bien fue desincorporado correctamente.', 'success');
            await refreshDetail();
          } catch (e) {
            showToast('No se pudo desincorporar', e.message, 'error');
          }
        },
      });
    });

    document.getElementById('btnReadmit')?.addEventListener('click', () => {
      confirmAction({
        title: 'Readmitir bien',
        message: '¿Confirmas la readmisión de <?= View::e($asset['numero_bien']) ?> al inventario activo?',
        confirmLabel: 'Readmitir',
        onConfirm: async () => {
          try {
            await api(`/api/assets/${assetId}/readmitir`, { method: 'POST', body: JSON.stringify({ motivo: 'Readmisión solicitada desde la ficha del bien' }) });
            showToast('Bien readmitido', 'El bien vuelve a estar activo.', 'success');
            await refreshDetail();
          } catch (e) {
            showToast('No se pudo readmitir', e.message, 'error');
          }
        },
      });
    });
  }

  bindPage();
</script>
