/**
 * lib/categorias.js
 *
 * Taxonomía de categorías usada para navegación/filtrado (íconos + nombres).
 * No es "data mock de negocios": no representa ningún emprendimiento ni
 * producto falso, es solo el menú de categorías que ya se usa para filtrar
 * en /cliente/catalogo?categoria=...
 *
 * La tabla `emprendimientos` todavía no tiene una columna `categoria` propia
 * (ver backend/sql/schema.sql), así que igual que en
 * app/cliente/catalogo/page.jsx, la categoría de un emprendimiento real se
 * aproxima comparando palabras clave contra su nombre_negocio + descripcion.
 * Si más adelante el backend agrega esa columna, `inferirCategoria` deja de
 * hacer falta y se puede usar `emprendimiento.categoria` directamente.
 */

export const CATEGORIAS = [
  { icon: '🍽️', name: 'Comida' },
  { icon: '🍰', name: 'Postres' },
  { icon: '☕', name: 'Cafetería' },
  { icon: '🛍️', name: 'Tiendas' },
  { icon: '🎁', name: 'Regalos' },
  { icon: '🥤', name: 'Bebidas' },
];

const PALABRAS_POR_CATEGORIA = {
  'Comida':     ['comida', 'restaurante', 'burger', 'hamburguesa', 'pollo', 'parrilla', 'parrillas', 'lomo', 'menú', 'menu', 'plato', 'sazón', 'sazon', 'sabores', 'cocina'],
  'Postres':    ['postre', 'dulce', 'dulces', 'torta', 'tortas', 'pastel', 'pasteles', 'brownie', 'cheesecake', 'chocolate', 'tentación', 'tentacion', 'repostería', 'reposteria'],
  'Cafetería':  ['café', 'cafe', 'cafetería', 'cafeteria', 'cappuccino', 'latte', 'espresso', 'barrio'],
  'Tiendas':    ['tienda', 'tiendas', 'bazar', 'market', 'abarrotes', 'boutique', 'minimarket'],
  'Regalos':    ['regalo', 'regalos', 'flor', 'flores', 'florería', 'floreria', 'bouquet', 'detalle', 'detalles'],
  'Bebidas':    ['bebida', 'bebidas', 'jugo', 'jugos', 'fruta', 'frutas', 'smoothie', 'refresco', 'zumo'],
};

/**
 * Aproxima la categoría de un emprendimiento real a partir de su nombre y
 * descripción. Devuelve el primer nombre de categoría que coincide, o
 * `null` si no hay coincidencias (el emprendimiento simplemente no muestra
 * etiqueta de categoría, en vez de inventar una).
 * @param {{ nombre_negocio?: string, descripcion?: string }} emprendimiento
 * @returns {string|null}
 */
export function inferirCategoria(emprendimiento) {
  const texto = `${emprendimiento?.nombre_negocio ?? ''} ${emprendimiento?.descripcion ?? ''}`.toLowerCase();

  for (const categoria of CATEGORIAS) {
    const palabras = PALABRAS_POR_CATEGORIA[categoria.name];
    if (palabras?.some((palabra) => texto.includes(palabra))) {
      return categoria.name;
    }
  }
  return null;
}
