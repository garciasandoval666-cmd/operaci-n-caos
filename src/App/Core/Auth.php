<?php
declare(strict_types=1);

namespace App\Core;

final class Auth
{
    /** Vida máxima de una sesión (8 horas). */
    public const VIDA_MAXIMA = 28800;

    /** Inactividad máxima antes de exigir de nuevo la contraseña (30 minutos). */
    public const INACTIVIDAD = 1800;

    public const ROLES = [
        'administrador' => 'Administrador',
        'comercial'     => 'Ejecutivo comercial',
        'coordinador'   => 'Coordinador de eventos',
        'banquetes'     => 'Banquetes / cocina',
        'operaciones'   => 'Operaciones / logística',
        'recepcion'     => 'Recepción / check-in',
        'finanzas'      => 'Finanzas',
        'auditor'       => 'Auditor',
    ];

    public static function usuario(): ?array
    {
        if (empty($_SESSION['usuario_id'])) {
            return null;
        }

        // Expiración absoluta y por inactividad (la cookie sola no basta).
        $ahora = time();
        $inicio = (int) ($_SESSION['iniciado_en'] ?? 0);
        $ultimo = (int) ($_SESSION['ultimo_actividad'] ?? $inicio);
        if ($inicio > 0 && ($ahora - $inicio) > self::VIDA_MAXIMA) {
            self::salir();
            return null;
        }
        if ($ultimo > 0 && ($ahora - $ultimo) > self::INACTIVIDAD) {
            self::salir();
            return null;
        }
        $_SESSION['ultimo_actividad'] = $ahora;

        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT u.id_usuario, u.nombre, u.correo, u.activo, r.nombre AS rol, r.id_rol
               FROM usuarios u
               JOIN roles r ON r.id_rol = u.id_rol
              WHERE u.id_usuario = ? LIMIT 1'
        );
        $stmt->execute([(int) $_SESSION['usuario_id']]);
        $usuario = $stmt->fetch();

        if (!$usuario || (int) $usuario['activo'] !== 1) {
            self::salir();
            return null;
        }
        return $usuario;
    }

    public static function id(): ?int
    {
        $u = self::usuario();
        return $u ? (int) $u['id_usuario'] : null;
    }

    public static function rol(): ?string
    {
        $u = self::usuario();
        return $u ? (string) $u['rol'] : null;
    }

    /**
     * Hash de relleno para igualar el tiempo de respuesta.
     *
     * Sin esto, `password_verify` solo se ejecutaba cuando el correo existía,
     * porque `||` corta la evaluación: un correo inexistente respondía ~90 ms
     * antes que uno válido, y esa diferencia basta para enumerar cuentas con un
     * script.
     *
     * Tiene que ser un bcrypt **válido** de coste 10, igual que los reales. Un
     * texto cualquiera no sirve: `password_verify` lo rechaza en 1 ms sin pagar
     * el coste del algoritmo, que es justo lo que queremos pagar. Corresponde a
     * una contraseña aleatoria de 64 caracteres que no se guardó en ninguna
     * parte, así que ninguna entrada puede coincidir con él.
     */
    private const HASH_RELLENO = '$2y$10$LCL/AvgwmPLV3SqOfEJUQeTiVPowYcQJjgWznOq0iIEmjm5/I3Za.';

    public static function intentar(string $correo, string $clave): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT u.id_usuario, u.password_hash, u.activo
               FROM usuarios u WHERE u.correo = ? LIMIT 1'
        );
        $stmt->execute([mb_strtolower(trim($correo))]);
        $u = $stmt->fetch();

        $existe = $u && (int) $u['activo'] === 1;
        $hash = $existe ? (string) $u['password_hash'] : self::HASH_RELLENO;

        // Siempre se verifica, aunque la cuenta no exista: tiempo constante.
        $correcta = password_verify($clave, $hash);

        if (!$existe || !$correcta) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['usuario_id'] = (int) $u['id_usuario'];
        $_SESSION['iniciado_en'] = time();
        $_SESSION['ultimo_actividad'] = time();
        Csrf::rotar();

        // Si el algoritmo o el coste por omisión de PHP cambian, el hash se
        // actualiza aprovechando que aquí sí tenemos la contraseña en claro.
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $pdo->prepare('UPDATE usuarios SET password_hash = ? WHERE id_usuario = ?')
                ->execute([password_hash($clave, PASSWORD_DEFAULT), (int) $u['id_usuario']]);
        }

        $pdo->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = ?')
            ->execute([(int) $u['id_usuario']]);

        return true;
    }

    public static function salir(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool) $p['secure'], (bool) $p['httponly']);
        }
        session_destroy();

        // Se abre una sesión nueva y vacía. Sin esto, cualquier mensaje que el
        // controlador guardara después de cerrar sesión se perdía: los datos de
        // la sesión destruida ya no se escriben y su cookie se expira en la
        // misma respuesta, así que el usuario volvía al sitio sin confirmación
        // de que su sesión se había cerrado.
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);
        Csrf::rotar();
    }

    public static function requiere(array $roles = []): void
    {
        $u = self::usuario();
        if (!$u) {
            Flash::guardar('aviso', 'Inicia sesión para continuar.');
            // ruta_actual() quita el prefijo del directorio base: con la app
            // montada en un alias (p. ej. /conectaeventos) REQUEST_URI lo
            // incluye y el destino quedaría duplicado al redirigir.
            $destino = ruta_actual();
            $consulta = (string) ($_SERVER['QUERY_STRING'] ?? '');
            if ($consulta !== '') {
                $destino .= '?' . $consulta;
            }
            redirect('/login?destino=' . urlencode($destino));
        }
        if ($roles !== [] && !in_array($u['rol'], $roles, true)) {
            http_response_code(403);
            View::render('errores/403', ['titulo' => 'Acceso restringido'], 'app');
            exit;
        }
    }

    public static function puede(array|string $roles): bool
    {
        $rol = self::rol();
        if ($rol === null) {
            return false;
        }
        if ($rol === 'administrador') {
            return true;
        }
        return in_array($rol, (array) $roles, true);
    }
}
