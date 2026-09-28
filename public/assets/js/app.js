import { $, $$ } from './core/dom.js';
import { initSidebar } from './core/sidebar.js';
import { initGlobalSearch } from './core/search.js';
import { initDrawers, closeAllDrawers } from './core/drawer.js';
import { initModals, closeAllModals, registerModal } from './core/modal.js';
import { initViewSwitcher } from './core/viewSwitcher.js';
import { initOperationDock } from './core/dock.js';
import './core/toast.js';
import { initNotifications } from './modules/notifications.js';
import { initLoanModal } from './modules/loanWizard.js';
import { initVerificationWizard } from './modules/verificationWizard.js';
import { initInventoryPanel, initInventoryFilters } from './modules/inventory.js';
import { initDocumentPreview } from './modules/documentPreview.js';

document.addEventListener('DOMContentLoaded', () => {
  initSidebar();
  initGlobalSearch();
  initDrawers();
  initModals();
  registerModal('confirmModal', 'confirmModalBackdrop');
  initViewSwitcher();
  initOperationDock();
  initNotifications();
  initLoanModal();
  initVerificationWizard();
  initInventoryPanel();
  initInventoryFilters();
  initDocumentPreview();

  // Quick action popover (topbar)
  const quickActionButton = $('#quickActionButton');
  const actionPopover = $('#actionPopover');
  quickActionButton?.addEventListener('click', (e) => {
    e.stopPropagation();
    actionPopover?.classList.toggle('show');
  });

  $$('[data-action]', actionPopover || document).forEach((btn) => {
    btn.addEventListener('click', () => {
      actionPopover?.classList.remove('show');
      if (btn.dataset.action === 'loan') window.__openLoanModal?.(null);
    });
  });

  // Menú de usuario (topbar)
  const topUserButton = $('#topUserButton');
  const topUserPopover = $('#topUserPopover');
  topUserButton?.addEventListener('click', (e) => {
    e.stopPropagation();
    topUserPopover?.classList.toggle('show');
  });

  // Menú del pie del sidebar (siempre accesible, incluso en móvil)
  const miniUserButton = $('#miniUserButton');
  const miniUserPopover = $('#miniUserPopover');
  miniUserButton?.addEventListener('click', (e) => {
    e.stopPropagation();
    miniUserPopover?.classList.toggle('show');
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('.quick-action-wrap')) actionPopover?.classList.remove('show');
    if (!e.target.closest('.top-user-wrap')) topUserPopover?.classList.remove('show');
    if (!e.target.closest('.mini-user-wrap')) miniUserPopover?.classList.remove('show');
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeAllDrawers();
      closeAllModals();
    }
  });
});
