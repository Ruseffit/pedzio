<?php

namespace Pedzio\Models;

use PDO;

class MovimientoFinanciero
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function registrar(int $emprendimientoId, string $tipo, string $categoria, float $monto, string $fecha, ?string $descripcion = null, ?int $pedidoId = null): int
    {
        if (!in_array($tipo, ['ingreso', 'gasto'], true)) {
            throw new \InvalidArgumentException('Tipo de movimiento inválido.');
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO movimientos_financieros (emprendimiento_id, pedido_id, tipo, categoria, monto, descripcion, fecha)
             VALUES (:eid, :pedido_id, :tipo, :categoria, :monto, :descripcion, :fecha)'
        );
        $stmt->execute([
            'eid'         => $emprendimientoId,
            'pedido_id'   => $pedidoId,
            'tipo'        => $tipo,
            'categoria'   => $categoria,
            'monto'       => $monto,
            'descripcion' => $descripcion,
            'fecha'       => $fecha,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function listarPorEmprendimiento(int $emprendimientoId, ?string $desde = null, ?string $hasta = null): array
    {
        $sql = 'SELECT * FROM movimientos_financieros WHERE emprendimiento_id = :eid';
        $params = ['eid' => $emprendimientoId];

        if ($desde) {
            $sql .= ' AND fecha >= :desde';
            $params['desde'] = $desde;
        }
        if ($hasta) {
            $sql .= ' AND fecha <= :hasta';
            $params['hasta'] = $hasta;
        }
        $sql .= ' ORDER BY fecha DESC, creado_en DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Ingresos totales de la plataforma agrupados por mes (para el gráfico de tendencia del SuperAdmin).
     * Devuelve ['labels' => ['2026-02', ...], 'valores' => [1200.50, ...]] en orden cronológico.
     */
    public function ventasMensualesGlobal(int $meses = 9): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DATE_FORMAT(fecha, '%Y-%m') AS mes, SUM(monto) AS total
             FROM movimientos_financieros
             WHERE tipo = 'ingreso'
             GROUP BY mes
             ORDER BY mes DESC
             LIMIT :meses"
        );
        $stmt->bindValue('meses', $meses, PDO::PARAM_INT);
        $stmt->execute();
        $filas = array_reverse($stmt->fetchAll());

        return [
            'labels'  => array_column($filas, 'mes'),
            'valores' => array_map('floatval', array_column($filas, 'total')),
        ];
    }

    public function resumenGlobal(): array
    {
        $stmt = $this->pdo->query(
            "SELECT tipo, SUM(monto) AS total FROM movimientos_financieros GROUP BY tipo"
        );
        $totales = ['ingreso' => 0.0, 'gasto' => 0.0];
        foreach ($stmt->fetchAll() as $fila) {
            $totales[$fila['tipo']] = (float) $fila['total'];
        }
        return [
            'total_ingresos' => $totales['ingreso'],
            'total_gastos'   => $totales['gasto'],
            'balance'        => $totales['ingreso'] - $totales['gasto'],
        ];
    }

    public function resumen(int $emprendimientoId, ?string $desde = null, ?string $hasta = null): array
    {
        $movimientos = $this->listarPorEmprendimiento($emprendimientoId, $desde, $hasta);
        $totalIngresos = 0.0;
        $totalGastos = 0.0;
        foreach ($movimientos as $mov) {
            if ($mov['tipo'] === 'ingreso') {
                $totalIngresos += (float) $mov['monto'];
            } else {
                $totalGastos += (float) $mov['monto'];
            }
        }
        return [
            'total_ingresos' => $totalIngresos,
            'total_gastos'   => $totalGastos,
            'balance'        => $totalIngresos - $totalGastos,
        ];
    }
}
