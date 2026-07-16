<?php

namespace Pedzio\Services;

use Pedzio\Models\Pedido;
use Pedzio\Models\MovimientoFinanciero;

class ReporteService
{
    private Pedido $pedidoModelo;
    private MovimientoFinanciero $finanzasModelo;

    public function __construct(Pedido $pedidoModelo, MovimientoFinanciero $finanzasModelo)
    {
        $this->pedidoModelo = $pedidoModelo;
        $this->finanzasModelo = $finanzasModelo;
    }

    public function reporteEmprendimiento(int $emprendimientoId): array
    {
        $pedidos = $this->pedidoModelo->listarPorEmprendimiento($emprendimientoId);
        $finanzas = $this->finanzasModelo->resumen($emprendimientoId);

        $conteoPorEstado = [];
        foreach ($pedidos as $pedido) {
            $estado = $pedido['estado'];
            $conteoPorEstado[$estado] = ($conteoPorEstado[$estado] ?? 0) + 1;
        }

        return [
            'total_pedidos'      => count($pedidos),
            'conteo_por_estado'  => $conteoPorEstado,
            'total_ingresos'     => $finanzas['total_ingresos'],
            'total_gastos'       => $finanzas['total_gastos'],
            'balance'            => $finanzas['balance'],
        ];
    }
}
