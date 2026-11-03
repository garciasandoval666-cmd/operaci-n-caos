<?php
declare(strict_types=1);

namespace App\Domain;

use App\Core\Auth;
use App\Core\Database;
use PDO;
use RuntimeException;

/**
 * Módulo B · inventario de insumos y kardex.
 */
final class Insumos
{
    public static function apartar(int $idEvento, int $idInsumo, float $cantidad, string $motivo = ''): int
    {
        if ($cantidad <= 0) {
            throw new RuntimeException('La cantidad a apartar debe ser mayor a cero.');
        }
        return Database::transaccion(static function (PDO $pdo) use ($idEvento, $idInsumo, $cantidad, $motivo): int {
            $evento = $pdo->prepare("SELECT nombre, estado FROM eventos WHERE id_evento = ? FOR UPDATE");
            $evento->execute([$idEvento]);
            $ev = $evento->fetch();
            if (!$ev || $ev['estado'] === 'cancelado') {
                throw new RuntimeException('El evento no existe o está cancelado.');
            }

            $insumoStmt = $pdo->prepare("SELECT * FROM insumos_menu WHERE id_insumo = ? AND estado = 'activo' FOR UPDATE");
            $insumoStmt->execute([$idInsumo]);
            $insumo = $insumoStmt->fetch();
            if (!$insumo) {
                throw new RuntimeException('El insumo no está disponible.');
            }

            $duplicado = $pdo->prepare("SELECT COUNT(*) FROM reservas_insumo WHERE id_evento = ? AND id_insumo = ? AND estado = 'confirmada'");
            $duplicado->execute([$idEvento, $idInsumo]);
            if ((int) $duplicado->fetchColumn() > 0) {
                throw new RuntimeException('Este insumo ya está apartado para el evento. Cancela el apartado anterior para ajustarlo.');
            }

            $disponible = (float) $insumo['stock_fisico'] - (float) $insumo['stock_comprometido'];
            if ($cantidad > $disponible) {
                throw new RuntimeException('No hay suficiente stock: disponible ' . number_format($disponible, 2) . ' ' . $insumo['unidad'] . '.');
            }

            $pdo->prepare('UPDATE insumos_menu SET stock_comprometido = stock_comprometido + ? WHERE id_insumo = ?')
                ->execute([$cantidad, $idInsumo]);

            $pdo->prepare(
                "INSERT INTO reservas_insumo (id_evento, id_insumo, cantidad, estado, id_usuario)
                 VALUES (?, ?, ?, 'confirmada', ?)"
            )->execute([$idEvento, $idInsumo, $cantidad, Auth::id() ?? 1]);
            $idReserva = (int) $pdo->lastInsertId();

            self::movimiento($pdo, $idInsumo, $idEvento, 'reserva', $cantidad, $motivo !== '' ? $motivo : 'Apartado para ' . $ev['nombre']);
            registrar_historial('reservas_insumo', $idReserva, 'estado', null, 'confirmada', $motivo);
            registrar_bitacora('APARTAR_INSUMOS', 'reservas_insumo', $idReserva, $ev['nombre'] . ' · ' . $insumo['nombre'] . ' × ' . $cantidad);
            return $idReserva;
        });
    }

    public static function liberar(int $idReserva, string $motivo): void
    {
        if (trim($motivo) === '') {
            throw new RuntimeException('El motivo es obligatorio.');
        }
        Database::transaccion(static function (PDO $pdo) use ($idReserva, $motivo): void {
            $stmt = $pdo->prepare('SELECT * FROM reservas_insumo WHERE id_reserva_insumo = ? FOR UPDATE');
            $stmt->execute([$idReserva]);
            $reserva = $stmt->fetch();
            if (!$reserva || $reserva['estado'] !== 'confirmada') {
                throw new RuntimeException('La reserva no existe o ya fue liberada.');
            }
            $pdo->prepare('UPDATE insumos_menu SET stock_comprometido = GREATEST(0, stock_comprometido - ?) WHERE id_insumo = ?')
                ->execute([(float) $reserva['cantidad'], (int) $reserva['id_insumo']]);
            $pdo->prepare("UPDATE reservas_insumo SET estado = 'cancelada' WHERE id_reserva_insumo = ?")->execute([$idReserva]);
            self::movimiento($pdo, (int) $reserva['id_insumo'], (int) $reserva['id_evento'], 'liberacion', (float) $reserva['cantidad'], $motivo);
            registrar_historial('reservas_insumo', $idReserva, 'estado', 'confirmada', 'cancelada', $motivo);
            registrar_bitacora('LIBERAR_INSUMOS', 'reservas_insumo', $idReserva, $motivo);
        });
    }

    public static function consumir(int $idReserva, string $motivo = 'Consumo en evento realizado'): void
    {
        Database::transaccion(static function (PDO $pdo) use ($idReserva, $motivo): void {
            $stmt = $pdo->prepare('SELECT * FROM reservas_insumo WHERE id_reserva_insumo = ? FOR UPDATE');
            $stmt->execute([$idReserva]);
            $reserva = $stmt->fetch();
            if (!$reserva || $reserva['estado'] !== 'confirmada') {
                throw new RuntimeException('La reserva no está confirmada.');
            }
            $insumo = $pdo->prepare('SELECT stock_fisico, stock_comprometido FROM insumos_menu WHERE id_insumo = ? FOR UPDATE');
            $insumo->execute([(int) $reserva['id_insumo']]);
            $s = $insumo->fetch();
            if ((float) $s['stock_fisico'] < (float) $reserva['cantidad']) {
                throw new RuntimeException('El stock físico no alcanza para consumir esta reserva.');
            }
            $pdo->prepare('UPDATE insumos_menu SET stock_fisico = stock_fisico - ?, stock_comprometido = GREATEST(0, stock_comprometido - ?) WHERE id_insumo = ?')
                ->execute([(float) $reserva['cantidad'], (float) $reserva['cantidad'], (int) $reserva['id_insumo']]);
            $pdo->prepare("UPDATE reservas_insumo SET estado = 'consumida' WHERE id_reserva_insumo = ?")->execute([$idReserva]);
            self::movimiento($pdo, (int) $reserva['id_insumo'], (int) $reserva['id_evento'], 'consumo', (float) $reserva['cantidad'], $motivo);
            registrar_bitacora('CONSUMIR_INSUMOS', 'reservas_insumo', $idReserva, $motivo);
        });
    }

    public static function entrada(int $idInsumo, float $cantidad, string $motivo): void
    {
        if ($cantidad <= 0) {
            throw new RuntimeException('La cantidad de entrada debe ser mayor a cero.');
        }
        Database::transaccion(static function (PDO $pdo) use ($idInsumo, $cantidad, $motivo): void {
            $pdo->prepare('UPDATE insumos_menu SET stock_fisico = stock_fisico + ? WHERE id_insumo = ?')->execute([$cantidad, $idInsumo]);
            self::movimiento($pdo, $idInsumo, null, 'entrada', $cantidad, $motivo);
            registrar_bitacora('ENTRADA_INSUMO', 'insumos_menu', $idInsumo, '+' . $cantidad . ' · ' . $motivo);
        });
    }

    public static function ajuste(int $idInsumo, float $nuevoStock, string $motivo): void
    {
        if ($motivo === '') {
            throw new RuntimeException('El ajuste exige un motivo.');
        }
        Database::transaccion(static function (PDO $pdo) use ($idInsumo, $nuevoStock, $motivo): void {
            $stmt = $pdo->prepare('SELECT stock_fisico, stock_comprometido FROM insumos_menu WHERE id_insumo = ? FOR UPDATE');
            $stmt->execute([$idInsumo]);
            $insumo = $stmt->fetch();
            if (!$insumo) {
                throw new RuntimeException('El insumo no existe.');
            }
            if ($nuevoStock < 0 || $nuevoStock < (float) $insumo['stock_comprometido']) {
                throw new RuntimeException('El stock no puede quedar por debajo de lo comprometido.');
            }
            $delta = $nuevoStock - (float) $insumo['stock_fisico'];
            $pdo->prepare('UPDATE insumos_menu SET stock_fisico = ? WHERE id_insumo = ?')->execute([$nuevoStock, $idInsumo]);
            self::movimiento($pdo, $idInsumo, null, 'ajuste', $delta, $motivo);
            registrar_bitacora('AJUSTE_INSUMO', 'insumos_menu', $idInsumo, 'Nuevo stock: ' . $nuevoStock . ' · ' . $motivo);
        });
    }

    private static function movimiento(PDO $pdo, int $idInsumo, ?int $idEvento, string $tipo, float $cantidad, string $motivo): void
    {
        $stmt = $pdo->prepare('SELECT stock_fisico, stock_comprometido FROM insumos_menu WHERE id_insumo = ?');
        $stmt->execute([$idInsumo]);
        $s = $stmt->fetch();
        $pdo->prepare(
            'INSERT INTO movimientos_insumo (id_insumo, id_evento, tipo, cantidad, stock_fisico_resultante, stock_comprometido_resultante, motivo, id_usuario)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([$idInsumo, $idEvento, $tipo, $cantidad, $s['stock_fisico'], $s['stock_comprometido'], mb_substr($motivo, 0, 220), Auth::id() ?? 1]);
    }
}
