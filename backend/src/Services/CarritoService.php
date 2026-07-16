<?php

namespace Pedzio\Services;

/**
 * Maneja el carrito de compras del cliente, guardado en sesión.
 * Estructura en sesión: $_SESSION['carrito'] = [producto_id => cantidad, ...]
 */
class CarritoService
{
    public static function agregar(int $productoId, int $cantidad = 1): void
    {
        if ($cantidad < 1) {
            $cantidad = 1;
        }
        $_SESSION['carrito'][$productoId] = ($_SESSION['carrito'][$productoId] ?? 0) + $cantidad;
    }

    public static function actualizarCantidad(int $productoId, int $cantidad): void
    {
        if ($cantidad <= 0) {
            self::eliminar($productoId);
            return;
        }
        $_SESSION['carrito'][$productoId] = $cantidad;
    }

    public static function eliminar(int $productoId): void
    {
        unset($_SESSION['carrito'][$productoId]);
    }

    public static function vaciar(): void
    {
        unset($_SESSION['carrito']);
    }

    public static function obtener(): array
    {
        return $_SESSION['carrito'] ?? [];
    }

    public static function estaVacio(): bool
    {
        return empty($_SESSION['carrito']);
    }

    /**
     * Calcula el detalle del carrito cruzando con precios reales de la base de datos
     * (nunca confía en precios enviados desde el navegador).
     */
    public static function calcularDetalle(array $productosPorId): array
    {
        $detalle = [];
        $total = 0.0;

        foreach (self::obtener() as $productoId => $cantidad) {
            if (!isset($productosPorId[$productoId])) {
                continue; // Producto ya no existe o no pertenece al catálogo actual.
            }
            $producto = $productosPorId[$productoId];
            $subtotal = (float) $producto['precio'] * $cantidad;
            $total += $subtotal;

            $detalle[] = [
                'producto_id'     => $productoId,
                'nombre'          => $producto['nombre'],
                'precio_unitario' => (float) $producto['precio'],
                'cantidad'        => $cantidad,
                'subtotal'        => $subtotal,
            ];
        }

        return ['items' => $detalle, 'total' => $total];
    }
}
