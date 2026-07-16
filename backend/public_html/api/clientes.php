<?php
/**
 * GET /api/clientes.php
 *
 * Devuelve los clientes que han hecho al menos un pedido al emprendimiento
 * del usuario emprendedor autenticado, con su cantidad total de pedidos.
 *
 * Respuesta (200):
 *   { "success": true, "clientes": [
 *       { id, nombre, email, telefono, total_pedidos }
 *   ] }
 *
 * Error (403 / 404):
 *   { "error": "..." }
 */

require_once __DIR__ . '/config.php';

use Pedzio\Models\Pedido;
use Pedzio\Models\Usuario;
use Pedzio\Models\Emprendimiento;

// ── 1. Solo GET ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Método no permitido. Usa GET.'], 405);
}

// ── 2. Solo emprendedores autenticados ──────────────────────────────────────
$usuario = requireAuth(['emprendedor']);

$pdo = obtenerConexion();

// ── 3. Resolver el emprendimiento del usuario logueado ──────────────────────
$emprendimiento = (new Emprendimiento($pdo))->buscarPorUsuarioId($usuario['id']);

if (!$emprendimiento) {
    jsonResponse(['error' => 'No tienes un emprendimiento registrado.'], 404);
}

$emprendimientoId = (int) $emprendimiento['id'];

// ── 4. Pedidos del emprendimiento → extraer clientes únicos ────────────────
$pedidos = (new Pedido($pdo))->listarPorEmprendimiento($emprendimientoId);

// Cuenta pedidos por cliente_id sin perder el orden de primera aparición.
$totalPedidosPorCliente = [];
foreach ($pedidos as $pedido) {
    $clienteId = (int) $pedido['cliente_id'];
    $totalPedidosPorCliente[$clienteId] = ($totalPedidosPorCliente[$clienteId] ?? 0) + 1;
}

if (empty($totalPedidosPorCliente)) {
    jsonResponse(['success' => true, 'clientes' => []]);
}

// ── 5. Cargar datos de cada cliente único ───────────────────────────────────
$modeloUsuario = new Usuario($pdo);

$clientes = [];
foreach ($totalPedidosPorCliente as $clienteId => $totalPedidos) {
    $datosCliente = $modeloUsuario->buscarPorId($clienteId);

    if (!$datosCliente) {
        continue; // Usuario eliminado o inconsistente; se omite en vez de romper la respuesta.
    }

    $clientes[] = [
        'id'             => (int) $datosCliente['id'],
        'nombre'         =>       $datosCliente['nombre'],
        'email'          =>       $datosCliente['email'],
        'telefono'       =>       $datosCliente['telefono'],
        'total_pedidos'  => (int) $totalPedidos,
    ];
}

// Clientes con más pedidos primero.
usort($clientes, fn($a, $b) => $b['total_pedidos'] <=> $a['total_pedidos']);

jsonResponse(['success' => true, 'clientes' => $clientes]);
