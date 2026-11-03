<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\View;

final class AuthController extends Controller
{
    public function formulario(): void
    {
        if (Auth::usuario()) {
            redirect('/panel');
        }
        $destino = $this->destino($_GET['destino'] ?? '/panel');
        View::render('auth/login', [
            'titulo'  => 'Iniciar sesión',
            'destino' => $destino,
        ], 'auth');
    }

    public function entrar(): void
    {
        $correo = mb_strtolower($this->texto('correo'));
        $clave = (string) ($_POST['clave'] ?? '');
        $destino = $this->destino($_POST['destino'] ?? '/panel');

        // Límite del lado del servidor: no depende de la cookie de sesión.
        // No se registra en bitácora cada intento ya bloqueado: eso haría crecer
        // la tabla sin aportar nada y alargaría la ventana de forma indefinida,
        // porque el propio conteo lee de ahí.
        if ($this->intentosFallidos() >= 10) {
            Flash::guardar('error', 'Demasiados intentos fallidos desde esta conexión. Espera 10 minutos.');
            redirect('/login');
        }

        // Límite por cuenta: el de arriba es por IP, y una IP rotativa lo evade.
        // Sin esto, un ataque distribuido contra el correo del administrador no
        // tiene techo. Se cuentan los fallos de los últimos 15 minutos.
        if ($correo !== '' && $this->intentosPorCuenta($correo) >= 8) {
            Flash::guardar('error', 'Esta cuenta quedó bloqueada temporalmente por intentos fallidos. Espera 15 minutos.');
            redirect('/login');
        }

        $intentos = $_SESSION['_intentos_login'] ?? ['n' => 0, 't' => time()];
        if ($intentos['n'] >= 6 && (time() - $intentos['t']) < 600) {
            Flash::guardar('error', 'Demasiados intentos fallidos. Espera 10 minutos antes de volver a intentar.');
            redirect('/login');
        }
        if ((time() - $intentos['t']) > 600) {
            $intentos = ['n' => 0, 't' => time()];
        }

        if ($correo === '' || $clave === '') {
            Flash::guardar('error', 'Escribe tu correo y contraseña.');
            recordar_viejos(['correo' => $correo]);
            redirect('/login?destino=' . urlencode($destino));
        }

        if (!Auth::intentar($correo, $clave)) {
            $intentos['n']++;
            $intentos['t'] = $intentos['n'] === 1 ? time() : $intentos['t'];
            $_SESSION['_intentos_login'] = $intentos;
            registrar_bitacora(
                'LOGIN_FALLIDO',
                'usuarios',
                null,
                'Credenciales rechazadas para ' . $this->correoSeguro($correo) . ' #' . $this->huellaCuenta($correo)
            );
            usleep(400000); // frena la fuerza bruta sin delatar si la cuenta existe
            Flash::guardar('error', 'Credenciales incorrectas o usuario inactivo.');
            recordar_viejos(['correo' => $correo]);
            redirect('/login?destino=' . urlencode($destino));
        }

        unset($_SESSION['_intentos_login'], $_SESSION['_old']);
        registrar_bitacora('INICIAR_SESION', 'usuarios', Auth::id(), 'Acceso al panel');
        Flash::guardar('ok', 'Bienvenido de nuevo, ' . (Auth::usuario()['nombre'] ?? 'equipo') . '.');
        redirect($destino);
    }

    /**
     * Huella estable de la cuenta.
     *
     * Permite contar los intentos de una misma cuenta sin guardar el correo en
     * claro en la bitácora. No es reversible y no identifica a nadie por sí
     * sola; solo sirve para agrupar.
     */
    private function huellaCuenta(string $correo): string
    {
        return substr(hash('sha256', 'login|' . mb_strtolower(trim($correo))), 0, 20);
    }

    /**
     * El valor del campo correo se registra solo si de verdad parece un correo.
     *
     * Si no lo parece, se omite: alguien que escribe su contraseña en la casilla
     * del correo no debe dejarla en texto plano dentro de la bitácora, que es
     * precisamente una tabla que los auditores consultan.
     */
    private function correoSeguro(string $correo): string
    {
        if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return 'un valor que no tiene forma de correo';
        }
        return mb_substr($correo, 0, 120);
    }

    /** Intentos fallidos contra esta cuenta en los últimos 15 minutos. */
    private function intentosPorCuenta(string $correo): int
    {
        try {
            $stmt = \App\Core\Database::pdo()->prepare(
                "SELECT COUNT(*) FROM bitacora
                  WHERE accion = 'LOGIN_FALLIDO'
                    AND entidad = 'usuarios'
                    AND detalle LIKE ?
                    AND fecha >= (NOW() - INTERVAL 15 MINUTE)"
            );
            $stmt->execute(['%#' . $this->huellaCuenta($correo)]);
            return (int) $stmt->fetchColumn();
        } catch (\Throwable) {
            return 0;
        }
    }

    /** Intentos fallidos registrados para esta IP en los últimos 10 minutos. */
    private function intentosFallidos(): int
    {
        try {
            $stmt = \App\Core\Database::pdo()->prepare(
                "SELECT COUNT(*) FROM bitacora
                  WHERE accion IN ('LOGIN_FALLIDO', 'LOGIN_BLOQUEADO')
                    AND ip = ? AND fecha >= (NOW() - INTERVAL 10 MINUTE)"
            );
            $stmt->execute([$_SERVER['REMOTE_ADDR'] ?? '']);
            return (int) $stmt->fetchColumn();
        } catch (\Throwable) {
            return 0;
        }
    }

    public function salir(): void
    {
        if (Auth::id()) {
            registrar_bitacora('CERRAR_SESION', 'usuarios', Auth::id(), 'Salida del panel');
        }
        Auth::salir();
        Flash::guardar('info', 'Tu sesión se cerró correctamente.');
        redirect('/');
    }

    private function destino(mixed $valor): string
    {
        $ruta = trim((string) $valor);
        if ($ruta === '' || $ruta[0] !== '/' || str_starts_with($ruta, '//') || str_contains($ruta, '\\')) {
            return '/panel';
        }
        if (!preg_match('#^/[A-Za-z0-9/_\-.,?=&%\#]*$#', $ruta) || str_contains($ruta, '://')) {
            return '/panel';
        }
        return $ruta;
    }
}
