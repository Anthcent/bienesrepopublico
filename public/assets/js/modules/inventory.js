import { $, $$, debounce } from '../core/dom.js';
import { openDrawer, registerDrawer, closeDrawer } from '../core/drawer.js';
import { refreshPageContent } from '../core/pageRefresh.js';
import { bindLoanWizardTrigger } from './loanWizard.js';
import { initViewSwitcher } from '../core/viewSwitcher.js';

/**
 * El drawer "Filtros" (`#filterForm`) es un componente global montado una
 * sola vez en el layout (fuera de `.page`), así que se enlaza una única vez
 * desde `app.js` — no desde `initInventoryPanel()`, que se vuelve a llamar
 * cada vez que un wizard global refresca la página en vivo y duplicaría los
 * listeners sobre esos mismos nodos persistentes.
 */
export function initInventoryFilters() {
  const form = $('#filterForm');
  if (!form) return;

  async function applyFilters() {
    if (!location.pathname.startsWith('/inventario')) {
      form.submit();
      return;
    }
    const params = new URLSearchParams(new FormData(form));
    const url = '/inventario' + (params.toString() ? '?' + params.toString() : '');
    closeDrawer('filterDrawer');
    await refreshPageContent(() => { initViewSwitcher(); initInventoryPanel(); bindLoanWizardTrigger(); }, url);
  }

  form.addEventListener('submit', (e) => { e.preventDefault(); applyFilters(); });
  form.addEventListener('reset', () => setTimeout(applyFilters, 0));
  /**
   * Solo Enter o "Aplicar filtros" disparan el filtrado — deliberadamente
   * NO en `change`, para que se puedan elegir varios criterios (estado,
   * disponibilidad, ubicación...) antes de aplicar, en vez de refrescar la
   * lista en cada select tocado.
   */
  $$('select', form).forEach((select) => {
    select.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') { e.preventDefault(); applyFilters(); }
    });
  });
}

/**
 * Búsqueda/filtro instantáneo dentro de la tabla ya renderizada por el
 * servidor (sin lógica de negocio en cliente: solo oculta/muestra filas
 * que el backend ya calificó como ACTIVO/DISPONIBLE/etc.).
 */
export function initInventoryPanel() {
  registerDrawer('filterDrawer', 'filterBackdrop');
  registerDrawer('assetDrawer', 'assetBackdrop');

  $('#filterButton')?.addEventListener('click', () => openDrawer('filterDrawer'));

  const searchInput = $('#inventorySearch');
  const tableBody = $('#assetTableBody');
  if (searchInput && tableBody) {
    searchInput.addEventListener('input', debounce(() => {
      const q = searchInput.value.toLowerCase().trim();
      const rows = $$('tr[data-search]', tableBody);
      let visible = 0;
      rows.forEach((row) => {
        const match = !q || row.dataset.search.includes(q);
        row.classList.toggle('hidden-row', !match);
        if (match) visible++;
      });
      let noResults = $('.no-results', tableBody);
      if (visible === 0) {
        if (!noResults) {
          noResults = document.createElement('tr');
          noResults.className = 'no-results';
          tableBody.appendChild(noResults);
        }
        noResults.innerHTML = `<td colspan="6">No encontramos bienes con esos criterios en esta página.</td>`;
      } else {
        noResults?.remove();
      }
    }, 300));
  }

  $$('.filter-chips .chip').forEach((chip) => chip.addEventListener('click', () => {
    $$('.filter-chips .chip').forEach((c) => c.classList.remove('active'));
    chip.classList.add('active');
    const filter = chip.dataset.filter;
    $$('tr[data-search]', tableBody || document).forEach((row) => {
      row.classList.toggle('hidden-row', filter !== 'all' && !row.dataset.search.includes(filter));
    });
  }));

  $$('.asset-view').forEach((btn) => btn.addEventListener('click', () => openAssetQuickView(btn)));
}

function openAssetQuickView(trigger) {
  const data = JSON.parse(trigger.dataset.asset || '{}');
  window.__currentDrawerAsset = data;

  const drawer = $('#assetDrawer');
  if (!drawer) {
    window.location.href = '/inventario/' + data.id;
    return;
  }

  drawer.querySelector('[data-drawer="numero"]').textContent = data.numero_bien;
  drawer.querySelector('[data-drawer="descripcion"]').textContent = data.descripcion;
  drawer.querySelector('[data-drawer="sub"]').textContent = [data.serial, data.marca_nombre, data.modelo_nombre].filter(Boolean).join(' · ');
  drawer.querySelector('[data-drawer="ubicacion"]').textContent = data.ubicacion_nombre || '—';
  drawer.querySelector('[data-drawer="responsable"]').textContent = data.responsable_nombre || '—';
  drawer.querySelector('[data-drawer="ficha"]').setAttribute('href', '/inventario/' + data.id);

  openDrawer('assetDrawer');
}
