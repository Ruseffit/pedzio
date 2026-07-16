<?php

namespace Pedzio\Models;

use PDO;

class Emprendimiento
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function crear(int $usuarioId, string $nombreNegocio, ?string $descripcion = null, ?string $direccion = null, ?string $telefono = null): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO emprendimientos (usuario_id, nombre_negocio, descripcion, direccion, telefono_contacto)
             VALUES (:usuario_id, :nombre, :descripcion, :direccion, :telefono)'
        );
        $stmt->execute([
            'usuario_id'  => $usuarioId,
            'nombre'      => $nombreNegocio,
            'descripcion' => $descripcion,
            'direccion'   => $direccion,
            'telefono'    => $telefono,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function buscarPorUsuarioId(int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM emprendimientos WHERE usuario_id = :uid LIMIT 1');
        $stmt->execute(['uid' => $usuarioId]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM emprendimientos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function listarActivos(): array
    {
        return $this->pdo->query('SELECT * FROM emprendimientos WHERE activo = 1 ORDER BY nombre_negocio')->fetchAll();
    }

    public function listarTodos(): array
    {
        return $this->pdo->query(
            'SELECT e.*, u.nombre AS nombre_dueno, u.email AS email_dueno
             FROM emprendimientos e
             JOIN usuarios u ON u.id = e.usuario_id
             ORDER BY e.creado_en DESC'
        )->fetchAll();
    }
}
