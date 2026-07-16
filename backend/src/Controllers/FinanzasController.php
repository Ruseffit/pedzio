<?php

namespace Pedzio\Controllers;

use Pedzio\Services\FinanzasService;
use Pedzio\Helpers\Validador;

class FinanzasController
{
    private FinanzasService $servicio;

    public function __construct(FinanzasService $servicio)
    {
        $this->servicio = $servicio;
    }

    public function registrarGasto(int $emprendimientoId, string $categoria, $monto, string $descripcion, string $fecha): array
    {
        $errores = [];
        if (!Validador::requerido($categoria)) {
            $errores[] = 'La categoría del gasto es obligatoria.';
        }
        if (!Validador::numeroPositivo($monto)) {
            $errores[] = 'El monto debe ser un número mayor a cero.';
        }
        if (!empty($errores)) {
            return ['ok' => false, 'errores' => $errores];
        }

        $id = $this->servicio->registrarGastoManual($emprendimientoId, $categoria, (float) $monto, $descripcion, $fecha);
        return ['ok' => true, 'errores' => [], 'movimiento_id' => $id];
    }

    public function resumenMensual(int $emprendimientoId): array
    {
        return $this->servicio->resumenMensual($emprendimientoId);
    }
}
