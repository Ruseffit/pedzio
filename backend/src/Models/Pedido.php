<?php

namespace Pedzio\Models;

use PDO;

class Pedido
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Crea un pedido junto con su detalle en una sola transacción.
     * $items = [['producto_id' => int, 'cantidad' => int, 'precio_unitario' => float], ...]
     */
    public function crearConDetalle(int $clienteId, int $emprendimientoId, string $direccionEntrega, array $items, ?string $notas = null): int
    {
        if (empty($items)) {
            throw new \InvalidArgumentException('El pedido debe tener al menos un producto.');
        }

        $total = 0.0;
        foreach ($items as $item) {
            $total += $item['precio_unitario'] * $item['cantidad'];
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO pedidos (cliente_id, emprendimiento_id, total, direccion_entrega, notas)
                 VALUES (:cliente_id, :eid, :total, :direccion, :notas)'
            );
            $stmt->execute([
                'cliente_id' => $clienteId,
                'eid'        => $emprendimientoId,
                'total'      => $total,
                'direccion'  => $direccionEntrega,
                'notas'      => $notas,
            ]);
            $pedidoId = (int) $this->pdo->lastInsertId();

            $stmtDetalle = $this->pdo->prepare(
                'INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unitario, subtotal)
                 VALUES (:pedido_id, :producto_id, :cantidad, :precio, :subtotal)'
            );
            foreach ($items as $item) {
                $subtotal = $item['precio_unitario'] * $item['cantidad'];
                $stmtDetalle->execute([
                    'pedido_id'   => $pedidoId,
                    'producto_id' => $item['producto_id'],
                    'cantidad'    => $item['cantidad'],
                    'precio'      => $item['precio_unitario'],
                    'subtotal'    => $subtotal,
                ]);
            }

            $this->pdo->commit();
            return $pedidoId;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pedidos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function detalleDePedido(int $pedidoId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT dp.*, p.nombre AS producto_nombre
             FROM detalle_pedidos dp
             JOIN productos p ON p.id = dp.producto_id
             WHERE dp.pedido_id = :pid'
        );
        $stmt->execute(['pid' => $pedidoId]);
        return $stmt->fetchAll();
    }

    public function listarPorCliente(int $clienteId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pedidos WHERE cliente_id = :cid ORDER BY creado_en DESC');
        $stmt->execute(['cid' => $clienteId]);
        return $stmt->fetchAll();
    }

    public function listarPorEmprendimiento(int $emprendimientoId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pedidos WHERE emprendimiento_id = :eid ORDER BY creado_en DESC');
        $stmt->execute(['eid' => $emprendimientoId]);
        return $stmt->fetchAll();
    }

    public function listarTodos(): array
    {
        return $this->pdo->query(
            'SELECT p.*, e.nombre_negocio, u.nombre AS nombre_cliente
             FROM pedidos p
             JOIN emprendimientos e ON e.id = p.emprendimiento_id
             JOIN usuarios u ON u.id = p.cliente_id
             ORDER BY p.creado_en DESC'
        )->fetchAll();
    }

    public function actualizarEstado(int $id, string $nuevoEstado, ?int $emprendimientoId = null): bool
    {
        if (!in_array($nuevoEstado, PEDZIO_ESTADOS_PEDIDO, true)) {
            throw new \InvalidArgumentException('Estado de pedido inválido.');
        }
        $sql = 'UPDATE pedidos SET estado = :estado WHERE id = :id';
        $params = ['estado' => $nuevoEstado, 'id' => $id];

        // Si se pasa emprendimientoId, se exige que el pedido pertenezca a ese negocio (control de acceso a nivel de datos).
        if ($emprendimientoId !== null) {
            $sql .= ' AND emprendimiento_id = :eid';
            $params['eid'] = $emprendimientoId;
        }

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
}
