<?php
declare(strict_types=1);

namespace App\Domain;

use App\Core\Database;
use DateTimeImmutable;
use PDO;
use RuntimeException;

/**
 * Módulo B · disponibilidad, espacios, recursos y reprogramación.
 * Todas las operaciones críticas usan transacción y bloqueo FOR UPDATE.
 */
final class Disponibilidad
{
    public static function dias(string $inicio, string $fin): array
    {
        $dias = [];
        $cursor = new DateTimeImmutable(date('Y-m-d', strtotime($inicio)));
        $limite = new DateTimeImmutable(date('Y-m-d', strtotime($fin)));
        if ($limite < $cursor) {
            return [];
        }
        while ($cursor <= $limite) {
            $dias[] = $cursor->format('Y-m-d');
            $cursor = $cursor->modify('+1 day');
        }
        return $dias;
    }

    /** Espacios libres para un rango de fechas. */
    public static function espaciosLibres(string $inicio, string $fin): array
    {
        $pdo = Database::pdo();
        $dias = self::dias($inicio, $fin);
        $stmt = $pdo->prepare(
            "SELECT e.*,
                    (SELECT COUNT(*) FROM reserva_espacio_dias d
                      WHERE d.id_espacio = e.id_espacio AND d.activa = 1
                        AND d.fecha BETWEEN ? AND ?) AS dias_ocupados
               FROM espacios e
              WHERE e.estado = 'activo'
              ORDER BY e.capacidad DESC"
        );
        $stmt->execute([$inicio, $fin]);
        $espacios = $stmt->fetchAll();
        foreach ($espacios as &$espacio) {
            $espacio['libre'] = (int) $espacio['dias_ocupados'] === 0 && $dias !== [];
        }
        return $espacios;
    }

    /** Disponibilidad de todos los recursos en un rango. */
    public static function recursosDisponibles(string $inicio, string $fin): array
    {
        $pdo = Database::pdo();
        $dias = self::dias($inicio, $fin);
        $stmt = $pdo->query(
            "SELECT r.*, p.nombre AS proveedor
               FROM recursos r
               LEFT JOIN proveedores p ON p.id_proveedor = r.id_proveedor
              WHERE r.estado = 'activo'
              ORDER BY r.categoria, r.nombre"
        );
        $recursos = $stmt->fetchAll();
        $consulta = $pdo->prepare(
            "SELECT COALESCE(SUM(cantidad), 0)
               FROM reservas_recurso
              WHERE id_recurso = ? AND estado = 'confirmada'
                AND DATE(fecha_inicio) <= ? AND DATE(fecha_fin) >= ?"
        );
        foreach ($recursos as &$recurso) {
            $maximo = 0;
            foreach ($dias as $dia) {
                $consulta->execute([(int) $recurso['id_recurso'], $dia, $dia]);
                $maximo = max($maximo, (int) $consulta->fetchColumn());
            }
            $recurso['reservado'] = $maximo;
            $recurso['disponible'] = max(0, (int) $recurso['existencias_total'] - $maximo);
        }
        return $recursos;
    }

    /**
     * Contrato A → B: ¿el evento puede moverse a la nueva ventana?
     * Devuelve ['ok' => bool, 'motivos' => string[]].
     */
    public static function hayDisponibilidad(int $idEvento, string $inicio, string $fin, ?int $idEspacio = null, array $recursos = []): array
    {
        $pdo = Database::pdo();
        $motivos = [];
        $dias = self::dias($inicio, $fin);
        if ($dias === []) {
            return ['ok' => false, 'motivos' => ['La fecha final debe ser posterior a la inicial.']];
        }

        /* Espacios ya reservados por el evento */
        $stmt = $pdo->prepare(
            "SELECT re.*, e.nombre AS espacio
               FROM reservas_espacio re
               JOIN espacios e ON e.id_espacio = re.id_espacio
              WHERE re.id_evento = ? AND re.estado = 'confirmada'"
        );
        $stmt->execute([$idEvento]);
        foreach ($stmt->fetchAll() as $reserva) {
            foreach ($dias as $dia) {
                $choque = $pdo->prepare(
                    "SELECT COUNT(*) FROM reserva_espacio_dias
                      WHERE id_espacio = ? AND fecha = ? AND activa = 1
                        AND id_reserva_espacio <> ?"
                );
                $choque->execute([(int) $reserva['id_espacio'], $dia, (int) $reserva['id_reserva_espacio']]);
                if ((int) $choque->fetchColumn() > 0) {
                    $motivos[] = 'El espacio ' . $reserva['espacio'] . ' ya está ocupado el ' . self::fechaCorta($dia) . '.';
                    break;
                }
            }
        }

        /* Espacio nuevo (si se está reservando por primera vez) */
        if ($idEspacio !== null) {
            $stmt = $pdo->prepare("SELECT nombre, capacidad, estado FROM espacios WHERE id_espacio = ?");
            $stmt->execute([$idEspacio]);
            $espacio = $stmt->fetch();
            if (!$espacio || $espacio['estado'] !== 'activo') {
                $motivos[] = 'El espacio seleccionado no está disponible.';
            } else {
                $aforo = (int) $pdo->query('SELECT aforo_maximo FROM eventos WHERE id_evento = ' . $idEvento)->fetchColumn();
                if ($aforo > (int) $espacio['capacidad']) {
                    $motivos[] = 'El aforo del evento supera la capacidad de ' . $espacio['nombre'] . '.';
                }
                foreach ($dias as $dia) {
                    $choque = $pdo->prepare("SELECT COUNT(*) FROM reserva_espacio_dias WHERE id_espacio = ? AND fecha = ? AND activa = 1");
                    $choque->execute([$idEspacio, $dia]);
                    if ((int) $choque->fetchColumn() > 0) {
                        $motivos[] = 'El espacio ' . $espacio['nombre'] . ' ya está ocupado el ' . self::fechaCorta($dia) . '.';
                        break;
                    }
                }
            }
        }

        /* Recursos ya reservados por el evento */
        $stmt = $pdo->prepare(
            "SELECT rr.*, r.nombre, r.existencias_total
               FROM reservas_recurso rr
               JOIN recursos r ON r.id_recurso = rr.id_recurso
              WHERE rr.id_evento = ? AND rr.estado = 'confirmada'"
        );
        $stmt->execute([$idEvento]);
        foreach ($stmt->fetchAll() as $reserva) {
            foreach ($dias as $dia) {
                $suma = $pdo->prepare(
                    "SELECT COALESCE(SUM(cantidad), 0) FROM reservas_recurso
                      WHERE id_recurso = ? AND estado = 'confirmada'
                        AND DATE(fecha_inicio) <= ? AND DATE(fecha_fin) >= ?
                        AND id_reserva_recurso <> ?"
                );
                $suma->execute([(int) $reserva['id_recurso'], $dia, $dia, (int) $reserva['id_reserva_recurso']]);
                if ((int) $suma->fetchColumn() + (int) $reserva['cantidad'] > (int) $reserva['existencias_total']) {
                    $motivos[] = 'No hay suficientes ' . $reserva['nombre'] . ' el ' . self::fechaCorta($dia) . '.';
                    break;
                }
            }
        }

        /* Recursos nuevos solicitados */
        foreach ($recursos as $idRecurso => $cantidad) {
            $stmt = $pdo->prepare("SELECT nombre, existencias_total, estado FROM recursos WHERE id_recurso = ?");
            $stmt->execute([(int) $idRecurso]);
            $recurso = $stmt->fetch();
            if (!$recurso || $recurso['estado'] !== 'activo') {
                $motivos[] = 'Uno de los recursos solicitados no está disponible.';
                continue;
            }
            foreach ($dias as $dia) {
                $suma = $pdo->prepare(
                    "SELECT COALESCE(SUM(cantidad), 0) FROM reservas_recurso
                      WHERE id_recurso = ? AND estado = 'confirmada'
                        AND DATE(fecha_inicio) <= ? AND DATE(fecha_fin) >= ?"
                );
                $suma->execute([(int) $idRecurso, $dia, $dia]);
                if ((int) $suma->fetchColumn() + (int) $cantidad > (int) $recurso['existencias_total']) {
                    $motivos[] = 'No hay suficientes ' . $recurso['nombre'] . ' el ' . self::fechaCorta($dia) . '.';
                    break;
                }
            }
        }

        return ['ok' => $motivos === [], 'motivos' => array_values(array_unique($motivos))];
    }

    /** Reserva un espacio validando capacidad, empalmes y estado. */
    public static function reservarEspacio(int $idEvento, int $idEspacio, string $inicio, string $fin, string $motivo = ''): int
    {
        return Database::transaccion(static function (PDO $pdo) use ($idEvento, $idEspacio, $inicio, $fin, $motivo): int {
            $evento = $pdo->prepare('SELECT aforo_maximo, nombre, estado FROM eventos WHERE id_evento = ? FOR UPDATE');
            $evento->execute([$idEvento]);
            $ev = $evento->fetch();
            if (!$ev) {
                throw new RuntimeException('El evento no existe.');
            }
            if ($ev['estado'] === 'cancelado') {
                throw new RuntimeException('No se puede reservar para un evento cancelado.');
            }

            $espacio = $pdo->prepare("SELECT capacidad, estado, nombre FROM espacios WHERE id_espacio = ? FOR UPDATE");
            $espacio->execute([$idEspacio]);
            $esp = $espacio->fetch();
            if (!$esp || $esp['estado'] !== 'activo') {
                throw new RuntimeException('El espacio no está disponible para reservar.');
            }
            if ((int) $ev['aforo_maximo'] > (int) $esp['capacidad']) {
                throw new RuntimeException('El aforo del evento (' . $ev['aforo_maximo'] . ') supera la capacidad de ' . $esp['nombre'] . ' (' . $esp['capacidad'] . ').');
            }
            if (strtotime($fin) <= strtotime($inicio)) {
                throw new RuntimeException('La fecha final debe ser posterior a la inicial.');
            }

            $duplicada = $pdo->prepare("SELECT COUNT(*) FROM reservas_espacio WHERE id_evento = ? AND id_espacio = ? AND estado = 'confirmada'");
            $duplicada->execute([$idEvento, $idEspacio]);
            if ((int) $duplicada->fetchColumn() > 0) {
                throw new RuntimeException('El evento ya tiene una reserva activa de este espacio.');
            }

            foreach (self::dias($inicio, $fin) as $dia) {
                $choque = $pdo->prepare('SELECT COUNT(*) FROM reserva_espacio_dias WHERE id_espacio = ? AND fecha = ? AND activa = 1');
                $choque->execute([$idEspacio, $dia]);
                if ((int) $choque->fetchColumn() > 0) {
                    throw new RuntimeException('El espacio ya está ocupado el ' . self::fechaCorta($dia) . '.');
                }
            }

            $insert = $pdo->prepare(
                "INSERT INTO reservas_espacio (id_evento, id_espacio, fecha_inicio, fecha_fin, estado, id_usuario)
                 VALUES (?, ?, ?, ?, 'confirmada', ?)"
            );
            $insert->execute([$idEvento, $idEspacio, $inicio, $fin, (int) (\App\Core\Auth::id() ?? 1)]);
            $idReserva = (int) $pdo->lastInsertId();

            $diaStmt = $pdo->prepare('INSERT INTO reserva_espacio_dias (id_reserva_espacio, id_espacio, fecha, activa) VALUES (?, ?, ?, 1)');
            foreach (self::dias($inicio, $fin) as $dia) {
                $diaStmt->execute([$idReserva, $idEspacio, $dia]);
            }

            registrar_historial('reservas_espacio', $idReserva, 'estado', null, 'confirmada', $motivo !== '' ? $motivo : 'Reserva creada');
            registrar_bitacora('RESERVAR_ESPACIO', 'reservas_espacio', $idReserva, $ev['nombre'] . ' → ' . $esp['nombre']);
            return $idReserva;
        });
    }

    /** Reserva una cantidad de recurso sin superar existencias en ningún día. */
    public static function reservarRecurso(int $idEvento, int $idRecurso, int $cantidad, string $inicio, string $fin, string $motivo = ''): int
    {
        if ($cantidad <= 0) {
            throw new RuntimeException('La cantidad debe ser mayor a cero.');
        }
        return Database::transaccion(static function (PDO $pdo) use ($idEvento, $idRecurso, $cantidad, $inicio, $fin, $motivo): int {
            $evento = $pdo->prepare('SELECT nombre, estado FROM eventos WHERE id_evento = ? FOR UPDATE');
            $evento->execute([$idEvento]);
            $ev = $evento->fetch();
            if (!$ev || $ev['estado'] === 'cancelado') {
                throw new RuntimeException('El evento no existe o está cancelado.');
            }

            $recursoStmt = $pdo->prepare("SELECT nombre, existencias_total, estado FROM recursos WHERE id_recurso = ? FOR UPDATE");
            $recursoStmt->execute([$idRecurso]);
            $recurso = $recursoStmt->fetch();
            if (!$recurso || $recurso['estado'] !== 'activo') {
                throw new RuntimeException('El recurso no está disponible.');
            }
            if (strtotime($fin) <= strtotime($inicio)) {
                throw new RuntimeException('La fecha final debe ser posterior a la inicial.');
            }

            $duplicada = $pdo->prepare("SELECT COUNT(*) FROM reservas_recurso WHERE id_evento = ? AND id_recurso = ? AND estado = 'confirmada'");
            $duplicada->execute([$idEvento, $idRecurso]);
            if ((int) $duplicada->fetchColumn() > 0) {
                throw new RuntimeException('El evento ya tiene una reserva activa de este recurso. Edita la cantidad en la reserva existente.');
            }

            $suma = $pdo->prepare(
                "SELECT COALESCE(SUM(cantidad), 0) FROM reservas_recurso
                  WHERE id_recurso = ? AND estado = 'confirmada'
                    AND DATE(fecha_inicio) <= ? AND DATE(fecha_fin) >= ?"
            );
            foreach (self::dias($inicio, $fin) as $dia) {
                $suma->execute([$idRecurso, $dia, $dia]);
                $ocupado = (int) $suma->fetchColumn();
                if ($ocupado + $cantidad > (int) $recurso['existencias_total']) {
                    throw new RuntimeException(
                        'Sin disponibilidad de ' . $recurso['nombre'] . ' el ' . self::fechaCorta($dia) .
                        '. Disponibles: ' . max(0, (int) $recurso['existencias_total'] - $ocupado) . '.'
                    );
                }
            }

            $insert = $pdo->prepare(
                "INSERT INTO reservas_recurso (id_evento, id_recurso, fecha_inicio, fecha_fin, cantidad, estado, id_usuario)
                 VALUES (?, ?, ?, ?, ?, 'confirmada', ?)"
            );
            $insert->execute([$idEvento, $idRecurso, $inicio, $fin, $cantidad, (int) (\App\Core\Auth::id() ?? 1)]);
            $idReserva = (int) $pdo->lastInsertId();

            registrar_historial('reservas_recurso', $idReserva, 'cantidad', null, (string) $cantidad, $motivo !== '' ? $motivo : 'Reserva creada');
            registrar_bitacora('RESERVAR_RECURSO', 'reservas_recurso', $idReserva, $ev['nombre'] . ' → ' . $recurso['nombre'] . ' × ' . $cantidad);
            return $idReserva;
        });
    }

    public static function cancelarReservaEspacio(int $idReserva, string $motivo): void
    {
        if (trim($motivo) === '') {
            throw new RuntimeException('El motivo de cancelación es obligatorio.');
        }
        Database::transaccion(static function (PDO $pdo) use ($idReserva, $motivo): void {
            $stmt = $pdo->prepare("SELECT * FROM reservas_espacio WHERE id_reserva_espacio = ? FOR UPDATE");
            $stmt->execute([$idReserva]);
            $reserva = $stmt->fetch();
            if (!$reserva) {
                throw new RuntimeException('La reserva no existe.');
            }
            if ($reserva['estado'] === 'cancelada') {
                throw new RuntimeException('La reserva ya estaba cancelada.');
            }
            $pdo->prepare("UPDATE reservas_espacio SET estado = 'cancelada', motivo_cancelacion = ?, cancelado_en = NOW() WHERE id_reserva_espacio = ?")
                ->execute([$motivo, $idReserva]);
            $pdo->prepare('UPDATE reserva_espacio_dias SET activa = NULL WHERE id_reserva_espacio = ? AND activa = 1')
                ->execute([$idReserva]);
            registrar_historial('reservas_espacio', $idReserva, 'estado', 'confirmada', 'cancelada', $motivo);
            registrar_bitacora('CANCELAR_RESERVA_ESPACIO', 'reservas_espacio', $idReserva, $motivo);
        });
    }

    public static function cancelarReservaRecurso(int $idReserva, string $motivo): void
    {
        if (trim($motivo) === '') {
            throw new RuntimeException('El motivo de cancelación es obligatorio.');
        }
        Database::transaccion(static function (PDO $pdo) use ($idReserva, $motivo): void {
            $stmt = $pdo->prepare('SELECT * FROM reservas_recurso WHERE id_reserva_recurso = ? FOR UPDATE');
            $stmt->execute([$idReserva]);
            $reserva = $stmt->fetch();
            if (!$reserva) {
                throw new RuntimeException('La reserva no existe.');
            }
            if ($reserva['estado'] === 'cancelada') {
                throw new RuntimeException('La reserva ya estaba cancelada.');
            }
            $pdo->prepare("UPDATE reservas_recurso SET estado = 'cancelada', motivo_cancelacion = ?, cancelado_en = NOW() WHERE id_reserva_recurso = ?")
                ->execute([$motivo, $idReserva]);
            registrar_historial('reservas_recurso', $idReserva, 'estado', 'confirmada', 'cancelada', $motivo);
            registrar_bitacora('CANCELAR_RESERVA_RECURSO', 'reservas_recurso', $idReserva, $motivo);
        });
    }

    /**
     * Mueve todas las reservas del evento o ninguna.
     * Contrato B → A / B → C.
     */
    public static function reprogramarReservasEvento(int $idEvento, string $inicio, string $fin, string $motivo = ''): void
    {
        Database::transaccion(static function (PDO $pdo) use ($idEvento, $inicio, $fin, $motivo): void {
            $evento = $pdo->prepare('SELECT * FROM eventos WHERE id_evento = ? FOR UPDATE');
            $evento->execute([$idEvento]);
            $ev = $evento->fetch();
            if (!$ev) {
                throw new RuntimeException('El evento no existe.');
            }
            if ($ev['estado'] === 'cancelado') {
                throw new RuntimeException('No se puede reprogramar un evento cancelado.');
            }
            if (strtotime($fin) <= strtotime($inicio)) {
                throw new RuntimeException('La fecha final debe ser posterior a la inicial.');
            }
            $dias = self::dias($inicio, $fin);

            /* 1. Espacios */
            $stmt = $pdo->prepare("SELECT re.*, e.nombre AS espacio FROM reservas_espacio re JOIN espacios e ON e.id_espacio = re.id_espacio WHERE re.id_evento = ? AND re.estado = 'confirmada' FOR UPDATE");
            $stmt->execute([$idEvento]);
            foreach ($stmt->fetchAll() as $reserva) {
                foreach ($dias as $dia) {
                    $choque = $pdo->prepare(
                        'SELECT COUNT(*) FROM reserva_espacio_dias
                          WHERE id_espacio = ? AND fecha = ? AND activa = 1 AND id_reserva_espacio <> ?'
                    );
                    $choque->execute([(int) $reserva['id_espacio'], $dia, (int) $reserva['id_reserva_espacio']]);
                    if ((int) $choque->fetchColumn() > 0) {
                        throw new RuntimeException('No se puede mover: ' . $reserva['espacio'] . ' está ocupado el ' . self::fechaCorta($dia) . '.');
                    }
                }
                $pdo->prepare('UPDATE reserva_espacio_dias SET activa = NULL WHERE id_reserva_espacio = ? AND activa = 1')
                    ->execute([(int) $reserva['id_reserva_espacio']]);
                $insert = $pdo->prepare('INSERT INTO reserva_espacio_dias (id_reserva_espacio, id_espacio, fecha, activa) VALUES (?, ?, ?, 1)');
                foreach ($dias as $dia) {
                    $insert->execute([(int) $reserva['id_reserva_espacio'], (int) $reserva['id_espacio'], $dia]);
                }
                $pdo->prepare('UPDATE reservas_espacio SET fecha_inicio = ?, fecha_fin = ? WHERE id_reserva_espacio = ?')
                    ->execute([$inicio, $fin, (int) $reserva['id_reserva_espacio']]);
                registrar_historial('reservas_espacio', (int) $reserva['id_reserva_espacio'], 'fecha_inicio', $reserva['fecha_inicio'], $inicio, $motivo);
                registrar_historial('reservas_espacio', (int) $reserva['id_reserva_espacio'], 'fecha_fin', $reserva['fecha_fin'], $fin, $motivo);
            }

            /* 2. Recursos */
            $stmt = $pdo->prepare('SELECT rr.*, r.nombre, r.existencias_total FROM reservas_recurso rr JOIN recursos r ON r.id_recurso = rr.id_recurso WHERE rr.id_evento = ? AND rr.estado = \'confirmada\' FOR UPDATE');
            $stmt->execute([$idEvento]);
            foreach ($stmt->fetchAll() as $reserva) {
                foreach ($dias as $dia) {
                    $suma = $pdo->prepare(
                        "SELECT COALESCE(SUM(cantidad), 0) FROM reservas_recurso
                          WHERE id_recurso = ? AND estado = 'confirmada'
                            AND DATE(fecha_inicio) <= ? AND DATE(fecha_fin) >= ?
                            AND id_reserva_recurso <> ?"
                    );
                    $suma->execute([(int) $reserva['id_recurso'], $dia, $dia, (int) $reserva['id_reserva_recurso']]);
                    if ((int) $suma->fetchColumn() + (int) $reserva['cantidad'] > (int) $reserva['existencias_total']) {
                        throw new RuntimeException('No se puede mover: faltan ' . $reserva['nombre'] . ' el ' . self::fechaCorta($dia) . '.');
                    }
                }
                $pdo->prepare('UPDATE reservas_recurso SET fecha_inicio = ?, fecha_fin = ? WHERE id_reserva_recurso = ?')
                    ->execute([$inicio, $fin, (int) $reserva['id_reserva_recurso']]);
                registrar_historial('reservas_recurso', (int) $reserva['id_reserva_recurso'], 'fecha_inicio', $reserva['fecha_inicio'], $inicio, $motivo);
                registrar_historial('reservas_recurso', (int) $reserva['id_reserva_recurso'], 'fecha_fin', $reserva['fecha_fin'], $fin, $motivo);
            }

            /* 3. Evento */
            $pdo->prepare('UPDATE eventos SET fecha_inicio = ?, fecha_fin = ? WHERE id_evento = ?')
                ->execute([$inicio, $fin, $idEvento]);
            registrar_historial('eventos', $idEvento, 'fecha_inicio', $ev['fecha_inicio'], $inicio, $motivo);
            registrar_historial('eventos', $idEvento, 'fecha_fin', $ev['fecha_fin'], $fin, $motivo);
            registrar_bitacora('REPROGRAMAR_EVENTO', 'eventos', $idEvento, 'Nueva ventana: ' . $inicio . ' → ' . $fin . '. ' . $motivo);
        });
    }

    public static function fechaCorta(string $dia): string
    {
        return fecha($dia, 'd M Y');
    }
}
