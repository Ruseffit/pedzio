<?php
$rolNav = $_SESSION['usuario_rol'] ?? null;
$nombreNav = $_SESSION['usuario_nombre'] ?? '';
$enlacesNav = pedzio_enlaces_rol($rolNav);
?>
<nav class="pedzio-navbar">
    <a class="pedzio-navbar__marca" href="/index.php">
        <img src="/assets/img/logo.png" alt="Pedzio">
    </a>
    <?php if ($rolNav === 'cliente'): ?>
        <ul class="pedzio-navbar__enlaces">
            <?php foreach ($enlacesNav as $ruta => $info): ?>
                <li><a href="<?= e($ruta) ?>"><?= e($info['icono']) ?> <?= e($info['etiqueta']) ?></a></li>
            <?php endforeach; ?>
        </ul>
        <div class="pedzio-navbar__usuario">
            <span><?= e($nombreNav) ?></span>
            <a href="/logout.php">Cerrar sesión</a>
        </div>
    <?php elseif (!$rolNav): ?>
        <div class="pedzio-navbar__usuario">
            <a class="pedzio-navbar__enlace-login" href="/login.php">Iniciar sesión</a>
            <a class="pedzio-btn pedzio-btn--primario pedzio-btn--chico" href="/registro.php">Crear cuenta</a>
        </div>
    <?php endif; ?>
</nav>
