import { $, escapeHtml } from './dom.js';

/**
 * Toasts en la esquina inferior derecha. Nunca usar alert()/confirm()
 * (prohibido explícitamente por 00_LEER_PRIMERO/README_PRIMERO.md §6).
 * Máximo 3 visibles; éxito/info se autocierran, warning/error persisten.
 */
export function showToast(title, message, type = 'info', action = null) {
  const stack = $('#toastStack');
  if (!stack) return;

  const toast = document.createElement('article');
  toast.className = `toast ${type}`;
  const icon = { success: '✓', warning: '!', error: '×' }[type] || 'i';
  toast.innerHTML = `
    <span class="toast-icon">${icon}</span>
    <div class="toast-content"><strong>${escapeHtml(title)}</strong><span>${escapeHtml(message)}</span></div>
    <button class="toast-close" aria-label="Cerrar">×</button>
    ${action ? `<button class="toast-action">${escapeHtml(action.label)} →</button>` : ''}
  `;
  stack.prepend(toast);
  while (stack.children.length > 3) stack.lastElementChild.remove();

  toast.querySelector('.toast-close').addEventListener('click', () => toast.remove());
  if (action) {
    toast.querySelector('.toast-action').addEventListener('click', () => {
      action.onClick?.();
      toast.remove();
    });
  }
  if (type === 'success' || type === 'info') {
    setTimeout(() => toast.remove(), 5200);
  }
}

window.showToast = showToast;
