import { $, $$ } from '../core/dom.js';
import { openModal, registerModal } from '../core/modal.js';

/**
 * M09/§21: los documentos se previsualizan en modal, no se descargan
 * solo para revisarlos. Cualquier enlace con [data-doc-preview="ID"]
 * abre el documento en un iframe dentro del modal global.
 */
export function initDocumentPreview() {
  const modal = $('#documentPreviewModal');
  if (!modal) return;
  registerModal('documentPreviewModal', 'documentPreviewBackdrop');

  const frame = $('#documentPreviewFrame');
  const downloadLink = $('#documentPreviewDownload');

  function openPreview(id) {
    frame.src = `/documentos/${id}/previsualizar`;
    downloadLink.href = `/documentos/${id}/descargar`;
    openModal('documentPreviewModal');
  }

  $$('[data-doc-preview]').forEach((el) => {
    el.addEventListener('click', (e) => {
      e.preventDefault();
      openPreview(el.dataset.docPreview);
    });
  });

  $('#documentPreviewPrint')?.addEventListener('click', () => {
    frame.contentWindow?.print();
  });
}
