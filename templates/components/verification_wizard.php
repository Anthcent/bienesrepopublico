<?php
use App\Core\View;
use App\Repositories\CatalogRepository;

$catalogs = new CatalogRepository();
$locations = $catalogs->all('locations');
$responsibles = $catalogs->all('responsibles');
$categories = $catalogs->all('categories');
?>
<div class="modal-backdrop" id="verificationWizardBackdrop"></div>
<section class="modal wizard-modal" id="verificationWizard" role="dialog" aria-modal="true">
  <div class="modal-header">
    <div><span class="panel-kicker">JORNADA DE VERIFICACIÓN</span><h3>Nueva jornada</h3></div>
    <button class="icon-btn modal-close" data-modal-close="verificationWizard">×</button>
  </div>

  <div class="wizard-progress">
    <button type="button" class="wizard-step active" data-vstep="1"><span>1</span><small>Alcance</small></button>
    <div class="wizard-line"></div>
    <button type="button" class="wizard-step" data-vstep="2"><span>2</span><small>Bienes</small></button>
    <div class="wizard-line"></div>
    <button type="button" class="wizard-step" data-vstep="3"><span>3</span><small>Campos</small></button>
    <div class="wizard-line"></div>
    <button type="button" class="wizard-step" data-vstep="4"><span>4</span><small>Revisión</small></button>
  </div>

  <div class="modal-body wizard-body">
    <div class="wizard-panel active" data-vpanel="1">
      <div class="wizard-copy">
        <h4>1. Define el alcance</h4>
        <p>Filtra de dónde saldrán los bienes a verificar. Podrás incluir o excluir individualmente en el siguiente paso.</p>
      </div>
      <div class="form-grid two">
        <label class="field"><span>Ubicación</span>
          <select id="vScopeLocation"><option value="">Todas</option>
            <?php foreach ($locations as $l): ?><option value="<?= (int) $l['id'] ?>"><?= View::e($l['nombre']) ?></option><?php endforeach; ?>
          </select>
        </label>
        <label class="field"><span>Responsable</span>
          <select id="vScopeResponsible"><option value="">Todos</option>
            <?php foreach ($responsibles as $r): ?><option value="<?= (int) $r['id'] ?>"><?= View::e($r['nombre']) ?></option><?php endforeach; ?>
          </select>
        </label>
        <label class="field"><span>Categoría</span>
          <select id="vScopeCategory"><option value="">Todas</option>
            <?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>"><?= View::e($c['nombre']) ?></option><?php endforeach; ?>
          </select>
        </label>
        <label class="field"><span>Disponibilidad</span>
          <select id="vScopeAvailability"><option value="">Todas (incluye prestados)</option><option value="DISPONIBLE">Solo disponibles</option><option value="PRESTADO">Solo prestados</option></select>
        </label>
      </div>
      <label class="field"><span><input type="checkbox" id="vScopeIncomplete"> Solo bienes con información incompleta</span></label>
      <div class="suggestion-box" style="margin-top:14px">
        <div class="suggestion-icon">i</div>
        <div><strong id="vCandidateCount">— bienes encontrados</strong><p>Ajusta los filtros; el conteo se actualiza automáticamente.</p></div>
      </div>
    </div>

    <div class="wizard-panel" data-vpanel="2">
      <div class="wizard-copy">
        <h4>2. Bienes incluidos</h4>
        <p>Desmarca los que no correspondan. El sistema guardará un snapshot de cada uno al generar la jornada.</p>
      </div>
      <div class="candidate-list" id="vCandidateList"></div>
      <p class="field-help" id="vSelectedCount" style="margin-top:8px"></p>
    </div>

    <div class="wizard-panel" data-vpanel="3">
      <div class="wizard-copy">
        <h4>3. ¿Qué se verificará?</h4>
        <p>Estos campos aparecerán en la hoja imprimible y en la captura.</p>
      </div>
      <div class="check-grid">
        <label><input type="checkbox" class="v-campo" value="ubicacion" checked> Ubicación</label>
        <label><input type="checkbox" class="v-campo" value="responsable" checked> Responsable</label>
        <label><input type="checkbox" class="v-campo" value="estado_fisico" checked> Estado físico</label>
        <label><input type="checkbox" class="v-campo" value="serial" checked> Serial</label>
        <label><input type="checkbox" class="v-campo" value="identificacion" checked> Identificación / etiqueta</label>
        <label><input type="checkbox" class="v-campo" value="fotografia" checked> Fotografía</label>
        <label><input type="checkbox" class="v-campo" value="observaciones" checked> Observaciones</label>
      </div>
    </div>

    <div class="wizard-panel" data-vpanel="4">
      <div class="wizard-copy">
        <h4>4. Revisión</h4>
        <p>Confirma los datos de la jornada antes de generarla.</p>
      </div>
      <label class="field"><span>Título <b>*</b></span><input id="vTitulo" placeholder="Ej. Verificación Almacén Central — Agosto 2026"></label>
      <div class="form-grid two">
        <label class="field"><span>Descripción</span><input id="vDescripcion" placeholder="Opcional"></label>
        <label class="field"><span>Fecha programada</span><input type="date" id="vFechaProgramada"></label>
      </div>
      <div class="review-card" style="margin-top:10px">
        <div class="review-main">
          <h3 id="vReviewTitulo">—</h3>
          <div class="review-grid">
            <div><span>Bienes</span><strong id="vReviewCount">0</strong></div>
            <div><span>Campos</span><strong id="vReviewCampos">—</strong></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal-footer wizard-footer">
    <button type="button" class="btn btn-ghost" id="vBack">Atrás</button>
    <div><button type="button" class="btn btn-primary" id="vNext">Continuar</button></div>
  </div>
</section>
