<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use App\Domain\Disponibilidad;

final class EspaciosController extends Controller
{
    public function index(): void
    {
        $pdo = Database::pdo();
        $espacios = $pdo->query(
            "SELECT e.*,
                    (SELECT COUNT(*) FROM reservas_espacio re WHERE re.id_espacio = e.id_espacio AND re.estado = 'confirmada') AS reservas,
                    (SELECT COUNT(*) FROM reserva_espacio_dias d
                      WHERE d.id_espacio = e.id_espacio AND d.activa = 1
                        AND d.fecha BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)) AS dias_ocupados
               FROM espacios e ORDER BY e.estado, e.capacidad DESC"
        )->fetchAll();

        foreach ($espacios as &$espacio) {
            $espacio['ocupacion'] = min(100, round(((int) $espacio['dias_ocupados'] / 90) * 100, 1));
        }
        unset($espacio);

        View::render('espacios/index', [
            'titulo'    => 'Espacios',
            'subtitulo' => 'Salones, galerías, terrazas y jardines con control de ocupación diario.',
            'espacios'  => $espacios,
            'reservas'  => $pdo->query(
                "SELECT re.*, e.nombre AS evento, e.id_evento, esp.nombre AS espacio
                   FROM reservas_espacio re
                   JOIN eventos e ON e.id_evento = re.id_evento
                   JOIN espacios esp ON esp.id_espacio = re.id_espacio
                  WHERE re.estado = 'confirmada' AND re.fecha_fin >= NOW()
                  ORDER BY re.fecha_inicio LIMIT 12"
            )->fetchAll(),
        ], 'app');
    }

    public function guardar(): void
    {
        $nombre = $this->texto('nombre');
        $tipo = $this->texto('tipo');
        $capacidad = $this->entero('capacidad');
        $costo = $this->decimal('costo_referencia');
        $descripcion = $this->texto('descripcion');
        $estado = $this->texto('estado');

        if ($nombre === '' || $capacidad < 1) {
            $this->error('El nombre y la capacidad son obligatorios.', '/espacios');
        }
        if (!in_array($tipo, ['salon_fiesta', 'salon_gala', 'sala_exhibicion', 'terraza', 'jardin'], true)) {
            $this->error('Selecciona un tipo de espacio válido.', '/espacios');
        }
        if (!in_array($estado, ['activo', 'mantenimiento', 'inactivo'], true)) {
            $estado = 'activo';
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM espacios WHERE nombre = ?');
        $stmt->execute([$nombre]);
        if ((int) $stmt->fetchColumn() > 0) {
            $this->error('Ya existe un espacio con ese nombre.', '/espacios');
        }

        $pdo->prepare(
            'INSERT INTO espacios (nombre, tipo, capacidad, costo_referencia, descripcion, estado) VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([mb_substr($nombre, 0, 100), $tipo, $capacidad, $costo, $descripcion !== '' ? mb_substr($descripcion, 0, 255) : null, $estado]);

        registrar_bitacora('CREAR_ESPACIO', 'espacios', (int) $pdo->lastInsertId(), $nombre . ' · ' . $capacidad . ' personas');
        $this->exito('Espacio creado correctamente.', '/espacios');
    }

    public function calendario(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM espacios WHERE id_espacio = ?');
        $stmt->execute([$id]);
        $espacio = $stmt->fetch();
        if (!$espacio) {
            $this->noEncontrado('El espacio no existe.');
        }

        $mes = (string) ($_GET['mes'] ?? date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
            $mes = date('Y-m');
        }
        $inicio = new \DateTimeImmutable($mes . '-01');
        $fin = $inicio->modify('last day of this month');

        $stmt = $pdo->prepare(
            "SELECT d.fecha, e.nombre AS evento, e.id_evento
               FROM reserva_espacio_dias d
               JOIN reservas_espacio re ON re.id_reserva_espacio = d.id_reserva_espacio
               JOIN eventos e ON e.id_evento = re.id_evento
              WHERE d.id_espacio = ? AND d.activa = 1 AND d.fecha BETWEEN ? AND ?
              ORDER BY d.fecha"
        );
        $stmt->execute([$id, $inicio->format('Y-m-d'), $fin->format('Y-m-d')]);
        $ocupados = [];
        foreach ($stmt->fetchAll() as $fila) {
            $ocupados[$fila['fecha']] = $fila;
        }

        $celdas = [];
        for ($i = 0; $i < (int) $inicio->format('N') - 1; $i++) {
            $celdas[] = ['dia' => null];
        }
        for ($dia = 1; $dia <= (int) $fin->format('j'); $dia++) {
            $fecha = $inicio->setDate((int) $inicio->format('Y'), (int) $inicio->format('n'), $dia)->format('Y-m-d');
            $celdas[] = [
                'dia'     => $dia,
                'fecha'   => $fecha,
                'ocupado' => isset($ocupados[$fecha]),
                'evento'  => $ocupados[$fecha] ?? null,
                'hoy'     => $fecha === date('Y-m-d'),
            ];
        }

        $stmt = $pdo->prepare(
            "SELECT re.*, e.nombre AS evento, e.id_evento, e.estado
               FROM reservas_espacio re JOIN eventos e ON e.id_evento = re.id_evento
              WHERE re.id_espacio = ? AND re.estado = 'confirmada' AND re.fecha_fin >= NOW()
              ORDER BY re.fecha_inicio LIMIT 12"
        );
        $stmt->execute([$id]);

        View::render('espacios/calendario', [
            'titulo'   => 'Calendario · ' . $espacio['nombre'],
            'migas'    => [['titulo' => 'Espacios', 'url' => '/espacios'], ['titulo' => $espacio['nombre']]],
            'espacio'  => $espacio,
            'celdas'   => $celdas,
            'etiqueta' => fecha($inicio->format('Y-m-d'), 'F Y'),
            'anterior' => $inicio->modify('-1 month')->format('Y-m'),
            'siguiente'=> $inicio->modify('+1 month')->format('Y-m'),
            'reservas' => $stmt->fetchAll(),
        ], 'app');
    }

    public function disponibilidad(): void
    {
        $inicio = $this->fecha('inicio') ?? date('Y-m-d');
        $fin = $this->rangoAcotado($inicio, $this->fecha('fin') ?? date('Y-m-d', strtotime($inicio . ' +1 day')));
        $this->json([
            'ok'      => true,
            'inicio'  => $inicio,
            'fin'     => $fin,
            'espacios'=> Disponibilidad::espaciosLibres($inicio, $fin),
        ]);
    }
}
