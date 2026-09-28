<?php
/** @var array $categories */
/** @var array $brands */
/** @var array $locations */
/** @var array $responsibles */
/** @var array $physicalStates */
use App\Core\View;
?>
<link rel="stylesheet" href="/assets/css/wizard-incorporar.css">

<div class="page-heading">
  <div>
    <span class="eyebrow">INCORPORACIÓN</span>
    <h1>Registrar bien</h1>
    <p>Un paso a la vez, con búsqueda interna y creación rápida de catálogos sin abandonar el registro.</p>
  </div>
  <div class="page-heading-actions aw-heading-actions">
    <a class="btn btn-ghost" href="/inventario">← Volver al inventario</a>
    <button class="btn btn-secondary" type="button" id="wizardSaveDraftTop">Guardar borrador</button>
    <button class="btn btn-primary" type="button" id="wizardTopContinue">Continuar →</button>
  </div>
</div>

<div class="aw-layout">
  <section class="panel aw-card">
    <header class="wizard-progress">
      <button type="button" class="wizard-step active" data-step="1"><span>1</span><small>Tipo</small></button>
      <div class="wizard-line"></div>
      <button type="button" class="wizard-step" data-step="2"><span>2</span><small>Identificación</small></button>
      <div class="wizard-line"></div>
      <button type="button" class="wizard-step" data-step="3"><span>3</span><small>Asignación</small></button>
      <div class="wizard-line"></div>
      <button type="button" class="wizard-step" data-step="4"><span>4</span><small>Revisión</small></button>
    </header>

    <form id="assetWizardForm">
      <div class="wizard-body">
        <!-- PASO 1: TIPO -->
        <section class="wizard-panel active" data-wizard-panel="1">
          <div class="aw-panel-head">
            <div class="wizard-copy">
              <small class="aw-eyebrow">PASO 1 DE 4</small>
              <h4>¿Qué tipo de bien vas a incorporar?</h4>
              <p>Los tipos se mantienen compactos. Puedes buscar si existen muchos y crear uno nuevo desde "Otro".</p>
            </div>
            <span class="aw-hint">Selección rápida</span>
          </div>

          <div class="suggestion-box hidden" id="wizardDraftBox">
            <div class="suggestion-icon">✎</div>
            <div><strong>Tienes un borrador guardado</strong><p id="wizardDraftMeta"></p></div>
            <button type="button" id="wizardDraftRestore">Continuar borrador</button>
            <button type="button" id="wizardDraftDiscard">Descartar</button>
          </div>

          <div class="aw-type-toolbar">
            <label class="aw-searchbox">⌕ <input id="typeSearch" placeholder="Buscar tipo de bien..."></label>
            <button type="button" class="btn btn-ghost btn-sm aw-type-quick-add" id="quickAddType">＋ Nuevo tipo</button>
          </div>

          <div class="type-grid" id="typeGrid">
            <?php foreach ($categories as $i => $category): ?>
              <button type="button" class="type-card <?= $i === 0 ? 'selected' : '' ?>" data-category-id="<?= (int) $category['id'] ?>">
                <i>▦</i><strong><?= View::e($category['nombre']) ?></strong><small><?= View::e($category['tipo_bien']) ?></small>
              </button>
            <?php endforeach; ?>
            <button type="button" class="type-card" id="typeCardOther"><i>＋</i><strong>Otro</strong><small>Crear nuevo tipo</small></button>
          </div>
          <input type="hidden" name="category_id" value="<?= $categories[0]['id'] ?? '' ?>" id="categoryIdInput">

          <div class="aw-other-create" id="otherCreate">
            <h3>Crear nuevo tipo de bien</h3>
            <div class="aw-form-grid">
              <label class="field"><span>Nombre del tipo</span><input id="otherName" placeholder="Ej. Escáner documental"></label>
              <label class="field"><span>Familia / categoría</span><input id="otherFamily" placeholder="Ej. Equipo de oficina"></label>
            </div>
            <div class="aw-inline-actions">
              <button class="btn btn-ghost btn-sm" type="button" id="cancelOther">Cancelar</button>
              <button class="btn btn-primary btn-sm" type="button" id="saveOther">Guardar y seleccionar</button>
            </div>
          </div>
        </section>

        <!-- PASO 2: IDENTIFICACIÓN -->
        <section class="wizard-panel" data-wizard-panel="2">
          <div class="aw-panel-head">
            <div class="wizard-copy">
              <small class="aw-eyebrow">PASO 2 DE 4</small>
              <h4>Identificación esencial</h4>
              <p>Solo se muestran los datos principales. Marca, modelo y color pueden ampliarse sin salir del wizard.</p>
            </div>
            <span class="aw-hint">Validación automática</span>
          </div>

          <div class="form-grid two">
            <label class="field">
              <span>Número de bien <b>*</b></span>
              <div class="aw-number-group">
                <span class="aw-number-prefix">BP-</span>
                <input id="assetNumber" inputmode="numeric" placeholder="0001">
                <button type="button" class="aw-number-random" id="assetNumberRandom" title="Generar número disponible">
                  <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8" cy="8" r="1.3" fill="currentColor" stroke="none"/><circle cx="16" cy="8" r="1.3" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.3" fill="currentColor" stroke="none"/><circle cx="8" cy="16" r="1.3" fill="currentColor" stroke="none"/><circle cx="16" cy="16" r="1.3" fill="currentColor" stroke="none"/></svg>
                </button>
              </div>
              <input type="hidden" name="numero_bien" id="assetNumberFull">
              <small class="field-help" id="assetNumberHelp"></small>
            </label>
            <label class="field">
              <span>Serial</span>
              <input id="assetSerial" name="serial">
              <small class="field-help" id="assetSerialHelp">Se verifica duplicidad automáticamente.</small>
            </label>
          </div>

          <label class="field" style="margin-bottom:12px">
            <span>Descripción <b>*</b></span>
            <input id="assetDescription" name="descripcion" required>
          </label>

          <div class="aw-form-grid">
            <!-- Marca -->
            <div class="field">
              <span>Marca</span>
              <div class="aw-smart-select" data-smart-select="brand">
                <div class="aw-smart-trigger">
                  <button type="button" id="brandTrigger">Sin especificar</button><i>⌄</i>
                </div>
                <div class="aw-smart-menu">
                  <div class="aw-smart-menu-search"><label>⌕ <input placeholder="Buscar marca..." data-smart-search></label></div>
                  <div class="aw-smart-options">
                    <?php foreach ($brands as $brand): ?>
                      <button type="button" class="aw-smart-option" data-value="<?= (int) $brand['id'] ?>" data-label="<?= View::e($brand['nombre']) ?>"><strong><?= View::e($brand['nombre']) ?></strong><small>Marca existente</small></button>
                    <?php endforeach; ?>
                    <div class="aw-smart-empty">No se encontraron marcas.</div>
                  </div>
                  <div class="aw-smart-create"><button type="button" data-create-brand>＋ Registrar nueva marca</button></div>
                </div>
              </div>
              <input type="hidden" name="brand_id" id="brandIdInput">
              <div class="aw-inline-create" id="brandCreate">
                <div class="aw-inline-create-head"><strong>Nueva marca</strong><button type="button" id="closeBrand">×</button></div>
                <label class="field"><span>Nombre</span><input id="newBrandName" placeholder="Ej. Acer"></label>
                <div class="aw-inline-actions"><button class="btn btn-primary btn-sm" type="button" id="saveBrand">Guardar marca</button></div>
              </div>
            </div>

            <!-- Modelo (depende de la marca) -->
            <div class="field">
              <span>Modelo</span>
              <div class="aw-smart-select" data-smart-select="model">
                <div class="aw-smart-trigger">
                  <button type="button" id="modelTrigger">Selecciona una marca primero</button><i>⌄</i>
                </div>
                <div class="aw-smart-menu">
                  <div class="aw-smart-menu-search"><label>⌕ <input placeholder="Buscar modelo..." data-smart-search></label></div>
                  <div class="aw-smart-options" id="modelOptions">
                    <div class="aw-smart-empty" id="modelEmpty">Selecciona una marca para ver sus modelos.</div>
                  </div>
                  <div class="aw-smart-create"><button type="button" data-create-model disabled>＋ Registrar nuevo modelo</button></div>
                </div>
              </div>
              <input type="hidden" name="model_id" id="modelIdInput">
              <div class="aw-inline-create" id="modelCreate">
                <div class="aw-inline-create-head"><strong>Nuevo modelo</strong><button type="button" id="closeModel">×</button></div>
                <label class="field"><span>Nombre</span><input id="newModelName" placeholder="Ej. ThinkPad T14"></label>
                <div class="aw-inline-actions"><button class="btn btn-primary btn-sm" type="button" id="saveModel">Guardar modelo</button></div>
              </div>
            </div>
          </div>

          <div class="field" style="margin-top:12px">
            <span>Color</span>
            <div class="aw-color-box">
              <div class="aw-color-input">
                <span class="aw-swatch-dot aw-color-preview" id="colorPreview"></span>
                <input id="colorInput" name="color" placeholder="Escribe o selecciona un color" autocomplete="off">
                <button type="button" class="btn btn-ghost btn-sm" id="saveColor">＋ Guardar como sugerencia</button>
              </div>
              <div class="aw-swatches" id="swatches">
                <button type="button" class="aw-swatch" data-color="Negro" data-hex="#111111"><span class="aw-swatch-dot" style="background:#111111"></span>Negro<span class="aw-swatch-remove" data-remove title="Quitar">×</span></button>
                <button type="button" class="aw-swatch" data-color="Blanco" data-hex="#ffffff"><span class="aw-swatch-dot" style="background:#ffffff;border:1px solid #ccc"></span>Blanco<span class="aw-swatch-remove" data-remove title="Quitar">×</span></button>
                <button type="button" class="aw-swatch" data-color="Gris" data-hex="#888888"><span class="aw-swatch-dot" style="background:#888888"></span>Gris<span class="aw-swatch-remove" data-remove title="Quitar">×</span></button>
                <button type="button" class="aw-swatch" data-color="Azul" data-hex="#3567c8"><span class="aw-swatch-dot" style="background:#3567c8"></span>Azul<span class="aw-swatch-remove" data-remove title="Quitar">×</span></button>
                <button type="button" class="aw-swatch" data-color="Rojo" data-hex="#c83a3a"><span class="aw-swatch-dot" style="background:#c83a3a"></span>Rojo<span class="aw-swatch-remove" data-remove title="Quitar">×</span></button>
                <button type="button" class="aw-swatch" data-color="Plateado" data-hex="#c8c8c8"><span class="aw-swatch-dot" style="background:#c8c8c8"></span>Plateado<span class="aw-swatch-remove" data-remove title="Quitar">×</span></button>
                <button type="button" class="aw-swatch" data-color="Verde" data-hex="#2e7d32"><span class="aw-swatch-dot" style="background:#2e7d32"></span>Verde<span class="aw-swatch-remove" data-remove title="Quitar">×</span></button>
                <button type="button" class="aw-swatch" data-color="Amarillo" data-hex="#f2c94c"><span class="aw-swatch-dot" style="background:#f2c94c"></span>Amarillo<span class="aw-swatch-remove" data-remove title="Quitar">×</span></button>
                <button type="button" class="aw-swatch" data-color="Naranja" data-hex="#e07b39"><span class="aw-swatch-dot" style="background:#e07b39"></span>Naranja<span class="aw-swatch-remove" data-remove title="Quitar">×</span></button>
                <button type="button" class="aw-swatch" data-color="Rosado" data-hex="#e88ab0"><span class="aw-swatch-dot" style="background:#e88ab0"></span>Rosado<span class="aw-swatch-remove" data-remove title="Quitar">×</span></button>
                <button type="button" class="aw-swatch" data-color="Morado" data-hex="#7c3aed"><span class="aw-swatch-dot" style="background:#7c3aed"></span>Morado<span class="aw-swatch-remove" data-remove title="Quitar">×</span></button>
                <button type="button" class="aw-swatch" data-color="Marrón" data-hex="#6b4226"><span class="aw-swatch-dot" style="background:#6b4226"></span>Marrón<span class="aw-swatch-remove" data-remove title="Quitar">×</span></button>
                <button type="button" class="aw-swatch" data-color="Beige" data-hex="#d8c9a3"><span class="aw-swatch-dot" style="background:#d8c9a3"></span>Beige<span class="aw-swatch-remove" data-remove title="Quitar">×</span></button>
              </div>
            </div>
          </div>

          <div class="aw-optional">
            <button type="button" id="optionalToggle">Datos adicionales ▾</button>
            <div class="aw-optional-body">
              <label class="field"><span>Material</span><input name="material" placeholder="Ej. Aluminio / plástico"></label>
            </div>
          </div>
        </section>

        <!-- PASO 3: ASIGNACIÓN -->
        <section class="wizard-panel" data-wizard-panel="3">
          <div class="aw-panel-head">
            <div class="wizard-copy">
              <small class="aw-eyebrow">PASO 3 DE 4</small>
              <h4>Asignación y estado</h4>
              <p>Ubicación y responsable usan desplegables con buscador interno. También puedes crear nuevos registros allí mismo.</p>
            </div>
            <span class="aw-hint">Selección con búsqueda</span>
          </div>

          <div class="aw-form-grid">
            <!-- Ubicación -->
            <div class="field">
              <span>Ubicación <b>*</b></span>
              <div class="aw-smart-select" data-smart-select="location">
                <div class="aw-smart-trigger">
                  <button type="button" id="locationTrigger"><?= View::e($locations[0]['nombre'] ?? 'Selecciona una ubicación') ?></button><i>⌄</i>
                </div>
                <div class="aw-smart-menu">
                  <div class="aw-smart-menu-search"><label>⌕ <input placeholder="Buscar ubicación..." data-smart-search></label></div>
                  <div class="aw-smart-options">
                    <?php foreach ($locations as $i => $location): ?>
                      <button type="button" class="aw-smart-option <?= $i === 0 ? 'selected' : '' ?>" data-value="<?= (int) $location['id'] ?>" data-label="<?= View::e($location['nombre']) ?>"><strong><?= View::e($location['nombre']) ?></strong><small><?= View::e($location['piso_zona'] ?: 'Sin piso/zona registrada') ?></small></button>
                    <?php endforeach; ?>
                    <div class="aw-smart-empty">No se encontraron ubicaciones.</div>
                  </div>
                  <div class="aw-smart-create"><button type="button" data-create-location>＋ Registrar nueva ubicación</button></div>
                </div>
              </div>
              <input type="hidden" name="location_id" id="locationIdInput" value="<?= (int) ($locations[0]['id'] ?? '') ?>" required>
              <div class="aw-inline-create" id="locationCreate">
                <div class="aw-inline-create-head"><strong>Nueva ubicación</strong><button type="button" id="closeLocation">×</button></div>
                <label class="field"><span>Nombre</span><input id="newLocationName" placeholder="Ej. Archivo central"></label>
                <label class="field"><span>Piso / zona</span><input id="newLocationZone" placeholder="Opcional"></label>
                <div class="aw-inline-actions"><button class="btn btn-primary btn-sm" type="button" id="saveLocation">Guardar ubicación</button></div>
              </div>
            </div>

            <!-- Responsable -->
            <div class="field">
              <span>Responsable <b>*</b></span>
              <div class="aw-smart-select" data-smart-select="responsible">
                <div class="aw-smart-trigger">
                  <button type="button" id="responsibleTrigger"><?= View::e($responsibles[0]['nombre'] ?? 'Selecciona un responsable') ?></button><i>⌄</i>
                </div>
                <div class="aw-smart-menu">
                  <div class="aw-smart-menu-search"><label>⌕ <input placeholder="Buscar nombre, cargo o dependencia..." data-smart-search></label></div>
                  <div class="aw-smart-options">
                    <?php foreach ($responsibles as $i => $responsible): ?>
                      <button type="button" class="aw-smart-option <?= $i === 0 ? 'selected' : '' ?>" data-value="<?= (int) $responsible['id'] ?>" data-label="<?= View::e($responsible['nombre']) ?>"><strong><?= View::e($responsible['nombre']) ?></strong><small><?= View::e(trim(($responsible['cargo'] ?: '') . (($responsible['cargo'] && $responsible['dependencia']) ? ' · ' : '') . ($responsible['dependencia'] ?: '')) ?: 'Sin cargo/dependencia registrada') ?></small></button>
                    <?php endforeach; ?>
                    <div class="aw-smart-empty">No se encontraron responsables.</div>
                  </div>
                  <div class="aw-smart-create"><button type="button" data-create-responsible>＋ Registrar nuevo responsable</button></div>
                </div>
              </div>
              <input type="hidden" name="responsible_id" id="responsibleIdInput" value="<?= (int) ($responsibles[0]['id'] ?? '') ?>" required>
              <div class="aw-inline-create" id="responsibleCreate">
                <div class="aw-inline-create-head"><strong>Nuevo responsable</strong><button type="button" id="closeResponsible">×</button></div>
                <label class="field"><span>Nombre completo</span><input id="newResponsibleName" placeholder="Nombre y apellido"></label>
                <label class="field"><span>Cargo</span><input id="newResponsibleCargo" placeholder="Ej. Coordinador de TI"></label>
                <label class="field"><span>Dependencia</span><input id="newResponsibleDependencia" placeholder="Ej. Sistemas"></label>
                <div class="aw-inline-actions"><button class="btn btn-primary btn-sm" type="button" id="saveResponsible">Guardar responsable</button></div>
              </div>
            </div>
          </div>

          <div style="margin-top:13px">
            <span class="field-label">Estado físico <b>*</b></span>
            <div class="state-grid" style="margin-top:7px">
              <?php foreach ($physicalStates as $i => $state): ?>
                <button type="button" class="state-card <?= $i === 0 ? 'selected' : '' ?>" data-state-id="<?= (int) $state['id'] ?>" style="--state-color:<?= View::e($state['color'] ?: '#50617a') ?>">
                  <span><?= $state['es_negativo'] ? '!' : '✓' ?></span><strong><?= View::e($state['nombre']) ?></strong>
                </button>
              <?php endforeach; ?>
            </div>
            <input type="hidden" name="physical_state_id" id="physicalStateIdInput" value="<?= $physicalStates[0]['id'] ?? '' ?>">
          </div>
        </section>

        <!-- PASO 4: REVISIÓN -->
        <section class="wizard-panel" data-wizard-panel="4">
          <div class="aw-panel-head">
            <div class="wizard-copy">
              <small class="aw-eyebrow">PASO 4 DE 4</small>
              <h4>Revisa antes de incorporar</h4>
              <p>La información esencial está lista. Puedes confirmar el registro o volver al paso que necesites corregir.</p>
            </div>
            <span class="aw-hint">Revisión final</span>
          </div>

          <article class="review-card" id="wizardReview">
            <div class="review-icon">▦</div>
            <div class="review-main">
              <h3 data-review="descripcion">—</h3>
              <p data-review="numero">—</p>
              <div class="review-grid">
                <div><span>Tipo</span><strong data-review="tipo">—</strong></div>
                <div><span>Marca</span><strong data-review="marca">—</strong></div>
                <div><span>Modelo</span><strong data-review="modelo">—</strong></div>
                <div><span>Color</span><strong data-review="color">—</strong></div>
                <div><span>Ubicación</span><strong data-review="ubicacion">—</strong></div>
                <div><span>Responsable</span><strong data-review="responsable">—</strong></div>
                <div><span>Estado físico</span><strong data-review="estado">—</strong></div>
              </div>
            </div>
          </article>
        </section>
      </div>

      <footer class="modal-footer wizard-footer">
        <button class="btn btn-ghost" type="button" id="wizardBack">← Atrás</button>
        <div>
          <button class="btn btn-secondary" type="button" id="wizardDraftBtn">Guardar borrador</button>
          <button class="btn btn-secondary hidden" type="button" id="wizardSubmitSimilar">Incorporar y registrar similar</button>
          <button class="btn btn-primary" type="button" id="wizardNextBtn">Continuar →</button>
        </div>
      </footer>
    </form>
  </section>

  <!-- RESUMEN -->
  <aside class="panel aw-summary">
    <div class="panel-header compact">
      <div><span class="panel-kicker">RESUMEN</span><h3>Bien en registro</h3></div>
    </div>
    <div class="aw-summary-body">
      <article class="aw-asset-summary">
        <div class="aw-asset-title">
          <i>▦</i>
          <div>
            <strong id="summaryDescription">Sin descripción</strong>
            <span id="summaryType">—</span>
          </div>
        </div>
        <div class="aw-summary-list">
          <div class="aw-summary-row"><span>Número</span><strong id="summaryNumber">—</strong></div>
          <div class="aw-summary-row"><span>Serial</span><strong id="summarySerial">—</strong></div>
          <div class="aw-summary-row"><span>Marca</span><strong id="summaryBrand">—</strong></div>
          <div class="aw-summary-row"><span>Modelo</span><strong id="summaryModel">—</strong></div>
          <div class="aw-summary-row"><span>Color</span><strong id="summaryColor">—</strong></div>
          <div class="aw-summary-row"><span>Ubicación</span><strong id="summaryLocation">—</strong></div>
          <div class="aw-summary-row"><span>Responsable</span><strong id="summaryResponsible">—</strong></div>
          <div class="aw-summary-row"><span>Estado</span><strong id="summaryState">—</strong></div>
        </div>
        <div class="aw-progress">
          <div class="aw-progress-top"><span>Progreso del registro</span><strong id="progressText">25%</strong></div>
          <div class="progress-bar"><b id="progressBar" style="width:25%"></b></div>
        </div>
      </article>
      <div class="aw-summary-help">Los elementos del resumen son únicamente informativos. Los cambios siempre se realizan desde el paso correspondiente del wizard.</div>
    </div>
  </aside>
</div>

<script type="module" src="/assets/js/modules/assetWizardPage.js"></script>
