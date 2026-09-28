export const $ = (sel, root = document) => root.querySelector(sel);
export const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];

export function escapeHtml(str) {
  return String(str ?? '').replace(/[&<>"']/g, (s) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
  }[s]));
}

export async function api(url, options = {}) {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
  const headers = {
    'Content-Type': 'application/json',
    ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {}),
    ...(options.headers || {}),
  };
  const response = await fetch(url, { ...options, headers, credentials: 'same-origin' });
  let body = null;
  try { body = await response.json(); } catch (e) { body = null; }
  if (!response.ok) {
    const message = body?.message || body?.error || 'Ocurrió un error al procesar la solicitud.';
    const error = new Error(message);
    error.status = response.status;
    error.body = body;
    throw error;
  }
  return body;
}

/**
 * `body` es el único contenedor que realmente scrollea la página (ver
 * app.css) — sin esto, un modal o drawer abierto no bloqueaba ese scroll:
 * al agotar el scroll interno del overlay, el gesto se encadenaba hacia la
 * página de atrás. Se consulta el DOM en vez de llevar un contador propio
 * porque modal.js y drawer.js abren/cierran independientemente (y
 * `confirmAction()` puede abrir un modal arriba de otro).
 */
export function syncOverlayScrollLock() {
  document.body.classList.toggle('overlay-open', !!document.querySelector('.modal.show, .drawer.show'));
}

export function debounce(fn, delay = 300) {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), delay);
  };
}
