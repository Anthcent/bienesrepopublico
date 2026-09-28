<?php
/** @var array $items */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var array $filters */
/** @var array $counters */
use App\Core\View;

$badgeMap = ['ACTIVO' => 'info', 'PARCIALMENTE_DEVUELTO' => 'warning', 'DEVUELTO' => 'success', 'VENCIDO' => 'danger', 'ANULADO' => 'neutral-status'];
$qs = fn (array $extra = []) => http_build_query(array_filter(array_merge([
    'q' => $filters['q'] ?? '',
    'estado' => $filters['estado'] ?? '',
    'desde' => $filters['desde'] ?? '',
    'hasta' => $filters['hasta'] ?? '',
], $extra)));
?>
<div class="page-heading">
  <div>
    <span class="eyebrow">PRÉSTAMOS</span>
    <h1>Préstamos y devoluciones</h1>
    <p>Gestiona salidas temporales de bienes, devoluciones y vencimientos.</p>
  </div>
  <div class="page-heading-actions">
    <button class="btn btn-primary" id="quickLoan"><span class="btn-icon">↗</span> Registrar préstamo</button>
  </div>
</div>

<section class="stat-tile-grid">
  <article class="stat-tile mint">
    <div class="stat-tile-info">
      <div class="stat-tile-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 7h8m-8 5h8m-8 5h5M5 3h14v18H5z"/></svg></div>
      <div class="stat-tile-text"><strong>Activos</strong><small>Préstamos actualmente abiertos</small></div>
    </div>
    <span class="stat-tile-value" id="statActivos"><?= (int) ($counters['activos'] ?? 0) ?></span>
  </article>
  <article class="stat-tile amber">
    <div class="stat-tile-info">
      <div class="stat-tile-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></svg></div>
      <div class="stat-tile-text"><strong>Próximos a vencer</strong><small>Dentro del periodo de alerta</small></div>
    </div>
    <span class="stat-tile-value"><?= (int) ($counters['proximos'] ?? 0) ?></span>
  </article>
  <article class="stat-tile violet">
    <div class="stat-tile-info">
      <div class="stat-tile-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg></div>
      <div class="stat-tile-text"><strong>Vencidos</strong><small>Requieren seguimiento</small></div>
    </div>
    <span class="stat-tile-value"><?= (int) ($counters['vencidos'] ?? 0) ?></span>
  </article>
</section>

<section class="inventory-panel panel">
  <div class="panel-header">
    <div><span class="panel-kicker">RESULTADOS</span><h3><?= number_format($total, 0, ',', '.') ?> préstamo<?= $total === 1 ? '' : 's' ?></h3></div>
    <div class="view-switcher-wrap">
      <span class="view-switcher-label">Vista del módulo</span>
      <div class="view-switcher" aria-label="Cambiar vista">
        <button class="active" data-view="table" title="Tabla"><svg viewBox="0 0 24 24"><path d="M4 5h16v14H4zM4 10h16M9 5v14"/></svg></button>
        <button data-view="cards" title="Tarjetas"><svg viewBox="0 0 24 24"><path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z"/></svg></button>
        <button data-view="compact" title="Compacta"><svg viewBox="0 0 24 24"><path d="M5 6h14M5 12h14M5 18h14"/></svg></button>
      </div>
    </div>
  </div>

  <div class="smart-search">
    <div class="search-box">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="text" id="loanSearch" placeholder="Buscar código, prestatario, área o estado…">
    </div>
    <button type="button" class="filter-btn" id="loanFilterButton">
      <svg viewBox="0 0 24 24"><path d="M4 5h16M7 12h10M10 19h4"/></svg>
      Filtros
      <?php $activeAdvanced = count(array_filter(['desde' => $filters['desde'] ?? '', 'hasta' => $filters['hasta'] ?? ''])); ?>
      <?php if ($activeAdvanced > 0): ?><span class="filter-count"><?= $activeAdvanced ?></span><?php endif; ?>
    </button>
  </div>

  <div class="filter-chips">
    <a class="chip <?= empty($filters['estado']) ? 'active' : '' ?>" href="/prestamos?<?= $qs(['estado' => '']) ?>">Todos</a>
    <a class="chip <?= $filters['estado'] === 'ACTIVO' ? 'active' : '' ?>" href="/prestamos?<?= $qs(['estado' => 'ACTIVO']) ?>">Activos</a>
    <a class="chip <?= $filters['estado'] === 'VENCIDO' ? 'active' : '' ?>" href="/prestamos?<?= $qs(['estado' => 'VENCIDO']) ?>">Vencidos</a>
    <a class="chip <?= $filters['estado'] === 'DEVUELTO' ? 'active' : '' ?>" href="/prestamos?<?= $qs(['estado' => 'DEVUELTO']) ?>">Devueltos</a>
  </div>

  <?php if (empty($items)): ?>
    <?php View::component('empty_state', ['title' => 'No hay préstamos con esos criterios', 'message' => 'Registra un nuevo préstamo para comenzar.']); ?>
  <?php else: ?>

  <div class="inventory-table-wrap active-view" data-view-panel="table">
    <table class="smart-table">
      <thead><tr><th>Código</th><th>Prestatario</th><th>Fecha entrega</th><th>Vence</th><th>Estado</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($items as $loan):
          $canOperate = in_array($loan['estado'], ['ACTIVO', 'PARCIALMENTE_DEVUELTO', 'VENCIDO'], true);
          $searchKey = mb_strtolower(implode(' ', [$loan['codigo'], $loan['prestatario_nombre_snapshot'], $loan['prestatario_dependencia_snapshot'] ?? '', $loan['estado']]));
        ?>
        <tr data-id="<?= (int) $loan['id'] ?>" data-search="<?= View::e($searchKey) ?>">
          <td><strong><?= View::e($loan['codigo']) ?></strong></td>
          <td><?= View::e($loan['prestatario_nombre_snapshot']) ?><?php if ($loan['prestatario_dependencia_snapshot']): ?><small><?= View::e($loan['prestatario_dependencia_snapshot']) ?></small><?php endif; ?></td>
          <td><?= (new DateTimeImmutable($loan['fecha_prestamo']))->format('d/m/Y') ?></td>
          <td>
            <strong><?= (new DateTimeImmutable($loan['fecha_vencimiento']))->format('d/m/Y') ?></strong>
            <?php if ($canOperate):
              $diff = (int) (new DateTimeImmutable('today'))->diff(new DateTimeImmutable($loan['fecha_vencimiento']))->format('%r%a');
            ?>
              <small style="display:block"><?= $diff < 0 ? 'Vencido hace ' . abs($diff) . ' día(s)' : ($diff === 0 ? 'Vence hoy' : 'Vence en ' . $diff . ' día(s)') ?></small>
            <?php endif; ?>
          </td>
          <td><span class="status <?= $badgeMap[$loan['estado']] ?? 'neutral-status' ?>"><?= View::e($loan['estado']) ?></span></td>
          <td class="row-actions">
            <a class="icon-action" href="/prestamos/<?= (int) $loan['id'] ?>" title="Ver detalle" aria-label="Ver detalle"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg></a>
            <div class="row-action-menu">
              <button class="icon-action loan-row-menu-btn" title="Más acciones" aria-label="Más acciones" aria-haspopup="menu" aria-expanded="false" aria-controls="loan-actions-<?= (int) $loan['id'] ?>">⋮</button>
              <div class="action-popover" id="loan-actions-<?= (int) $loan['id'] ?>" role="menu">
                <a href="/prestamos/<?= (int) $loan['id'] ?>" role="menuitem">Ver detalle</a>
                <?php if (in_array($loan['estado'], ['ACTIVO', 'PARCIALMENTE_DEVUELTO'], true)): ?><button type="button" class="loan-cancel" data-id="<?= (int) $loan['id'] ?>" role="menuitem">Anular préstamo</button><?php endif; ?>
              </div>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="loan-card-grid" data-view-panel="cards">
    <?php foreach ($items as $loan):
      $canOperate = in_array($loan['estado'], ['ACTIVO', 'PARCIALMENTE_DEVUELTO', 'VENCIDO'], true);
    ?>
    <article class="loan-card loan-card-<?= $badgeMap[$loan['estado']] ?? 'neutral-status' ?>" data-id="<?= (int) $loan['id'] ?>">
      <div class="loan-card-top">
        <strong><?= View::e($loan['codigo']) ?></strong>
        <span class="status <?= $badgeMap[$loan['estado']] ?? 'neutral-status' ?>"><?= View::e($loan['estado']) ?></span>
      </div>
      <div class="loan-card-person">
        <strong><?= View::e($loan['prestatario_nombre_snapshot']) ?></strong>
        <?php if ($loan['prestatario_dependencia_snapshot']): ?><small><?= View::e($loan['prestatario_dependencia_snapshot']) ?></small><?php endif; ?>
      </div>
      <div class="loan-card-dates">
        <div class="loan-date-entrega"><span>Entrega</span><strong><?= (new DateTimeImmutable($loan['fecha_prestamo']))->format('d/m/Y') ?></strong></div>
        <div class="loan-date-vence">
          <span>Vence</span><strong><?= (new DateTimeImmutable($loan['fecha_vencimiento']))->format('d/m/Y') ?></strong>
          <?php if ($canOperate):
            $diff = (int) (new DateTimeImmutable('today'))->diff(new DateTimeImmutable($loan['fecha_vencimiento']))->format('%r%a');
          ?>
            <small><?= $diff < 0 ? 'Vencido hace ' . abs($diff) . ' día(s)' : ($diff === 0 ? 'Vence hoy' : 'Vence en ' . $diff . ' día(s)') ?></small>
          <?php endif; ?>
        </div>
      </div>
      <div class="loan-card-actions">
        <a class="btn btn-ghost btn-sm" href="/prestamos/<?= (int) $loan['id'] ?>">Ver detalle</a>
      </div>
    </article>
    <?php endforeach; ?>
  </div>

  <div class="compact-list" data-view-panel="compact">
    <?php foreach ($items as $loan): ?>
    <a class="compact-row" data-id="<?= (int) $loan['id'] ?>" href="/prestamos/<?= (int) $loan['id'] ?>">
      <span class="compact-main">
        <strong><?= View::e($loan['codigo']) ?> · <?= View::e($loan['prestatario_nombre_snapshot']) ?></strong>
        <small>Entrega <?= (new DateTimeImmutable($loan['fecha_prestamo']))->format('d/m/Y') ?> · Vence <?= (new DateTimeImmutable($loan['fecha_vencimiento']))->format('d/m/Y') ?></small>
      </span>
      <span class="status <?= $badgeMap[$loan['estado']] ?? 'neutral-status' ?>"><?= View::e($loan['estado']) ?></span>
    </a>
    <?php endforeach; ?>
  </div>

  <p class="no-results hidden-row" id="loanNoResults">No encontramos préstamos con esos criterios en esta página.</p>
  <?php endif; ?>

  <?php View::component('pagination', ['total' => $total, 'page' => $page, 'perPage' => $perPage, 'baseUrl' => '/prestamos']); ?>
</section>

<div class="drawer-backdrop" id="loanFilterBackdrop"></div>
<aside class="drawer filter-drawer" id="loanFilterDrawer">
  <div class="drawer-header">
    <div><span class="panel-kicker">FILTROS</span><h3>Refinar préstamos</h3></div>
    <button class="icon-btn close-drawer" data-close="loanFilterDrawer">×</button>
  </div>
  <form class="drawer-body" method="get" action="/prestamos" id="loanFilterForm">
    <?php if (!empty($filters['q'])): ?><input type="hidden" name="q" value="<?= View::e($filters['q']) ?>"><?php endif; ?>
    <label class="field">
      <span>Estado</span>
      <select name="estado">
        <option value="">Todos</option>
        <option value="ACTIVO" <?= $filters['estado'] === 'ACTIVO' ? 'selected' : '' ?>>Activo</option>
        <option value="PARCIALMENTE_DEVUELTO" <?= $filters['estado'] === 'PARCIALMENTE_DEVUELTO' ? 'selected' : '' ?>>Parcialmente devuelto</option>
        <option value="VENCIDO" <?= $filters['estado'] === 'VENCIDO' ? 'selected' : '' ?>>Vencido</option>
        <option value="DEVUELTO" <?= $filters['estado'] === 'DEVUELTO' ? 'selected' : '' ?>>Devuelto</option>
        <option value="ANULADO" <?= $filters['estado'] === 'ANULADO' ? 'selected' : '' ?>>Anulado</option>
      </select>
    </label>
    <label class="field">
      <span>Vence desde</span>
      <input type="date" name="desde" value="<?= View::e($filters['desde'] ?? '') ?>">
    </label>
    <label class="field">
      <span>Vence hasta</span>
      <input type="date" name="hasta" value="<?= View::e($filters['hasta'] ?? '') ?>">
    </label>
  </form>
  <div class="drawer-footer">
    <a class="btn btn-ghost" href="/prestamos">Limpiar</a>
    <button type="submit" form="loanFilterForm" class="btn btn-primary">Aplicar filtros</button>
  </div>
</aside>

<script type="module">
  import { $, $$, api, escapeHtml } from '/assets/js/core/dom.js';
  import { showToast } from '/assets/js/core/toast.js';
  import { confirmAction } from '/assets/js/core/modal.js';
  import { registerDrawer, openDrawer } from '/assets/js/core/drawer.js';

  const badgeMap = { ACTIVO: 'info', PARCIALMENTE_DEVUELTO: 'warning', DEVUELTO: 'success', VENCIDO: 'danger', ANULADO: 'neutral-status' };

  function applyCanceledState(loan) {
    const badge = badgeMap[loan.estado] || 'neutral-status';
    const row = document.querySelector(`tr[data-id="${loan.id}"]`);
    if (row) {
      row.querySelector('td:nth-child(4) small')?.remove();
      const statusCell = row.children[4];
      statusCell.innerHTML = `<span class="status ${badge}">${escapeHtml(loan.estado)}</span>`;
    }
    const card = document.querySelector(`.loan-card[data-id="${loan.id}"]`);
    if (card) {
      card.className = `loan-card loan-card-${badge}`;
      card.querySelector('.loan-card-top .status').outerHTML = `<span class="status ${badge}">${escapeHtml(loan.estado)}</span>`;
      card.querySelector('.loan-date-vence small')?.remove();
    }
    const compact = document.querySelector(`.compact-row[data-id="${loan.id}"]`);
    if (compact) {
      compact.querySelector('.status').outerHTML = `<span class="status ${badge}">${escapeHtml(loan.estado)}</span>`;
    }
    const menuCancelBtn = document.querySelector(`.loan-cancel[data-id="${loan.id}"]`);
    menuCancelBtn?.remove();
    const activosEl = $('#statActivos');
    if (activosEl) activosEl.textContent = Math.max(0, parseInt(activosEl.textContent, 10) - 1);
  }

  /**
   * Se reata tras cada refresco en vivo, incluyendo los que dispara el
   * wizard global de "Registrar préstamo" desde esta misma página — ver
   * `window.__pageBindPage` en core/pageRefresh.js.
   */
  let floatingRowMenu = null;
  let floatingRowMenuOwner = null;
  let floatingRowMenuButton = null;

  function closeRowMenu(restoreFocus = false) {
    if (!floatingRowMenu) return;
    const button = floatingRowMenuButton;
    floatingRowMenu.classList.remove('show', 'floating-action-popover');
    floatingRowMenu.removeAttribute('style');
    if (floatingRowMenuOwner?.isConnected) {
      floatingRowMenuOwner.appendChild(floatingRowMenu);
    } else {
      floatingRowMenu.remove();
    }
    floatingRowMenu = null;
    floatingRowMenuOwner = null;
    floatingRowMenuButton = null;
    button?.setAttribute('aria-expanded', 'false');
    if (restoreFocus && button?.isConnected) button.focus();
  }

  function openRowMenu(button, menu) {
    closeRowMenu();
    floatingRowMenu = menu;
    floatingRowMenuOwner = button.closest('.row-action-menu');
    floatingRowMenuButton = button;
    button.setAttribute('aria-expanded', 'true');
    document.body.appendChild(menu);
    menu.classList.add('show', 'floating-action-popover');
    menu.style.visibility = 'hidden';

    const trigger = button.getBoundingClientRect();
    const bounds = menu.getBoundingClientRect();
    const margin = 8;
    const left = Math.min(window.innerWidth - bounds.width - margin, Math.max(margin, trigger.right - bounds.width));
    let top = trigger.bottom + 6;
    if (top + bounds.height > window.innerHeight - margin) {
      top = trigger.top - bounds.height - 6;
    }
    top = Math.max(margin, Math.min(top, window.innerHeight - bounds.height - margin));

    menu.style.left = `${left}px`;
    menu.style.top = `${top}px`;
    menu.style.visibility = 'visible';
    menu.querySelector('[role="menuitem"]')?.focus({ preventScroll: true });
  }

  function bindPage() {
    closeRowMenu();
    registerDrawer('loanFilterDrawer', 'loanFilterBackdrop');
    $('#loanFilterButton')?.addEventListener('click', () => openDrawer('loanFilterDrawer'));

    const searchInput = $('#loanSearch');
    const noResults = $('#loanNoResults');
    searchInput?.addEventListener('input', () => {
      const q = searchInput.value.toLowerCase().trim();
      let visible = 0;
      $$('tr[data-search]').forEach((row) => {
        const match = !q || row.dataset.search.includes(q);
        row.classList.toggle('hidden-row', !match);
        if (match) visible++;
      });
      noResults?.classList.toggle('hidden-row', visible !== 0);
    });

    $$('.loan-row-menu-btn').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const owner = btn.closest('.row-action-menu');
        if (floatingRowMenuOwner === owner) {
          closeRowMenu();
          return;
        }
        const popover = btn.nextElementSibling;
        openRowMenu(btn, popover);
      });
    });

    $$('.loan-cancel').forEach((btn) => {
      btn.addEventListener('click', () => {
        const loanId = btn.dataset.id;
        confirmAction({
          title: 'Anular préstamo',
          message: 'Los bienes pendientes de devolución volverán a estar disponibles. Esta acción queda registrada en auditoría.',
          confirmLabel: 'Anular préstamo',
          danger: true,
          onConfirm: async () => {
            try {
              const res = await api(`/api/loans/${loanId}/cancel`, { method: 'POST' });
              applyCanceledState(res.data.loan);
              showToast('Préstamo anulado', 'El préstamo fue anulado correctamente.', 'success');
            } catch (e) {
              showToast('No se pudo anular', e.message, 'error');
            }
          },
        });
      });
    });
  }
  document.addEventListener('click', (event) => {
    if (event.target.closest('.floating-action-popover a, .floating-action-popover button')) {
      closeRowMenu();
      return;
    }
    if (!event.target.closest('.floating-action-popover')) closeRowMenu();
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeRowMenu(true);
  });
  window.addEventListener('resize', closeRowMenu);
  window.addEventListener('scroll', closeRowMenu, true);

  window.__pageBindPage = bindPage;
  bindPage();
</script>
