/**
 * Refresca el contenido de la página actual sin navegar: vuelve a pedir la
 * misma URL, toma el `.page` del HTML devuelto y reemplaza el `.page` actual.
 * Útil para fichas de un solo registro con muchas secciones interdependientes
 * (préstamo, bien, jornada de verificación) donde parchear cada pieza a mano
 * es más frágil que dejar que el servidor siga siendo la fuente de verdad.
 *
 * Además de `afterSwap` (callbacks propios de quien llama), siempre se invoca
 * `window.__pageBindPage` si la página actual lo definió. Esto cubre el caso
 * de los wizards globales (Incorporar bien/Registrar préstamo): pueden
 * disparar un refresco estando parados en cualquier página (dashboard,
 * inventario, préstamos), y no conocen los bindings propios de esa página
 * (buscador, drawer de filtros, menú ⋮ de cada fila, etc.).
 *
 * `url` es opcional: por defecto se vuelve a pedir la página actual, pero se
 * puede pasar otra URL de la misma vista (p. ej. `/inventario?estado=...`)
 * para aplicar filtros sin navegar — en ese caso también se actualiza la
 * barra de direcciones con `history.pushState` para que la URL siga
 * reflejando los filtros activos (compartir el enlace, recargar, "atrás").
 */
export async function refreshPageContent(afterSwap, url) {
  const res = await fetch(url || location.href, { credentials: 'same-origin' });
  const html = await res.text();
  const parsed = new DOMParser().parseFromString(html, 'text/html');
  const freshPage = parsed.querySelector('.page');
  const currentPage = document.querySelector('.page');
  if (!freshPage || !currentPage) return false;
  currentPage.innerHTML = freshPage.innerHTML;
  if (url) history.pushState(null, '', url);
  afterSwap?.();
  window.__pageBindPage?.();
  return true;
}
