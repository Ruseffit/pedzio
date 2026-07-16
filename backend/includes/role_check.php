<?php
/**
 * Restringe el acceso a un rol específico.
 * Uso: define $rolesPermitidos antes de incluir este archivo, ej:
 *   $rolesPermitidos = ['emprendedor'];
 *   require_once __DIR__ . '/../../includes/role_check.php';
 *
 * Requiere que auth_check.php ya se haya incluido antes.
 */

if (!isset($rolesPermitidos) || !is_array($rolesPermitidos)) {
    // Falla segura: si el desarrollador olvidó definir el rol permitido, se bloquea todo.
    http_response_code(403);
    die('Acceso no configurado correctamente para esta página.');
}

$rolActual = $_SESSION['usuario_rol'] ?? null;

if (!$rolActual || !in_array($rolActual, $rolesPermitidos, true)) {
    http_response_code(403);
    flashSet('error', 'No tienes permiso para acceder a esta sección.');

    // Redirige a su propio panel según su rol real, en vez de dejarlo en un 403 seco.
    $destinos = [
        'cliente'      => '/cliente/dashboard.php',
        'emprendedor'  => '/emprendedor/dashboard.php',
        'superadmin'   => '/superadmin/dashboard.php',
    ];
    redirigir($destinos[$rolActual] ?? '/login.php');
}
