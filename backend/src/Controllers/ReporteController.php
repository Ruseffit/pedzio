<?php

namespace Pedzio\Controllers;

use Pedzio\Services\ReporteService;

class ReporteController
{
    private ReporteService $servicio;

    public function __construct(ReporteService $servicio)
    {
        $this->servicio = $servicio;
    }

    public function reporteDeEmprendimiento(int $emprendimientoId): array
    {
        return $this->servicio->reporteEmprendimiento($emprendimientoId);
    }
}
