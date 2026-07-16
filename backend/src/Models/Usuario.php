<?php

namespace Pedzio\Models;

use PDO;

class Usuario
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function crear(string $nombre, string $email, string $passwordPlano, string $rol, ?string $telefono = null): int
    {
        $hash = password_hash($passwordPlano, PASSWORD_BCRYPT);
        $stmt = $this->pdo->prepare(
            'INSERT INTO usuarios (nombre, email, password_hash, telefono, rol) VALUES (:nombre, :email, :hash, :telefono, :rol)'
        );
        $stmt->execute([
            'nombre'   => $nombre,
            'email'    => $email,
            'hash'     => $hash,
            'telefono' => $telefono,
            'rol'      => $rol,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function buscarPorEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function existeEmail(string $email): bool
    {
        return $this->buscarPorEmail($email) !== null;
    }

    public function verificarCredenciales(string $email, string $passwordPlano): ?array
    {
        $usuario = $this->buscarPorEmail($email);
        if (!$usuario || !$usuario['activo']) {
            return null;
        }
        if (!password_verify($passwordPlano, $usuario['password_hash'])) {
            return null;
        }
        return $usuario;
    }

    public function listarPorRol(string $rol): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE rol = :rol ORDER BY creado_en DESC');
        $stmt->execute(['rol' => $rol]);
        return $stmt->fetchAll();
    }

    public function listarTodos(): array
    {
        return $this->pdo->query('SELECT * FROM usuarios ORDER BY creado_en DESC')->fetchAll();
    }

    public function activarDesactivar(int $id, bool $activo): bool
    {
        $stmt = $this->pdo->prepare('UPDATE usuarios SET activo = :activo WHERE id = :id');
        return $stmt->execute(['activo' => $activo ? 1 : 0, 'id' => $id]);
    }

    /**
     * Actualiza la ruta de la foto de perfil (relativa, ej. "/uploads/perfiles/x.jpg").
     * Pasa null para borrar la foto actual.
     */
    public function actualizarFoto(int $id, ?string $rutaFoto): bool
    {
        $stmt = $this->pdo->prepare('UPDATE usuarios SET foto_url = :foto WHERE id = :id');
        return $stmt->execute(['foto' => $rutaFoto, 'id' => $id]);
    }
}
