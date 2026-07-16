<?php
/**
 * Sistema simple de mensajes flash (éxito/error) guardados un solo ciclo en sesión.
 */

function flashSet(string $tipo, string $mensaje): void
{
    $_SESSION['_flash'][$tipo] = $mensaje;
}

function flashGetAll(): array
{
    $mensajes = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $mensajes;
}
