<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$rolesPermitidos = ['cliente'];
require_once __DIR__ . '/../../includes/role_check.php';

use Pedzio\Models\Usuario;

$pdo = obtenerConexion();
$usuario = (new Usuario($pdo))->buscarPorId((int) $_SESSION['usuario_id']);

$tituloPagina = 'Mi perfil';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>
<main class="pedzio-perfil">
    <h1>Mi perfil</h1>
    <p><strong>Nombre:</strong> <?= e($usuario['nombre']) ?></p>
    <p><strong>Correo:</strong> <?= e($usuario['email']) ?></p>
    <p><strong>Teléfono:</strong> <?= e($usuario['telefono'] ?? 'No registrado') ?></p>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
