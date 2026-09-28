import { $, $$ } from './dom.js';

const HIDDEN_KEY = 'bp_dock_hidden';

/**
 * Dock de operaciones (Plan V5 §5): componente fijo inferior-central siempre
 * visible. Reutiliza las acciones ya existentes (wizard de incorporación,
 * modal de préstamo, drawer de notificaciones) en vez de duplicar lógica —
 * el dock es una segunda entrada a lo que ya existe. La búsqueda global vive
 * solo en el navbar superior (antes se duplicaba aquí como atajo redundante).
 */
export function initOperationDock() {
  const dock = $('#operationDock');
  if (!dock) return;

  const mainBtn = $('#dockMain', dock);
  const menu = $('#operationMenu', dock);
  const bellBtn = $('#dockBell', dock);

  mainBtn?.addEventListener('click', (e) => {
    e.stopPropagation();
    const show = !menu.classList.contains('show');
    menu.classList.toggle('show', show);
    mainBtn.setAttribute('aria-expanded', String(show));
  });

  $$('[data-dock-action]', dock).forEach((btn) => {
    btn.addEventListener('click', () => {
      menu.classList.remove('show');
      mainBtn.setAttribute('aria-expanded', 'false');
      const action = btn.dataset.dockAction;
      if (action === 'asset') window.location.href = '/inventario/nuevo';
      else if (action === 'loan') window.__openLoanModal?.(null);
      else if (action === 'verification') window.__openVerificationWizard?.();
    });
  });

  bellBtn?.addEventListener('click', () => {
    $('#notificationButton')?.click();
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('#operationMenu') && !e.target.closest('#dockMain')) {
      menu?.classList.remove('show');
      mainBtn?.setAttribute('aria-expanded', 'false');
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      menu?.classList.remove('show');
      mainBtn?.setAttribute('aria-expanded', 'false');
    }
  });

  const toggleBtn = $('#dockToggleButton');
  const applyHidden = (hidden) => {
    dock.classList.toggle('dock-hidden', hidden);
    toggleBtn?.classList.toggle('is-off', hidden);
    toggleBtn?.setAttribute('aria-pressed', String(!hidden));
  };
  applyHidden(localStorage.getItem(HIDDEN_KEY) === '1');

  toggleBtn?.addEventListener('click', () => {
    const hidden = !dock.classList.contains('dock-hidden');
    applyHidden(hidden);
    localStorage.setItem(HIDDEN_KEY, hidden ? '1' : '0');
  });
}
