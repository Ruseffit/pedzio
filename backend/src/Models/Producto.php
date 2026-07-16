<?php

namespace Pedzio\Models;

use PDO;

class Producto
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function crear(int $emprendimientoId, string $nombre, float $precio, ?int $categoriaId = null, ?string $descripcion = null, ?string $imagenPath = null): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO productos (emprendimiento_id, categoria_id, nombre, descripcion, precio, imagen_path)
             VALUES (:eid, :cid, :nombre, :descripcion, :precio, :imagen)'
        );
        $stmt->execute([
            'eid'         => $emprendimientoId,
            'cid'         => $categoriaId,
            'nombre'      => $nombre,
            'descripcion' => $descripcion,
            'precio'      => $precio,
            'imagen'      => $imagenPath,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function actualizar(int $id, int $emprendimientoId, array $datos): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE productos SET nombre = :nombre, descripcion = :descripcion, precio = :precio,
                    categoria_id = :cid, disponible = :disponible
             WHERE id = :id AND emprendimiento_id = :eid'
        );
        return $stmt->execute([
            'nombre'      => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
            'precio'      => $datos['precio'],
            'cid'         => $datos['categoria_id'] ?? null,
            'disponible'  => !empty($datos['disponible']) ? 1 : 0,
            'id'          => $id,
            'eid'         => $emprendimientoId,
        ]);
    }

    public function eliminar(int $id, int $emprendimientoId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM productos WHERE id = :id AND emprendimiento_id = :eid');
        return $stmt->execute(['id' => $id, 'eid' => $emprendimientoId]);
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM productos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function listarPorEmprendimiento(int $emprendimientoId, bool $soloDisponibles = false): array
    {
        $sql = 'SELECT * FROM productos WHERE emprendimiento_id = :eid';
        if ($soloDisponibles) {
            $sql .= ' AND disponible = 1';
        }
        $sql .= ' ORDER BY nombre';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['eid' => $emprendimientoId]);
        return $stmt->fetchAll();
    }
}
