import { $, syncOverlayScrollLock } from './dom.js';

const registry = new Map();

export function registerDrawer(drawerId, backdropId) {
  registry.set(drawerId, backdropId);
}

export function openDrawer(id) {
  $('#' + id)?.classList.add('show');
  const backdropId = registry.get(id);
  if (backdropId) $('#' + backdropId)?.classList.add('show');
  syncOverlayScrollLock();
}

export function closeDrawer(id) {
  $('#' + id)?.classList.remove('show');
  const backdropId = registry.get(id);
  if (backdropId) $('#' + backdropId)?.classList.remove('show');
  syncOverlayScrollLock();
}

export function closeAllDrawers() {
  registry.forEach((_, id) => closeDrawer(id));
}

/**
 * Delegado a nivel `document` en vez de atar un listener por nodo: varios
 * módulos llaman `registerDrawer()` (y renderizan el backdrop) después de
 * que `initDrawers()` ya corrió al cargar la página, así que enganchar los
 * listeners directamente sobre cada backdrop en ese momento los dejaba sin
 * efecto — el clic afuera del panel de filtros nunca lo cerraba. Delegando
 * en `document` el registro se resuelve en el momento del clic, no al
 * inicializar, y también sobrevive a un refresco en vivo que reemplace el
 * backdrop por un nodo nuevo.
 */
export function initDrawers() {
  document.addEventListener('click', (e) => {
    const closeBtn = e.target.closest('[data-close]');
    if (closeBtn) {
      closeDrawer(closeBtn.dataset.close);
      return;
    }
    const backdrop = e.target.closest('.drawer-backdrop');
    if (!backdrop) return;
    const [id] = [...registry.entries()].find(([, b]) => b === backdrop.id) || [];
    if (id) closeDrawer(id);
  });
}
