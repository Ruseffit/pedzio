<?php

namespace Pedzio\Models;

use PDO;

class Categoria
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function crear(int $emprendimientoId, string $nombre): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO categorias (emprendimiento_id, nombre) VALUES (:eid, :nombre)');
        $stmt->execute(['eid' => $emprendimientoId, 'nombre' => $nombre]);
        return (int) $this->pdo->lastInsertId();
    }

    public function listarPorEmprendimiento(int $emprendimientoId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM categorias WHERE emprendimiento_id = :eid ORDER BY nombre');
        $stmt->execute(['eid' => $emprendimientoId]);
        return $stmt->fetchAll();
    }

    public function eliminar(int $id, int $emprendimientoId): bool
    {
        // El filtro por emprendimiento_id evita que un emprendedor borre categorías ajenas.
        $stmt = $this->pdo->prepare('DELETE FROM categorias WHERE id = :id AND emprendimiento_id = :eid');
        return $stmt->execute(['id' => $id, 'eid' => $emprendimientoId]);
    }
}
