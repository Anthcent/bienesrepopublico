<?php
/** @var int $vencidos */
$vencidos = $vencidos ?? 0;
?>
<div class="operation-dock" id="operationDock">
  <a class="dock-overdue <?= $vencidos > 0 ? 'has-overdue' : '' ?>" href="/prestamos?estado=VENCIDO" aria-label="Préstamos vencidos">
    <svg viewBox="0 0 24 24"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></svg>
    <span class="dock-overdue-count"><?= (int) $vencidos ?></span>
    <span class="dock-overdue-label"><?= $vencidos === 1 ? 'vencido' : 'vencidos' ?></span>
  </a>
  <button class="dock-main" id="dockMain" type="button" aria-haspopup="true" aria-expanded="false">
    <span>＋</span><span>Nueva operación</span>
  </button>
  <button class="dock-bell" id="dockBell" type="button" aria-label="Notificaciones">
    <svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
    <span class="notification-count" style="display:none">0</span>
  </button>

  <div class="operation-menu" id="operationMenu">
    <small>OPERACIÓN RÁPIDA</small>
    <div>
      <button type="button" data-dock-action="asset"><i>＋</i><span><strong>Incorporar bien</strong><small>Registro asistido</small></span></button>
      <button type="button" data-dock-action="loan"><i>↗</i><span><strong>Prestar bien</strong><small>Salida temporal</small></span></button>
      <a href="/prestamos"><i>✓</i><span><strong>Registrar devolución</strong><small>Total o parcial</small></span></a>
      <a href="/inventario?estado_administrativo=ACTIVO&amp;disponibilidad=DISPONIBLE"><i>⇄</i><span><strong>Reasignar</strong><small>Ubicación o custodio</small></span></a>
      <a href="/inventario?estado_administrativo=ACTIVO"><i>−</i><span><strong>Desincorporar</strong><small>Retirar del inventario activo</small></span></a>
      <a href="/inventario?estado_administrativo=DESINCORPORADO"><i>↶</i><span><strong>Readmitir</strong><small>Volver a incorporar</small></span></a>
      <button type="button" data-dock-action="verification"><i>☑</i><span><strong>Crear jornada de verificación</strong><small>Reporte con checks</small></span></button>
      <a href="/reportes"><i>▥</i><span><strong>Generar reporte</strong><small>Plantillas listas</small></span></a>
    </div>
  </div>
</div>
