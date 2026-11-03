<?php
declare(strict_types=1);

namespace App\Domain;

use App\Core\Database;
use PDO;
use RuntimeException;

/**
 * Módulo A · RSVP por token, aforo, mesas y check-in.
 */
final class Invitados
{
    public static function confirmarRsvp(string $token, string $respuesta, ?int $idEventoMenu, string $restriccion, string $alergias): array
    {
        if (!in_array($respuesta, ['confirmado', 'declinado'], true)) {
            throw new RuntimeException('Respuesta no válida.');
        }
        return Database::transaccion(static function (PDO $pdo) use ($token, $respuesta, $idEventoMenu, $restriccion, $alergias): array {
            $stmt = $pdo->prepare(
                'SELECT i.*, e.nombre AS evento, e.fecha_inicio, e.fecha_fin, e.aforo_maximo, e.estado AS estado_evento
                   FROM invitados i JOIN eventos e ON e.id_evento = i.id_evento
                  WHERE i.token_acceso = ? FOR UPDATE'
            );
            $stmt->execute([$token]);
            $invitado = $stmt->fetch();
            if (!$invitado) {
                throw new RuntimeException('El enlace no es válido o ya expiró.');
            }
            if (in_array($invitado['estado_evento'], ['cancelado', 'realizado', 'cerrado'], true)) {
                throw new RuntimeException('Este evento ya no recibe confirmaciones.');
            }

            $evento = $pdo->prepare('SELECT aforo_maximo, estado FROM eventos WHERE id_evento = ? FOR UPDATE');
            $evento->execute([(int) $invitado['id_evento']]);
            $ev = $evento->fetch();

            $estadoFinal = 'declinado';
            if ($respuesta === 'confirmado') {
                $conteo = $pdo->prepare("SELECT COUNT(*) FROM invitados WHERE id_evento = ? AND estado_rsvp = 'confirmado' AND id_invitado <> ?");
                $conteo->execute([(int) $invitado['id_evento'], (int) $invitado['id_invitado']]);
                $confirmados = (int) $conteo->fetchColumn();
                $estadoFinal = $confirmados < (int) $ev['aforo_maximo'] ? 'confirmado' : 'lista_espera';
            }

            $menu = $idEventoMenu;
            if ($menu !== null) {
                $valido = $pdo->prepare('SELECT COUNT(*) FROM evento_menus WHERE id_evento_menu = ? AND id_evento = ?');
                $valido->execute([$menu, (int) $invitado['id_evento']]);
                if ((int) $valido->fetchColumn() === 0) {
                    $menu = null;
                }
            }

            $pdo->prepare(
                'UPDATE invitados SET estado_rsvp = ?, id_evento_menu = ?, restriccion_alimenticia = ?, alergias = ?
                  WHERE id_invitado = ?'
            )->execute([$estadoFinal, $menu, $restriccion, mb_substr($alergias, 0, 220), (int) $invitado['id_invitado']]);

            registrar_historial('invitados', (int) $invitado['id_invitado'], 'estado_rsvp', $invitado['estado_rsvp'], $estadoFinal, 'Respuesta del invitado vía RSVP');
            registrar_bitacora('RSVP_INVITADO', 'invitados', (int) $invitado['id_invitado'], $invitado['nombre'] . ' → ' . $estadoFinal);

            return ['estado' => $estadoFinal, 'nombre' => $invitado['nombre'], 'evento' => $invitado['evento'], 'aforo' => (int) $ev['aforo_maximo']];
        });
    }

    public static function checkin(int $idInvitado, int $idUsuario): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            "UPDATE invitados SET checkin_en = NOW(), checkin_por = ?
              WHERE id_invitado = ? AND estado_rsvp = 'confirmado' AND checkin_en IS NULL"
        );
        $stmt->execute([$idUsuario, $idInvitado]);
        if ($stmt->rowCount() === 0) {
            $info = $pdo->prepare('SELECT estado_rsvp, checkin_en, nombre FROM invitados WHERE id_invitado = ?');
            $info->execute([$idInvitado]);
            $inv = $info->fetch();
            if (!$inv) {
                throw new RuntimeException('El invitado no existe.');
            }
            if ($inv['checkin_en'] !== null) {
                throw new RuntimeException($inv['nombre'] . ' ya registró su entrada a las ' . date('H:i', strtotime($inv['checkin_en'])) . '.');
            }
            throw new RuntimeException('El invitado no está confirmado (estado: ' . $inv['estado_rsvp'] . ').');
        }
        registrar_bitacora('CHECKIN', 'invitados', $idInvitado, 'Entrada registrada');
        return ['ok' => true];
    }

    public static function asignarMesa(int $idInvitado, ?int $idMesa): void
    {
        Database::transaccion(static function (PDO $pdo) use ($idInvitado, $idMesa): void {
            $stmt = $pdo->prepare('SELECT * FROM invitados WHERE id_invitado = ? FOR UPDATE');
            $stmt->execute([$idInvitado]);
            $invitado = $stmt->fetch();
            if (!$invitado) {
                throw new RuntimeException('El invitado no existe.');
            }
            if ($idMesa !== null) {
                $mesa = $pdo->prepare('SELECT * FROM mesas WHERE id_mesa = ? AND id_evento = ? FOR UPDATE');
                $mesa->execute([$idMesa, (int) $invitado['id_evento']]);
                $m = $mesa->fetch();
                if (!$m) {
                    throw new RuntimeException('La mesa no pertenece a este evento.');
                }
                $ocupados = $pdo->prepare('SELECT COUNT(*) FROM invitados WHERE id_mesa = ? AND id_invitado <> ?');
                $ocupados->execute([$idMesa, $idInvitado]);
                if ((int) $ocupados->fetchColumn() >= (int) $m['capacidad']) {
                    throw new RuntimeException('La mesa ' . $m['numero'] . ' está llena (' . $m['capacidad'] . ' lugares).');
                }
            }
            $pdo->prepare('UPDATE invitados SET id_mesa = ? WHERE id_invitado = ?')->execute([$idMesa, $idInvitado]);
            registrar_historial('invitados', $idInvitado, 'id_mesa', $invitado['id_mesa'], $idMesa, 'Asignación de mesa');
            registrar_bitacora('ASIGNAR_MESA', 'invitados', $idInvitado, 'Mesa: ' . ($idMesa ?? 'sin asignar'));
        });
    }
}
