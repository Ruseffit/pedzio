<?php

namespace Pedzio\Helpers;

class Validador
{
    public static function requerido($valor): bool
    {
        return $valor !== null && trim((string) $valor) !== '';
    }

    public static function email(string $valor): bool
    {
        return filter_var($valor, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function numeroPositivo($valor): bool
    {
        return is_numeric($valor) && (float) $valor > 0;
    }

    public static function passwordSegura(string $valor): bool
    {
        // Mínimo 8 caracteres, al menos una letra y un número (regla simple para usuarios de baja alfabetización digital).
        return strlen($valor) >= 8 && preg_match('/[A-Za-z]/', $valor) && preg_match('/\d/', $valor);
    }

    public static function longitudMaxima(string $valor, int $maximo): bool
    {
        $longitud = function_exists('mb_strlen') ? mb_strlen($valor) : strlen($valor);
        return $longitud <= $maximo;
    }
}
