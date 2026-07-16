<?php
$tituloPagina = $tituloPagina ?? 'Pedzio';
$mensajesFlash = flashGetAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tituloPagina) ?> · Pedzio</title>
    <link rel="stylesheet" href="/assets/css/variables.css">
    <link rel="stylesheet" href="/assets/css/base.css">
    <link rel="stylesheet" href="/assets/css/componentes.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js" defer></script>
</head>
<body>
<?php if (!empty($mensajesFlash)): ?>
    <div class="pedzio-flash-container">
        <?php foreach ($mensajesFlash as $tipo => $mensaje): ?>
            <div class="pedzio-flash pedzio-flash--<?= e($tipo) ?>"><?= e($mensaje) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
