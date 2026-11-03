<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function campo(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    /** Genera un token nuevo (se usa al iniciar sesión). */
    public static function rotar(): string
    {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        return $_SESSION['_csrf'];
    }

    public static function valido(?string $token): bool
    {
        return is_string($token) && $token !== '' && hash_equals(self::token(), $token);
    }

    public static function verificar(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return;
        }
        if (!self::valido($_POST['_csrf'] ?? null)) {
            // 419 no es un código estándar: Apache lo degrada a 500 si se envía
            // con http_response_code(), así que se manda la línea de estado tal cual.
            if (!headers_sent()) {
                header('HTTP/1.1 419 Sesión expirada');
            }
            View::render('errores/419', ['titulo' => 'Sesión expirada'], Auth::usuario() ? 'app' : 'error');
            exit;
        }
    }
}
