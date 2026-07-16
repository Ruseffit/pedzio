# Pedzio — Next.js App Router

Conversión de la maqueta HTML de Pedzio a una aplicación Next.js 14 con App Router,
usando Tailwind CSS, Context API y localStorage.

## Cómo correrlo

```bash
npm install
npm run dev
```

Abre http://localhost:3000

## Estructura de rutas

- `/` — Landing
- `/login` — Iniciar sesión
- `/register` — Crear cuenta
- `/cliente` — Dashboard cliente (grupo con sidebar compartido)
- `/cliente/catalogo` — Catálogo de productos (búsqueda + filtro por categoría)
- `/cliente/carrito` — Carrito de compras
- `/cliente/pedidos` — Mis pedidos (con tabs de filtro)
- `/cliente/perfil` — Perfil y cambio de contraseña
- `/emprendedor` — Dashboard emprendedor
- `/admin` — Dashboard administrador

## Arquitectura

- **`app/cliente/layout.jsx`**, **`app/emprendedor/layout.jsx`**, **`app/admin/layout.jsx`**:
  layouts de cada grupo de rutas, cada uno renderiza el `Sidebar` con los items de ese rol.
  Al compartir el mismo componente `Sidebar` pero pasarle distintos `items`, no se duplica
  el markup del sidebar entre páginas de un mismo rol.
- **`components/CartContext.jsx`**: Context API + `useReducer`-like state con `useState`,
  persistido en `localStorage` bajo la key `pedzio_cart`. Expone `agregarAlCarrito`,
  `cambiarQty`, `eliminarItem`, `vaciarCarrito`, `totalItems`, `subtotal`, `total`.
- **`components/ToastContext.jsx`** / **`ModalContext.jsx`**: recrean el toast y el modal
  de confirmación globales de la maqueta original, ahora como contexts de React en vez de
  funciones globales `toast()` / `showModal()`.
- **`lib/data.js`**: datos mock (productos, pedidos, negocios) que en una app real vendrían
  de una API.

## Notas

- El diseño visual (colores, tipografía, espaciados, componentes) se mantiene con Tailwind,
  usando los mismos tokens de color que la maqueta original (`--orange`, `--dark`, etc.)
  definidos en `tailwind.config.js`.
- Los íconos siguen siendo emojis, igual que en la maqueta, para no depender de una librería
  de íconos externa y mantener el look idéntico.
- Este proyecto no incluye backend: el login/registro simulan una llamada (con `setTimeout`)
  y redirigen a la ruta correspondiente, igual que hacía el JS de la maqueta original.
