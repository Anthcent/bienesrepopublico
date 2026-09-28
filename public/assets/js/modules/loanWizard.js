import { $, $$, api, debounce, escapeHtml } from '../core/dom.js';
import { openModal, closeModal, registerModal } from '../core/modal.js';
import { showToast } from '../core/toast.js';
import { refreshPageContent } from '../core/pageRefresh.js';
import { initViewSwitcher } from '../core/viewSwitcher.js';
import { initInventoryPanel } from './inventory.js';
import { initSmartSelect } from '../core/smartSelect.js';

/** Re-ata solo el botón "Registrar préstamo" cuando vive dentro de `.page` (Dashboard, Préstamos) tras un refreshPageContent(). */
export function bindLoanWizardTrigger() {
  document.getElementById('quickLoan')?.addEventListener('click', () => window.__openLoanModal?.(null));
}

export function initLoanModal() {
  const modal = $('#loanModal');
  if (!modal) return;
  registerModal('loanModal', 'loanModalBackdrop');

  let selectedAssets = [];
  let selectedBorrowerId = null;

  function applyDuration(days) {
    const start = $('#loanStartDate', modal).value || new Date().toISOString().slice(0, 10);
    const due = new Date(`${start}T00:00:00`);
    due.setDate(due.getDate() + days);
    const year = due.getFullYear();
    const month = String(due.getMonth() + 1).padStart(2, '0');
    const day = String(due.getDate()).padStart(2, '0');
    $('#loanDueDate', modal).value = `${year}-${month}-${day}`;
  }

  function resetBorrower() {
    selectedBorrowerId = null;
    $('#loanBorrowerTrigger', modal).textContent = 'Selecciona un prestatario';
    $$('.aw-smart-option', modal).forEach((o) => o.classList.remove('selected'));
    $('#loanBorrowerCreate', modal)?.classList.remove('show');
  }

  const borrowerSelect = initSmartSelect('loanBorrower', {
    onSelect: (value) => { selectedBorrowerId = value; },
  });

  $('#createLoanBorrower', modal)?.addEventListener('click', () => {
    $('[data-smart-select="loanBorrower"] .aw-smart-menu', modal).classList.remove('show');
    $('#loanBorrowerCreate', modal).classList.add('show');
  });
  $('#closeLoanBorrowerCreate', modal)?.addEventListener('click', () => $('#loanBorrowerCreate', modal).classList.remove('show'));
  $('#saveLoanBorrower', modal)?.addEventListener('click', async () => {
    const nombre = $('#newBorrowerName', modal).value.trim();
    const cedula = $('#newBorrowerCedula', modal).value.trim();
    const telefono = $('#newBorrowerPhone', modal).value.trim();
    const cargo = $('#newBorrowerCargo', modal).value.trim();
    if (!nombre) { showToast('Falta el nombre', 'Escribe el nombre del prestatario.', 'warning'); return; }
    try {
      const res = await api('/api/catalogs/responsibles', { method: 'POST', body: JSON.stringify({ nombre, cedula, telefono, cargo }) });
      const sub = [res.item.cedula, res.item.cargo, res.item.dependencia].filter(Boolean).join(' · ');
      borrowerSelect.addOption(res.item.id, res.item.nombre, sub || 'Sin datos adicionales');
      selectedBorrowerId = res.item.id;
      $('#newBorrowerName', modal).value = '';
      $('#newBorrowerCedula', modal).value = '';
      $('#newBorrowerPhone', modal).value = '';
      $('#newBorrowerCargo', modal).value = '';
      $('#loanBorrowerCreate', modal).classList.remove('show');
      showToast('Prestatario registrado', `${res.item.nombre} quedó seleccionado.`, 'success');
    } catch (e) {
      showToast('No se pudo registrar el prestatario', e.message, 'error');
    }
  });

  function openWithAsset(asset) {
    selectedAssets = asset ? [asset] : [];
    renderSelected();
    resetBorrower();
    openModal('loanModal');
  }
  window.__openLoanModal = openWithAsset;

  $('#quickLoan')?.addEventListener('click', () => openWithAsset(null));
  $('#drawerLoanAction')?.addEventListener('click', () => {
    const asset = window.__currentDrawerAsset;
    openWithAsset(asset || null);
  });

  const assetSearch = $('#loanAssetSearch', modal);
  const assetResults = $('#loanAssetResults', modal);
  assetSearch?.addEventListener('input', debounce(async () => {
    const q = assetSearch.value.trim();
    if (q.length < 2) { assetResults.innerHTML = ''; return; }
    const res = await api('/api/assets/search-available?q=' + encodeURIComponent(q));
    assetResults.innerHTML = res.items.map((a) => `
      <button type="button" class="search-result" data-add-asset="${a.id}" data-numero="${escapeHtml(a.numero_bien)}" data-desc="${escapeHtml(a.descripcion)}">
        <span class="result-icon blue">▦</span>
        <span><strong>${escapeHtml(a.numero_bien)}</strong><small>${escapeHtml(a.descripcion)}</small></span>
      </button>
    `).join('') || '<div class="no-results">Sin bienes disponibles con ese criterio.</div>';

    $$('[data-add-asset]', assetResults).forEach((btn) => btn.addEventListener('click', () => {
      const id = Number(btn.dataset.addAsset);
      if (!selectedAssets.find((a) => a.id === id)) {
        selectedAssets.push({ id, numero_bien: btn.dataset.numero, descripcion: btn.dataset.desc });
      }
      assetSearch.value = '';
      assetResults.innerHTML = '';
      renderSelected();
    }));
  }, 300));

  function renderSelected() {
    const box = $('#loanSelectedAssets', modal);
    if (!box) return;
    box.innerHTML = selectedAssets.map((a) => `
      <div class="selected-asset" data-id="${a.id}">
        <span class="asset-thumb blue">▦</span>
        <div><strong>${escapeHtml(a.numero_bien)} · ${escapeHtml(a.descripcion)}</strong><small>Disponible</small></div>
        <button type="button" class="icon-action" data-remove="${a.id}">✕</button>
      </div>
    `).join('') || '<p class="field-help">Busca y agrega uno o varios bienes disponibles.</p>';

    $$('[data-remove]', box).forEach((btn) => btn.addEventListener('click', () => {
      selectedAssets = selectedAssets.filter((a) => a.id !== Number(btn.dataset.remove));
      renderSelected();
    }));
  }

  $$('.duration-pills button', modal).forEach((btn) => btn.addEventListener('click', () => {
    $$('.duration-pills button', modal).forEach((b) => b.classList.remove('active'));
    btn.classList.add('active');
    applyDuration(Number(btn.dataset.days));
  }));
  $('#loanStartDate', modal)?.addEventListener('change', () => {
    const selectedDuration = $('.duration-pills button.active', modal);
    applyDuration(Number(selectedDuration?.dataset.days || modal.dataset.defaultLoanDays || 7));
  });

  $('#confirmLoan')?.addEventListener('click', async () => {
    if (selectedAssets.length === 0) {
      showToast('Selecciona al menos un bien', 'Agrega uno o varios bienes disponibles antes de continuar.', 'warning');
      return;
    }
    if (!selectedBorrowerId) {
      showToast('Selecciona el prestatario', 'Elige un prestatario registrado o da de alta uno nuevo.', 'warning');
      return;
    }
    const payload = {
      asset_ids: selectedAssets.map((a) => a.id),
      responsible_id: selectedBorrowerId,
      fecha_prestamo: $('#loanStartDate', modal)?.value,
      fecha_vencimiento: $('#loanDueDate', modal)?.value,
    };
    try {
      const res = await api('/api/loans', { method: 'POST', body: JSON.stringify(payload) });
      closeModal('loanModal');
      showToast('Préstamo registrado', `${res.data.loan.codigo} fue registrado correctamente.`, 'success', {
        label: 'Ver préstamo',
        onClick: () => { window.location.href = '/prestamos/' + res.data.loan.id; },
      });
      selectedAssets = [];
      resetBorrower();
      await refreshPageContent(() => { initViewSwitcher(); initInventoryPanel(); bindLoanWizardTrigger(); });
    } catch (e) {
      showToast('No se pudo registrar el préstamo', e.message, 'error');
    }
  });
}
