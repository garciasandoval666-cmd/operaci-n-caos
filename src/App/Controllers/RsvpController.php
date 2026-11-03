<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use App\Domain\Invitados;

/**
 * Módulo A · RSVP público por token, sin exponer datos de otros invitados.
 */
final class RsvpController extends Controller
{
    public function formulario(): void
    {
        $token = trim((string) ($_GET['token'] ?? ''));
        $invitado = null;
        $menus = [];
        $error = null;

        if ($token !== '') {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare(
                'SELECT i.*, e.nombre AS evento, e.fecha_inicio, e.fecha_fin, e.aforo_maximo,
                        e.estado AS estado_evento, e.id_evento, e.aforo_estimado,
                        c.nombre AS cliente, t.nombre AS tipo_evento
                   FROM invitados i
                   JOIN eventos e ON e.id_evento = i.id_evento
                   JOIN clientes c ON c.id_cliente = e.id_cliente
                   JOIN tipos_evento t ON t.id_tipo_evento = e.id_tipo_evento
                  WHERE i.token_acceso = ? LIMIT 1'
            );
            $stmt->execute([$token]);
            $invitado = $stmt->fetch();

            if (!$invitado) {
                $error = 'No encontramos una invitación con ese código. Verifica el enlace o escribe a tu anfitrión.';
            } else {
                $stmt = $pdo->prepare(
                    'SELECT em.id_evento_menu, em.personas, m.nombre, m.tipo_servicio, m.precio_persona
                       FROM evento_menus em JOIN menus m ON m.id_menu = em.id_menu
                      WHERE em.id_evento = ? ORDER BY m.nombre'
                );
                $stmt->execute([(int) $invitado['id_evento']]);
                $menus = $stmt->fetchAll();

                if (in_array($invitado['estado_evento'], ['cancelado', 'realizado', 'cerrado'], true)) {
                    $error = 'Este evento ya no recibe confirmaciones. Contacta a tu anfitrión para más información.';
                }
            }
        }

        View::render('rsvp/formulario', [
            'titulo'   => 'Confirma tu asistencia',
            'token'    => $token,
            'invitado' => $invitado,
            'menus'    => $menus,
            'error'    => $error,
        ], 'boda');
    }

    public function responder(): void
    {
        $token = trim((string) ($_POST['token'] ?? ''));
        $respuesta = (string) ($_POST['respuesta'] ?? '');
        $menu = $this->entero('id_evento_menu') ?: null;
        $restriccion = (string) ($_POST['restriccion_alimenticia'] ?? 'ninguna');
        $alergias = $this->texto('alergias');

        if (!in_array($restriccion, ['ninguna', 'vegetariano', 'vegano', 'sin_gluten', 'otra'], true)) {
            $restriccion = 'ninguna';
        }

        if ($token === '') {
            $this->error('El enlace de invitación no es válido.', '/rsvp');
        }

        try {
            $resultado = Invitados::confirmarRsvp($token, $respuesta, $menu, $restriccion, $alergias);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage(), '/rsvp?token=' . urlencode($token));
        }

        View::render('rsvp/respuesta', [
            'titulo'    => $resultado['estado'] === 'confirmado' ? 'Asistencia confirmada' : 'Respuesta registrada',
            'resultado' => $resultado,
        ], 'boda');
    }
}
