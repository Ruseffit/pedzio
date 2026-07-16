<?php
/**
 * Verifica que exista una sesión de usuario autenticado.
 * Debe incluirse en TODA página que requiera login, después de config/session.php.
 */

if (empty($_SESSION['usuario_id'])) {
    flashSet('error', 'Debes iniciar sesión para continuar.');
    redirigir('/login.php');
}
