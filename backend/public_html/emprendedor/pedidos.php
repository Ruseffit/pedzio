<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$rolesPermitidos = ['emprendedor'];
require_once __DIR__ . '/../../includes/role_check.php';

use Pedzio\Models\Emprendimiento;
use Pedzio\Models\Pedido;
use Pedzio\Models\Producto;
use Pedzio\Models\MovimientoFinanciero;
use Pedzio\Services\FinanzasService;
use Pedzio\Services\PushSenderService;
use Pedzio\Controllers\PedidoController;
use Pedzio\Helpers\Formato;

$pdo = obtenerConexion();
$emprendimiento = (new Emprendimiento($pdo))->buscarPorUsuarioId((int) $_SESSION['usuario_id']);

if (!$emprendimiento) {
    flashSet('error', 'No se encontró un emprendimiento asociado a tu cuenta.');
    redirigir('/login.php');
}
$emprendimientoId = (int) $emprendimiento['id'];

$pedidoController = new PedidoController(
    new Pedido($pdo),
    new Producto($pdo),
    new FinanzasService(new MovimientoFinanciero($pdo))
);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && pedzio_csrf_valido($_POST['_csrf'] ?? null)) {
    $pedidoId = (int) ($_POST['pedido_id'] ?? 0);
    $nuevoEstado = $_POST['estado'] ?? '';
    try {
        $pedidoController->cambiarEstado($pedidoId, $nuevoEstado, $emprendimientoId);
        flashSet('exito', "Pedido #{$pedidoId} actualizado a " . Formato::estadoPedidoLegible($nuevoEstado) . '.');

        // ── Push real al cliente dueño del pedido ───────────────────────────
        // Se dispara DESPUÉS de que cambiarEstado() confirmó el cambio. Si el
        // push falla (VAPID sin configurar, cliente sin suscripción, etc.) no
        // debe romper el flujo de actualizar el pedido: solo se registra el
        // error en el log del servidor.
        try {
            // Se filtra también por emprendimiento_id y por el estado nuevo: así se
            // confirma que el UPDATE realmente se aplicó a un pedido de este negocio
            // (actualizarEstado() devuelve true con execute() incluso si 0 filas
            // cambiaron, ej. si el pedido_id no pertenece a este emprendimiento).
            $stmtCliente = $pdo->prepare(
                'SELECT cliente_id FROM pedidos WHERE id = :id AND emprendimiento_id = :eid AND estado = :estado LIMIT 1'
            );
            $stmtCliente->execute(['id' => $pedidoId, 'eid' => $emprendimientoId, 'estado' => $nuevoEstado]);
            $clienteId = (int) ($stmtCliente->fetchColumn() ?: 0);

            if ($clienteId > 0) {
                $mensajesPorEstado = [
                    'pendiente'      => ['titulo' => 'Pedido recibido', 'mensaje' => 'Tu pedido fue recibido y está pendiente de confirmación.'],
                    'confirmado'     => ['titulo' => 'Pedido confirmado', 'mensaje' => 'Tu pedido fue confirmado por el negocio.'],
                    'en_preparacion' => ['titulo' => 'Preparando tu pedido', 'mensaje' => 'Tu pedido ya se está preparando.'],
                    'en_camino'      => ['titulo' => '¡Tu pedido está en camino!', 'mensaje' => 'El repartidor ya salió hacia tu dirección.'],
                    'entregado'      => ['titulo' => 'Pedido entregado', 'mensaje' => 'Tu pedido fue entregado. ¡Buen provecho!'],
                    'cancelado'      => ['titulo' => 'Pedido cancelado', 'mensaje' => 'Tu pedido fue cancelado.'],
                ];
                $textos = $mensajesPorEstado[$nuevoEstado] ?? [
                    'titulo'  => 'Actualización de tu pedido',
                    'mensaje' => "Tu pedido en {$emprendimiento['nombre_negocio']} cambió de estado.",
                ];

                (new PushSenderService($pdo))->enviarAUsuario(
                    $clienteId,
                    "{$textos['titulo']} · {$emprendimiento['nombre_negocio']}",
                    $textos['mensaje'],
                    '/cliente/pedidos'
                );
            }
        } catch (\Throwable $e) {
            error_log('[push] No se pudo notificar el cambio de estado del pedido #' . $pedidoId . ': ' . $e->getMessage());
        }
    } catch (\InvalidArgumentException $e) {
        flashSet('error', 'Estado inválido.');
    }
    redirigir('/emprendedor/pedidos.php');
}

$pedidos = $pedidoController->listarPorEmprendimiento($emprendimientoId);

// KPIs del encabezado: ventas de hoy y pedidos nuevos (pendientes), calculados sobre datos reales.
$ventasHoy = 0.0;
$pedidosNuevos = 0;
$hoy = date('Y-m-d');
foreach ($pedidos as $p) {
    if (substr($p['creado_en'], 0, 10) === $hoy) {
        $ventasHoy += (float) $p['total'];
    }
    if ($p['estado'] === 'pendiente') {
        $pedidosNuevos++;
    }
}

$tituloPagina = 'Gestión de Pedidos';
$eyebrowPagina = 'Emprendedor';
$itemActivo = '/emprendedor/pedidos.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/app_header.php';
?>
<div class="pedzio-kpi-row">
    <div class="pedzio-kpi-card">
        <div class="pedzio-kpi-card__icono">💰</div>
        <div class="pedzio-kpi-card__texto"><p>Ventas hoy</p><strong><?= formatearMoneda($ventasHoy) ?></strong></div>
    </div>
    <div class="pedzio-kpi-card">
        <div class="pedzio-kpi-card__icono">📄</div>
        <div class="pedzio-kpi-card__texto"><p>Nuevos pedidos</p><strong><?= $pedidosNuevos ?></strong></div>
    </div>
</div>

<div class="pedzio-panel">
    <h2>Pedidos recibidos</h2>
    <table class="pedzio-tabla">
        <thead><tr><th>ID Pedido</th><th>Total (S/)</th><th>Estado</th><th>Fecha</th><th>Cambiar estado</th></tr></thead>
        <tbody>
            <?php foreach ($pedidos as $pedido): ?>
                <tr>
                    <td>#<?= (int) $pedido['id'] ?></td>
                    <td><?= formatearMoneda((float) $pedido['total']) ?></td>
                    <td><span class="pedzio-badge <?= Formato::estadoPedidoClase($pedido['estado']) ?>">
                        <?= e(Formato::estadoPedidoLegible($pedido['estado'])) ?>
                    </span></td>
                    <td><?= formatearFecha($pedido['creado_en']) ?></td>
                    <td>
                        <form method="post" action="/emprendedor/pedidos.php" style="display:flex; gap:4px;">
                            <input type="hidden" name="_csrf" value="<?= e(pedzio_csrf_token()) ?>">
                            <input type="hidden" name="pedido_id" value="<?= (int) $pedido['id'] ?>">
                            <select name="estado">
                                <?php foreach (PEDZIO_ESTADOS_PEDIDO as $estado): ?>
                                    <option value="<?= e($estado) ?>" <?= $estado === $pedido['estado'] ? 'selected' : '' ?>>
                                        <?= e(Formato::estadoPedidoLegible($estado)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="pedzio-btn pedzio-btn--secundario">Actualizar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($pedidos)): ?>
                <tr><td colspan="5">Aún no has recibido pedidos.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php
require_once __DIR__ . '/../../includes/app_footer.php';
require_once __DIR__ . '/../../includes/footer.php';
