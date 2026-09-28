import { $, $$, api, escapeHtml } from '../core/dom.js';
import { openDrawer, registerDrawer } from '../core/drawer.js';
import { showToast } from '../core/toast.js';

const LEVEL_CLASS = { danger: 'urgent', warning: 'warning', info: 'info-card', success: 'info-card' };
const LOAN_TYPES = ['loan_alert', 'return_damage'];
const TYPE_LABELS = {
  loan_alert: 'Préstamo',
  return_damage: 'Devolución',
  incomplete_info: 'Inventario',
};
const ENTITY_LINKS = {
  loan: (id) => `/prestamos/${id}`,
  asset: (id) => `/inventario/${id}`,
};

function formatDate(value) {
  if (!value) return '';
  const d = new Date(value.replace(' ', 'T'));
  if (Number.isNaN(d.getTime())) return value;
  const diffMin = Math.round((Date.now() - d.getTime()) / 60000);
  if (diffMin < 1) return 'Justo ahora';
  if (diffMin < 60) return `Hace ${diffMin} min`;
  if (diffMin < 1440) return `Hace ${Math.round(diffMin / 60)} h`;
  return d.toLocaleString('es-CO', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
}

export function initNotifications() {
  registerDrawer('notificationDrawer', 'notificationBackdrop');
  const button = $('#notificationButton');
  const countBadges = $$('.notification-count');
  const list = $('#notificationList');
  const tabsBar = $('#notificationTabs');
  let currentTab = 'nuevas';
  let currentItems = [];
  let dataRevision = 0;

  function applyFilter() {
    let filtered = currentItems;
    if (currentTab === 'nuevas') {
      filtered = currentItems.filter((n) => !n.leida);
    } else if (currentTab === 'prestamos') {
      filtered = currentItems.filter((n) => LOAN_TYPES.includes(n.tipo));
    } else if (currentTab === 'sistema') {
      filtered = currentItems.filter((n) => !LOAN_TYPES.includes(n.tipo));
    }
    renderList(list, filtered, handleAction);
  }

  function setTabBadge(elId, items) {
    const badge = $(`#${elId}`);
    if (!badge) return;
    const total = items.length;
    badge.textContent = total;
    badge.style.display = total > 0 ? 'inline-block' : 'none';
    badge.classList.remove('badge-red', 'badge-yellow');
    if (total > 0) badge.classList.add(items.some((n) => !n.leida) ? 'badge-red' : 'badge-yellow');
  }

  function updateCounts() {
    const unread = currentItems.filter((n) => !n.leida);
    setTabBadge('notifTabCountNuevas', unread);
    setTabBadge('notifTabCountPrestamos', currentItems.filter((n) => LOAN_TYPES.includes(n.tipo)));
    setTabBadge('notifTabCountSistema', currentItems.filter((n) => !LOAN_TYPES.includes(n.tipo)));
    countBadges.forEach((badge) => {
      badge.textContent = unread.length;
      badge.style.display = unread.length > 0 ? 'grid' : 'none';
    });
  }

  tabsBar?.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-tab]');
    if (!btn) return;
    currentTab = btn.dataset.tab;
    $$('button[data-tab]', tabsBar).forEach((b) => b.classList.toggle('active', b === btn));
    applyFilter();
  });

  async function refresh() {
    const revision = dataRevision;
    try {
      const res = await api('/api/notifications');
      if (revision !== dataRevision) return;
      currentItems = res.items || [];
      applyFilter();
      updateCounts();
    } catch (e) {
      if (e.status === 401) {
        window.location.href = '/login?next=' + encodeURIComponent(window.location.pathname + window.location.search);
      }
    }
  }

  async function handleAction(id, action) {
    dataRevision++;
    try {
      if (action === 'resolve') {
        await api(`/api/notifications/${id}/resolve`, { method: 'POST' });
        currentItems = currentItems.filter((n) => String(n.id) !== String(id));
        showToast('Notificación resuelta', 'Se quitó del centro de avisos.', 'success');
      } else {
        await api(`/api/notifications/${id}/read`, { method: 'POST' });
        currentItems = currentItems.map((n) => (String(n.id) === String(id) ? { ...n, leida: 1 } : n));
      }
      dataRevision++;
      applyFilter();
      updateCounts();
    } catch (e) {
      dataRevision++;
      showToast('No se pudo actualizar la notificación', e.message, 'error');
    }
  }

  button?.addEventListener('click', () => {
    openDrawer('notificationDrawer');
    refresh();
  });

  $('#markAllReadBtn')?.addEventListener('click', async () => {
    try {
      await api('/api/notifications/read-all', { method: 'POST' });
      currentItems = currentItems.map((n) => ({ ...n, leida: 1 }));
      applyFilter();
      updateCounts();
      showToast('Notificaciones actualizadas', 'Se marcaron todas como leídas.', 'success');
    } catch (e) {
      showToast('No se pudo completar la acción', e.message, 'error');
    }
  });

  refresh();
  setInterval(refresh, 60000);
}

function renderList(list, items, onAction) {
  if (!list) return;
  if (items.length === 0) {
    list.innerHTML = '<div class="empty-notifications">No tienes notificaciones en esta categoría.</div>';
    return;
  }
  list.innerHTML = items.map((n) => {
    const typeLabel = TYPE_LABELS[n.tipo] || 'Sistema';
    const linkBuilder = ENTITY_LINKS[n.entidad_tipo];
    const link = linkBuilder && n.entidad_id ? linkBuilder(n.entidad_id) : null;
    const detailLabel = n.entidad_tipo === 'loan' ? 'Ver préstamo' : 'Ver bien';
    return `
    <article class="notification-card ${LEVEL_CLASS[n.nivel] || 'info-card'}${n.leida ? ' is-read' : ''}" data-id="${n.id}">
      <span class="notification-dot"></span>
      <div class="notification-content">
        <div class="notification-top-row">
          <strong>${escapeHtml(n.titulo)}</strong>
          <span class="notification-type">${escapeHtml(typeLabel)}</span>
        </div>
        <p>${escapeHtml(n.mensaje)}</p>
        <div class="notification-footer-row">
          <small title="${escapeHtml(n.created_at)}">${escapeHtml(formatDate(n.created_at))}</small>
        </div>
        <div class="notification-actions">
          ${link ? `<a class="notification-detail-action" href="${link}">${detailLabel}</a>` : ''}
          ${!n.leida ? `<button type="button" class="notification-mark-read" data-id="${n.id}" data-action="read">Marcar como leída</button>` : ''}
          <button type="button" class="notification-resolve" data-id="${n.id}" data-action="resolve">Quitar aviso</button>
        </div>
      </div>
    </article>
  `;
  }).join('');

  list.querySelectorAll('.notification-actions button').forEach((btn) => {
    btn.addEventListener('click', async () => {
      btn.disabled = true;
      await onAction(btn.dataset.id, btn.dataset.action);
      if (btn.isConnected) btn.disabled = false;
    });
  });
}
