<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use App\Domain\Disponibilidad;

final class ReservasController extends Controller
{
    public function index(): void
    {
        $pdo = Database::pdo();
        $estado = $this->filtro('estado', ['confirmada', 'cancelada']);
        $eventoId = (int) ($_GET['evento'] ?? 0);
        $q = $this->termino();

        $where = [];
        $params = [];
        if ($estado !== '') {
            $where[] = 're.estado = ?';
            $params[] = $estado;
        }
        if ($eventoId > 0) {
            $where[] = 're.id_evento = ?';
            $params[] = $eventoId;
        }
        if ($q !== '') {
            $where[] = '(e.nombre LIKE ? OR esp.nombre LIKE ?)';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }
        $filtroSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';

        $stmt = $pdo->prepare(
            "SELECT re.*, e.nombre AS evento, e.fecha_inicio AS evento_inicio, c.nombre AS cliente,
                    esp.nombre AS espacio, esp.capacidad, u.nombre AS usuario
               FROM reservas_espacio re
               JOIN eventos e ON e.id_evento = re.id_evento
               JOIN clientes c ON c.id_cliente = e.id_cliente
               JOIN espacios esp ON esp.id_espacio = re.id_espacio
               LEFT JOIN usuarios u ON u.id_usuario = re.id_usuario" . $filtroSql . '
              ORDER BY re.fecha_inicio DESC LIMIT 100'
        );
        $stmt->execute($params);
        $espacios = $stmt->fetchAll();

        $whereR = [];
        $paramsR = [];
        if ($estado !== '') {
            $whereR[] = 'rr.estado = ?';
            $paramsR[] = $estado;
        }
        if ($eventoId > 0) {
            $whereR[] = 'rr.id_evento = ?';
            $paramsR[] = $eventoId;
        }
        if ($q !== '') {
            $whereR[] = '(e.nombre LIKE ? OR r.nombre LIKE ?)';
            $paramsR[] = '%' . $q . '%';
            $paramsR[] = '%' . $q . '%';
        }
        $filtroR = $whereR !== [] ? ' WHERE ' . implode(' AND ', $whereR) : '';

        $stmt = $pdo->prepare(
            "SELECT rr.*, e.nombre AS evento, c.nombre AS cliente, r.nombre, r.unidad,
                    p.nombre AS proveedor, u.nombre AS usuario
               FROM reservas_recurso rr
               JOIN eventos e ON e.id_evento = rr.id_evento
               JOIN clientes c ON c.id_cliente = e.id_cliente
               JOIN recursos r ON r.id_recurso = rr.id_recurso
               LEFT JOIN proveedores p ON p.id_proveedor = r.id_proveedor
               LEFT JOIN usuarios u ON u.id_usuario = rr.id_usuario" . $filtroR . '
              ORDER BY rr.fecha_inicio DESC LIMIT 100'
        );
        $stmt->execute($paramsR);
        $recursos = $stmt->fetchAll();

        View::render('reservas/index', [
            'titulo'    => 'Reservas',
            'subtitulo' => 'Bloqueos de espacios y apartado de recursos con validación de empalmes.',
            'espacios'  => $espacios,
            'recursos'  => $recursos,
            'eventos'   => $pdo->query('SELECT id_evento, nombre, fecha_inicio FROM eventos ORDER BY fecha_inicio DESC')->fetchAll(),
            'estado'    => $estado,
            'eventoId'  => $eventoId,
            'q'         => $q,
        ], 'app');
    }

    public function crear(): void
    {
        $pdo = Database::pdo();
        $eventos = $pdo->query(
            "SELECT e.*, c.nombre AS cliente, t.nombre AS tipo
               FROM eventos e JOIN clientes c ON c.id_cliente = e.id_cliente
               JOIN tipos_evento t ON t.id_tipo_evento = e.id_tipo_evento
              WHERE e.estado NOT IN ('cancelado','cerrado')
              ORDER BY e.fecha_inicio"
        )->fetchAll();
        $eventoId = (int) ($_GET['evento'] ?? ($eventos[0]['id_evento'] ?? 0));
        $evento = null;
        foreach ($eventos as $fila) {
            if ((int) $fila['id_evento'] === $eventoId) {
                $evento = $fila;
                break;
            }
        }

        View::render('reservas/crear', [
            'titulo'   => 'Nueva reserva',
            'migas'    => [['titulo' => 'Reservas', 'url' => '/reservas'], ['titulo' => 'Nueva']],
            'eventos'  => $eventos,
            'evento'   => $evento,
            'espacios' => $pdo->query("SELECT * FROM espacios WHERE estado = 'activo' ORDER BY capacidad DESC")->fetchAll(),
            'recursos' => $pdo->query("SELECT r.*, p.nombre AS proveedor FROM recursos r LEFT JOIN proveedores p ON p.id_proveedor = r.id_proveedor WHERE r.estado = 'activo' ORDER BY r.categoria, r.nombre")->fetchAll(),
        ], 'app');
    }

    public function guardar(): void
    {
        $tipo = $this->texto('tipo');
        $idEvento = $this->entero('id_evento');
        $inicio = $this->fechaHora('fecha_inicio');
        $fin = $this->fechaHora('fecha_fin');
        $motivo = $this->texto('motivo');

        if ($idEvento <= 0) {
            $this->error('Selecciona el evento.', '/reservas/crear');
        }
        if ($inicio === null || $fin === null) {
            $this->error('Indica la fecha y hora de inicio y fin.', '/reservas/crear');
        }

        try {
            if ($tipo === 'espacio') {
                $idEspacio = $this->entero('id_espacio');
                if ($idEspacio <= 0) {
                    $this->error('Selecciona un espacio.', '/reservas/crear');
                }
                Disponibilidad::reservarEspacio($idEvento, $idEspacio, $inicio, $fin, $motivo);
                $this->exito('Espacio reservado y bloqueado en el calendario.', '/eventos/' . $idEvento);
            }

            if ($tipo === 'recurso') {
                $idRecurso = $this->entero('id_recurso');
                $cantidad = $this->entero('cantidad');
                if ($idRecurso <= 0 || $cantidad <= 0) {
                    $this->error('Selecciona un recurso e indica la cantidad.', '/reservas/crear');
                }
                Disponibilidad::reservarRecurso($idEvento, $idRecurso, $cantidad, $inicio, $fin, $motivo);
                $this->exito('Recurso apartado y validado contra existencias.', '/eventos/' . $idEvento);
            }

            $this->error('Selecciona si la reserva es de espacio o de recurso.', '/reservas/crear');
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage(), '/reservas/crear?evento=' . $idEvento);
        }
    }

    public function cancelar(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $tipo = $this->texto('tipo');
        $motivo = $this->texto('motivo');
        if ($motivo === '') {
            $this->error('Explica el motivo de la cancelación.', '/reservas');
        }

        try {
            if ($tipo === 'recurso') {
                Disponibilidad::cancelarReservaRecurso($id, $motivo);
            } else {
                Disponibilidad::cancelarReservaEspacio($id, $motivo);
            }
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage(), '/reservas');
        }

        $this->exito('Reserva cancelada y disponibilidad liberada.', '/reservas');
    }
}
