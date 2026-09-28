import { $$ } from './dom.js';

const STORAGE_KEY = 'bp_inventory_view';

export function initViewSwitcher() {
  const switcher = document.querySelector('.view-switcher');
  if (!switcher) return;

  const saved = localStorage.getItem(STORAGE_KEY);
  if (saved) applyView(saved);

  $$('button', switcher).forEach((btn) => {
    btn.addEventListener('click', () => {
      $$('button', switcher).forEach((b) => b.classList.toggle('active', b === btn));
      applyView(btn.dataset.view);
      localStorage.setItem(STORAGE_KEY, btn.dataset.view);
    });
  });
}

function applyView(view) {
  document.querySelectorAll('[data-view-panel]').forEach((panel) => {
    panel.classList.toggle('active-view', panel.dataset.viewPanel === view);
  });
  document.querySelectorAll('.view-switcher button').forEach((b) => {
    b.classList.toggle('active', b.dataset.view === view);
  });
}
