import { $, $$, escapeHtml } from './dom.js';

/**
 * Select con búsqueda interna + creación rápida, sin salir del formulario.
 * Nació en el wizard de Incorporar bien (marca/modelo/ubicación/responsable)
 * y lo reutiliza el modal de Registrar préstamo para el prestatario — por
 * eso vive en `core/` y no en `modules/assetWizardPage.js`.
 *
 * Requiere el markup `[data-smart-select="name"]` con `.aw-smart-trigger
 * button`, `.aw-smart-menu`, `[data-smart-search]`, `.aw-smart-options` y
 * `.aw-smart-empty` (ver wizard.php o loan_modal.php para el HTML exacto).
 */
export function initSmartSelect(name, { onSelect } = {}) {
  const root = $(`[data-smart-select="${name}"]`);
  const trigger = $('.aw-smart-trigger button', root);
  const menu = $('.aw-smart-menu', root);
  const search = $('[data-smart-search]', root);
  const optionsBox = $('.aw-smart-options', root);
  const empty = $('.aw-smart-empty', root);
  const options = () => $$('.aw-smart-option', optionsBox);

  trigger.addEventListener('click', (e) => {
    e.stopPropagation();
    $$('.aw-smart-menu.show').forEach((m) => { if (m !== menu) m.classList.remove('show'); });
    menu.classList.toggle('show');
    if (menu.classList.contains('show')) {
      if (search) { search.value = ''; setTimeout(() => search.focus(), 30); }
      options().forEach((o) => { o.style.display = 'flex'; });
      if (empty) empty.style.display = options().length ? 'none' : 'block';
    }
  });

  search?.addEventListener('input', () => {
    const q = search.value.toLowerCase().trim();
    let visible = 0;
    options().forEach((o) => {
      const match = !q || o.textContent.toLowerCase().includes(q);
      o.style.display = match ? 'flex' : 'none';
      if (match) visible++;
    });
    if (empty) empty.style.display = visible ? 'none' : 'block';
  });

  function bindOption(opt) {
    opt.addEventListener('click', () => {
      options().forEach((x) => x.classList.remove('selected'));
      opt.classList.add('selected');
      trigger.textContent = opt.dataset.label;
      menu.classList.remove('show');
      onSelect?.(opt.dataset.value, opt.dataset.label);
    });
  }
  options().forEach(bindOption);

  return {
    addOption(value, label, sub, { select = true } = {}) {
      const opt = document.createElement('button');
      opt.type = 'button';
      opt.className = 'aw-smart-option';
      opt.dataset.value = value;
      opt.dataset.label = label;
      opt.innerHTML = `<strong>${escapeHtml(label)}</strong><small>${escapeHtml(sub || '')}</small>`;
      bindOption(opt);
      optionsBox.insertBefore(opt, empty);
      if (select) opt.click();
      return opt;
    },
    selectByValue(value) {
      const opt = options().find((o) => o.dataset.value === String(value));
      opt?.click();
      return opt;
    },
    clearOptions() {
      options().forEach((o) => o.remove());
    },
    close() { menu.classList.remove('show'); },
  };
}

document.addEventListener('click', (e) => {
  if (!e.target.closest('.aw-smart-select')) $$('.aw-smart-menu.show').forEach((m) => m.classList.remove('show'));
});
