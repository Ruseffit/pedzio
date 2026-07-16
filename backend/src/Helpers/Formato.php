<?php

namespace Pedzio\Helpers;

class Formato
{
    public static function moneda(float $monto): string
    {
        return 'S/ ' . number_format($monto, 2);
    }

    public static function fechaCorta(string $fecha): string
    {
        $dt = date_create($fecha);
        return $dt ? date_format($dt, 'd/m/Y') : $fecha;
    }

    public static function estadoPedidoLegible(string $estado): string
    {
        $mapa = [
            'pendiente'       => 'Pendiente',
            'confirmado'      => 'Confirmado',
            'en_preparacion'  => 'En preparación',
            'en_camino'       => 'En camino',
            'entregado'       => 'Entregado',
            'cancelado'       => 'Cancelado',
        ];
        return $mapa[$estado] ?? ucfirst($estado);
    }

    /**
     * Clase CSS del badge de color según el estado del pedido (ver componentes.css).
     */
    public static function estadoPedidoClase(string $estado): string
    {
        $mapa = [
            'pendiente'       => 'pedzio-badge--pendiente',
            'confirmado'      => 'pedzio-badge--confirmado',
            'en_preparacion'  => 'pedzio-badge--preparacion',
            'en_camino'       => 'pedzio-badge--camino',
            'entregado'       => 'pedzio-badge--entregado',
            'cancelado'       => 'pedzio-badge--cancelado',
        ];
        return $mapa[$estado] ?? 'pedzio-badge--pendiente';
    }
}
