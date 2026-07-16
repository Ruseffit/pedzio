<?php
/**
 * Funciones de utilidad general usadas en toda la aplicación.
 */

function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

function redirigir(string $ruta): never
{
    header("Location: {$ruta}");
    exit;
}

function formatearMoneda(float $monto): string
{
    return 'S/ ' . number_format($monto, 2);
}

function formatearFecha(string $fecha, string $formato = 'd/m/Y H:i'): string
{
    $dt = date_create($fecha);
    return $dt ? date_format($dt, $formato) : $fecha;
}

/**
 * Genera un nombre de archivo único y no adivinable para uploads públicos
 * (ej. fotos de perfil o de productos).
 */
function nombreArchivoSeguro(string $extension): string
{
    $extension = strtolower(ltrim($extension, '.'));
    return bin2hex(random_bytes(16)) . '.' . $extension;
}

function validarEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function esRolValido(string $rol): bool
{
    return in_array($rol, PEDZIO_ROLES, true);
}

/**
 * Convierte una ruta relativa de uploads (ej. "/uploads/perfiles/x.jpg") en una
 * URL absoluta usando el esquema y host de la petición actual. Así funciona
 * igual sin importar el puerto en el que corra el backend (8000 en dev, 80/443
 * detrás de Apache en producción) sin tener que hardcodear APP_URL.
 */
function pedzio_url_absoluta(?string $rutaRelativa): ?string
{
    if (!$rutaRelativa) {
        return null;
    }
    if (str_starts_with($rutaRelativa, 'http://') || str_starts_with($rutaRelativa, 'https://')) {
        return $rutaRelativa; // ya es absoluta
    }
    $esquema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return "{$esquema}://{$host}{$rutaRelativa}";
}

/**
 * Mapa único de navegación por rol (ruta => [etiqueta, icono]).
 * Centralizado aquí para que navbar.php (roles sin sidebar) y el sidebar
 * de panel (emprendedor/superadmin) nunca queden desincronizados.
 */
function pedzio_enlaces_rol(?string $rol): array
{
    $mapa = [
        'cliente' => [
            '/cliente/dashboard.php'   => ['etiqueta' => 'Panel', 'icono' => '🏠'],
            '/cliente/catalogo.php'    => ['etiqueta' => 'Catálogo', 'icono' => '🍽️'],
            '/cliente/carrito.php'     => ['etiqueta' => 'Carrito', 'icono' => '🛒'],
            '/cliente/mis_pedidos.php' => ['etiqueta' => 'Mis pedidos', 'icono' => '📦'],
            '/cliente/perfil.php'      => ['etiqueta' => 'Perfil', 'icono' => '👤'],
        ],
        'emprendedor' => [
            '/emprendedor/dashboard.php' => ['etiqueta' => 'Panel Control', 'icono' => '📊'],
            '/emprendedor/productos.php' => ['etiqueta' => 'Productos', 'icono' => '🍽️'],
            '/emprendedor/categorias.php' => ['etiqueta' => 'Categorías', 'icono' => '🏷️'],
            '/emprendedor/pedidos.php'   => ['etiqueta' => 'Pedidos', 'icono' => '📦'],
            '/emprendedor/finanzas.php'  => ['etiqueta' => 'Finanzas', 'icono' => '💰'],
            '/emprendedor/reportes.php'  => ['etiqueta' => 'Reportes', 'icono' => '📈'],
        ],
        'superadmin' => [
            '/superadmin/dashboard.php'        => ['etiqueta' => 'Métricas Globales', 'icono' => '📊'],
            '/superadmin/usuarios.php'         => ['etiqueta' => 'Usuarios', 'icono' => '👥'],
            '/superadmin/emprendimientos.php'  => ['etiqueta' => 'Emprendimientos', 'icono' => '🏪'],
            '/superadmin/pedidos_global.php'   => ['etiqueta' => 'Pedidos Totales', 'icono' => '📦'],
            '/superadmin/finanzas_global.php'  => ['etiqueta' => 'Auditoría Financiera', 'icono' => '💰'],
            '/superadmin/reportes_global.php'  => ['etiqueta' => 'Reportes', 'icono' => '📈'],
        ],
    ];

    return $mapa[$rol] ?? [];
}
