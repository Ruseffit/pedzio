<?php
/**
 * Apertura del layout de panel interno (sidebar + topbar) para páginas de
 * emprendedor y superadmin. Requiere que la página defina antes de incluir esto:
 *   $tituloPagina    (string) - título mostrado en el topbar
 *   $itemActivo      (string) - ruta exacta del ítem de sidebar a resaltar
 *   $eyebrowPagina   (string, opcional) - texto pequeño sobre el título
 *
 * Debe incluirse DESPUÉS de includes/header.php (que ya abrió <body> y mostró flashes).
 */

$rolApp = $_SESSION['usuario_rol'] ?? null;
$nombreApp = $_SESSION['usuario_nombre'] ?? '';
$enlacesApp = pedzio_enlaces_rol($rolApp);
$itemActivo = $itemActivo ?? '';
$inicialAvatar = '?';
if ($nombreApp !== '') {
    $inicialAvatar = function_exists('mb_strtoupper')
        ? mb_strtoupper(mb_substr($nombreApp, 0, 1))
        : strtoupper(substr($nombreApp, 0, 1));
}
?>
<div class="pedzio-app">
    <aside class="pedzio-app__sidebar">
        <div class="pedzio-app__logo">
            <img src="/assets/img/logo.png" alt="Pedzio">
        </div>
        <nav class="pedzio-app__nav">
            <?php foreach ($enlacesApp as $ruta => $info): ?>
                <a href="<?= e($ruta) ?>"
                   class="pedzio-app__nav-item <?= $itemActivo === $ruta ? 'pedzio-app__nav-item--activo' : '' ?>">
                    <span><?= $info['icono'] ?></span> <span><?= e($info['etiqueta']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>
    <div class="pedzio-app__main">
        <header class="pedzio-app__topbar">
            <div>
                <p class="pedzio-app__eyebrow"><?= e($eyebrowPagina ?? ucfirst((string) $rolApp)) ?></p>
                <h1><?= e($tituloPagina ?? '') ?></h1>
            </div>
            <div class="pedzio-app__usuario">
                <div class="pedzio-app__avatar"><?= e($inicialAvatar) ?></div>
                <div>
                    <strong><?= e($nombreApp) ?></strong><br>
                    <a href="/logout.php">Cerrar sesión</a>
                </div>
            </div>
        </header>
        <main class="pedzio-app__content">
