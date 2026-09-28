<?php
use App\Core\View;
use App\Repositories\CatalogRepository;
use App\Services\LoanSettingsService;

/**
 * El prestatario ya no es texto libre: se elige del catálogo Responsables
 * (o se da de alta rápida sin salir del modal) — ver migración 003 y
 * `LoanService::create()`, que arma el snapshot del préstamo a partir del
 * registro encontrado, no de lo que mande el cliente.
 */
$loanBorrowers = (new CatalogRepository())->all('responsibles');
$loanSettings = (new LoanSettingsService())->current();
$defaultLoanDays = $loanSettings['default_loan_days'];
$durationOptions = array_values(array_unique([1, 3, $defaultLoanDays, 7, 15, 30]));
sort($durationOptions);
?>
<div class="modal-backdrop" id="loanModalBackdrop"></div>
<section class="modal loan-modal" id="loanModal" role="dialog" aria-modal="true" data-default-loan-days="<?= $defaultLoanDays ?>">
  <div class="modal-header">
    <div><span class="panel-kicker">PRÉSTAMOS</span><h3>Registrar préstamo</h3></div>
    <button class="icon-btn modal-close" data-modal-close="loanModal">×</button>
  </div>
  <div class="modal-body">
    <label class="field">
      <span>Buscar bien disponible</span>
      <input id="loanAssetSearch" placeholder="Número, serial o descripción…" autocomplete="off">
    </label>
    <div id="loanAssetResults"></div>

    <div class="inline-section">
      <div class="inline-section-header"><strong>Bienes seleccionados</strong></div>
      <div id="loanSelectedAssets"><p class="field-help">Busca y agrega uno o varios bienes disponibles.</p></div>
    </div>

    <div class="field" style="margin-top:16px">
      <span>Prestatario <b>*</b></span>
      <div class="aw-smart-select" data-smart-select="loanBorrower">
        <div class="aw-smart-trigger">
          <button type="button" id="loanBorrowerTrigger">Selecciona un prestatario</button><i>⌄</i>
        </div>
        <div class="aw-smart-menu">
          <div class="aw-smart-menu-search"><label>⌕ <input placeholder="Buscar nombre, cédula o cargo…" data-smart-search></label></div>
          <div class="aw-smart-options">
            <?php foreach ($loanBorrowers as $person): ?>
              <?php $sub = implode(' · ', array_filter([$person['cedula'] ?? null, $person['cargo'] ?? null, $person['dependencia'] ?? null])); ?>
              <button type="button" class="aw-smart-option" data-value="<?= (int) $person['id'] ?>" data-label="<?= View::e($person['nombre']) ?>">
                <strong><?= View::e($person['nombre']) ?></strong>
                <small><?= View::e($sub ?: 'Sin datos adicionales') ?></small>
              </button>
            <?php endforeach; ?>
            <div class="aw-smart-empty">No se encontraron prestatarios.</div>
          </div>
          <div class="aw-smart-create"><button type="button" id="createLoanBorrower">＋ Registrar nuevo prestatario</button></div>
        </div>
      </div>
      <div class="aw-inline-create" id="loanBorrowerCreate">
        <div class="aw-inline-create-head"><strong>Nuevo prestatario</strong><button type="button" id="closeLoanBorrowerCreate">×</button></div>
        <div class="aw-form-grid">
          <label class="field"><span>Nombre completo</span><input id="newBorrowerName" placeholder="Nombre y apellido"></label>
          <label class="field"><span>Cédula</span><input id="newBorrowerCedula" placeholder="Ej. 12.345.678"></label>
          <label class="field"><span>Teléfono</span><input id="newBorrowerPhone" placeholder="Opcional"></label>
          <label class="field"><span>Cargo</span><input id="newBorrowerCargo" placeholder="Ej. Coordinador de TI"></label>
        </div>
        <div class="aw-inline-actions"><button class="btn btn-primary btn-sm" type="button" id="saveLoanBorrower">Guardar prestatario</button></div>
      </div>
    </div>

    <div class="loan-form-grid" style="margin-top:12px">
      <label class="field"><span>Fecha de entrega</span><input id="loanStartDate" type="date" value="<?= date('Y-m-d') ?>"></label>
      <label class="field"><span>Fecha de devolución prevista</span><input id="loanDueDate" type="date" value="<?= date('Y-m-d', strtotime('+' . $defaultLoanDays . ' days')) ?>"></label>
    </div>
    <div class="duration-pills">
      <?php foreach ($durationOptions as $days): ?>
        <button type="button" class="<?= $days === $defaultLoanDays ? 'active' : '' ?>" data-days="<?= $days ?>">+<?= $days ?> día<?= $days === 1 ? '' : 's' ?></button>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="modal-footer">
    <button class="btn btn-ghost modal-close-action" data-modal-close="loanModal">Cancelar</button>
    <button class="btn btn-primary" id="confirmLoan">Registrar préstamo</button>
  </div>
</section>
