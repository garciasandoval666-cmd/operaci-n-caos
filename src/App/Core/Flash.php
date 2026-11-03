<?php
declare(strict_types=1);

namespace App\Core;

final class Flash
{
    public static function iniciar(): void
    {
        if (!isset($_SESSION['_flash'])) {
            $_SESSION['_flash'] = [];
        }
    }

    public static function guardar(string $tipo, string $mensaje): void
    {
        $_SESSION['_flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
    }

    public static function obtener(): array
    {
        $mensajes = $_SESSION['_flash'] ?? [];
        $_SESSION['_flash'] = [];
        return $mensajes;
    }
}
