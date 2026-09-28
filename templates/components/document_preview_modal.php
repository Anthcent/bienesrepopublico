<div class="modal-backdrop" id="documentPreviewBackdrop"></div>
<section class="modal" id="documentPreviewModal" role="dialog" aria-modal="true" style="width:min(820px,calc(100vw - 32px));height:min(88vh,calc(100dvh - 32px))">
  <div class="modal-header">
    <div><span class="panel-kicker">DOCUMENTO</span><h3>Previsualización</h3></div>
    <button class="icon-btn modal-close" data-modal-close="documentPreviewModal">×</button>
  </div>
  <div class="modal-body" style="padding:0;flex:1;display:flex">
    <iframe id="documentPreviewFrame" src="about:blank" style="width:100%;height:100%;border:0;min-height:60vh"></iframe>
  </div>
  <div class="modal-footer">
    <a class="btn btn-ghost" id="documentPreviewDownload" href="#" target="_blank">Descargar</a>
    <button class="btn btn-primary" id="documentPreviewPrint">Imprimir</button>
  </div>
</section>
