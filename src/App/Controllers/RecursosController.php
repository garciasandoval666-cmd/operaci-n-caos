<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use App\Domain\Disponibilidad;

final class RecursosController extends Controller
{
    public function index(): void
    {
        $pdo = Database::pdo();
        $recursos = $pdo->query(
            "SELECT r.*, p.nombre AS proveedor,
                    (SELECT COALESCE(SUM(rr.cantidad),0) FROM reservas_recurso rr
                      WHERE rr.id_recurso = r.id_recurso AND rr.estado = 'confirmada'
                        AND DATE(rr.fecha_inicio) <= CURDATE() AND DATE(rr.fecha_fin) >= CURDATE()) AS reservado
               FROM recursos r LEFT JOIN proveedores p ON p.id_proveedor = r.id_proveedor
              ORDER BY r.estado, r.categoria, r.nombre"
        )->fetchAll();

        foreach ($recursos as &$recurso) {
            $recurso['disponible'] = max(0, (int) $recurso['existencias_total'] - (int) $recurso['reservado']);
        }
        unset($recurso);

        View::render('recursos/index', [
            'titulo'      => 'Recursos',
            'subtitulo'   => 'Mobiliario, audio, personal y equipo con existencias y disponibilidad diaria.',
            'recursos'    => $recursos,
            'proveedores' => $pdo->query("SELECT * FROM proveedores WHERE estado = 'activo' ORDER BY nombre")->fetchAll(),
            'reservas'    => $pdo->query(
                "SELECT rr.*, r.nombre, r.unidad, e.nombre AS evento, e.id_evento
                   FROM reservas_recurso rr
                   JOIN recursos r ON r.id_recurso = rr.id_recurso
                   JOIN eventos e ON e.id_evento = rr.id_evento
                  WHERE rr.estado = 'confirmada' AND rr.fecha_fin >= NOW()
                  ORDER BY rr.fecha_inicio LIMIT 12"
            )->fetchAll(),
        ], 'app');
    }

    public function guardar(): void
    {
        $idProveedor = $this->entero('id_proveedor') ?: null;
        $categoria = $this->texto('categoria');
        $nombre = $this->texto('nombre');
        $unidad = $this->texto('unidad', 'pieza');
        $existencias = $this->entero('existencias_total');
        $costo = $this->decimal('costo_referencia');

        if ($nombre === '' || $existencias < 0) {
            $this->error('El nombre y las existencias son obligatorios.', '/recursos');
        }
        if (!in_array($categoria, ['mobiliario', 'personal', 'audio_video', 'decoracion', 'transporte', 'otro'], true)) {
            $this->error('Selecciona una categoría válida.', '/recursos');
        }

        try {
            Database::pdo()->prepare(
                'INSERT INTO recursos (id_proveedor, categoria, nombre, unidad, existencias_total, costo_referencia) VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$idProveedor, $categoria, mb_substr($nombre, 0, 100), mb_substr($unidad, 0, 20), $existencias, $costo]);
        } catch (\PDOException) {
            $this->error('Ya existe un recurso con ese nombre para el proveedor seleccionado.', '/recursos');
        }

        registrar_bitacora('CREAR_RECURSO', 'recursos', (int) Database::pdo()->lastInsertId(), $nombre . ' × ' . $existencias);
        $this->exito('Recurso agregado al inventario.', '/recursos');
    }

    public function disponibilidad(): void
    {
        $inicio = $this->fecha('inicio') ?? date('Y-m-d');
        $fin = $this->rangoAcotado($inicio, $this->fecha('fin') ?? date('Y-m-d', strtotime($inicio . ' +1 day')));
        $recursos = Disponibilidad::recursosDisponibles($inicio, $fin);

        View::render('recursos/disponibilidad', [
            'titulo'    => 'Disponibilidad de recursos',
            'subtitulo' => 'Consulta por rango de fechas antes de comprometer equipo o personal.',
            'recursos'  => $recursos,
            'inicio'    => $inicio,
            'fin'       => $fin,
        ], 'app');
    }
}
