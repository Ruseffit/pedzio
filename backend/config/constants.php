<?php
/**
 * Constantes globales del sistema Pedzio.
 */

define('PEDZIO_ROLES', ['cliente', 'emprendedor', 'superadmin']);

define('PEDZIO_ESTADOS_PEDIDO', [
    'pendiente', 'confirmado', 'en_preparacion', 'en_camino', 'entregado', 'cancelado'
]);

define('PEDZIO_COLOR_PRIMARIO', '#FF6B2B');   // naranja de marca
define('PEDZIO_COLOR_SECUNDARIO', '#1A2E4A'); // azul marino de marca

define('PEDZIO_RUTA_UPLOADS_PRODUCTOS', __DIR__ . '/../public_html/uploads/productos');
define('PEDZIO_RUTA_UPLOADS_PERFILES', __DIR__ . '/../public_html/uploads/perfiles');
define('PEDZIO_RUTA_UPLOADS_COMPROBANTES', __DIR__ . '/../public_html/uploads/comprobantes');

define('PEDZIO_UPLOADS_MAX_BYTES', (int) (env('UPLOADS_MAX_MB', 5)) * 1024 * 1024);
define('PEDZIO_EXTENSIONES_IMAGEN_PERMITIDAS', ['jpg', 'jpeg', 'png', 'webp']);
