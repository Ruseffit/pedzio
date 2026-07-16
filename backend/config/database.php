<?php
/**
 * Conexión PDO a MySQL/MariaDB.
 * Uso: $pdo = obtenerConexion();
 */

require_once __DIR__ . '/env.php';

function obtenerConexion(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = env('DB_HOST', 'localhost');
    $puerto = env('DB_PORT', '3306');
    $nombreBD = env('DB_NAME', 'pedzio_db');
    $usuario = env('DB_USER', 'root');
    $clave = env('DB_PASS', '');

    $dsn = "mysql:host={$host};port={$puerto};dbname={$nombreBD};charset=utf8mb4";

    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $usuario, $clave, $opciones);
    } catch (PDOException $e) {
        // No se expone el detalle real del error en producción (evita fuga de credenciales).
        if (env('APP_DEBUG', false)) {
            die('Error de conexión a la base de datos: ' . $e->getMessage());
        }
        die('No se pudo conectar a la base de datos. Intenta más tarde.');
    }

    return $pdo;
}
