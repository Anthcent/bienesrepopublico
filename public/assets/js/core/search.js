import { $, $$, api, debounce, escapeHtml } from './dom.js';

const GROUP_LABELS = {
  bienes: 'Bienes', prestamos: 'Préstamos', responsables: 'Responsables',
  ubicaciones: 'Ubicaciones', usuarios: 'Usuarios', documentos: 'Documentos',
  jornadas: 'Jornadas de verificación',
};
const GROUP_LINKS = {
  bienes: (item) => `/inventario/${item.id}`,
  prestamos: (item) => `/prestamos/${item.id}`,
  documentos: (item) => `/documentos/${item.id}/previsualizar`,
  responsables: (item) => `/catalogos/responsibles?q=${encodeURIComponent(item.label)}`,
  ubicaciones: (item) => `/catalogos/locations?q=${encodeURIComponent(item.label)}`,
  usuarios: (item) => `/usuarios?q=${encodeURIComponent(item.label)}`,
  jornadas: (item) => `/verificacion/${item.id}`,
};
const GROUP_ICON_CLASS = {
  bienes: 'blue', prestamos: 'orange', responsables: 'violet',
  ubicaciones: 'blue', usuarios: 'violet', documentos: 'amber',
  jornadas: 'orange',
};
const GROUP_ICON_SYMBOL = {
  bienes: '▦', prestamos: '↗', responsables: '◍',
  ubicaciones: '⌂', usuarios: '◍', documentos: '▤',
  jornadas: '✓',
};

export function initGlobalSearch() {
  const input = $('#globalSearch');
  const popover = $('#searchPopover');
  if (!input || !popover) return;

  const runSearch = debounce(async () => {
    const q = input.value.trim();
    if (q.length < 2) {
      popover.classList.remove('show');
      return;
    }
    try {
      const res = await api('/api/search?q=' + encodeURIComponent(q));
      renderGroups(popover, res.groups || {});
      popover.classList.add('show');
    } catch (e) {
      popover.innerHTML = '<div class="no-results">No fue posible buscar en este momento.</div>';
      popover.classList.add('show');
    }
  }, 320);

  input.addEventListener('input', runSearch);
  input.addEventListener('focus', () => {
    if (input.value.trim().length >= 2) popover.classList.add('show');
  });

  document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
      e.preventDefault();
      input.focus();
    }
    if (e.key === 'Escape') popover.classList.remove('show');
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('.global-search-wrap')) popover.classList.remove('show');
  });
}

function renderGroups(popover, groups) {
  const entries = Object.entries(groups).filter(([, items]) => items.length > 0);
  if (entries.length === 0) {
    popover.innerHTML = '<div class="no-results">Sin resultados para tu búsqueda.</div>';
    return;
  }
  popover.innerHTML = entries.map(([key, items]) => `
    <div class="search-group">
      <span class="search-group-title">${GROUP_LABELS[key] || key}</span>
      ${items.map((item) => {
        const link = GROUP_LINKS[key] ? GROUP_LINKS[key](item) : '#';
        return `<a class="search-result" href="${link}">
          <span class="result-icon ${GROUP_ICON_CLASS[key] || 'blue'}">${GROUP_ICON_SYMBOL[key] || '▦'}</span>
          <span><strong>${escapeHtml(item.label)}</strong><small>${escapeHtml(item.sublabel || '')}</small></span>
        </a>`;
      }).join('')}
    </div>
  `).join('');
}
