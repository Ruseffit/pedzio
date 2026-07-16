<?php

namespace Pedzio\Services;

use Pedzio\Models\MovimientoFinanciero;

class FinanzasService
{
    private MovimientoFinanciero $modelo;

    public function __construct(MovimientoFinanciero $modelo)
    {
        $this->modelo = $modelo;
    }

    public function registrarIngresoPorPedido(int $emprendimientoId, int $pedidoId, float $total): int
    {
        return $this->modelo->registrar(
            $emprendimientoId,
            'ingreso',
            'venta',
            $total,
            date('Y-m-d'),
            "Venta pedido #{$pedidoId}",
            $pedidoId
        );
    }

    public function registrarGastoManual(int $emprendimientoId, string $categoria, float $monto, string $descripcion, string $fecha): int
    {
        return $this->modelo->registrar($emprendimientoId, 'gasto', $categoria, $monto, $fecha, $descripcion);
    }

    public function resumenMensual(int $emprendimientoId): array
    {
        $primerDiaMes = date('Y-m-01');
        $hoy = date('Y-m-d');
        return $this->modelo->resumen($emprendimientoId, $primerDiaMes, $hoy);
    }
}
