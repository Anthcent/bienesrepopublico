<div class="modal-backdrop" id="confirmModalBackdrop"></div>
<section class="modal" id="confirmModal" role="alertdialog" aria-modal="true" style="width:min(420px,calc(100% - 30px))">
  <div class="modal-header">
    <div><span class="panel-kicker">CONFIRMAR</span><h3 id="confirmModalTitle">¿Confirmar acción?</h3></div>
    <button class="icon-btn modal-close" data-modal-close="confirmModal">×</button>
  </div>
  <div class="modal-body">
    <p id="confirmModalMessage" style="margin:0;color:var(--text-secondary);font-size:12.5px;line-height:1.6"></p>
  </div>
  <div class="modal-footer">
    <button class="btn btn-ghost" data-modal-close="confirmModal">Cancelar</button>
    <button class="btn btn-primary" id="confirmModalConfirm">Confirmar</button>
  </div>
</section>
