<?php
/**
 * Funciones de apoyo compartidas por toda la aplicación.
 */
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Flash;

/* ------------------------------------------------------------------
 * Salida y URLs
 * ------------------------------------------------------------------ */

function e(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_url(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $configurada = trim((string) env('APP_URL', ''));
    if ($configurada !== '') {
        $base = rtrim($configurada, '/');
        return $base;
    }
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
    $base = ($dir === '.' || $dir === '/') ? '' : $dir;
    return $base;
}

function url(string $ruta = ''): string
{
    if (preg_match('#^https?://#i', $ruta)) {
        return $ruta;
    }
    $ruta = '/' . ltrim($ruta, '/');
    return base_url() . ($ruta === '/' ? '/' : $ruta);
}

function asset(string $ruta): string
{
    $ruta = '/' . ltrim($ruta, '/');
    $absoluta = BASE_PATH . $ruta;
    $version = is_file($absoluta) ? '?v=' . filemtime($absoluta) : '';
    return base_url() . $ruta . $version;
}

function ruta_actual(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = base_url();
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }
    $ruta = '/' . trim($uri, '/');
    if ($ruta === '/index.php') {
        $ruta = '/';
    }
    if (isset($_GET['r']) && $_GET['r'] !== '') {
        $ruta = '/' . trim((string) $_GET['r'], '/');
    }
    return $ruta;
}

function nav_activo(string ...$prefijos): string
{
    $actual = ruta_actual();
    foreach ($prefijos as $prefijo) {
        $prefijo = '/' . trim($prefijo, '/');
        if ($actual === $prefijo || str_starts_with($actual, $prefijo . '/')) {
            return ' activo';
        }
    }
    return '';
}

function redirect(string $ruta): never
{
    header('Location: ' . url($ruta));
    exit;
}

function volver(string $defecto = '/panel'): never
{
    $referer = trim((string) ($_SERVER['HTTP_REFERER'] ?? ''));
    $referer = str_replace(["\r", "\n"], '', $referer);

    $mismoOrigen = false;
    if ($referer !== '') {
        $hostRef = parse_url($referer, PHP_URL_HOST);
        if ($hostRef === null) {
            // Ruta relativa al mismo sitio.
            $mismoOrigen = str_starts_with($referer, '/') && !str_starts_with($referer, '//');
        } else {
            $base = base_url();
            $hostBase = $base !== '' ? parse_url($base, PHP_URL_HOST) : null;
            $hostBase ??= $_SERVER['HTTP_HOST'] ?? null;
            $mismoOrigen = $hostBase !== null && strcasecmp((string) $hostRef, (string) $hostBase) === 0;
        }
    }

    header('Location: ' . ($mismoOrigen ? $referer : url($defecto)));
    exit;
}

function es_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function query_actual(array $cambios = []): string
{
    $params = array_merge($_GET, $cambios);
    foreach ($params as $k => $v) {
        if ($v === null || $v === '') {
            unset($params[$k]);
        }
    }
    $qs = http_build_query($params);
    return $qs !== '' ? '?' . $qs : '';
}

/* ------------------------------------------------------------------
 * Formularios
 * ------------------------------------------------------------------ */

function csrf_field(): string
{
    return Csrf::campo();
}

function viejo(string $campo, mixed $defecto = ''): string
{
    $datos = $_SESSION['_old'] ?? [];
    return e($datos[$campo] ?? $defecto);
}

function recordar_viejos(array $datos): void
{
    unset($datos['_csrf'], $datos['password'], $datos['clave']);
    $_SESSION['_old'] = $datos;
}

function limpiar_viejos(): void
{
    unset($_SESSION['_old']);
}

function guardar_errores(array $errores): void
{
    $_SESSION['_errores'] = $errores;
}

function errores(): array
{
    $errores = $_SESSION['_errores'] ?? [];
    unset($_SESSION['_errores']);
    return $errores;
}

/**
 * Mensaje de error de un campo, sin tener que acarrear el arreglo.
 *
 * `errores()` vacia la sesion al leerla, asi que llamarla una vez por campo
 * devolveria el primero y perderia el resto. Aqui se lee una sola vez y se
 * guarda en estatico: la vista puede preguntar por cada campo sin pensar en
 * el orden ni en pasar el arreglo de un sitio a otro.
 */
function error_campo(string $campo): string
{
    static $errores = null;
    if ($errores === null) {
        $errores = errores();
    }
    return (string) ($errores[$campo] ?? '');
}

function error_de(string $campo, array $errores): string
{
    return isset($errores[$campo]) ? '<p class="campo-error">' . e($errores[$campo]) . '</p>' : '';
}

function clase_error(string $campo, array $errores): string
{
    return isset($errores[$campo]) ? ' tiene-error' : '';
}

/* ------------------------------------------------------------------
 * Formato
 * ------------------------------------------------------------------ */

function money(float|int|string|null $cantidad, string $moneda = 'MXN'): string
{
    $simbolo = match (strtoupper($moneda)) {
        'MXN' => '$',
        'USD' => 'US$',
        'EUR' => '€',
        default => $moneda . ' ',
    };
    return $simbolo . number_format((float) $cantidad, 2, '.', ',');
}

function money_corto(float|int|string|null $cantidad): string
{
    $n = (float) $cantidad;
    if (abs($n) >= 1000000) {
        return '$' . number_format($n / 1000000, 1, '.', ',') . 'M';
    }
    if (abs($n) >= 1000) {
        return '$' . number_format($n / 1000, 0, '.', ',') . 'k';
    }
    return '$' . number_format($n, 0, '.', ',');
}

function fecha(?string $valor, string $formato = 'd M Y'): string
{
    if (!$valor) {
        return '—';
    }
    $ts = strtotime($valor);
    if ($ts === false) {
        return '—';
    }
    $meses = ['01' => 'ene', '02' => 'feb', '03' => 'mar', '04' => 'abr', '05' => 'may', '06' => 'jun', '07' => 'jul', '08' => 'ago', '09' => 'sep', '10' => 'oct', '11' => 'nov', '12' => 'dic'];
    $mesesLargos = ['01' => 'enero', '02' => 'febrero', '03' => 'marzo', '04' => 'abril', '05' => 'mayo', '06' => 'junio', '07' => 'julio', '08' => 'agosto', '09' => 'septiembre', '10' => 'octubre', '11' => 'noviembre', '12' => 'diciembre'];
    $dias = ['Mon' => 'lun', 'Tue' => 'mar', 'Wed' => 'mié', 'Thu' => 'jue', 'Fri' => 'vie', 'Sat' => 'sáb', 'Sun' => 'dom'];
    return strtr($formato, [
        'H:i' => date('H:i', $ts),
        'F'   => $mesesLargos[date('m', $ts)] ?? date('F', $ts),
        'M'   => $meses[date('m', $ts)] ?? date('M', $ts),
        'D'   => $dias[date('D', $ts)] ?? date('D', $ts),
        'd'   => date('d', $ts),
        'j'   => date('j', $ts),
        'm'   => date('m', $ts),
        'Y'   => date('Y', $ts),
        'y'   => date('y', $ts),
    ]);
}

function fecha_larga(?string $valor): string
{
    return fecha($valor, 'D d F Y');
}

function fecha_hora(?string $valor): string
{
    if (!$valor) {
        return '—';
    }
    return fecha($valor, 'd M Y') . ' · ' . date('H:i', strtotime($valor));
}

function fecha_input(?string $valor): string
{
    if (!$valor) {
        return '';
    }
    $ts = strtotime($valor);
    return $ts ? date('Y-m-d\TH:i', $ts) : '';
}

function iniciales(?string $nombre): string
{
    $partes = preg_split('/\s+/', trim((string) $nombre)) ?: [];
    $letras = '';
    foreach ($partes as $parte) {
        if ($parte === '') {
            continue;
        }
        $letras .= mb_strtoupper(mb_substr($parte, 0, 1));
        if (mb_strlen($letras) >= 2) {
            break;
        }
    }
    return $letras !== '' ? $letras : 'CE';
}

function recortar(?string $texto, int $largo = 80, string $sufijo = '…'): string
{
    $texto = trim((string) preg_replace('/\s+/u', ' ', (string) $texto));
    if (mb_strlen($texto) <= $largo) {
        return $texto;
    }

    $corte = mb_substr($texto, 0, $largo);
    $espacio = mb_strrpos($corte, ' ');
    if ($espacio !== false && $espacio >= (int) ($largo * 0.6)) {
        $corte = mb_substr($corte, 0, $espacio);
    }

    return rtrim($corte, " ,.;:") . $sufijo;
}

function porcentaje(float|int $parte, float|int $total): float
{
    return $total > 0 ? round(($parte / $total) * 100, 1) : 0.0;
}

/**
 * Concuerda el sustantivo con la cantidad: plural(1, 'día', 'días') → "1 día".
 */
function plural(int|float $cantidad, string $singular, ?string $plural = null): string
{
    $numero = is_float($cantidad) && floor($cantidad) !== $cantidad
        ? rtrim(rtrim(number_format($cantidad, 2, '.', ''), '0'), '.')
        : (string) (int) $cantidad;

    $palabra = abs($cantidad) === 1.0 || abs($cantidad) === 1
        ? $singular
        : ($plural ?? $singular . 's');

    return $numero . ' ' . $palabra;
}

/* ------------------------------------------------------------------
 * Estados y badges
 * ------------------------------------------------------------------ */

function badge(string $texto, string $tono = 'neutro'): string
{
    return '<span class="badge badge--' . e($tono) . '">' . e($texto) . '</span>';
}

function badge_estado(?string $estado): string
{
    $mapa = [
        'prospecto'      => ['Prospecto', 'neutro'],
        'cotizado'       => ['Cotizado', 'info'],
        'confirmado'     => ['Confirmado', 'ok'],
        'en_preparacion' => ['En preparación', 'aviso'],
        'en_curso'       => ['En curso', 'acento'],
        'realizado'      => ['Realizado', 'ok'],
        'cerrado'        => ['Cerrado', 'ok'],
        'cancelado'      => ['Cancelado', 'error'],
        'pendiente'      => ['Pendiente', 'neutro'],
        'lista_espera'   => ['Lista de espera', 'aviso'],
        'declinado'      => ['Declinado', 'error'],
        'confirmada'     => ['Confirmada', 'ok'],
        'cancelada'      => ['Cancelada', 'error'],
        'consumida'      => ['Consumida', 'info'],
        'activo'         => ['Activo', 'ok'],
        'inactivo'       => ['Inactivo', 'neutro'],
        'mantenimiento'  => ['Mantenimiento', 'aviso'],
        'borrador'       => ['Borrador', 'neutro'],
        'enviada'        => ['Enviada', 'info'],
        'aceptada'       => ['Aceptada', 'ok'],
        'rechazada'      => ['Rechazada', 'error'],
        'liquidada'      => ['Liquidada', 'ok'],
        'parcial'        => ['Pago parcial', 'aviso'],
        'vencida'        => ['Vencida', 'error'],
        'registrado'     => ['Registrado', 'info'],
        'aplicado'       => ['Aplicado', 'ok'],
        'revertido'      => ['Revertido', 'error'],
        'entrada'        => ['Entrada', 'ok'],
        'reserva'        => ['Reserva', 'info'],
        'liberacion'     => ['Liberación', 'aviso'],
        'consumo'        => ['Consumo', 'neutro'],
        'ajuste'         => ['Ajuste', 'aviso'],
        'devuelto'       => ['Devuelto', 'error'],
        'pagado'         => ['Pagado', 'ok'],
    ];
    [$texto, $tono] = $mapa[$estado ?? ''] ?? [ucfirst(str_replace('_', ' ', (string) $estado)), 'neutro'];
    return badge($texto, $tono);
}

function icono(string $nombre, string $clase = ''): string
{
    static $iconos = [
        'panel'      => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'calendario' => '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'clientes'   => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13A4 4 0 0 1 16 11"/>',
        'invitados'  => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/>',
        'espacios'   => '<path d="M3 21h18M5 21V7l7-4 7 4v14"/><path d="M9 21v-6h6v6"/>',
        'recursos'   => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/>',
        'proveedores'=> '<path d="M10 17h4V5H2v12h3M20 17h2v-3.34a4 4 0 0 0-1.17-2.83L19 9h-5v8h1"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>',
        'reservas'   => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M9 16l2 2 4-4"/>',
        'insumos'    => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18M16 10a4 4 0 0 1-8 0"/>',
        'cotizacion' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>',
        'pagos'      => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h2M11 15h4"/>',
        'reportes'   => '<path d="M3 3v18h18"/><path d="m7 15 4-4 4 4 5-6"/>',
        'bitacora'   => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
        'usuarios'   => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>',
        'menu'       => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'buscar'     => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'campana'    => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>',
        'salir'      => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
        'mas'        => '<path d="M12 5v14M5 12h14"/>',
        'editar'     => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'eliminar'   => '<path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>',
        'ver'        => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
        'descargar'  => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>',
        'check'      => '<path d="M20 6 9 17l-5-5"/>',
        'x'          => '<path d="M18 6 6 18M6 6l12 12"/>',
        'flecha'     => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'atras'      => '<path d="M19 12H5M11 18l-6-6 6-6"/>',
        'reloj'      => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'ubicacion'  => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
        'correo'     => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
        'telefono'   => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2z"/>',
        'destello'   => '<path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3z"/>',
        'cubiertos'  => '<path d="M3 2v7a3 3 0 0 0 3 3 3 3 0 0 0 3-3V2M6 12v10M18 2v20M18 8h4V2h-4z"/>',
        'musica'     => '<path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>',
        'imprimir'   => '<path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>',
        'alerta'     => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>',
        'candado'    => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'llave'      => '<circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6M15.5 7.5l3 3L21 8l-3-3"/>',
        'refrescar'  => '<path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16M3 21v-5h5M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8M21 3v5h-5"/>',
        'filtrar'    => '<path d="M22 3H2l8 9.46V19l4 2v-8.54z"/>',
        'abajo'      => '<path d="m6 9 6 6 6-6"/>',
        'arriba'     => '<path d="m18 15-6-6-6 6"/>',
        'externo'    => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14 21 3"/>',
        'casa'       => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>',
        'basura'     => '<path d="M3 6h18M8 6V4h8v2M19 6l-1 15H6L5 6"/>',
        'paquete'    => '<path d="m7.5 4.3 9 5.2M21 8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/>',
        'grafico'    => '<path d="M3 3v18h18"/><rect x="7" y="12" width="3" height="6"/><rect x="12" y="8" width="3" height="10"/><rect x="17" y="5" width="3" height="13"/>',
        'corazon'    => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8z"/>',
    ];
    $svg = $iconos[$nombre] ?? $iconos['destello'];
    return '<svg class="icono ' . e($clase) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $svg . '</svg>';
}

/* ------------------------------------------------------------------
 * Sesión / permisos
 * ------------------------------------------------------------------ */

function usuario_actual(): ?array
{
    static $cache = false;
    if ($cache === false) {
        $cache = Auth::usuario();
    }
    return $cache;
}

function puede(array|string $roles): bool
{
    return Auth::puede($roles);
}

/**
 * Grupos de roles de lectura.
 *
 * Viven aquí, en un solo sitio, porque los usan dos capas que tienen que decir
 * lo mismo: las rutas de `index.php`, que devuelven 403, y las vistas, que
 * deciden qué bloques pintar. Si cada capa llevara su propia lista, bastaría
 * tocar una para que un rol viera un dato que su ruta ya le niega (o al revés,
 * para que reciba una página vacía sin explicación).
 *
 * `operativo` es la agenda del recinto: la necesitan los ocho roles.
 * Los demás grupos siguen la tabla de roles del README.
 */
function roles_grupo(string $grupo): array
{
    return match ($grupo) {
        'operativo' => ['administrador', 'comercial', 'coordinador', 'banquetes', 'operaciones', 'recepcion', 'finanzas', 'auditor'],
        'dinero'    => ['administrador', 'comercial', 'coordinador', 'finanzas', 'auditor'],
        'clientes'  => ['administrador', 'comercial', 'coordinador', 'finanzas', 'auditor'],
        'insumos'   => ['administrador', 'banquetes', 'operaciones', 'coordinador', 'auditor'],
        default     => ['administrador'],
    };
}

function rol_actual(): string
{
    return (string) (Auth::rol() ?? '');
}

function es_rol(string ...$roles): bool
{
    return in_array(rol_actual(), $roles, true);
}

/* ------------------------------------------------------------------
 * Paginación
 * ------------------------------------------------------------------ */

function pagina_actual(): int
{
    return max(1, (int) ($_GET['pagina'] ?? 1));
}

function paginacion(int $total, int $porPagina = 15): string
{
    $paginas = (int) ceil($total / max(1, $porPagina));
    $actual = min(pagina_actual(), max(1, $paginas));
    if ($paginas <= 1) {
        return '';
    }
    $html = '<nav class="paginacion" aria-label="Paginación">';
    $html .= '<a class="pagina' . ($actual <= 1 ? ' deshabilitada' : '') . '" href="' . e(query_actual(['pagina' => max(1, $actual - 1)])) . '">' . icono('atras') . '</a>';
    $inicio = max(1, $actual - 2);
    $fin = min($paginas, $actual + 2);
    if ($inicio > 1) {
        $html .= '<a class="pagina" href="' . e(query_actual(['pagina' => 1])) . '">1</a>';
        if ($inicio > 2) {
            $html .= '<span class="pagina-puntos">…</span>';
        }
    }
    for ($i = $inicio; $i <= $fin; $i++) {
        $html .= '<a class="pagina' . ($i === $actual ? ' activa' : '') . '" href="' . e(query_actual(['pagina' => $i])) . '">' . $i . '</a>';
    }
    if ($fin < $paginas) {
        if ($fin < $paginas - 1) {
            $html .= '<span class="pagina-puntos">…</span>';
        }
        $html .= '<a class="pagina" href="' . e(query_actual(['pagina' => $paginas])) . '">' . $paginas . '</a>';
    }
    $html .= '<a class="pagina' . ($actual >= $paginas ? ' deshabilitada' : '') . '" href="' . e(query_actual(['pagina' => min($paginas, $actual + 1)])) . '">' . icono('flecha') . '</a>';
    $html .= '</nav>';
    return $html;
}

/* ------------------------------------------------------------------
 * Utilidades de negocio
 * ------------------------------------------------------------------ */

function dias_entre(string $inicio, string $fin): array
{
    $dias = [];
    $cursor = new DateTimeImmutable(date('Y-m-d', strtotime($inicio)));
    $limite = new DateTimeImmutable(date('Y-m-d', strtotime($fin)));
    while ($cursor <= $limite) {
        $dias[] = $cursor->format('Y-m-d');
        $cursor = $cursor->modify('+1 day');
    }
    return $dias;
}

function registrar_bitacora(string $accion, string $entidad = '', ?int $idRegistro = null, string $detalle = ''): void
{
    try {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO bitacora (id_usuario, accion, entidad, id_registro, detalle, ip, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            Auth::id(),
            mb_substr($accion, 0, 80),
            mb_substr($entidad, 0, 60),
            $idRegistro,
            mb_substr($detalle, 0, 2000),
            $_SERVER['REMOTE_ADDR'] ?? null,
            mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    } catch (Throwable $e) {
        if ((bool) env('APP_DEBUG', false)) {
            error_log('Bitácora: ' . $e->getMessage());
        }
    }
}

function registrar_historial(string $entidad, int $idRegistro, string $campo, mixed $anterior, mixed $nuevo, string $motivo = ''): void
{
    $pdo = Database::pdo();
    $stmt = $pdo->prepare(
        'INSERT INTO historial_cambios (entidad, id_registro, campo, valor_anterior, valor_nuevo, motivo, id_usuario)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $entidad,
        $idRegistro,
        $campo,
        $anterior === null ? null : mb_substr((string) $anterior, 0, 255),
        $nuevo === null ? null : mb_substr((string) $nuevo, 0, 255),
        mb_substr($motivo, 0, 200),
        Auth::id(),
    ]);
}

function configuracion(string $clave, mixed $defecto = null): mixed
{
    static $cache = [];
    if (array_key_exists($clave, $cache)) {
        return $cache[$clave];
    }
    try {
        $stmt = Database::pdo()->prepare('SELECT valor FROM configuracion WHERE clave = ? LIMIT 1');
        $stmt->execute([$clave]);
        $valor = $stmt->fetchColumn();
        $cache[$clave] = $valor === false ? $defecto : $valor;
    } catch (Throwable) {
        $cache[$clave] = $defecto;
    }
    return $cache[$clave];
}

function saldo_cotizacion(int $idCotizacion): array
{
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('SELECT total, anticipo_requerido, estado FROM cotizaciones WHERE id_cotizacion = ?');
    $stmt->execute([$idCotizacion]);
    $cot = $stmt->fetch();
    if (!$cot) {
        return ['total' => 0, 'pagado' => 0, 'saldo' => 0];
    }
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN tipo = 'devolucion' THEN -monto ELSE monto END), 0)
                           FROM pagos WHERE id_cotizacion = ? AND estado <> 'revertido'");
    $stmt->execute([$idCotizacion]);
    $pagado = (float) $stmt->fetchColumn();
    return [
        'total'  => (float) $cot['total'],
        'pagado' => $pagado,
        'saldo'  => max(0, (float) $cot['total'] - $pagado),
        'estado' => $cot['estado'],
    ];
}

function nombre_archivo(string $texto): string
{
    $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;
    $texto = preg_replace('/[^A-Za-z0-9]+/', '-', $texto) ?: 'archivo';
    return strtolower(trim($texto, '-'));
}

function metodo_pago_etiqueta(string $metodo): string
{
    return match ($metodo) {
        'efectivo'      => 'Efectivo',
        'transferencia' => 'Transferencia',
        'tarjeta'       => 'Tarjeta',
        'cheque'        => 'Cheque',
        default         => ucfirst($metodo),
    };
}

/**
 * Banco de fotografias del sitio publico.
 *
 * Las imagenes viven en Pexels (licencia libre, sin atribucion obligatoria) y
 * la politica de seguridad del proyecto ya permite ese origen en `img-src`.
 * Se centralizan aqui, y no sueltas por las vistas, por tres razones: el
 * identificador se escribe una vez, el ancho se pide a medida en cada uso, y
 * si manana hay que cambiar de proveedor se toca un solo sitio.
 *
 * Todos los identificadores estan verificados: responden 200.
 */
function foto(string $clave, int $ancho = 1200): string
{
    static $banco = [
        // Portada y llamadas
        'portada'      => 2291462,  // nave con drapeados: tonos calidos y zona alta oscura
        'cta'          => 3171837,  // brindis
        // Tipos de evento
        'boda'         => 169198,   // pasillo de ceremonia con petalos
        'xv'           => 1616113,  // centros de mesa altos
        'corporativo'  => 1181396,  // sala de juntas
        'congreso'     => 7648047,  // asistentes con gafete en sesion
        'exposicion'   => 2608517,  // nave con montaje
        'graduacion'   => 267885,   // lanzamiento de birretes
        'social'       => 169190,   // mesa larga con flores y luces
        'concierto'    => 976866,   // publico en concierto
        // Espacios
        'salon_gala'      => 265947,  // salon de banquete con ventanales
        'sala_exhibicion' => 2608517,
        'salon_fiesta'    => 1114425, // cristaleria sobre mantel
        'terraza'         => 775219,  // mirador al exterior
        'jardin'          => 60217,   // palmeras al atardecer
        // Collage
        'collage-1'    => 2306281,
        'collage-2'    => 169190,
        'collage-3'    => 1157557,
        'collage-4'    => 3182812,
        'collage-5'    => 1540406,
        'collage-6'    => 1024993,
    ];

    $id = $banco[$clave] ?? $banco['portada'];
    return 'https://images.pexels.com/photos/' . $id . '/pexels-photo-' . $id
         . '.jpeg?auto=compress&cs=tinysrgb&w=' . $ancho;
}

/**
 * Foto para un tipo de evento, a partir de su nombre.
 *
 * Trabaja sobre el nombre y no sobre el id porque los tipos los administra el
 * usuario: si manana se agrega uno, cae en la portada en vez de romperse.
 */
function foto_tipo(string $nombre, int $ancho = 800): string
{
    $n = mb_strtolower($nombre);
    $clave = match (true) {
        str_contains($n, 'boda')       => 'boda',
        str_contains($n, 'xv')         => 'xv',
        str_contains($n, 'corporativ') => 'corporativo',
        str_contains($n, 'congreso')   => 'congreso',
        str_contains($n, 'expo')       => 'exposicion',
        str_contains($n, 'graduaci')   => 'graduacion',
        str_contains($n, 'concierto')  => 'concierto',
        str_contains($n, 'social')     => 'social',
        default                        => 'portada',
    };
    return foto($clave, $ancho);
}

/**
 * Nombre legible de una accion de bitacora.
 *
 * La columna `accion` guarda constantes (`ACEPTAR_COTIZACION`) porque sobre
 * ellas se filtra, se agrupa y se cuenta. Mostrarlas tal cual deja la pantalla
 * llena de mayusculas sin acentos, asi que la vista pide la etiqueta y la
 * constante se queda en la base. Lo que no este en el mapa se degrada a la
 * forma generica en vez de desaparecer.
 */
function accion_legible(string $accion): string
{
    static $mapa = [
        'INICIAR_SESION'           => 'Inicio de sesión',
        'CERRAR_SESION'            => 'Cierre de sesión',
        'LOGIN_FALLIDO'            => 'Intento de acceso fallido',
        'LOGIN_BLOQUEADO'          => 'Acceso bloqueado',
        'CREAR_CLIENTE'            => 'Alta de cliente',
        'EDITAR_CLIENTE'           => 'Edición de cliente',
        'ESTADO_CLIENTE'           => 'Cambio de estado del cliente',
        'CREAR_EVENTO'             => 'Alta de evento',
        'EDITAR_EVENTO'            => 'Edición de evento',
        'CAMBIAR_ESTADO_EVENTO'    => 'Cambio de estado del evento',
        'REPROGRAMAR_EVENTO'       => 'Reprogramación de evento',
        'CANCELAR_EVENTO'          => 'Cancelación de evento',
        'ACTUALIZAR_SERVICIOS'     => 'Actualización de servicios',
        'AGREGAR_PROGRAMA'         => 'Alta de bloque del programa',
        'AGREGAR_INVITADO'         => 'Alta de invitado',
        'IMPORTAR_INVITADOS'       => 'Importación de invitados',
        'ESTADO_INVITADO'          => 'Cambio de estado del invitado',
        'RSVP_INVITADO'            => 'Respuesta de invitado',
        'ASIGNAR_MESA'             => 'Asignación de mesa',
        'CREAR_MESA'               => 'Alta de mesa',
        'CHECKIN'                  => 'Registro de llegada',
        'CREAR_ESPACIO'            => 'Alta de espacio',
        'RESERVAR_ESPACIO'         => 'Reserva de espacio',
        'CANCELAR_RESERVA_ESPACIO' => 'Cancelación de reserva de espacio',
        'CREAR_RECURSO'            => 'Alta de recurso',
        'RESERVAR_RECURSO'         => 'Reserva de recurso',
        'CANCELAR_RESERVA_RECURSO' => 'Cancelación de reserva de recurso',
        'CANCELAR_RESERVA'         => 'Cancelación de reserva',
        'CREAR_PROVEEDOR'          => 'Alta de proveedor',
        'CREAR_INSUMO'             => 'Alta de insumo',
        'APARTAR_INSUMOS'          => 'Apartado de insumos',
        'LIBERAR_INSUMOS'          => 'Liberación de insumos',
        'CONSUMIR_INSUMOS'         => 'Consumo de insumos',
        'ENTRADA_INSUMO'           => 'Entrada de insumo',
        'AJUSTE_INSUMO'            => 'Ajuste de inventario',
        'CREAR_MENU'               => 'Alta de menú',
        'CONTRATAR_MENU'           => 'Contratación de menú',
        'CREAR_PLATILLO'           => 'Alta de platillo',
        'CREAR_COTIZACION'         => 'Alta de cotización',
        'ACEPTAR_COTIZACION'       => 'Aceptación de cotización',
        'CANCELAR_COTIZACION'      => 'Cancelación de cotización',
        'REGISTRAR_PAGO'           => 'Registro de pago',
        'EXPORTAR_REPORTE'         => 'Exportación de reporte',
        'REVISAR_INCONSISTENCIA'   => 'Revisión de inconsistencias',
        'CREAR_USUARIO'            => 'Alta de usuario',
        'EDITAR_USUARIO'           => 'Edición de usuario',
        'LEAD_SITIO'               => 'Contacto desde el sitio',
        'ESTADO_SOLICITUD'         => 'Seguimiento de solicitud',
        'CONVERTIR_SOLICITUD'      => 'Solicitud convertida en evento',
    ];

    return $mapa[$accion] ?? ucfirst(mb_strtolower(str_replace('_', ' ', $accion)));
}

/**
 * Icono que corresponde a una accion de bitacora.
 *
 * El timeline pintaba el mismo destello en todas las filas, asi que la columna
 * de iconos no decia nada. Se decide por lo que toca la accion, no por su
 * nombre exacto: asi una accion nueva cae en el icono de su familia sin tener
 * que volver aqui.
 */
function accion_icono(string $accion): string
{
    return match (true) {
        str_contains($accion, 'SESION'), str_contains($accion, 'LOGIN') => 'llave',
        str_contains($accion, 'PAGO')                                   => 'pagos',
        str_contains($accion, 'COTIZACION')                             => 'cotizacion',
        str_contains($accion, 'RESERVA')                                => 'reservas',
        str_contains($accion, 'INSUMO')                                 => 'insumos',
        str_contains($accion, 'MENU'), str_contains($accion, 'PLATILLO') => 'cubiertos',
        str_contains($accion, 'CLIENTE')                                => 'clientes',
        str_contains($accion, 'INVITADO'), str_contains($accion, 'MESA'),
        str_contains($accion, 'RSVP'), str_contains($accion, 'CHECKIN') => 'invitados',
        str_contains($accion, 'ESPACIO')                                => 'espacios',
        str_contains($accion, 'RECURSO')                                => 'recursos',
        str_contains($accion, 'PROVEEDOR')                              => 'proveedores',
        str_contains($accion, 'USUARIO')                                => 'usuarios',
        str_contains($accion, 'EVENTO'), str_contains($accion, 'PROGRAMA') => 'calendario',
        str_contains($accion, 'REPORTE'), str_contains($accion, 'INCONSISTENCIA') => 'reportes',
        str_contains($accion, 'LEAD'), str_contains($accion, 'SOLICITUD') => 'correo',
        default                                                         => 'destello',
    };
}

/**
 * Sello de un proveedor: su logotipo si lo tiene, su monograma si no.
 *
 * El nombre del archivo se valida contra los que existen de verdad en
 * `assets/img/proveedores/`. Es una columna de la base y la base la escribe un
 * formulario, asi que un `../../.env` ahi dentro no debe acabar en un `src`:
 * lo que no este en la lista se trata como si no hubiera logo.
 */
function sello_proveedor(array $proveedor, int $tamano = 52): string
{
    $color = (string) ($proveedor['color_marca'] ?? '#1f6b4f');
    if (preg_match('/^#[0-9a-fA-F]{6}$/', $color) !== 1) {
        $color = '#1f6b4f';
    }

    $archivo = (string) ($proveedor['logo'] ?? '');
    if ($archivo !== '' && in_array($archivo, logos_disponibles(), true)) {
        return '<img class="sello-marca" src="' . e(asset('assets/img/proveedores/' . $archivo)) . '"'
            . ' alt="" width="' . $tamano . '" height="' . $tamano . '" loading="lazy" decoding="async">';
    }

    return '<span class="sello-marca sello-marca--texto" style="--marca: ' . e($color) . '">'
        . e(marca_monograma((string) ($proveedor['nombre'] ?? ''))) . '</span>';
}

/**
 * Logotipos de proveedor que existen en disco.
 *
 * Se lee una vez por peticion. Sirve de lista blanca al pintar y de catalogo
 * al elegir logo en el panel, de modo que las dos cosas no puedan discrepar.
 *
 * @return list<string>
 */
function logos_disponibles(): array
{
    static $lista = null;
    if ($lista !== null) {
        return $lista;
    }
    $rutas = glob(dirname(__DIR__, 2) . '/assets/img/proveedores/*.svg') ?: [];
    $lista = array_values(array_map('basename', $rutas));
    sort($lista);
    return $lista;
}

/**
 * Monograma de una marca: las iniciales con que se dibuja su sello.
 *
 * Se saltan las particulas (`y`, `de`, `del`, `la`...) porque no son parte del
 * nombre comercial: «Sillas y Mesas del Valle» da SM, no SY. Si el nombre es
 * de una sola palabra se toman sus dos primeras letras, que es lo que hace
 * cualquier identidad de una sola palabra.
 */
/**
 * Solicitudes del sitio que nadie ha atendido todavia.
 *
 * La barra lateral la pinta como contador. Se consulta una vez por peticion y
 * un fallo de base devuelve cero: un numerito no puede tumbar el menu.
 */
function solicitudes_sin_atender(): int
{
    static $total = null;
    if ($total !== null) {
        return $total;
    }
    try {
        $total = (int) Database::pdo()
            ->query("SELECT COUNT(*) FROM leads WHERE estado = 'nuevo'")
            ->fetchColumn();
    } catch (Throwable) {
        $total = 0;
    }
    return $total;
}

/**
 * Nombre comercial del recinto.
 *
 * Vive en la tabla `configuracion`, no escrito en las vistas: cambiar de
 * nombre tiene que ser cambiar una fila, no setenta literales repartidos por
 * el proyecto. El valor por defecto solo cubre el caso de que la base no
 * responda, que es justo cuando se pinta la pantalla de error.
 */
function marca(): string
{
    return (string) configuracion('empresa_nombre', 'Solera');
}

/**
 * Simbolo de la casa, en SVG dentro del documento.
 *
 * Va inline y no como <img> para que herede `currentColor`: el mismo trazo
 * sirve en tinta sobre marfil y en blanco sobre tinta, sin un archivo por
 * variante y sin una peticion mas.
 */
function logo(string $clase = 'logo'): string
{
    return '<svg class="' . e($clase) . '" viewBox="0 0 32 32" fill="none" aria-hidden="true" focusable="false">'
        . '<path d="M4 27.2h24" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>'
        . '<path d="M6.8 27.2V16.2a9.2 9.2 0 0 1 18.4 0v11" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/>'
        . '<path d="M12.9 27.2v-8.6a3.1 3.1 0 0 1 6.2 0v8.6" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/>'
        . '</svg>';
}

function marca_monograma(string $nombre): string
{
    $particulas = ['y', 'e', 'de', 'del', 'la', 'las', 'el', 'los', 'en', 'con', 'para', 'a'];

    $palabras = array_values(array_filter(
        preg_split('/[\s\-]+/u', trim($nombre)) ?: [],
        static fn (string $palabra): bool => $palabra !== ''
            && !in_array(mb_strtolower($palabra), $particulas, true)
    ));

    if ($palabras === []) {
        return '??';
    }
    if (count($palabras) === 1) {
        return mb_strtoupper(mb_substr($palabras[0], 0, 2));
    }

    return mb_strtoupper(mb_substr($palabras[0], 0, 1) . mb_substr($palabras[1], 0, 1));
}

/**
 * Etiqueta legible de la categoria de un proveedor.
 *
 * Comparte la lista con `recursos.categoria`: lo que un proveedor suministra
 * es lo que el inventario registra de el.
 */
function categoria_proveedor(string $categoria): string
{
    return [
        'mobiliario'  => 'Mobiliario',
        'personal'    => 'Personal',
        'audio_video' => 'Audio y video',
        'decoracion'  => 'Decoración',
        'transporte'  => 'Transporte',
        'otro'        => 'Servicios',
    ][$categoria] ?? ucfirst($categoria);
}

function tipo_evento_icono(string $nombre): string
{
    $n = mb_strtolower($nombre);
    return match (true) {
        str_contains($n, 'boda')        => 'corazon',
        str_contains($n, 'xv')          => 'destello',
        str_contains($n, 'corporativ')  => 'grafico',
        str_contains($n, 'congreso')    => 'usuarios',
        str_contains($n, 'expo')        => 'espacios',
        str_contains($n, 'graduaci')    => 'musica',
        str_contains($n, 'concierto')   => 'musica',
        default                         => 'calendario',
    };
}

function flash_render(): string
{
    $html = '';
    foreach (Flash::obtener() as $m) {
        $tipo = in_array($m['tipo'], ['ok', 'error', 'aviso', 'info'], true) ? $m['tipo'] : 'info';
        $icono = match ($tipo) {
            'ok' => 'check',
            'error' => 'x',
            'aviso' => 'alerta',
            default => 'destello',
        };
        $html .= '<div class="flash flash--' . $tipo . '" role="status">' . icono($icono) . '<span>' . e($m['mensaje']) . '</span><button type="button" class="flash-cerrar" aria-label="Cerrar">' . icono('x') . '</button></div>';
    }
    return $html;
}
