# Manual técnico — Pedzio

## 1. Arquitectura

Pedzio usa una arquitectura simple en capas, sin framework:

- **`public_html/`**: capa de presentación/enrutamiento. Cada archivo `.php` orquesta la petición: valida sesión/rol, llama a un Controller, y renderiza HTML directamente (no hay motor de plantillas separado por simplicidad, pero sí separación de responsabilidades).
- **`src/Controllers/`**: reciben datos ya saneados desde `public_html/`, aplican validaciones de negocio y llaman a Models/Services.
- **`src/Models/`**: única capa que habla con MySQL vía PDO. Ningún Controller ejecuta SQL directo.
- **`src/Services/`**: lógica reutilizable que no pertenece a una sola entidad (carrito en sesión, cálculos financieros, reportes agregados).
- **`src/Helpers/`**: funciones puras de validación y formato, sin estado ni dependencia de base de datos.
- **`config/`**: `.env`, conexión PDO, constantes, sesión — nunca expuestos a `public_html/`.
- **`includes/`**: fragmentos de vista reutilizables (header, navbar, footer) y guardias de seguridad (`auth_check.php`, `role_check.php`).

## 2. Roles del sistema

| Rol | Alcance |
|---|---|
| Cliente | Navega catálogo, arma carrito, confirma pedidos, ve su historial. |
| Emprendedor | Gestiona su propio catálogo, pedidos recibidos, finanzas y reportes de su negocio (relación 1:1 con `emprendimientos`). |
| SuperAdmin | Supervisa usuarios, emprendimientos y pedidos de toda la plataforma. |

## 3. Seguridad implementada

- **Contraseñas**: `password_hash()` (bcrypt), nunca texto plano.
- **CSRF**: token de sesión validado en todo formulario POST (`pedzio_csrf_token()` / `pedzio_csrf_valido()`).
- **Control de acceso por rol**: `includes/role_check.php` bloquea cualquier página fuera del rol de sesión activo.
- **Control de acceso por dato**: los Models filtran siempre por `emprendimiento_id` del dueño de sesión (nunca solo por `id` del recurso), evitando que un emprendedor edite datos de otro.
- **SQL**: 100% mediante PDO con prepared statements (`:parametros` nombrados). Cero concatenación de SQL con datos del usuario.
- **Uploads**: `uploads/comprobantes/` bloqueado por `.htaccess` (`Require all denied`); solo accesible mediante un script PHP futuro que valide sesión, rol y propiedad del pedido antes de servir el archivo.
- **Sesión**: cookies `httponly`, `samesite=Lax`, `secure` automático si se detecta HTTPS, regeneración periódica de ID de sesión.

## 4. Base de datos

Ver `sql/schema.sql` para el DDL completo y `sql/seed.sql` para datos de prueba.

Tablas: `usuarios`, `emprendimientos` (1:1 con usuarios rol=emprendedor), `categorias`, `productos`, `pedidos`, `detalle_pedidos`, `movimientos_financieros`.

## 5. Autoload

No se usa Composer real por simplicidad de hosting compartido: `config/app.php` registra un autoloader PSR-4 manual que mapea `Pedzio\Algo\Clase` → `src/Algo/Clase.php`. El `composer.json` incluido documenta la intención PSR-4 por si en el futuro se instala Composer.

## 6. Variables de entorno

Ver `.env.example`. Copiar como `.env` en el servidor real con las credenciales del hosting. `config/env.php` lo carga sin dependencias externas.

## 7. Sistema de layout (rediseño visual)

Desde el rediseño de marca, existen dos sistemas de layout independientes:

- **`includes/header.php` + `includes/navbar.php`**: usado por páginas públicas (`index.php`, `login.php`, `registro.php`) y por el rol **cliente**. Barra superior simple con logo y enlaces.
- **`includes/header.php` + `includes/app_header.php` + `includes/app_footer.php`**: usado por los roles **emprendedor** y **superadmin**. Sidebar fijo con navegación por rol (generada por `pedzio_enlaces_rol()` en `includes/functions.php`), topbar con avatar/nombre/cerrar sesión, y tarjetas KPI (`.pedzio-kpi-row`).

`includes/sidebar.php` se conserva vacío por compatibilidad con la estructura de carpetas acordada, pero no se usa: su función quedó absorbida por `app_header.php`.

**Nunca mezclar ambos sistemas en una misma página** (no incluir `navbar.php` y `app_header.php` juntos): cada página de `emprendedor/` y `superadmin/` debe usar únicamente `app_header.php` + `app_footer.php`.

### Dependencia externa: Chart.js

Los gráficos de `superadmin/dashboard.php`, `superadmin/reportes_global.php` y `emprendedor/reportes.php` usan [Chart.js](https://www.chartjs.org/) cargado vía CDN (`cdnjs.cloudflare.com`) en `includes/header.php`. Esto requiere que el servidor de hosting tenga salida a internet habilitada en el navegador del usuario final (no en el servidor) — es una dependencia del lado del cliente, no afecta el hosting compartido en sí. Si el hosting bloquea CDNs externos por política, se puede descargar `chart.umd.min.js` y servirlo desde `assets/js/` en su lugar.

### Logo e ilustración de marca

`public_html/assets/img/logo.png` es el logo oficial de Pedzio. `public_html/assets/img/hero-ilustracion.svg` es una ilustración original creada para este proyecto (no una fotografía con derechos de terceros) usada en el hero de `index.php`. Si se cuenta con fotografía profesional propia de los productos, puede reemplazarse el `<?php readfile(...) ?>` en `index.php` por un `<img>` a la foto real.
