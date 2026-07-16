<?php

namespace Pedzio\Controllers;

use Pedzio\Models\Producto;
use Pedzio\Helpers\Validador;

class ProductoController
{
    private Producto $modelo;

    public function __construct(Producto $modelo)
    {
        $this->modelo = $modelo;
    }

    public function crear(int $emprendimientoId, string $nombre, $precio, ?int $categoriaId, ?string $descripcion): array
    {
        $errores = [];
        if (!Validador::requerido($nombre)) {
            $errores[] = 'El nombre del producto es obligatorio.';
        }
        if (!Validador::numeroPositivo($precio)) {
            $errores[] = 'El precio debe ser un número mayor a cero.';
        }
        if (!empty($errores)) {
            return ['ok' => false, 'errores' => $errores];
        }

        $id = $this->modelo->crear($emprendimientoId, $nombre, (float) $precio, $categoriaId, $descripcion);
        return ['ok' => true, 'errores' => [], 'producto_id' => $id];
    }

    public function actualizar(int $id, int $emprendimientoId, array $datos): array
    {
        $errores = [];
        if (!Validador::requerido($datos['nombre'] ?? '')) {
            $errores[] = 'El nombre del producto es obligatorio.';
        }
        if (!Validador::numeroPositivo($datos['precio'] ?? 0)) {
            $errores[] = 'El precio debe ser un número mayor a cero.';
        }
        if (!empty($errores)) {
            return ['ok' => false, 'errores' => $errores];
        }

        $exito = $this->modelo->actualizar($id, $emprendimientoId, $datos);
        return ['ok' => $exito, 'errores' => $exito ? [] : ['No se pudo actualizar el producto.']];
    }

    public function eliminar(int $id, int $emprendimientoId): bool
    {
        return $this->modelo->eliminar($id, $emprendimientoId);
    }

    public function listarPorEmprendimiento(int $emprendimientoId, bool $soloDisponibles = false): array
    {
        return $this->modelo->listarPorEmprendimiento($emprendimientoId, $soloDisponibles);
    }
}
