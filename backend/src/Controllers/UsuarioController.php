<?php

namespace Pedzio\Controllers;

use Pedzio\Models\Usuario;
use Pedzio\Helpers\Validador;

class UsuarioController
{
    private Usuario $modelo;

    public function __construct(Usuario $modelo)
    {
        $this->modelo = $modelo;
    }

    /**
     * Registra un nuevo usuario (cliente o emprendedor; superadmin no se autorregistra).
     * Devuelve ['ok' => bool, 'errores' => array, 'usuario_id' => int|null]
     */
    public function registrar(string $nombre, string $email, string $password, string $rol, ?string $telefono = null): array
    {
        $errores = [];

        if (!Validador::requerido($nombre)) {
            $errores[] = 'El nombre es obligatorio.';
        }
        if (!Validador::email($email)) {
            $errores[] = 'El correo no tiene un formato válido.';
        }
        if (!Validador::passwordSegura($password)) {
            $errores[] = 'La contraseña debe tener mínimo 8 caracteres, con letras y números.';
        }
        if (!in_array($rol, ['cliente', 'emprendedor'], true)) {
            $errores[] = 'Rol de registro inválido.';
        }
        if (empty($errores) && $this->modelo->existeEmail($email)) {
            $errores[] = 'Ya existe una cuenta registrada con ese correo.';
        }

        if (!empty($errores)) {
            return ['ok' => false, 'errores' => $errores, 'usuario_id' => null];
        }

        $usuarioId = $this->modelo->crear($nombre, $email, $password, $rol, $telefono);
        return ['ok' => true, 'errores' => [], 'usuario_id' => $usuarioId];
    }

    /**
     * Intenta iniciar sesión. Devuelve el usuario (sin password_hash) o null si falla.
     */
    public function iniciarSesion(string $email, string $password): ?array
    {
        $usuario = $this->modelo->verificarCredenciales($email, $password);
        if (!$usuario) {
            return null;
        }
        unset($usuario['password_hash']);
        return $usuario;
    }
}
