import { $, $$ } from './dom.js';

const STORAGE_KEY = 'bp_sidebar_collapsed';

export function initSidebar() {
  const sidebar = $('#sidebar');
  if (!sidebar) return;

  if (localStorage.getItem(STORAGE_KEY) === '1') {
    sidebar.classList.add('collapsed');
  }

  $('#sidebarToggle')?.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    localStorage.setItem(STORAGE_KEY, sidebar.classList.contains('collapsed') ? '1' : '0');
  });

  $('#mobileMenu')?.addEventListener('click', () => sidebar.classList.toggle('mobile-open'));

  $$('.nav-parent', sidebar).forEach((btn) => {
    btn.addEventListener('click', () => btn.closest('.nav-group').classList.toggle('open'));
  });
}
