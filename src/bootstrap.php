<?php
/**
 * Solera · arranque de la aplicación
 */
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_VERSION', '1.4.0');

/* ---------------------------------------------------------------
 * Carga de variables de entorno (.env y .env.local)
 * --------------------------------------------------------------- */
function cargar_env(string $archivo, bool $sobrescribir = false): void
{
    if (!is_file($archivo)) {
        return;
    }
    $lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lineas as $linea) {
        $linea = trim($linea);
        if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
            continue;
        }
        [$clave, $valor] = array_pad(explode('=', $linea, 2), 2, '');
        $clave = trim($clave);
        $valor = trim($valor);
        if (strlen($valor) > 1 && (($valor[0] === '"' && str_ends_with($valor, '"')) || ($valor[0] === "'" && str_ends_with($valor, "'")))) {
            $valor = substr($valor, 1, -1);
        }
        if (!$sobrescribir && (getenv($clave) !== false || isset($_ENV[$clave]))) {
            continue;
        }
        putenv($clave . '=' . $valor);
        $_ENV[$clave] = $valor;
        $_SERVER[$clave] = $valor;
    }
}

cargar_env(BASE_PATH . '/.env');
cargar_env(BASE_PATH . '/.env.local', true);

function env(string $clave, mixed $defecto = null): mixed
{
    $valor = getenv($clave);
    if ($valor === false || $valor === '') {
        return $defecto;
    }
    return match (strtolower((string) $valor)) {
        'true', '(true)'   => true,
        'false', '(false)' => false,
        'null', '(null)'   => null,
        default            => $valor,
    };
}

date_default_timezone_set((string) env('APP_TIMEZONE', 'America/Mexico_City'));
mb_internal_encoding('UTF-8');

if ((bool) env('APP_DEBUG', false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', BASE_PATH . '/storage/logs/php-error.log');
}

/* ---------------------------------------------------------------
 * Autoload PSR-4 simple: App\ => src/App/
 * --------------------------------------------------------------- */
spl_autoload_register(static function (string $clase): void {
    $prefijo = 'App\\';
    if (!str_starts_with($clase, $prefijo)) {
        return;
    }
    $ruta = BASE_PATH . '/src/App/' . str_replace('\\', '/', substr($clase, strlen($prefijo))) . '.php';
    if (is_file($ruta)) {
        require $ruta;
    }
});

require BASE_PATH . '/src/Support/helpers.php';

/* ---------------------------------------------------------------
 * Sesión segura
 * --------------------------------------------------------------- */
if (session_status() !== PHP_SESSION_ACTIVE) {
    $segura = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $segura,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('conectaeventos_sid');
    session_start();
}

/* Mensajes flash y token CSRF disponibles desde el primer request */
App\Core\Flash::iniciar();
App\Core\Csrf::token();
