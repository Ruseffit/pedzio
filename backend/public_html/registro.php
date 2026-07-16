<?php
require_once __DIR__ . '/_bootstrap.php';

use Pedzio\Models\Usuario;
use Pedzio\Models\Emprendimiento;
use Pedzio\Controllers\UsuarioController;

if (!empty($_SESSION['usuario_id'])) {
    redirigir('/index.php');
}

$pdo = obtenerConexion();
$usuarioModelo = new Usuario($pdo);
$controlador = new UsuarioController($usuarioModelo);

$errores = [];
$rolInicial = in_array($_GET['rol'] ?? '', ['cliente', 'emprendedor'], true) ? $_GET['rol'] : 'cliente';
$valores = ['nombre' => '', 'email' => '', 'telefono' => '', 'rol' => $rolInicial, 'nombre_negocio' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!pedzio_csrf_valido($_POST['_csrf'] ?? null)) {
        $errores[] = 'Sesión expirada, por favor intenta de nuevo.';
    } else {
        $valores['nombre'] = trim($_POST['nombre'] ?? '');
        $valores['email'] = trim($_POST['email'] ?? '');
        $valores['telefono'] = trim($_POST['telefono'] ?? '');
        $valores['rol'] = $_POST['rol'] ?? 'cliente';
        $valores['nombre_negocio'] = trim($_POST['nombre_negocio'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($valores['rol'] === 'emprendedor' && empty($valores['nombre_negocio'])) {
            $errores[] = 'El nombre del negocio es obligatorio para cuentas de emprendedor.';
        }

        if (empty($errores)) {
            $resultado = $controlador->registrar(
                $valores['nombre'],
                $valores['email'],
                $password,
                $valores['rol'],
                $valores['telefono'] ?: null
            );

            if ($resultado['ok']) {
                if ($valores['rol'] === 'emprendedor') {
                    $emprendimientoModelo = new Emprendimiento($pdo);
                    $emprendimientoModelo->crear($resultado['usuario_id'], $valores['nombre_negocio']);
                }
                flashSet('exito', 'Cuenta creada correctamente. Ya puedes iniciar sesión.');
                redirigir('/login.php');
            } else {
                $errores = $resultado['errores'];
            }
        }
    }
}

$tituloPagina = 'Crear cuenta';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<main class="pedzio-auth">
    <h1>Crear cuenta en Pedzio</h1>

    <?php foreach ($errores as $error): ?>
        <div class="pedzio-flash pedzio-flash--error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post" action="/registro.php" class="pedzio-form">
        <input type="hidden" name="_csrf" value="<?= e(pedzio_csrf_token()) ?>">

        <label for="rol">Quiero registrarme como</label>
        <select name="rol" id="rol">
            <option value="cliente" <?= $valores['rol'] === 'cliente' ? 'selected' : '' ?>>Cliente</option>
            <option value="emprendedor" <?= $valores['rol'] === 'emprendedor' ? 'selected' : '' ?>>Emprendedor</option>
        </select>

        <label for="nombre">Nombre completo</label>
        <input type="text" id="nombre" name="nombre" value="<?= e($valores['nombre']) ?>" required>

        <div id="campo-negocio">
            <label for="nombre_negocio">Nombre de tu negocio (solo emprendedores)</label>
            <input type="text" id="nombre_negocio" name="nombre_negocio" value="<?= e($valores['nombre_negocio']) ?>">
        </div>

        <label for="email">Correo electrónico</label>
        <input type="email" id="email" name="email" value="<?= e($valores['email']) ?>" required>

        <label for="telefono">Teléfono</label>
        <input type="text" id="telefono" name="telefono" value="<?= e($valores['telefono']) ?>">

        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required>
        <small>Mínimo 8 caracteres, con letras y números.</small>

        <button type="submit" class="pedzio-btn pedzio-btn--primario">Crear cuenta</button>
    </form>

    <p>¿Ya tienes cuenta? <a href="/login.php">Inicia sesión aquí</a></p>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
