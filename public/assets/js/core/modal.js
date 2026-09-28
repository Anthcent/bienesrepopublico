import { $, syncOverlayScrollLock } from './dom.js';

const registry = new Map();

export function registerModal(modalId, backdropId) {
  registry.set(modalId, backdropId);
}

export function openModal(id) {
  $('#' + id)?.classList.add('show');
  const backdropId = registry.get(id);
  if (backdropId) $('#' + backdropId)?.classList.add('show');
  syncOverlayScrollLock();
}

export function closeModal(id) {
  $('#' + id)?.classList.remove('show');
  const backdropId = registry.get(id);
  if (backdropId) $('#' + backdropId)?.classList.remove('show');
  syncOverlayScrollLock();
}

export function closeAllModals() {
  registry.forEach((_, id) => closeModal(id));
}

/**
 * Delegado a nivel `document` en vez de atar un listener por nodo: varios
 * módulos llaman `registerModal()` (y renderizan el modal/backdrop) después
 * de que `initModals()` ya corrió al cargar la página, y un refresco en vivo
 * (`refreshPageContent()`) reemplaza ese HTML por nodos nuevos sin volver a
 * llamar `initModals()`. Atar los listeners directamente a cada botón/backdrop
 * los dejaba sin efecto tras el primer refresco: el modal quedaba abierto sin
 * forma de cerrarlo desde "Cancelar"/"×" ni haciendo clic afuera, y su backdrop
 * (a pantalla completa, por encima incluso de la barra superior) seguía
 * capturando todos los clics de la página. Delegando en `document` el destino
 * del clic se resuelve en el momento, no al inicializar, así que sobrevive a
 * cualquier refresco. Mismo patrón que `initDrawers()` en drawer.js.
 */
export function initModals() {
  document.addEventListener('click', (e) => {
    const closeBtn = e.target.closest('[data-modal-close]');
    if (closeBtn) {
      closeModal(closeBtn.dataset.modalClose);
      return;
    }
    const backdrop = e.target.closest('.modal-backdrop');
    if (!backdrop) return;
    const [id] = [...registry.entries()].find(([, b]) => b === backdrop.id) || [];
    if (id) closeModal(id);
  });
}

/**
 * Confirmación crítica con modal propio (nunca window.confirm()).
 * Reutiliza el markup de templates/components/modal_confirm.php.
 */
export function confirmAction({ title, message, confirmLabel = 'Confirmar', danger = false, onConfirm }) {
  const modal = $('#confirmModal');
  if (!modal) return;
  $('#confirmModalTitle', modal).textContent = title;
  $('#confirmModalMessage', modal).textContent = message;
  const confirmBtn = $('#confirmModalConfirm', modal);
  confirmBtn.textContent = confirmLabel;
  confirmBtn.className = 'btn ' + (danger ? 'btn-danger-soft' : 'btn-primary');

  const handler = () => {
    closeModal('confirmModal');
    confirmBtn.removeEventListener('click', handler);
    onConfirm?.();
  };
  confirmBtn.addEventListener('click', handler);
  openModal('confirmModal');
}
