<?php
/** @var array $items */
/** @var int $total */
/** @var array $roles */
use App\Core\View;
?>
<div class="page-heading">
  <div>
    <span class="eyebrow">ADMINISTRACIÓN</span>
    <h1>Usuarios</h1>
    <p>Gestiona cuentas, roles y estado de acceso al sistema.</p>
  </div>
  <div class="page-heading-actions">
    <button class="btn btn-primary" id="openUserCreate">＋ Nuevo usuario</button>
  </div>
</div>

<section class="inventory-panel panel">
  <div class="panel-header">
    <div><span class="panel-kicker">RESULTADOS</span><h3 id="usersResultsCount"><?= number_format($total, 0, ',', '.') ?> usuarios</h3></div>
    <div class="view-switcher" aria-label="Cambiar vista">
      <button class="active" data-view="table" title="Tabla"><svg viewBox="0 0 24 24"><path d="M4 5h16v14H4zM4 10h16M9 5v14"/></svg></button>
      <button data-view="cards" title="Tarjetas"><svg viewBox="0 0 24 24"><path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z"/></svg></button>
    </div>
  </div>

  <div class="smart-search">
    <div class="search-box">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input id="usersSearch" placeholder="Buscar nombre, correo o cargo…">
    </div>
  </div>

  <?php if (empty($items)): ?>
    <?php View::component('empty_state', ['title' => 'Sin usuarios todavía', 'message' => 'Crea el primer usuario para dar acceso al sistema.']); ?>
  <?php else: ?>

  <div class="inventory-table-wrap active-view" data-view-panel="table">
    <table class="smart-table">
      <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Último acceso</th><th>Estado</th><th></th></tr></thead>
      <tbody id="usersTableBody">
        <?php foreach ($items as $user):
          $searchKey = mb_strtolower(implode(' ', [$user['nombre'], $user['email'], $user['cargo'] ?? '', $user['role_nombre']]));
        ?>
        <tr data-id="<?= (int) $user['id'] ?>" data-search="<?= View::e($searchKey) ?>">
          <td><strong><?= View::e($user['nombre']) ?></strong><small><?= View::e($user['cargo'] ?? '') ?></small></td>
          <td><?= View::e($user['email']) ?></td>
          <td><?= View::e($user['role_nombre']) ?></td>
          <td><?= $user['ultimo_acceso'] ? (new DateTimeImmutable($user['ultimo_acceso']))->format('d/m/Y H:i') : 'Nunca' ?></td>
          <td><span class="status <?= $user['activo'] ? 'success' : 'neutral-status' ?>"><?= $user['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
          <td class="row-actions">
            <button class="icon-action user-edit" data-user='<?= View::e(json_encode($user, JSON_UNESCAPED_UNICODE)) ?>'>Editar</button>
            <button class="icon-action user-toggle" data-id="<?= (int) $user['id'] ?>" data-active="<?= (int) $user['activo'] ?>"><?= $user['activo'] ? 'Desactivar' : 'Activar' ?></button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="catalog-grid" data-view-panel="cards" style="padding:0 18px 18px">
    <?php foreach ($items as $user):
      $userInitials = mb_strtoupper(mb_substr($user['nombre'], 0, 1) . mb_substr(strrchr($user['nombre'], ' ') ?: '', 1, 1));
    ?>
    <article class="catalog-card" data-id="<?= (int) $user['id'] ?>">
      <div style="display:flex;align-items:center;gap:10px">
        <span class="avatar" style="background:#2457a6"><?= View::e($userInitials) ?></span>
        <div>
          <strong><?= View::e($user['nombre']) ?></strong>
          <div class="catalog-meta"><span><?= View::e($user['cargo'] ?: $user['role_nombre']) ?></span></div>
        </div>
      </div>
      <div class="catalog-meta">
        <span><?= View::e($user['email']) ?></span>
        <span>Último acceso: <?= $user['ultimo_acceso'] ? (new DateTimeImmutable($user['ultimo_acceso']))->format('d/m/Y H:i') : 'Nunca' ?></span>
      </div>
      <div class="catalog-stats">
        <span class="status <?= $user['activo'] ? 'success' : 'neutral-status' ?>" style="background:none;padding:0"><?= $user['activo'] ? '● Activo' : '● Inactivo' ?></span>
      </div>
      <div class="catalog-actions">
        <button class="btn btn-ghost btn-sm user-edit" data-user='<?= View::e(json_encode($user, JSON_UNESCAPED_UNICODE)) ?>'>Editar</button>
        <button class="btn btn-ghost btn-sm user-toggle" data-id="<?= (int) $user['id'] ?>" data-active="<?= (int) $user['activo'] ?>"><?= $user['activo'] ? 'Desactivar' : 'Activar' ?></button>
      </div>
    </article>
    <?php endforeach; ?>
  </div>

  <?php View::component('pagination', ['total' => $total, 'page' => $page, 'perPage' => $perPage, 'baseUrl' => '/usuarios']); ?>
  <?php endif; ?>
</section>

<div class="modal-backdrop" id="userModalBackdrop"></div>
<section class="modal" id="userModal" role="dialog" aria-modal="true" style="width:min(460px,calc(100% - 30px))">
  <div class="modal-header"><div><span class="panel-kicker">USUARIOS</span><h3 id="userModalTitle">Nuevo usuario</h3></div><button class="icon-btn modal-close" data-modal-close="userModal">×</button></div>
  <div class="modal-body">
    <form id="userForm">
      <label class="field" style="margin-bottom:12px"><span>Nombre completo</span><input name="nombre" required></label>
      <label class="field" style="margin-bottom:12px"><span>Cargo</span><input name="cargo"></label>
      <label class="field" style="margin-bottom:12px"><span>Correo electrónico</span><input name="email" type="email" required></label>
      <label class="field" style="margin-bottom:12px" id="passwordField"><span>Contraseña</span><input name="password" type="password" required minlength="<?= (int) $passwordMinLength ?>"><small>Mínimo <?= (int) $passwordMinLength ?> caracteres según la política vigente.</small></label>
      <label class="field"><span>Rol</span>
        <select name="role_id">
          <?php foreach ($roles as $role): ?><option value="<?= (int) $role['id'] ?>"><?= View::e($role['nombre']) ?></option><?php endforeach; ?>
        </select>
      </label>
    </form>
  </div>
  <div class="modal-footer"><button class="btn btn-ghost" data-modal-close="userModal">Cancelar</button><button class="btn btn-primary" id="userSave">Guardar</button></div>
</section>

<script type="module">
  import { $, $$, api, debounce, escapeHtml } from '/assets/js/core/dom.js';
  import { showToast } from '/assets/js/core/toast.js';
  import { openModal, closeModal, registerModal } from '/assets/js/core/modal.js';

  registerModal('userModal', 'userModalBackdrop');
  let editingId = null;

  const tbody = $('#usersTableBody');
  const cardGrid = document.querySelector('.catalog-grid[data-view-panel="cards"]');

  function formatLastAccess(iso) {
    if (!iso) return 'Nunca';
    const d = new Date(iso.replace(' ', 'T'));
    if (Number.isNaN(d.getTime())) return 'Nunca';
    const pad = (n) => String(n).padStart(2, '0');
    return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
  }

  function initialsOf(name) {
    const parts = (name || '').trim().split(' ');
    return ((parts[0]?.[0] || '') + (parts[parts.length - 1]?.[0] || '')).toUpperCase();
  }

  function rowHtml(u) {
    const active = !!Number(u.activo);
    const searchKey = [u.nombre, u.email, u.cargo || '', u.role_nombre].join(' ').toLowerCase();
    return `
    <tr data-id="${u.id}" data-search="${escapeHtml(searchKey)}">
      <td><strong>${escapeHtml(u.nombre)}</strong><small>${escapeHtml(u.cargo || '')}</small></td>
      <td>${escapeHtml(u.email)}</td>
      <td>${escapeHtml(u.role_nombre)}</td>
      <td>${formatLastAccess(u.ultimo_acceso)}</td>
      <td><span class="status ${active ? 'success' : 'neutral-status'}">${active ? 'Activo' : 'Inactivo'}</span></td>
      <td class="row-actions">
        <button class="icon-action user-edit" data-user='${JSON.stringify(u).replace(/'/g, '&#39;')}'>Editar</button>
        <button class="icon-action user-toggle" data-id="${u.id}" data-active="${active ? 1 : 0}">${active ? 'Desactivar' : 'Activar'}</button>
      </td>
    </tr>`;
  }

  function cardHtml(u) {
    const active = !!Number(u.activo);
    return `
    <article class="catalog-card" data-id="${u.id}">
      <div style="display:flex;align-items:center;gap:10px">
        <span class="avatar" style="background:#2457a6">${escapeHtml(initialsOf(u.nombre))}</span>
        <div><strong>${escapeHtml(u.nombre)}</strong><div class="catalog-meta"><span>${escapeHtml(u.cargo || u.role_nombre)}</span></div></div>
      </div>
      <div class="catalog-meta">
        <span>${escapeHtml(u.email)}</span>
        <span>Último acceso: ${formatLastAccess(u.ultimo_acceso)}</span>
      </div>
      <div class="catalog-stats"><span class="status ${active ? 'success' : 'neutral-status'}" style="background:none;padding:0">${active ? '● Activo' : '● Inactivo'}</span></div>
      <div class="catalog-actions">
        <button class="btn btn-ghost btn-sm user-edit" data-user='${JSON.stringify(u).replace(/'/g, '&#39;')}'>Editar</button>
        <button class="btn btn-ghost btn-sm user-toggle" data-id="${u.id}" data-active="${active ? 1 : 0}">${active ? 'Desactivar' : 'Activar'}</button>
      </div>
    </article>`;
  }

  function bindRow(el) {
    el.querySelector('.user-edit')?.addEventListener('click', () => openEdit(el.querySelector('.user-edit').dataset.user));
    el.querySelector('.user-toggle')?.addEventListener('click', () => toggleUser(el.querySelector('.user-toggle')));
  }

  function updateResultsCount() {
    const el = $('#usersResultsCount');
    if (!el || !tbody) return;
    const n = $$('tr[data-id]', tbody).length;
    el.textContent = `${n} usuario${n === 1 ? '' : 's'}`;
  }

  function upsertUser(u) {
    const existingRow = tbody?.querySelector(`tr[data-id="${u.id}"]`);
    const existingCard = cardGrid?.querySelector(`.catalog-card[data-id="${u.id}"]`);
    if (existingRow) existingRow.outerHTML = rowHtml(u); else tbody?.insertAdjacentHTML('beforeend', rowHtml(u));
    if (existingCard) existingCard.outerHTML = cardHtml(u); else cardGrid?.insertAdjacentHTML('beforeend', cardHtml(u));
    const newRow = tbody?.querySelector(`tr[data-id="${u.id}"]`);
    const newCard = cardGrid?.querySelector(`.catalog-card[data-id="${u.id}"]`);
    if (newRow) bindRow(newRow);
    if (newCard) bindRow(newCard);
    updateResultsCount();
  }

  function openEdit(userJson) {
    const data = JSON.parse(userJson);
    editingId = data.id;
    const form = document.getElementById('userForm');
    form.reset();
    ['nombre', 'cargo', 'email', 'role_id'].forEach((key) => { if (form[key]) form[key].value = data[key] ?? ''; });
    document.getElementById('userModalTitle').textContent = 'Editar usuario';
    document.getElementById('passwordField').style.display = 'none';
    openModal('userModal');
  }

  async function toggleUser(btn) {
    const activo = btn.dataset.active === '1' ? 0 : 1;
    try {
      const res = await api(`/api/users/${btn.dataset.id}/toggle`, { method: 'POST', body: JSON.stringify({ activo: !!activo }) });
      upsertUser(res.user);
      showToast(activo ? 'Usuario activado' : 'Usuario desactivado', 'El cambio ya está reflejado en la lista.', 'success');
    } catch (e) {
      showToast('No se pudo cambiar el estado', e.message, 'error');
    }
  }

  document.getElementById('usersSearch')?.addEventListener('input', debounce((e) => {
    const q = e.target.value.toLowerCase().trim();
    document.querySelectorAll('#usersTableBody tr[data-search]').forEach((row) => {
      row.classList.toggle('hidden-row', !!q && !row.dataset.search.includes(q));
    });
  }, 300));

  document.getElementById('openUserCreate')?.addEventListener('click', () => {
    editingId = null;
    document.getElementById('userForm').reset();
    document.getElementById('userModalTitle').textContent = 'Nuevo usuario';
    document.getElementById('passwordField').style.display = '';
    openModal('userModal');
  });

  $$('.user-edit').forEach((btn) => btn.addEventListener('click', () => openEdit(btn.dataset.user)));
  $$('.user-toggle').forEach((btn) => btn.addEventListener('click', () => toggleUser(btn)));

  document.getElementById('userSave')?.addEventListener('click', async () => {
    const form = document.getElementById('userForm');
    const payload = Object.fromEntries(new FormData(form).entries());
    try {
      let res;
      if (editingId) {
        res = await api(`/api/users/${editingId}`, { method: 'PUT', body: JSON.stringify(payload) });
      } else {
        res = await api('/api/users', { method: 'POST', body: JSON.stringify(payload) });
      }
      closeModal('userModal');
      upsertUser(res.user);
      showToast('Usuario guardado', 'Los cambios se aplicaron correctamente.', 'success');
    } catch (e) {
      showToast('No se pudo guardar', e.message, 'error');
    }
  });
</script>
