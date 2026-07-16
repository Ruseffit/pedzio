<?php
require_once __DIR__ . '/_bootstrap.php';

$tituloPagina = 'Inicio';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<main class="pedzio-landing">
    <section class="pedzio-hero-split">
        <div class="pedzio-hero-split__texto">
            <h1>Antojos locales, directos a tu puerta. Apoya a los emprendimientos de Lima.</h1>
            <div class="pedzio-hero-split__botones">
                <?php if (empty($_SESSION['usuario_id'])): ?>
                    <a class="pedzio-btn pedzio-btn--primario" href="/registro.php?rol=cliente">Explorar Catálogo (Cliente)</a>
                    <a class="pedzio-btn pedzio-btn--secundario" href="/registro.php?rol=emprendedor">Registrar mi Negocio (Emprendedor)</a>
                <?php else: ?>
                    <a class="pedzio-btn pedzio-btn--primario" href="/cliente/catalogo.php">Ver catálogo</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="pedzio-hero-split__imagen">
            <?php readfile(__DIR__ . '/assets/img/hero-ilustracion.svg'); ?>
        </div>
    </section>

    <section class="pedzio-caracteristicas">
        <div class="pedzio-caracteristica pedzio-caracteristica--cliente">
            <div class="pedzio-caracteristica__icono">🛒</div>
            <div>
                <h3>Para Clientes</h3>
                <p>Navega catálogos, arma tu carrito, confirma pedidos y ve tu historial.</p>
            </div>
        </div>
        <div class="pedzio-caracteristica pedzio-caracteristica--emprendedor">
            <div class="pedzio-caracteristica__icono">🏪</div>
            <div>
                <h3>Para Emprendedores</h3>
                <p>Gestiona productos, categorías, pedidos recibidos y finanzas.</p>
            </div>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
