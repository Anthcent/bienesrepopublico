<?php
use App\Core\View;
use App\Repositories\CatalogRepository;

$catalogs = new CatalogRepository();
$locations = $catalogs->all('locations');
$physicalStates = $catalogs->all('physical_states', false);
?>
<div class="drawer-backdrop" id="filterBackdrop"></div>
<aside class="drawer filter-drawer" id="filterDrawer">
  <div class="drawer-header">
    <div><span class="panel-kicker">FILTROS</span><h3>Refinar inventario</h3></div>
    <button class="icon-btn close-drawer" data-close="filterDrawer">×</button>
  </div>
  <form class="drawer-body" method="get" action="/inventario" id="filterForm">
    <label class="field">
      <span>Estado administrativo</span>
      <select name="estado_administrativo">
        <option value="">Todos</option>
        <option value="ACTIVO">Activo</option>
        <option value="DESINCORPORADO">Desincorporado</option>
      </select>
    </label>
    <label class="field">
      <span>Disponibilidad</span>
      <select name="disponibilidad">
        <option value="">Todos</option>
        <option value="DISPONIBLE">Disponible</option>
        <option value="PRESTADO">Prestado</option>
      </select>
    </label>
    <label class="field">
      <span>Estado físico</span>
      <select name="physical_state_id">
        <option value="">Todos</option>
        <?php foreach ($physicalStates as $state): ?>
          <option value="<?= (int) $state['id'] ?>"><?= View::e($state['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="field">
      <span>Ubicación</span>
      <select name="location_id">
        <option value="">Todas</option>
        <?php foreach ($locations as $location): ?>
          <option value="<?= (int) $location['id'] ?>"><?= View::e($location['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </form>
  <div class="drawer-footer">
    <button type="button" class="btn btn-ghost" onclick="document.getElementById('filterForm').reset()">Limpiar</button>
    <button type="submit" form="filterForm" class="btn btn-primary">Aplicar filtros</button>
  </div>
</aside>
