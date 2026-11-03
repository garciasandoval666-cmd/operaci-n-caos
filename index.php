<?php
/**
 * ConectaEventos · front controller
 */
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Router;
use App\Core\View;
use App\Controllers\AuthController;
use App\Controllers\BitacoraController;
use App\Controllers\ClientesController;
use App\Controllers\CotizacionesController;
use App\Controllers\EspaciosController;
use App\Controllers\EventosController;
use App\Controllers\InsumosController;
use App\Controllers\InvitadosController;
use App\Controllers\MenusController;
use App\Controllers\PagosController;
use App\Controllers\PanelController;
use App\Controllers\ProveedoresController;
use App\Controllers\RecursosController;
use App\Controllers\ReportesController;
use App\Controllers\ReservasController;
use App\Controllers\RsvpController;
use App\Controllers\SitioController;
use App\Controllers\UsuariosController;

/* ---------------------------------------------------------------
 * Comprobación temprana de la base de datos
 * --------------------------------------------------------------- */
if (!Database::intentar()) {
    http_response_code(503);
    View::render('errores/base-datos', [
        'titulo' => 'Base de datos no disponible',
        'error'  => (bool) env('APP_DEBUG', false) ? Database::error() : null,
    ], null);
    exit;
}

/* ---------------------------------------------------------------
 * Resolución de la ruta (soporta subcarpeta y ?r=)
 * --------------------------------------------------------------- */
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
if ($scriptDir !== '' && $scriptDir !== '/' && str_starts_with($uri, $scriptDir)) {
    $uri = substr($uri, strlen($scriptDir));
}
$ruta = '/' . trim($uri, '/');
if ($ruta === '/index.php') {
    $ruta = '/';
}
if (isset($_GET['r'])) {
    $ruta = '/' . trim((string) $_GET['r'], '/');
}

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

/* CSRF en cualquier POST */
Csrf::verificar();

/* ---------------------------------------------------------------
 * Rutas
 * --------------------------------------------------------------- */
$router = new Router();

$admin = ['administrador'];
$comercial = ['administrador', 'comercial'];
$operacion = ['administrador', 'coordinador', 'operaciones'];
$finanzas = ['administrador', 'finanzas'];

/* Lectura operativa: la agenda del recinto la necesitan los ocho roles. */
$lectura = roles_grupo('operativo');

/* Lecturas con dato sensible. No basta ocultar el enlace en la barra lateral:
 * sin esta separación, cualquier rol que escriba la URL a mano veía dinero y
 * datos de contacto que no le corresponden según la tabla de roles del README.
 * Las listas están en roles_grupo() para que las vistas usen las mismas. */
$leeDinero = roles_grupo('dinero');
$leeClientes = roles_grupo('clientes');
$leeInsumos = roles_grupo('insumos');

/* Público */
$router->get('/', [SitioController::class, 'inicio'], true);
$router->get('/boda', [SitioController::class, 'boda'], true);
$router->post('/contacto', [SitioController::class, 'contacto'], true);
$router->get('/login', [AuthController::class, 'formulario'], true);
$router->post('/login', [AuthController::class, 'entrar'], true);
$router->post('/logout', [AuthController::class, 'salir']);
$router->get('/rsvp', [RsvpController::class, 'formulario'], true);
$router->post('/rsvp', [RsvpController::class, 'responder'], true);
$router->get('/sitio/disponibilidad', [SitioController::class, 'disponibilidad'], true);
$router->get('/salud', [SitioController::class, 'salud'], true);

/* Panel y búsqueda */
$router->get('/panel', [PanelController::class, 'index'], false, $lectura);
$router->get('/buscar', [PanelController::class, 'buscar'], false, $lectura);

/* Clientes */
$router->get('/clientes', [ClientesController::class, 'index'], false, $leeClientes);
$router->get('/clientes/crear', [ClientesController::class, 'crear'], false, $comercial);
$router->post('/clientes', [ClientesController::class, 'guardar'], false, $comercial);
$router->get('/clientes/{id}', [ClientesController::class, 'ver'], false, $leeClientes);
$router->get('/clientes/{id}/editar', [ClientesController::class, 'editar'], false, $comercial);
$router->post('/clientes/{id}', [ClientesController::class, 'actualizar'], false, $comercial);
$router->post('/clientes/{id}/estado', [ClientesController::class, 'estado'], false, $comercial);

/* Eventos */
$router->get('/eventos', [EventosController::class, 'index'], false, $lectura);
$router->get('/eventos/crear', [EventosController::class, 'crear'], false, $comercial);
$router->post('/eventos', [EventosController::class, 'guardar'], false, $comercial);
$router->get('/eventos/{id}', [EventosController::class, 'ver'], false, $lectura);
$router->get('/eventos/{id}/editar', [EventosController::class, 'editar'], false, $comercial);
$router->post('/eventos/{id}', [EventosController::class, 'actualizar'], false, $comercial);
$router->post('/eventos/{id}/fecha', [EventosController::class, 'cambiarFecha'], false, $comercial);
$router->post('/eventos/{id}/estado', [EventosController::class, 'estado'], false, $operacion);
$router->post('/eventos/{id}/cancelar', [EventosController::class, 'cancelar'], false, ['administrador', 'coordinador']);
$router->get('/eventos/{id}/historial', [EventosController::class, 'historial'], false, $lectura);
$router->post('/eventos/{id}/programa', [EventosController::class, 'programa'], false, $operacion);
$router->post('/eventos/{id}/servicios', [EventosController::class, 'servicios'], false, $comercial);
$router->get('/eventos/{id}/produccion', [MenusController::class, 'produccion'], false, ['administrador', 'coordinador', 'banquetes']);
$router->post('/eventos/{id}/menus', [MenusController::class, 'contratar'], false, ['administrador', 'coordinador', 'banquetes']);

/* Invitados */
$router->get('/invitados', [InvitadosController::class, 'index'], false, $lectura);
$router->get('/eventos/{id}/invitados', [InvitadosController::class, 'lista'], false, $lectura);
$router->post('/eventos/{id}/invitados', [InvitadosController::class, 'guardar'], false, $operacion);
$router->post('/invitados/{id}/estado', [InvitadosController::class, 'estado'], false, $operacion);
$router->get('/invitados/importar', [InvitadosController::class, 'importarForm'], false, $operacion);
$router->post('/eventos/{id}/invitados/importar', [InvitadosController::class, 'importar'], false, $operacion);
$router->get('/eventos/{id}/mesas', [InvitadosController::class, 'mesas'], false, $operacion);
$router->post('/eventos/{id}/mesas', [InvitadosController::class, 'crearMesa'], false, $operacion);
$router->post('/invitados/{id}/mesa', [InvitadosController::class, 'asignarMesa'], false, $operacion);
$router->get('/checkin', [InvitadosController::class, 'checkin'], false, ['administrador', 'coordinador', 'recepcion']);
$router->post('/checkin', [InvitadosController::class, 'registrarCheckin'], false, ['administrador', 'coordinador', 'recepcion']);

/* Menús y platillos */
$router->get('/menus', [MenusController::class, 'index'], false, $lectura);
$router->post('/menus', [MenusController::class, 'guardarMenu'], false, ['administrador', 'banquetes']);
$router->get('/menus/platillos', [MenusController::class, 'platillos'], false, $lectura);
$router->post('/menus/platillos', [MenusController::class, 'guardarPlatillo'], false, ['administrador', 'banquetes']);

/* Espacios y recursos */
$router->get('/espacios', [EspaciosController::class, 'index'], false, $lectura);
$router->post('/espacios', [EspaciosController::class, 'guardar'], false, ['administrador', 'operaciones']);
$router->get('/espacios/{id}/calendario', [EspaciosController::class, 'calendario'], false, $lectura);
$router->get('/espacios/disponibilidad', [EspaciosController::class, 'disponibilidad'], false, $lectura);

$router->get('/recursos', [RecursosController::class, 'index'], false, $lectura);
$router->post('/recursos', [RecursosController::class, 'guardar'], false, ['administrador', 'operaciones']);
$router->get('/recursos/disponibilidad', [RecursosController::class, 'disponibilidad'], false, $lectura);

$router->get('/proveedores', [ProveedoresController::class, 'index'], false, $lectura);
$router->post('/proveedores', [ProveedoresController::class, 'guardar'], false, ['administrador', 'operaciones']);

/* Reservas */
$router->get('/reservas', [ReservasController::class, 'index'], false, $lectura);
$router->get('/reservas/crear', [ReservasController::class, 'crear'], false, $operacion);
$router->post('/reservas', [ReservasController::class, 'guardar'], false, $operacion);
$router->post('/reservas/{id}/cancelar', [ReservasController::class, 'cancelar'], false, $operacion);

/* Insumos */
$router->get('/insumos', [InsumosController::class, 'index'], false, $leeInsumos);
$router->post('/insumos', [InsumosController::class, 'guardar'], false, ['administrador', 'banquetes']);
$router->get('/insumos/{id}/kardex', [InsumosController::class, 'kardex'], false, $leeInsumos);
$router->post('/insumos/movimiento', [InsumosController::class, 'movimiento'], false, ['administrador', 'banquetes']);
$router->post('/eventos/{id}/insumos', [InsumosController::class, 'apartar'], false, ['administrador', 'banquetes']);

/* Cotizaciones y pagos */
$router->get('/cotizaciones', [CotizacionesController::class, 'index'], false, $leeDinero);
$router->get('/eventos/{id}/cotizacion/crear', [CotizacionesController::class, 'crear'], false, $comercial);
$router->post('/cotizaciones', [CotizacionesController::class, 'guardar'], false, $comercial);
$router->get('/cotizaciones/{id}', [CotizacionesController::class, 'ver'], false, $leeDinero);
$router->post('/cotizaciones/{id}/aceptar', [CotizacionesController::class, 'aceptar'], false, ['administrador', 'comercial', 'finanzas']);
$router->post('/cotizaciones/{id}/cancelar', [CotizacionesController::class, 'cancelar'], false, ['administrador', 'comercial', 'finanzas']);

$router->get('/pagos', [PagosController::class, 'index'], false, $leeDinero);
$router->post('/pagos', [PagosController::class, 'registrar'], false, $finanzas);

/* Reportes, bitácora y usuarios */
$router->get('/reportes', [ReportesController::class, 'index'], false, ['administrador', 'finanzas', 'auditor', 'coordinador']);
$router->get('/reportes/ocupacion', [ReportesController::class, 'ocupacion'], false, ['administrador', 'operaciones', 'auditor']);
$router->get('/reportes/produccion', [ReportesController::class, 'produccion'], false, ['administrador', 'banquetes', 'coordinador']);
$router->get('/reportes/auditoria', [ReportesController::class, 'auditoria'], false, ['administrador', 'auditor']);
$router->get('/reportes/inconsistencias', [ReportesController::class, 'inconsistencias'], false, ['administrador', 'auditor']);
$router->get('/reportes/exportar', [ReportesController::class, 'exportar'], false, ['administrador', 'finanzas', 'auditor']);

$router->get('/bitacora', [BitacoraController::class, 'index'], false, ['administrador', 'auditor']);
$router->get('/usuarios', [UsuariosController::class, 'index'], false, $admin);
$router->post('/usuarios', [UsuariosController::class, 'guardar'], false, $admin);
$router->post('/usuarios/{id}', [UsuariosController::class, 'actualizar'], false, $admin);

/* ---------------------------------------------------------------
 * Despacho con manejo de errores
 * --------------------------------------------------------------- */
try {
    $router->despachar($metodo, $ruta);
} catch (Throwable $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    View::render('errores/500', [
        'titulo' => 'Ocurrió un problema',
        'error'  => (bool) env('APP_DEBUG', false) ? $e : null,
    ], App\Core\Auth::usuario() ? 'app' : 'sitio');
}
