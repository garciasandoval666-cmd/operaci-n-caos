<?php
declare(strict_types=1);

namespace App\Domain;

use App\Core\Auth;
use App\Core\Database;
use PDO;
use RuntimeException;

/**
 * Módulo A · ciclo de vida del evento y operaciones críticas.
 */
final class Eventos
{
    public const TRANSICIONES = [
        'prospecto'      => ['cotizado', 'cancelado'],
        'cotizado'       => ['confirmado', 'cancelado'],
        'confirmado'     => ['en_preparacion', 'cancelado'],
        'en_preparacion' => ['en_curso', 'cancelado'],
        'en_curso'       => ['realizado'],
        'realizado'      => ['cerrado'],
        'cerrado'        => [],
        'cancelado'      => [],
    ];

    public static function crear(array $datos): int
    {
        return Database::transaccion(static function (PDO $pdo) use ($datos): int {
            self::validar($pdo, $datos);

            $stmt = $pdo->prepare(
                'INSERT INTO eventos (id_cliente, id_tipo_evento, nombre, descripcion, fecha_inicio, fecha_fin,
                                      aforo_estimado, aforo_maximo, estado, notas, id_usuario_creador)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                (int) $datos['id_cliente'],
                (int) $datos['id_tipo_evento'],
                trim($datos['nombre']),
                trim($datos['descripcion'] ?? ''),
                $datos['fecha_inicio'],
                $datos['fecha_fin'],
                (int) $datos['aforo_estimado'],
                (int) $datos['aforo_maximo'],
                $datos['estado'] ?? 'prospecto',
                trim($datos['notas'] ?? ''),
                Auth::id(),
            ]);
            $idEvento = (int) $pdo->lastInsertId();

            if (!empty($datos['id_espacio'])) {
                Disponibilidad::reservarEspacio($idEvento, (int) $datos['id_espacio'], $datos['fecha_inicio'], $datos['fecha_fin'], 'Reserva inicial del evento');
            }

            registrar_historial('eventos', $idEvento, 'estado', null, 'prospecto', 'Alta del evento');
            registrar_bitacora('CREAR_EVENTO', 'eventos', $idEvento, $datos['nombre']);
            return $idEvento;
        });
    }

    public static function actualizar(int $idEvento, array $datos): void
    {
        Database::transaccion(static function (PDO $pdo) use ($idEvento, $datos): void {
            $stmt = $pdo->prepare('SELECT * FROM eventos WHERE id_evento = ? FOR UPDATE');
            $stmt->execute([$idEvento]);
            $anterior = $stmt->fetch();
            if (!$anterior) {
                throw new RuntimeException('El evento no existe.');
            }
            if ($anterior['estado'] === 'cancelado') {
                throw new RuntimeException('Un evento cancelado ya no se puede editar.');
            }
            self::validar($pdo, $datos);

            /* Si cambian las fechas, se mueven todas las reservas o ninguna */
            if (date('Y-m-d H:i', strtotime($datos['fecha_inicio'])) !== date('Y-m-d H:i', strtotime($anterior['fecha_inicio']))
                || date('Y-m-d H:i', strtotime($datos['fecha_fin'])) !== date('Y-m-d H:i', strtotime($anterior['fecha_fin']))) {
                Disponibilidad::reprogramarReservasEvento($idEvento, $datos['fecha_inicio'], $datos['fecha_fin'], 'Cambio de fechas desde la edición del evento');
            }

            /* Validar aforo contra los espacios reservados */
            $espacio = $pdo->prepare(
                'SELECT e.nombre, e.capacidad FROM reservas_espacio re
                  JOIN espacios e ON e.id_espacio = re.id_espacio
                 WHERE re.id_evento = ? AND re.estado = \'confirmada\' LIMIT 1'
            );
            $espacio->execute([$idEvento]);
            $esp = $espacio->fetch();
            if ($esp && (int) $datos['aforo_maximo'] > (int) $esp['capacidad']) {
                throw new RuntimeException('El aforo máximo supera la capacidad de ' . $esp['nombre'] . ' (' . $esp['capacidad'] . ').');
            }

            $pdo->prepare(
                'UPDATE eventos SET id_cliente = ?, id_tipo_evento = ?, nombre = ?, descripcion = ?,
                        aforo_estimado = ?, aforo_maximo = ?, notas = ? WHERE id_evento = ?'
            )->execute([
                (int) $datos['id_cliente'],
                (int) $datos['id_tipo_evento'],
                trim($datos['nombre']),
                trim($datos['descripcion'] ?? ''),
                (int) $datos['aforo_estimado'],
                (int) $datos['aforo_maximo'],
                trim($datos['notas'] ?? ''),
                $idEvento,
            ]);

            $campos = ['nombre', 'descripcion', 'aforo_estimado', 'aforo_maximo', 'notas', 'id_cliente', 'id_tipo_evento'];
            foreach ($campos as $campo) {
                $nuevo = (string) ($datos[$campo] ?? '');
                $viejo = (string) ($anterior[$campo] ?? '');
                if ($nuevo !== $viejo && !($campo === 'aforo_estimado' || $campo === 'aforo_maximo')) {
                    registrar_historial('eventos', $idEvento, $campo, $viejo, $nuevo, 'Edición del evento');
                }
            }
            registrar_bitacora('EDITAR_EVENTO', 'eventos', $idEvento, $datos['nombre']);
        });
    }

    public static function cambiarFecha(int $idEvento, string $inicio, string $fin, string $motivo): void
    {
        if (trim($motivo) === '') {
            throw new RuntimeException('Explica el motivo del cambio de fecha.');
        }
        if (strtotime($inicio) === false || strtotime($fin) === false) {
            throw new RuntimeException('Las fechas no son válidas.');
        }
        if (strtotime($fin) <= strtotime($inicio)) {
            throw new RuntimeException('La fecha final debe ser posterior a la inicial.');
        }
        Disponibilidad::reprogramarReservasEvento($idEvento, $inicio, $fin, $motivo);
    }

    public static function cambiarEstado(int $idEvento, string $nuevoEstado, string $motivo = ''): void
    {
        Database::transaccion(static function (PDO $pdo) use ($idEvento, $nuevoEstado, $motivo): void {
            $stmt = $pdo->prepare('SELECT * FROM eventos WHERE id_evento = ? FOR UPDATE');
            $stmt->execute([$idEvento]);
            $evento = $stmt->fetch();
            if (!$evento) {
                throw new RuntimeException('El evento no existe.');
            }
            $permitidos = self::TRANSICIONES[$evento['estado']] ?? [];
            if (!in_array($nuevoEstado, $permitidos, true)) {
                throw new RuntimeException('Transición no permitida: ' . $evento['estado'] . ' → ' . $nuevoEstado . '.');
            }
            if ($nuevoEstado === 'confirmado') {
                $cot = $pdo->prepare("SELECT COUNT(*) FROM cotizaciones WHERE id_evento = ? AND estado = 'aceptada'");
                $cot->execute([$idEvento]);
                if ((int) $cot->fetchColumn() === 0) {
                    throw new RuntimeException('No se puede confirmar sin una cotización aceptada.');
                }
                $pago = $pdo->prepare(
                    "SELECT COUNT(*) FROM pagos p JOIN cotizaciones c ON c.id_cotizacion = p.id_cotizacion
                      WHERE c.id_evento = ? AND p.estado <> 'revertido' AND p.tipo IN ('anticipo','abono','saldo')"
                );
                $pago->execute([$idEvento]);
                if ((int) $pago->fetchColumn() === 0) {
                    throw new RuntimeException('No se puede confirmar sin un anticipo registrado.');
                }
            }
            if ($nuevoEstado === 'cerrado') {
                $saldo = $pdo->prepare(
                    "SELECT COALESCE(SUM(saldo), 0) FROM v_cotizacion_saldo
                      WHERE id_evento = ? AND estado = 'aceptada'"
                );
                $saldo->execute([$idEvento]);
                if ((float) $saldo->fetchColumn() > 0.01) {
                    throw new RuntimeException('No se puede cerrar un evento con saldo pendiente.');
                }
            }
            $pdo->prepare('UPDATE eventos SET estado = ? WHERE id_evento = ?')->execute([$nuevoEstado, $idEvento]);
            registrar_historial('eventos', $idEvento, 'estado', $evento['estado'], $nuevoEstado, $motivo);
            registrar_bitacora('CAMBIAR_ESTADO_EVENTO', 'eventos', $idEvento, $evento['estado'] . ' → ' . $nuevoEstado);
        });
    }

    /**
     * Cancelación en cascada: evento, espacios, recursos, insumos y cotización.
     * Todo ocurre en una sola transacción.
     */
    public static function cancelar(int $idEvento, string $motivo): array
    {
        if (trim($motivo) === '') {
            throw new RuntimeException('El motivo de cancelación es obligatorio.');
        }
        return Database::transaccion(static function (PDO $pdo) use ($idEvento, $motivo): array {
            $stmt = $pdo->prepare('SELECT * FROM eventos WHERE id_evento = ? FOR UPDATE');
            $stmt->execute([$idEvento]);
            $evento = $stmt->fetch();
            if (!$evento) {
                throw new RuntimeException('El evento no existe.');
            }
            if ($evento['estado'] === 'cancelado') {
                throw new RuntimeException('El evento ya está cancelado.');
            }

            $dias = (int) floor((strtotime($evento['fecha_inicio']) - time()) / 86400);
            $pol = $pdo->prepare('SELECT porcentaje, descripcion FROM politicas_cancelacion WHERE dias_antes <= ? ORDER BY dias_antes DESC LIMIT 1');
            $pol->execute([max(0, $dias)]);
            $politica = $pol->fetch() ?: ['porcentaje' => 0, 'descripcion' => 'Sin política aplicable'];

            $pdo->prepare("UPDATE eventos SET estado = 'cancelado', motivo_cancelacion = ? WHERE id_evento = ?")
                ->execute([$motivo, $idEvento]);

            /* Espacios */
            $espacios = $pdo->prepare("SELECT id_reserva_espacio FROM reservas_espacio WHERE id_evento = ? AND estado = 'confirmada' FOR UPDATE");
            $espacios->execute([$idEvento]);
            foreach ($espacios->fetchAll() as $r) {
                $pdo->prepare("UPDATE reservas_espacio SET estado = 'cancelada', motivo_cancelacion = ?, cancelado_en = NOW() WHERE id_reserva_espacio = ?")
                    ->execute([$motivo, (int) $r['id_reserva_espacio']]);
                $pdo->prepare('UPDATE reserva_espacio_dias SET activa = NULL WHERE id_reserva_espacio = ? AND activa = 1')
                    ->execute([(int) $r['id_reserva_espacio']]);
            }

            /* Recursos */
            $recursos = $pdo->prepare("SELECT id_reserva_recurso FROM reservas_recurso WHERE id_evento = ? AND estado = 'confirmada' FOR UPDATE");
            $recursos->execute([$idEvento]);
            foreach ($recursos->fetchAll() as $r) {
                $pdo->prepare("UPDATE reservas_recurso SET estado = 'cancelada', motivo_cancelacion = ?, cancelado_en = NOW() WHERE id_reserva_recurso = ?")
                    ->execute([$motivo, (int) $r['id_reserva_recurso']]);
            }

            /* Insumos: libera el comprometido y deja movimiento de kardex */
            $insumos = $pdo->prepare("SELECT * FROM reservas_insumo WHERE id_evento = ? AND estado = 'confirmada' FOR UPDATE");
            $insumos->execute([$idEvento]);
            foreach ($insumos->fetchAll() as $r) {
                $pdo->prepare('UPDATE insumos_menu SET stock_comprometido = GREATEST(0, stock_comprometido - ?) WHERE id_insumo = ?')
                    ->execute([(float) $r['cantidad'], (int) $r['id_insumo']]);
                $pdo->prepare("UPDATE reservas_insumo SET estado = 'cancelada' WHERE id_reserva_insumo = ?")
                    ->execute([(int) $r['id_reserva_insumo']]);
                $stock = $pdo->prepare('SELECT stock_fisico, stock_comprometido FROM insumos_menu WHERE id_insumo = ?');
                $stock->execute([(int) $r['id_insumo']]);
                $s = $stock->fetch();
                $pdo->prepare(
                    "INSERT INTO movimientos_insumo (id_insumo, id_evento, tipo, cantidad, stock_fisico_resultante, stock_comprometido_resultante, motivo, id_usuario)
                     VALUES (?, ?, 'liberacion', ?, ?, ?, ?, ?)"
                )->execute([(int) $r['id_insumo'], $idEvento, (float) $r['cantidad'], $s['stock_fisico'], $s['stock_comprometido'], 'Liberación por cancelación: ' . $motivo, Auth::id() ?? 1]);
            }

            /* Cotización y penalización */
            $cotStmt = $pdo->prepare("SELECT * FROM cotizaciones WHERE id_evento = ? AND estado IN ('enviada','aceptada') ORDER BY id_cotizacion DESC LIMIT 1 FOR UPDATE");
            $cotStmt->execute([$idEvento]);
            $cot = $cotStmt->fetch();
            $penalizacion = 0.0;
            if ($cot) {
                $penalizacion = round((float) $cot['total'] * ((float) $politica['porcentaje'] / 100), 2);
                $pdo->prepare(
                    "UPDATE cotizaciones SET estado = 'cancelada', penalizacion = ?, motivo_cancelacion = ?, cancelada_en = NOW() WHERE id_cotizacion = ?"
                )->execute([$penalizacion, $motivo, (int) $cot['id_cotizacion']]);
                registrar_historial('cotizaciones', (int) $cot['id_cotizacion'], 'estado', $cot['estado'], 'cancelada', $motivo);
            }

            registrar_historial('eventos', $idEvento, 'estado', $evento['estado'], 'cancelado', $motivo);
            registrar_bitacora('CANCELAR_EVENTO', 'eventos', $idEvento, $motivo . ' | Penalización: ' . $penalizacion);
            return ['penalizacion' => $penalizacion, 'politica' => $politica];
        });
    }

    public static function sincronizarServicios(int $idEvento, array $servicios): void
    {
        Database::transaccion(static function (PDO $pdo) use ($idEvento, $servicios): void {
            $pdo->prepare('DELETE FROM evento_servicios WHERE id_evento = ?')->execute([$idEvento]);
            $stmt = $pdo->prepare('INSERT INTO evento_servicios (id_evento, id_servicio, cantidad, precio_unitario) VALUES (?, ?, ?, ?)');
            foreach ($servicios as $idServicio) {
                $precio = $pdo->prepare('SELECT precio_base FROM servicios WHERE id_servicio = ? AND activo = 1');
                $precio->execute([(int) $idServicio]);
                $base = $precio->fetchColumn();
                if ($base !== false) {
                    $stmt->execute([$idEvento, (int) $idServicio, 1, $base]);
                }
            }
            registrar_bitacora('ACTUALIZAR_SERVICIOS', 'eventos', $idEvento, count($servicios) . ' servicios vinculados');
        });
    }

    private static function validar(PDO $pdo, array $datos): void
    {
        if (trim((string) ($datos['nombre'] ?? '')) === '') {
            throw new RuntimeException('El nombre del evento es obligatorio.');
        }
        if (empty($datos['fecha_inicio']) || empty($datos['fecha_fin'])) {
            throw new RuntimeException('Las fechas del evento son obligatorias.');
        }
        if (strtotime($datos['fecha_fin']) <= strtotime($datos['fecha_inicio'])) {
            throw new RuntimeException('La fecha de fin debe ser posterior a la de inicio.');
        }
        if ((int) $datos['aforo_estimado'] < 1 || (int) $datos['aforo_maximo'] < 1) {
            throw new RuntimeException('El aforo debe ser mayor a cero.');
        }
        if ((int) $datos['aforo_maximo'] < (int) $datos['aforo_estimado']) {
            throw new RuntimeException('El aforo máximo no puede ser menor al estimado.');
        }
        $cliente = $pdo->prepare("SELECT estado FROM clientes WHERE id_cliente = ?");
        $cliente->execute([(int) $datos['id_cliente']]);
        if ($cliente->fetchColumn() !== 'activo') {
            throw new RuntimeException('El cliente no existe o está inactivo.');
        }
        $tipo = $pdo->prepare('SELECT activo FROM tipos_evento WHERE id_tipo_evento = ?');
        $tipo->execute([(int) $datos['id_tipo_evento']]);
        if ((int) $tipo->fetchColumn() !== 1) {
            throw new RuntimeException('El tipo de evento no está disponible.');
        }
    }
}
