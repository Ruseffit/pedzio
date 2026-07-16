<?php
require_once __DIR__ . '/_bootstrap.php';

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $parametros = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $parametros['path'], $parametros['domain'], $parametros['secure'], $parametros['httponly']
    );
}

session_destroy();
redirigir('/login.php');
