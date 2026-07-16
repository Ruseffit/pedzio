<?php

namespace Pedzio\Controllers;

use Pedzio\Models\Pedido;
use Pedzio\Models\Producto;
use Pedzio\Services\CarritoService;
use Pedzio\Services\FinanzasService;
use Pedzio\Helpers\Validador;

class PedidoController
{
    private Pedido $modelo;
    private Producto $productoModelo;
    private FinanzasService $finanzasService;

    public function __construct(Pedido $modelo, Producto $productoModelo, FinanzasService $finanzasService)
    {
        $this->modelo = $modelo;
        $this->productoModelo = $productoModelo;
        $this->finanzasService = $finanzasService;
    }

    /**
     * Confirma el pedido a partir del carrito en sesión.
     * Todos los precios se recalculan desde la base de datos (nunca desde el navegador).
     */
    public function confirmarDesdeCarrito(int $clienteId, int $emprendimientoId, string $direccionEntrega, ?string $notas = null): array
    {
        if (!Validador::requerido($direccionEntrega)) {
            return ['ok' => false, 'errores' => ['La dirección de entrega es obligatoria.']];
        }
        if (CarritoService::estaVacio()) {
            return ['ok' => false, 'errores' => ['El carrito está vacío.']];
        }

        $carrito = CarritoService::obtener();
        $items = [];

        foreach ($carrito as $productoId => $cantidad) {
            $producto = $this->productoModelo->buscarPorId((int) $productoId);
            if (!$producto || (int) $producto['emprendimiento_id'] !== $emprendimientoId || !$producto['disponible']) {
                continue; // Ignora productos que ya no existen o no pertenecen a este negocio.
            }
            $items[] = [
                'producto_id'     => (int) $productoId,
                'cantidad'        => (int) $cantidad,
                'precio_unitario' => (float) $producto['precio'],
            ];
        }

        if (empty($items)) {
            return ['ok' => false, 'errores' => ['Ningún producto del carrito está disponible actualmente.']];
        }

        $pedidoId = $this->modelo->crearConDetalle($clienteId, $emprendimientoId, $direccionEntrega, $items, $notas);
        $pedido = $this->modelo->buscarPorId($pedidoId);

        $this->finanzasService->registrarIngresoPorPedido($emprendimientoId, $pedidoId, (float) $pedido['total']);

        CarritoService::vaciar();

        return ['ok' => true, 'errores' => [], 'pedido_id' => $pedidoId];
    }

    public function cambiarEstado(int $pedidoId, string $nuevoEstado, int $emprendimientoId): bool
    {
        return $this->modelo->actualizarEstado($pedidoId, $nuevoEstado, $emprendimientoId);
    }

    public function listarPorCliente(int $clienteId): array
    {
        return $this->modelo->listarPorCliente($clienteId);
    }

    public function listarPorEmprendimiento(int $emprendimientoId): array
    {
        return $this->modelo->listarPorEmprendimiento($emprendimientoId);
    }

    public function detalle(int $pedidoId): array
    {
        return $this->modelo->detalleDePedido($pedidoId);
    }
}
