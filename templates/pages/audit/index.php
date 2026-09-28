<?php
/** @var array $items */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var array $filters */
/** @var array $filterOptions */
use App\Core\View;

$actionLabels = [
    'auth.login.locked' => 'Acceso bloqueado por seguridad',
    'auth.login.success' => 'Inicio de sesión',
    'auth.logout' => 'Cierre de sesión',
    'user.create' => 'Creación de usuario',
    'user.update' => 'Actualización de usuario',
    'user.activate' => 'Activación de usuario',
    'user.deactivate' => 'Desactivación de usuario',
    'catalog.create' => 'Creación en un catálogo',
    'catalog.update' => 'Actualización de un catálogo',
    'asset.incorporate' => 'Registro inicial del bien',
    'asset.update' => 'Actualización de información',
    'asset.reassign' => 'Cambio de ubicación o responsable',
    'asset.decommission' => 'Retiro de un bien del inventario',
    'asset.readmit' => 'Reingreso de un bien al inventario',
    'loan.create' => 'Registro de préstamo',
    'loan.return' => 'Devolución de préstamo',
    'loan.extend' => 'Extensión de préstamo',
    'loan.cancel' => 'Anulación de préstamo',
    'verification.create' => 'Inicio de una verificación',
    'verification.capture' => 'Registro de una verificación',
    'verification.correct' => 'Corrección durante una revisión física',
    'verification.complete' => 'Finalización de una verificación',
    'verification.cancel' => 'Cancelación de una verificación',
    'institutional_identity.update' => 'Cambio de identidad institucional',
    'loan_settings.update' => 'Cambio en préstamos y alertas',
    'security_settings.update' => 'Cambio en seguridad y respaldo',
];
$entityLabels = [
    'authentication' => 'Accesos al sistema',
    'user' => 'Usuarios',
    'asset' => 'Bienes públicos',
    'loan' => 'Préstamos',
    'verification_campaign' => 'Jornadas de verificación',
    'verification_campaign_item' => 'Bienes verificados',
    'locations' => 'Ubicaciones',
    'responsibles' => 'Responsables',
    'categories' => 'Categorías',
    'brands' => 'Marcas',
    'models' => 'Modelos',
    'physical_states' => 'Estados físicos',
    'movement_types' => 'Tipos de movimiento',
    'institutional_identity' => 'Identidad institucional',
    'loan_settings' => 'Préstamos y alertas',
    'security_settings' => 'Seguridad y respaldo',
];
$actionLabel = static fn (string $action): string => $actionLabels[$action] ?? 'Otra actividad del sistema';
$entityLabel = static fn (string $entity): string => $entityLabels[$entity] ?? 'Otra sección del sistema';
$activeFilters = count(array_filter([
    $filters['accion'], $filters['entidad_tipo'], $filters['usuario_id'], $filters['desde'], $filters['hasta'],
]));
$formatJson = static function (?string $json): string {
    if ($json === null || $json === '') return '';
    $decoded = json_decode($json, true);
    return json_last_error() === JSON_ERROR_NONE
        ? (string) json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        : $json;
};
$eventClass = static function (string $action): string {
    return match (strtok($action, '.')) {
        'auth', 'security_settings' => 'security',
        'asset', 'catalog' => 'inventory',
        'loan' => 'loan',
        'user' => 'user',
        default => 'system',
    };
};
?>
<div class="page-heading audit-heading">
  <div>
    <span class="eyebrow">TRAZABILIDAD Y CONTROL</span>
    <h1>Logs del sistema</h1>
    <p>Consulta quién realizó cada acción, cuándo ocurrió y qué información fue modificada.</p>
  </div>
  <a class="btn btn-ghost" href="/configuracion">Volver a configuración</a>
</div>

<section class="panel audit-panel">
  <div class="panel-header audit-panel-header">
    <div>
      <span class="panel-kicker">REGISTRO DE ACTIVIDAD</span>
      <h3><?= number_format($total, 0, ',', '.') ?> evento<?= $total === 1 ? '' : 's' ?></h3>
    </div>
    <span class="audit-retention-note">Ordenados del más reciente al más antiguo</span>
  </div>

  <form class="audit-filters" method="get" action="/auditoria">
    <label class="search-box audit-search">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input name="q" value="<?= View::e($filters['q']) ?>" placeholder="Buscar por lo ocurrido o por el nombre de una persona…" aria-label="Buscar en el registro de actividad">
    </label>
    <button class="btn btn-primary audit-search-button" type="submit">Buscar</button>

    <div class="audit-filter-grid">
      <label class="field">
        <span>Tipo de actividad</span>
        <select name="accion">
          <option value="">Cualquier actividad</option>
          <?php foreach ($filterOptions['actions'] as $action): ?>
            <option value="<?= View::e($action) ?>" <?= $filters['accion'] === $action ? 'selected' : '' ?>><?= View::e($actionLabel($action)) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field">
        <span>Sección del sistema</span>
        <select name="entidad_tipo">
          <option value="">Cualquier sección</option>
          <?php foreach ($filterOptions['entities'] as $entity): ?>
            <option value="<?= View::e($entity) ?>" <?= $filters['entidad_tipo'] === $entity ? 'selected' : '' ?>><?= View::e($entityLabel($entity)) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field">
        <span>Realizado por</span>
        <select name="usuario_id">
          <option value="">Cualquier persona</option>
          <option value="system" <?= $filters['usuario_id'] === 'system' ? 'selected' : '' ?>>Proceso automático del sistema</option>
          <?php foreach ($filterOptions['users'] as $user): ?>
            <option value="<?= (int) $user['id'] ?>" <?= $filters['usuario_id'] === (string) $user['id'] ? 'selected' : '' ?>><?= View::e($user['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field"><span>Fecha inicial</span><input type="date" name="desde" value="<?= View::e($filters['desde']) ?>"></label>
      <label class="field"><span>Fecha final</span><input type="date" name="hasta" value="<?= View::e($filters['hasta']) ?>"></label>
      <div class="audit-filter-actions">
        <?php if ($activeFilters > 0 || $filters['q'] !== ''): ?><a class="btn btn-ghost" href="/auditoria">Limpiar</a><?php endif; ?>
        <button class="btn btn-primary" type="submit">Aplicar filtros<?= $activeFilters > 0 ? ' (' . $activeFilters . ')' : '' ?></button>
      </div>
    </div>
  </form>

  <?php if (empty($items)): ?>
    <?php View::component('empty_state', ['title' => 'No hay eventos con esos criterios', 'message' => 'Prueba con otros términos o limpia los filtros aplicados.']); ?>
  <?php else: ?>
  <div class="audit-list">
    <?php foreach ($items as $log):
      $createdAt = new DateTimeImmutable($log['created_at']);
      $kind = $eventClass($log['accion']);
    ?>
    <article class="audit-event audit-event-<?= $kind ?>">
      <div class="audit-event-date">
        <strong><?= $createdAt->format('d') ?></strong>
        <span><?= $createdAt->format('m/Y') ?></span>
        <small><?= $createdAt->format('H:i') ?></small>
      </div>
      <span class="audit-event-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M12 3v9l4 2M5 4h14v16H5z"/></svg>
      </span>
      <div class="audit-event-content">
        <div class="audit-event-title">
          <strong><?= View::e($log['resumen_humano']) ?></strong>
          <span class="audit-event-kind"><?= View::e($actionLabel($log['accion'])) ?></span>
        </div>
        <div class="audit-event-meta">
          <span><b>Realizado por</b> <?= View::e($log['usuario_nombre'] ?? 'Proceso automático') ?></span>
          <span><b>Sección</b> <?= View::e($entityLabel($log['entidad_tipo'])) ?> · registro <?= (int) $log['entidad_id'] ?></span>
          <span><b>Dirección de conexión</b> <?= View::e($log['ip'] ?: 'No registrada') ?></span>
          <span><b>Momento exacto</b> <?= $createdAt->format('d/m/Y H:i:s') ?></span>
        </div>
        <?php if ($log['valores_anteriores_json'] || $log['valores_nuevos_json']): ?>
        <details class="audit-event-details">
          <summary>Ver cambios registrados</summary>
          <div class="audit-change-grid">
            <?php if ($log['valores_anteriores_json']): ?>
              <div><span>Antes</span><pre><?= View::e($formatJson($log['valores_anteriores_json'])) ?></pre></div>
            <?php endif; ?>
            <?php if ($log['valores_nuevos_json']): ?>
              <div><span>Después</span><pre><?= View::e($formatJson($log['valores_nuevos_json'])) ?></pre></div>
            <?php endif; ?>
          </div>
        </details>
        <?php endif; ?>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php View::component('pagination', ['total' => $total, 'page' => $page, 'perPage' => $perPage, 'baseUrl' => '/auditoria']); ?>
</section>
