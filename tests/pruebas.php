<?php
/**
 * Solera · matriz de pruebas de integración
 *
 * Ejecuta los casos críticos de los cuatro módulos directamente contra la capa
 * de dominio. Todo el recorrido ocurre dentro de una transacción externa con un
 * SAVEPOINT por caso y se revierte al terminar: la base de datos demo queda
 * exactamente igual, incluso si alguna prueba falla.
 *
 * Uso:
 *   php tests/pruebas.php
 *
 * Si la base local escucha en un puerto distinto al de .env (que apunta al MySQL
 * de XAMPP en 3306), define DB_PORT antes de ejecutar:
 *   PowerShell:  $env:DB_PORT='3310'; php tests/pruebas.php
 *   Bash:        DB_PORT=3310 php tests/pruebas.php
 *
 * Código de salida: 0 = todas las pruebas pasaron, 1 = hubo fallas.
 */
declare(strict_types=1);

// Esta matriz solo corre desde la terminal: nunca por HTTP.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\Database;
use App\Domain\Cotizaciones;
use App\Domain\Disponibilidad;
use App\Domain\Eventos;
use App\Domain\Insumos;
use App\Domain\Invitados;
use App\Domain\Solicitudes;

$pdo = Database::pdo();

/* ------------------------------------------------------------------
 * Infraestructura mínima de pruebas
 * ------------------------------------------------------------------ */

$GLOBALS['pruebas_total'] = 0;
$GLOBALS['pruebas_ok'] = 0;
$GLOBALS['pruebas_fallidas'] = [];

function caso(string $nombre, callable $fn): void
{
    global $pdo;
    $GLOBALS['pruebas_total']++;
    $numero = (int) $GLOBALS['pruebas_total'];
    $savepoint = 'caso_' . $numero;

    $pdo->exec('SAVEPOINT ' . $savepoint);
    try {
        $fn();
        $pdo->exec('RELEASE SAVEPOINT ' . $savepoint);
        $GLOBALS['pruebas_ok']++;
        printf("  OK    %02d · %s\n", $numero, $nombre);
    } catch (Throwable $e) {
        try {
            $pdo->exec('ROLLBACK TO SAVEPOINT ' . $savepoint);
            $pdo->exec('RELEASE SAVEPOINT ' . $savepoint);
        } catch (Throwable) {
            // Si el savepoint ya no existe, la transacción externa sigue siendo válida.
        }
        $GLOBALS['pruebas_fallidas'][] = ['nombre' => $nombre, 'error' => $e->getMessage()];
        printf("  FALLA %02d · %s\n         → %s\n", $numero, $nombre, $e->getMessage());
    }
}

function afirmar(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

function esperarError(callable $fn, string $fragmento): void
{
    try {
        $fn();
    } catch (Throwable $e) {
        afirmar(
            mb_stripos($e->getMessage(), $fragmento) !== false,
            'Se esperaba un error que contuviera «' . $fragmento . '» y llegó: ' . $e->getMessage()
        );
        return;
    }
    throw new RuntimeException('Se esperaba un error con «' . $fragmento . '» y la operación no falló.');
}

function valor(string $sql, array $parametros = []): mixed
{
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);
    return $stmt->fetchColumn();
}

function fila(string $sql, array $parametros = []): array
{
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);
    return $stmt->fetch() ?: [];
}

/** Huella de los datos demo que deben sobrevivir al rollback. */
function huella(): array
{
    return [
        'eventos'         => (int) valor('SELECT COUNT(*) FROM eventos'),
        'estado_evento_6' => (string) valor('SELECT estado FROM eventos WHERE id_evento = 6'),
        'invitado_8'      => (string) valor('SELECT estado_rsvp FROM invitados WHERE id_invitado = 8'),
        'cotizaciones'    => (int) valor('SELECT COUNT(*) FROM cotizaciones'),
        'pagos'           => (int) valor('SELECT COUNT(*) FROM pagos'),
        'reservas_esp'    => (int) valor('SELECT COUNT(*) FROM reservas_espacio'),
        'reservas_rec'    => (int) valor('SELECT COUNT(*) FROM reservas_recurso'),
        'stock_insumo_6'  => (string) valor('SELECT stock_fisico FROM insumos_menu WHERE id_insumo = 6'),
        'bitacora'        => (int) valor('SELECT COUNT(*) FROM bitacora'),
        'historial'       => (int) valor('SELECT COUNT(*) FROM historial_cambios'),
    ];
}

/* ------------------------------------------------------------------
 * Contexto de la corrida
 * ------------------------------------------------------------------ */

echo "Solera · pruebas de integración\n";
echo 'Base de datos: ' . env('DB_DATABASE', 'conectaeventos') . ' @ ' . env('DB_HOST', '127.0.0.1') . ':' . env('DB_PORT', '3306') . "\n";
echo 'Fecha del sistema: ' . date('Y-m-d H:i') . "\n\n";

$huellaAntes = huella();
$pdo->beginTransaction();

/* ------------------------------------------------------------------
 * Módulo 0 · conexión y catálogos
 * ------------------------------------------------------------------ */

caso('Conexión PDO y catálogos base disponibles', static function (): void {
    afirmar(valor('SELECT COUNT(*) FROM eventos') >= 7, 'El seed debe tener al menos 7 eventos.');
    afirmar(valor('SELECT COUNT(*) FROM espacios') >= 5, 'Faltan espacios en el catálogo.');
    afirmar(valor('SELECT COUNT(*) FROM recursos') >= 9, 'Faltan recursos en el catálogo.');
    afirmar(valor('SELECT COUNT(*) FROM roles') === 8, 'Deben existir 8 roles.');
    afirmar(valor("SELECT COUNT(*) FROM configuracion WHERE clave IN ('iva_porcentaje','anticipo_porcentaje')") === 2, 'Faltan parámetros de cotización.');
});

/* ------------------------------------------------------------------
 * Módulo B · disponibilidad
 * ------------------------------------------------------------------ */

caso('Disponibilidad::dias expande el rango y rechaza rangos invertidos', static function (): void {
    $dias = Disponibilidad::dias('2027-03-18 09:00:00', '2027-03-20 18:00:00');
    afirmar($dias === ['2027-03-18', '2027-03-19', '2027-03-20'], 'El rango de tres días no se expandió correctamente.');
    afirmar(Disponibilidad::dias('2027-03-20', '2027-03-18') === [], 'Un rango invertido debe devolver una lista vacía.');
});

caso('Espacios libres distingue ocupado, libre y fuera de servicio', static function (): void {
    $espacios = Disponibilidad::espaciosLibres('2027-02-14', '2027-02-14');
    $porId = [];
    foreach ($espacios as $espacio) {
        $porId[(int) $espacio['id_espacio']] = $espacio;
    }
    afirmar(isset($porId[1]) && (bool) $porId[1]['libre'] === true, 'El Salón Aurora debería estar libre el 14 de febrero.');
    afirmar(isset($porId[2]) && (bool) $porId[2]['libre'] === false, 'El Gran Salón Imperial está reservado y no debe aparecer libre.');
    afirmar(!isset($porId[5]), 'Un espacio en mantenimiento no debe ofrecerse como disponible.');
});

caso('Reserva de espacio válida bloquea todos los días del rango', static function (): void {
    $id = Disponibilidad::reservarEspacio(6, 3, '2027-06-10 08:00:00', '2027-06-10 23:59:00', 'Prueba de integración');
    afirmar($id > 0, 'No se obtuvo el id de la reserva.');
    afirmar((string) valor('SELECT estado FROM reservas_espacio WHERE id_reserva_espacio = ?', [$id]) === 'confirmada', 'La reserva debe quedar confirmada.');
    afirmar((int) valor('SELECT COUNT(*) FROM reserva_espacio_dias WHERE id_reserva_espacio = ? AND activa = 1', [$id]) === 1, 'Debe existir un día activo para la reserva.');
    afirmar((int) valor("SELECT COUNT(*) FROM bitacora WHERE accion = 'RESERVAR_ESPACIO' AND id_registro = ?", [$id]) === 1, 'La reserva debe registrarse en la bitácora.');
    $GLOBALS['reserva_espacio_prueba'] = $id;
});

caso('Rechaza doble reserva del mismo espacio y día', static function (): void {
    esperarError(
        static fn () => Disponibilidad::reservarEspacio(4, 3, '2027-06-10 09:00:00', '2027-06-10 20:00:00', 'Choque esperado'),
        'ya está ocupado'
    );
    afirmar((int) valor("SELECT COUNT(*) FROM reservas_espacio WHERE id_evento = 4 AND id_espacio = 3") === 0, 'No debe quedar ninguna reserva del evento 4.');
});

caso('Rechaza un espacio cuya capacidad es menor al aforo', static function (): void {
    esperarError(
        static fn () => Disponibilidad::reservarEspacio(6, 4, '2027-06-12 10:00:00', '2027-06-12 22:00:00'),
        'supera la capacidad'
    );
});

caso('Rechaza un espacio en mantenimiento', static function (): void {
    esperarError(
        static fn () => Disponibilidad::reservarEspacio(6, 5, '2027-06-13 10:00:00', '2027-06-13 22:00:00'),
        'no está disponible para reservar'
    );
});

caso('Reserva de recurso válida descuenta existencias', static function (): void {
    $id = Disponibilidad::reservarRecurso(6, 9, 1, '2027-06-10 10:00:00', '2027-06-10 22:00:00', 'Pista de prueba');
    afirmar($id > 0, 'No se obtuvo el id de la reserva de recurso.');
    $recurso = fila('SELECT * FROM recursos WHERE id_recurso = 9');
    afirmar((int) $recurso['existencias_total'] === 2, 'El recurso de prueba debe tener 2 existencias.');
    $disponibles = Disponibilidad::recursosDisponibles('2027-06-10', '2027-06-10');
    $pista = array_values(array_filter($disponibles, static fn (array $r): bool => (int) $r['id_recurso'] === 9))[0] ?? [];
    afirmar((int) ($pista['disponible'] ?? -1) === 1, 'Debe quedar una pista disponible tras la reserva.');
});

caso('Rechaza reservar más recursos de los existentes', static function (): void {
    esperarError(
        static fn () => Disponibilidad::reservarRecurso(6, 4, 5, '2027-06-10 10:00:00', '2027-06-10 22:00:00'),
        'Sin disponibilidad'
    );
});

caso('Rechaza duplicar la reserva de un recurso para el mismo evento', static function (): void {
    esperarError(
        static fn () => Disponibilidad::reservarRecurso(6, 9, 1, '2027-06-11 10:00:00', '2027-06-11 22:00:00'),
        'ya tiene una reserva activa'
    );
});

/* ------------------------------------------------------------------
 * Módulo C · cotizaciones y pagos
 * ------------------------------------------------------------------ */

$GLOBALS['cotizacion_prueba'] = 0;

caso('Cotización calcula subtotal, descuento, IVA, total y anticipo', static function (): void {
    $id = Cotizaciones::crear(6, [
        ['tipo_concepto' => 'espacio', 'descripcion' => 'Renta de espacio', 'cantidad' => 1, 'precio_unitario' => 5000],
        ['tipo_concepto' => 'menu', 'descripcion' => 'Menú de prueba', 'cantidad' => 10, 'precio_unitario' => 500],
    ], 500, '2027-01-31', 'Cotización generada por la matriz de pruebas');

    afirmar($id > 0, 'No se obtuvo el id de la cotización.');
    $cot = fila('SELECT * FROM cotizaciones WHERE id_cotizacion = ?', [$id]);
    afirmar((float) $cot['subtotal'] === 10000.0, 'Subtotal esperado 10000, obtenido ' . $cot['subtotal']);
    afirmar((float) $cot['descuento'] === 500.0, 'Descuento esperado 500, obtenido ' . $cot['descuento']);
    afirmar((float) $cot['impuestos'] === 1520.0, 'IVA esperado 1520, obtenido ' . $cot['impuestos']);
    afirmar((float) $cot['total'] === 11020.0, 'Total esperado 11020, obtenido ' . $cot['total']);
    afirmar((float) $cot['anticipo_requerido'] === 3306.0, 'Anticipo esperado 3306, obtenido ' . $cot['anticipo_requerido']);
    afirmar((string) $cot['estado'] === 'enviada', 'La cotización debe nacer enviada.');
    afirmar(str_starts_with((string) $cot['folio'], 'COT-'), 'El folio debe seguir el formato COT-AAAA-NNNN.');
    afirmar((string) valor('SELECT estado FROM eventos WHERE id_evento = 6') === 'cotizado', 'El evento prospecto debe pasar a cotizado.');
    afirmar((int) valor('SELECT COUNT(*) FROM cotizacion_detalle WHERE id_cotizacion = ?', [$id]) === 2, 'Deben guardarse los dos conceptos.');
    $GLOBALS['cotizacion_prueba'] = $id;
});

caso('Aceptar cotización congela precios y registra historial', static function (): void {
    $id = (int) $GLOBALS['cotizacion_prueba'];
    Cotizaciones::aceptar($id, 'Aceptada en la prueba');
    $cot = fila('SELECT * FROM cotizaciones WHERE id_cotizacion = ?', [$id]);
    afirmar((string) $cot['estado'] === 'aceptada', 'La cotización debe quedar aceptada.');
    afirmar($cot['congelada_en'] !== null, 'Debe registrarse la fecha de congelamiento.');
    afirmar((int) valor("SELECT COUNT(*) FROM historial_cambios WHERE entidad = 'cotizaciones' AND id_registro = ? AND valor_nuevo = 'aceptada'", [$id]) === 1, 'Falta el historial de aceptación.');
    esperarError(static fn () => Cotizaciones::aceptar($id), 'Solo se puede aceptar');
});

caso('Pago parcial actualiza el saldo sin confirmar el evento', static function (): void {
    $id = (int) $GLOBALS['cotizacion_prueba'];
    Cotizaciones::registrarPago($id, 1000, 'abono', 'transferencia', 'REF-PRUEBA-1', 'Abono de prueba');
    $saldo = fila('SELECT pagado, saldo FROM v_cotizacion_saldo WHERE id_cotizacion = ?', [$id]);
    afirmar(abs((float) $saldo['pagado'] - 1000.0) < 0.01, 'El pagado debe ser 1000.');
    afirmar(abs((float) $saldo['saldo'] - 10020.0) < 0.01, 'El saldo debe ser 10020.');
    afirmar((string) valor('SELECT estado FROM eventos WHERE id_evento = 6') === 'cotizado', 'El evento aún no debe confirmarse.');
});

caso('Al cubrir el anticipo el evento pasa a confirmado', static function (): void {
    $id = (int) $GLOBALS['cotizacion_prueba'];
    Cotizaciones::registrarPago($id, 2306, 'anticipo', 'transferencia', 'REF-PRUEBA-2', 'Anticipo de prueba');
    afirmar((string) valor('SELECT estado FROM eventos WHERE id_evento = 6') === 'confirmado', 'El evento debe quedar confirmado al cubrir el anticipo.');
    $saldo = fila('SELECT pagado, saldo FROM v_cotizacion_saldo WHERE id_cotizacion = ?', [$id]);
    afirmar(abs((float) $saldo['saldo'] - 7714.0) < 0.01, 'El saldo restante debe ser 7714.');
    afirmar((int) valor("SELECT COUNT(*) FROM historial_cambios WHERE entidad = 'eventos' AND id_registro = 6 AND valor_nuevo = 'confirmado'") >= 1, 'Falta el historial de confirmación del evento.');
});

caso('Rechaza un pago mayor al saldo pendiente', static function (): void {
    esperarError(
        static fn () => Cotizaciones::registrarPago((int) $GLOBALS['cotizacion_prueba'], 999999, 'saldo', 'efectivo', 'REF-X', 'Sobre-pago'),
        'excede el saldo pendiente'
    );
});

caso('Rechaza pagos sobre cotizaciones no aceptadas', static function (): void {
    afirmar((string) valor('SELECT estado FROM cotizaciones WHERE id_cotizacion = 3') === 'enviada', 'La cotización 3 del seed debe estar enviada.');
    esperarError(
        static fn () => Cotizaciones::registrarPago(3, 1000, 'abono', 'transferencia', 'REF-Y', 'No aceptada'),
        'Solo se registran pagos sobre cotizaciones aceptadas'
    );
});

/* ------------------------------------------------------------------
 * Módulo B · insumos y kardex
 * ------------------------------------------------------------------ */

caso('Entrada de insumo incrementa stock y deja kardex', static function (): void {
    $antes = (float) valor('SELECT stock_fisico FROM insumos_menu WHERE id_insumo = 6');
    $movimientos = (int) valor('SELECT COUNT(*) FROM movimientos_insumo WHERE id_insumo = 6');
    Insumos::entrada(6, 20, 'Compra de prueba');
    afirmar((float) valor('SELECT stock_fisico FROM insumos_menu WHERE id_insumo = 6') === $antes + 20, 'El stock físico no aumentó 20.');
    afirmar((int) valor('SELECT COUNT(*) FROM movimientos_insumo WHERE id_insumo = 6') === $movimientos + 1, 'No se registró el movimiento de entrada.');
    afirmar((int) valor("SELECT COUNT(*) FROM movimientos_insumo WHERE id_insumo = 6 AND tipo = 'entrada' AND motivo = 'Compra de prueba'") === 1, 'El kardex no guardó el motivo.');
});

caso('Apartar insumo compromete stock disponible', static function (): void {
    $id = Insumos::apartar(6, 6, 10, 'Apartado de prueba');
    $insumo = fila('SELECT * FROM insumos_menu WHERE id_insumo = 6');
    afirmar((float) $insumo['stock_comprometido'] === 10.0, 'El comprometido debe ser 10.');
    afirmar((float) $insumo['stock_fisico'] - (float) $insumo['stock_comprometido'] === 90.0, 'El disponible debe ser 90.');
    afirmar((string) valor('SELECT estado FROM reservas_insumo WHERE id_reserva_insumo = ?', [$id]) === 'confirmada', 'La reserva debe quedar confirmada.');
    $GLOBALS['reserva_insumo_prueba'] = $id;
});

caso('Consumir insumo descuenta stock físico y libera comprometido', static function (): void {
    $id = (int) $GLOBALS['reserva_insumo_prueba'];
    Insumos::consumir($id, 'Consumo de prueba');
    $insumo = fila('SELECT * FROM insumos_menu WHERE id_insumo = 6');
    afirmar((float) $insumo['stock_fisico'] === 90.0, 'El stock físico debe quedar en 90.');
    afirmar((float) $insumo['stock_comprometido'] === 0.0, 'El comprometido debe quedar en 0.');
    afirmar((string) valor('SELECT estado FROM reservas_insumo WHERE id_reserva_insumo = ?', [$id]) === 'consumida', 'La reserva debe quedar consumida.');
    afirmar((int) valor("SELECT COUNT(*) FROM movimientos_insumo WHERE id_insumo = 6 AND tipo = 'consumo' AND motivo = 'Consumo de prueba'") === 1, 'Falta el movimiento de consumo.');
    esperarError(static fn () => Insumos::consumir($id), 'no está confirmada');
});

caso('Rechaza apartar más insumo que el disponible', static function (): void {
    esperarError(static fn () => Insumos::apartar(6, 1, 9999, 'Sobre-stock'), 'No hay suficiente stock');
});

caso('Rechaza ajustar el stock por debajo de lo comprometido', static function (): void {
    $comprometido = (float) valor('SELECT stock_comprometido FROM insumos_menu WHERE id_insumo = 1');
    afirmar($comprometido > 0, 'El insumo 1 debe tener stock comprometido para esta prueba.');
    esperarError(static fn () => Insumos::ajuste(1, 1, 'Ajuste inválido'), 'no puede quedar por debajo de lo comprometido');
});

/* ------------------------------------------------------------------
 * Módulo A · RSVP y aforo
 * ------------------------------------------------------------------ */

caso('RSVP con token válido confirma y guarda menú y restricción', static function (): void {
    $resultado = Invitados::confirmarRsvp('8b2f5d9a1e6c4703bd82f5a1c9e4b80a', 'confirmado', 1, 'vegetariano', 'Nueces');
    afirmar($resultado['estado'] === 'confirmado', 'La respuesta debía quedar confirmada.');
    $invitado = fila('SELECT * FROM invitados WHERE id_invitado = 8');
    afirmar((string) $invitado['estado_rsvp'] === 'confirmado', 'El invitado 8 no quedó confirmado.');
    afirmar((int) $invitado['id_evento_menu'] === 1, 'No se guardó el menú elegido.');
    afirmar((string) $invitado['restriccion_alimenticia'] === 'vegetariano', 'No se guardó la restricción.');
    afirmar((string) $invitado['alergias'] === 'Nueces', 'No se guardaron las alergias.');
    afirmar((int) valor("SELECT COUNT(*) FROM historial_cambios WHERE entidad = 'invitados' AND id_registro = 8 AND campo = 'estado_rsvp'") >= 1, 'Falta el historial del RSVP.');
});

caso('RSVP descarta un menú que no pertenece al evento', static function (): void {
    Invitados::confirmarRsvp('8b2f5d9a1e6c4703bd82f5a1c9e4b80a', 'confirmado', 999, 'ninguna', '');
    afirmar(valor('SELECT id_evento_menu FROM invitados WHERE id_invitado = 8') === null, 'Un menú ajeno al evento debe descartarse.');
});

caso('RSVP con token inválido no revela datos y falla', static function (): void {
    esperarError(
        static fn () => Invitados::confirmarRsvp('00000000000000000000000000000000', 'confirmado', null, 'ninguna', ''),
        'no es válido'
    );
});

caso('Con aforo completo el RSVP pasa a lista de espera', static function (): void {
    $pdo = Database::pdo();
    $pdo->exec('UPDATE eventos SET aforo_estimado = 11, aforo_maximo = 11 WHERE id_evento = 1');
    $resultado = Invitados::confirmarRsvp('d7c3a9f6b1e54802ca93e7b4d8f1a90b', 'confirmado', 1, 'ninguna', '');
    afirmar($resultado['estado'] === 'lista_espera', 'Con el aforo lleno la respuesta debía quedar en lista de espera.');
    afirmar((string) valor('SELECT estado_rsvp FROM invitados WHERE id_invitado = 9') === 'lista_espera', 'El invitado 9 debía quedar en lista de espera.');
});

/* ------------------------------------------------------------------
 * Integridad, cancelaciones y vistas
 * ------------------------------------------------------------------ */

caso('Cancelar exige motivo en evento y en reserva de espacio', static function (): void {
    esperarError(static fn () => Eventos::cancelar(6, '   '), 'motivo de cancelación es obligatorio');
    esperarError(
        static fn () => Disponibilidad::cancelarReservaEspacio((int) $GLOBALS['reserva_espacio_prueba'], ''),
        'motivo de cancelación es obligatorio'
    );
});

caso('Cancelación en cascada libera espacios, recursos y cotización', static function (): void {
    $pdo = Database::pdo();
    $reservaEspacio = (int) $GLOBALS['reserva_espacio_prueba'];
    $cotizacion = (int) $GLOBALS['cotizacion_prueba'];

    // Se acerca la fecha para probar la política de penalización del 100 %.
    $pdo->exec("UPDATE eventos SET fecha_inicio = '2026-11-05 18:00:00', fecha_fin = '2026-11-06 01:00:00' WHERE id_evento = 6");

    $resultado = Eventos::cancelar(6, 'Cancelación de prueba');
    afirmar(abs((float) $resultado['penalizacion'] - 11020.0) < 0.01, 'La penalización debía ser el 100 % del total (11020).');

    afirmar((string) valor('SELECT estado FROM eventos WHERE id_evento = 6') === 'cancelado', 'El evento debe quedar cancelado.');
    afirmar((string) valor('SELECT estado FROM reservas_espacio WHERE id_reserva_espacio = ?', [$reservaEspacio]) === 'cancelada', 'La reserva de espacio debe cancelarse.');
    afirmar((int) valor('SELECT COUNT(*) FROM reserva_espacio_dias WHERE id_reserva_espacio = ? AND activa = 1', [$reservaEspacio]) === 0, 'Los días de la reserva deben quedar inactivos.');
    afirmar((int) valor("SELECT COUNT(*) FROM reservas_recurso WHERE id_evento = 6 AND estado = 'confirmada'") === 0, 'No deben quedar recursos confirmados.');
    afirmar((string) valor('SELECT estado FROM cotizaciones WHERE id_cotizacion = ?', [$cotizacion]) === 'cancelada', 'La cotización debe cancelarse.');
    afirmar((float) valor('SELECT penalizacion FROM cotizaciones WHERE id_cotizacion = ?', [$cotizacion]) === 11020.0, 'La penalización debe guardarse en la cotización.');
    afirmar((int) valor("SELECT COUNT(*) FROM bitacora WHERE accion = 'CANCELAR_EVENTO' AND id_registro = 6") === 1, 'Falta el registro de cancelación en bitácora.');
});

caso('Rechaza transiciones de estado no permitidas', static function (): void {
    esperarError(static fn () => Eventos::cambiarEstado(7, 'en_curso'), 'Transición no permitida');
    esperarError(static fn () => Eventos::cambiarEstado(6, 'confirmado'), 'Transición no permitida');
});

caso('Vistas de consistencia sin saldos negativos ni sobreventa', static function (): void {
    afirmar((int) valor('SELECT COUNT(*) FROM v_resumen_evento') === 7, 'La vista de resumen debe cubrir los 7 eventos.');
    afirmar((int) valor('SELECT COUNT(*) FROM v_cotizacion_saldo WHERE saldo < -0.01') === 0, 'Hay cotizaciones con saldo negativo.');
    afirmar((int) valor('SELECT COUNT(*) FROM v_disponibilidad_insumo WHERE disponible < 0') === 0, 'Hay insumos con disponible negativo.');
    afirmar((int) valor('SELECT COUNT(*) FROM insumos_menu WHERE stock_comprometido > stock_fisico') === 0, 'Hay insumos con comprometido mayor al stock físico.');
    afirmar((int) valor('SELECT COUNT(*) FROM v_ocupacion_espacio') > 0, 'La vista de ocupación no devolvió filas.');
});

caso('El precio de catálogo se lee del servidor y el ajuste deja rastro', static function (): void {
    $id = Cotizaciones::crear(3, [
        [
            'tipo_concepto'   => 'menu',
            'id_referencia'   => 1,
            'descripcion'     => 'Banquete negociado',
            'cantidad'        => 10,
            'precio_catalogo' => 999999, // intento de falsear el catálogo desde el formulario
            'precio_unitario' => 1000,
        ],
    ], 0, null, 'Prueba de precio de catálogo');

    $detalle = fila('SELECT * FROM cotizacion_detalle WHERE id_cotizacion = ?', [$id]);
    afirmar((float) $detalle['precio_catalogo'] === 1250.0, 'El catálogo debía leerse del servidor (1250) y llegó ' . $detalle['precio_catalogo']);
    afirmar((float) $detalle['precio_unitario'] === 1000.0, 'El precio pactado debía conservarse en 1000.');
    afirmar(
        (int) valor("SELECT COUNT(*) FROM historial_precios WHERE entidad = 'menus' AND id_registro = 1 AND precio_anterior = 1250 AND precio_nuevo = 1000") === 1,
        'Falta el rastro del ajuste comercial en historial_precios.'
    );
});

caso('La exportación CSV neutraliza fórmulas y conserva números', static function (): void {
    $metodo = new ReflectionMethod(\App\Controllers\ReportesController::class, 'celdaCsv');
    $metodo->setAccessible(true);
    $controlador = new \App\Controllers\ReportesController();

    afirmar($metodo->invoke($controlador, '=HYPERLINK("http://x")') === '\'=HYPERLINK("http://x")', 'No se neutralizó una fórmula.');
    afirmar($metodo->invoke($controlador, '@SUM(1)') === '\'@SUM(1)', 'No se neutralizó la arroba.');
    afirmar($metodo->invoke($controlador, '-12.50') === '-12.50', 'Un número negativo debe conservarse.');
    afirmar($metodo->invoke($controlador, 1250.0) === '1250', 'Un número debe conservarse.');
    afirmar($metodo->invoke($controlador, 'Boda de Aurora') === 'Boda de Aurora', 'El texto normal no debe alterarse.');
});

/* ------------------------------------------------------------------
 * Módulo A · solicitudes del sitio
 * ------------------------------------------------------------------
 * Van al final a proposito: dan de alta clientes y eventos que no estan
 * en la demo, y el SAVEPOINT de `caso()` solo revierte cuando el caso
 * falla. En medio de la matriz, las pruebas que cuentan los siete
 * eventos del seed habrian visto mas de los que esperaban.
 * ------------------------------------------------------------------ */

caso('Convertir una solicitud crea cliente, evento y reserva en un paso', static function (): void {
    $clientesAntes = (int) valor('SELECT COUNT(*) FROM clientes');

    $r = Solicitudes::convertir(1, [
        'id_tipo_evento' => 1,
        'nombre'         => 'Boda de prueba desde solicitud',
        'fecha_inicio'   => '2027-06-12 17:00:00',
        'fecha_fin'      => '2027-06-12 23:00:00',
        'aforo'          => 180,
        'id_espacio'     => 2,
    ]);

    afirmar($r['cliente_nuevo'] === true, 'La solicitud 1 traía un correo que no era de ningún cliente.');
    afirmar((int) valor('SELECT COUNT(*) FROM clientes') === $clientesAntes + 1, 'Debía crearse exactamente un cliente.');

    $evento = fila('SELECT * FROM eventos WHERE id_evento = ' . (int) $r['id_evento']);
    afirmar((string) $evento['estado'] === 'prospecto', 'El evento debe nacer como prospecto, no confirmado.');
    afirmar((int) $evento['aforo_maximo'] === 180, 'El aforo pedido no llegó al evento.');
    afirmar((int) $evento['id_cliente'] === (int) $r['id_cliente'], 'El evento no quedó enlazado a su cliente.');

    afirmar(
        (int) valor('SELECT COUNT(*) FROM reservas_espacio WHERE id_evento = ' . (int) $r['id_evento'] . " AND estado = 'confirmada'") === 1,
        'El espacio elegido debía quedar reservado.'
    );
    afirmar((string) valor('SELECT estado FROM leads WHERE id_lead = 1') === 'convertido', 'La solicitud debía quedar marcada como convertida.');
    afirmar(
        (int) valor("SELECT COUNT(*) FROM bitacora WHERE accion = 'CONVERTIR_SOLICITUD' AND id_registro = 1") === 1,
        'La conversión debe dejar rastro en bitácora.'
    );
});

caso('Una solicitud no se convierte dos veces', static function (): void {
    Solicitudes::convertir(3, [
        'id_tipo_evento' => 2,
        'nombre'         => 'XV de prueba',
        'fecha_inicio'   => '2027-07-17 17:00:00',
        'fecha_fin'      => '2027-07-17 23:00:00',
        'aforo'          => 140,
        'id_espacio'     => null,
    ]);

    esperarError(static fn () => Solicitudes::convertir(3, [
        'id_tipo_evento' => 2,
        'nombre'         => 'Intento duplicado',
        'fecha_inicio'   => '2027-07-17 17:00:00',
        'fecha_fin'      => '2027-07-17 23:00:00',
        'aforo'          => 140,
        'id_espacio'     => null,
    ]), 'ya se convirtió');
});

caso('Un correo que ya es cliente no genera un cliente duplicado', static function (): void {
    $correo = (string) valor('SELECT correo FROM clientes WHERE id_cliente = 1');
    Database::pdo()->prepare(
        "INSERT INTO leads (nombre, correo, tipo_evento, fecha_estimada, aforo, origen)
         VALUES ('Cliente que repite', ?, 'Corporativo', '2027-09-15', 200, 'corporativo')"
    )->execute([$correo]);
    $idLead = (int) Database::pdo()->lastInsertId();

    $clientesAntes = (int) valor('SELECT COUNT(*) FROM clientes');
    $r = Solicitudes::convertir($idLead, [
        'id_tipo_evento' => 3,
        'nombre'         => 'Segundo evento del mismo cliente',
        'fecha_inicio'   => '2027-09-15 10:00:00',
        'fecha_fin'      => '2027-09-15 20:00:00',
        'aforo'          => 200,
        'id_espacio'     => null,
    ]);

    afirmar($r['cliente_nuevo'] === false, 'El cliente ya existía: no debía marcarse como nuevo.');
    afirmar((int) $r['id_cliente'] === 1, 'El evento debía colgar del cliente que ya existía.');
    afirmar((int) valor('SELECT COUNT(*) FROM clientes') === $clientesAntes, 'No debía crearse ningún cliente.');
});

caso('El estado «convertido» no se puede poner a mano', static function (): void {
    esperarError(static fn () => Solicitudes::cambiarEstado(2, 'convertido'), 'no es válido');
    esperarError(static fn () => Solicitudes::cambiarEstado(2, 'inventado'), 'no es válido');

    Solicitudes::cambiarEstado(2, 'descartado');
    afirmar((string) valor('SELECT estado FROM leads WHERE id_lead = 2') === 'descartado', 'El descarte no se guardó.');
    afirmar(
        (int) valor("SELECT COUNT(*) FROM historial_cambios WHERE entidad = 'leads' AND id_registro = 2") >= 1,
        'El cambio de estado debe dejar historial.'
    );
});

caso('La disponibilidad de una solicitud sale de las reservas reales', static function (): void {
    $sinFecha = Solicitudes::disponibilidadPara(null, 100);
    afirmar($sinFecha['comprobable'] === false, 'Sin fecha no hay nada que comprobar.');

    // El 14 feb 2027 el Gran Salón Imperial está ocupado por la boda del seed.
    $ocupado = Solicitudes::disponibilidadPara('2027-02-14', 400);
    afirmar($ocupado['comprobable'] === true, 'Con fecha sí debe comprobarse.');
    afirmar($ocupado['cabe'] === false, 'Ningún espacio libre ese día llega a 400 personas.');
    afirmar($ocupado['ocupados'] >= 1, 'Ese día hay al menos un espacio reservado.');

    $libre = Solicitudes::disponibilidadPara('2027-06-12', 100);
    afirmar($libre['cabe'] === true, 'Esa fecha debería tener espacios libres para 100 personas.');
});

caso('El tipo de evento se deduce del texto libre del visitante', static function (): void {
    afirmar(Solicitudes::tipoSugerido('Boda') === 1, 'No reconoció «Boda».');
    afirmar(Solicitudes::tipoSugerido('graduacion') !== null, 'Debe reconocer «graduacion» sin acento ni mayúscula.');
    afirmar(Solicitudes::tipoSugerido('Graduación') === Solicitudes::tipoSugerido('graduacion'), 'Con y sin acento deben caer en el mismo tipo.');
    afirmar(Solicitudes::tipoSugerido('') === null, 'Un texto vacío no sugiere nada.');
    afirmar(Solicitudes::tipoSugerido('Torneo de ajedrez') === null, 'Lo que no está en el catálogo lo decide una persona.');
});

/* ------------------------------------------------------------------
 * Rollback y verificación de que la demo quedó intacta
 * ------------------------------------------------------------------ */

$pdo->rollBack();

echo "\n";
$huellaDespues = huella();
$intacta = $huellaAntes === $huellaDespues;
echo 'Rollback aplicado · datos demo ' . ($intacta ? 'intactos' : 'MODIFICADOS') . "\n";
if (!$intacta) {
    echo "  Antes:  " . json_encode($huellaAntes, JSON_UNESCAPED_UNICODE) . "\n";
    echo "  Después:" . json_encode($huellaDespues, JSON_UNESCAPED_UNICODE) . "\n";
    $GLOBALS['pruebas_fallidas'][] = ['nombre' => 'Rollback de datos demo', 'error' => 'La huella cambió tras revertir la transacción.'];
}

$total = (int) $GLOBALS['pruebas_total'];
$ok = (int) $GLOBALS['pruebas_ok'];
$fallidas = $GLOBALS['pruebas_fallidas'];

echo "\n" . str_repeat('─', 58) . "\n";
echo sprintf("Resultado: %d de %d pruebas aprobadas\n", $ok, $total);
if ($fallidas !== []) {
    echo "Fallas:\n";
    foreach ($fallidas as $falla) {
        echo '  · ' . $falla['nombre'] . ' → ' . $falla['error'] . "\n";
    }
    exit(1);
}
echo "Todas las pruebas pasaron. La base de datos no conserva cambios.\n";
exit(0);
