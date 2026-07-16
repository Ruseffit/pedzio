<?php
require_once __DIR__ . '/_bootstrap.php';

use Pedzio\Models\Usuario;
use Pedzio\Controllers\UsuarioController;

if (!empty($_SESSION['usuario_id'])) {
    redirigir('/index.php');
}

$pdo = obtenerConexion();
$controlador = new UsuarioController(new Usuario($pdo));

$errores = [];
$emailValor = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!pedzio_csrf_valido($_POST['_csrf'] ?? null)) {
        $errores[] = 'Sesión expirada, por favor intenta de nuevo.';
    } else {
        $emailValor = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $usuario = $controlador->iniciarSesion($emailValor, $password);

        if ($usuario) {
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = (int) $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_rol'] = $usuario['rol'];

            $destinos = [
                'cliente'     => '/cliente/dashboard.php',
                'emprendedor' => '/emprendedor/dashboard.php',
                'superadmin'  => '/superadmin/dashboard.php',
            ];
            redirigir($destinos[$usuario['rol']] ?? '/index.php');
        } else {
            $errores[] = 'Correo o contraseña incorrectos.';
        }
    }
}

$tituloPagina = 'Iniciar sesión';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<main class="pedzio-auth">
    <h1>Iniciar sesión</h1>

    <?php foreach ($errores as $error): ?>
        <div class="pedzio-flash pedzio-flash--error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post" action="/login.php" class="pedzio-form">
        <input type="hidden" name="_csrf" value="<?= e(pedzio_csrf_token()) ?>">

        <label for="email">Correo electrónico</label>
        <input type="email" id="email" name="email" value="<?= e($emailValor) ?>" required autofocus>

        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required>

        <button type="submit" class="pedzio-btn pedzio-btn--primario">Entrar</button>
    </form>

    <p>¿No tienes cuenta? <a href="/registro.php">Regístrate aquí</a></p>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
