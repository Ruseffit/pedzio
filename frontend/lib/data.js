export const productos = [
  { id: 1, nombre: 'Pizza Pepperoni', tienda: 'Pizzería Don Mario', emoji: '🍕', precio: 32.0, categoria: 'Comida', rating: 4.6 },
  { id: 2, nombre: 'Cappuccino', tienda: 'Café del Barrio', emoji: '☕', precio: 9.5, categoria: 'Cafetería', rating: 4.7 },
  { id: 3, nombre: 'Brownie con Helado', tienda: 'Dulce Tentación', emoji: '🍫', precio: 16.0, categoria: 'Postres', rating: 4.8 },
  { id: 4, nombre: 'Combo Clásico', tienda: 'Burger House', emoji: '🍔', precio: 25.9, categoria: 'Comida', rating: 4.5 },
  { id: 5, nombre: 'Cheesecake de Fresa', tienda: 'Dulce Tentación', emoji: '🍰', precio: 15.0, categoria: 'Postres', rating: 4.6 },
  { id: 6, nombre: 'Latte Vainilla', tienda: 'Café del Barrio', emoji: '☕', precio: 9.8, categoria: 'Cafetería', rating: 4.7 },
  { id: 7, nombre: 'Bouquet Rosas', tienda: 'Florería Lulú', emoji: '💐', precio: 45.0, categoria: 'Regalos', rating: 4.9 },
  { id: 8, nombre: 'Jugo Natural', tienda: 'Frutas del Valle', emoji: '🍊', precio: 7.0, categoria: 'Bebidas', rating: 4.5 },
];

export const categorias = [
  { icon: '🍽️', name: 'Comida' },
  { icon: '🍰', name: 'Postres' },
  { icon: '☕', name: 'Cafetería' },
  { icon: '🛍️', name: 'Tiendas' },
  { icon: '🎁', name: 'Regalos' },
  { icon: '🥤', name: 'Bebidas' },
];

export const negociosDestacados = [
  { nombre: 'Donas Mary', categoria: 'Postres', emoji: '🍩', rating: 4.8 },
  { nombre: 'Burger House', categoria: 'Comida', emoji: '🍔', rating: 4.6 },
  { nombre: 'Café del Barrio', categoria: 'Cafetería', emoji: '☕', rating: 4.7 },
  { nombre: 'Florería Lulú', categoria: 'Regalos', emoji: '💐', rating: 4.9 },
];

export const pedidosCliente = [
  { id: '#1025', negocio: 'Burger House', total: 35.9, estado: 'Entregado', fecha: '12/05/2026' },
  { id: '#1024', negocio: 'Dulce Tentación', total: 26.0, estado: 'En camino', fecha: '12/05/2026' },
  { id: '#1023', negocio: 'Café del Barrio', total: 18.5, estado: 'En preparación', fecha: '11/05/2026' },
  { id: '#1022', negocio: 'Pizzería Don Mario', total: 42.0, estado: 'Confirmado', fecha: '11/05/2026' },
  { id: '#1021', negocio: 'Frutas del Valle', total: 14.0, estado: 'Cancelado', fecha: '10/05/2026' },
];

export const pedidosEmprendedor = [
  { id: '#1025', cliente: 'María López', total: 35.9, estado: 'En preparación', fecha: '12/05/2026' },
  { id: '#1024', cliente: 'Pedro Ramírez', total: 26.0, estado: 'Confirmado', fecha: '12/05/2026' },
  { id: '#1023', cliente: 'Lucía Torres', total: 41.5, estado: 'En camino', fecha: '12/05/2026' },
  { id: '#1022', cliente: 'Ana Gómez', total: 18.0, estado: 'Entregado', fecha: '11/05/2026' },
];

export const estadoBadgeClass = {
  Entregado: 'badge-green',
  'En camino': 'badge-orange',
  'En preparación': 'badge-yellow',
  Confirmado: 'badge-blue',
  Cancelado: 'badge-red',
  Pendiente: 'badge-yellow',
};
