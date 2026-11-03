<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use App\Domain\Eventos;

final class EventosController extends Controller
{
    public function index(): void
    {
        $pdo = Database::pdo();
        $q = $this->termino();
        $estado = $this->filtro('estado', array_keys(Eventos::TRANSICIONES));
        $tipo = (int) ($_GET['tipo'] ?? 0);
        $desde = $this->fechaGet('desde');
        $hasta = $this->fechaGet('hasta');

        $where = [];
        $params = [];
        if ($q !== '') {
            $where[] = '(e.nombre LIKE ? OR c.nombre LIKE ?)';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }
        if ($estado !== '') {
            $where[] = 'e.estado = ?';
            $params[] = $estado;
        }
        if ($tipo > 0) {
            $where[] = 'e.id_tipo_evento = ?';
            $params[] = $tipo;
        }
        if ($desde !== null) {
            $where[] = 'e.fecha_inicio >= ?';
            $params[] = $desde . ' 00:00:00';
        }
        if ($hasta !== null) {
            $where[] = 'e.fecha_inicio <= ?';
            $params[] = $hasta . ' 23:59:59';
        }
        $filtroSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';

        $total = $pdo->prepare(
            'SELECT COUNT(*) FROM eventos e JOIN clientes c ON c.id_cliente = e.id_cliente' . $filtroSql
        );
        $total->execute($params);
        $total = (int) $total->fetchColumn();

        $porPagina = 15;
        $sql = "SELECT e.*, c.nombre AS cliente, t.nombre AS tipo, t.icono,
                       (SELECT esp.nombre FROM reservas_espacio re
                          JOIN espacios esp ON esp.id_espacio = re.id_espacio
                         WHERE re.id_evento = e.id_evento AND re.estado = 'confirmada'
                         ORDER BY re.id_reserva_espacio LIMIT 1) AS espacio,
                       (SELECT COUNT(*) FROM invitados i WHERE i.id_evento = e.id_evento AND i.estado_rsvp = 'confirmado') AS confirmados,
                       (SELECT COALESCE(SUM(v.total),0) FROM v_cotizacion_saldo v WHERE v.id_evento = e.id_evento AND v.estado = 'aceptada') AS cotizado,
                       (SELECT COALESCE(SUM(v.saldo),0) FROM v_cotizacion_saldo v WHERE v.id_evento = e.id_evento AND v.estado = 'aceptada') AS saldo
                  FROM eventos e
                  JOIN clientes c ON c.id_cliente = e.id_cliente
                  JOIN tipos_evento t ON t.id_tipo_evento = e.id_tipo_evento" . $filtroSql . '
                 ORDER BY CASE WHEN e.fecha_inicio >= NOW() THEN 0 ELSE 1 END, e.fecha_inicio
                 LIMIT ? OFFSET ?';
        $stmt = $pdo->prepare($sql);
        $i = 1;
        foreach ($params as $param) {
            $stmt->bindValue($i++, $param, is_int($param) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $stmt->bindValue($i++, $porPagina, \PDO::PARAM_INT);
        $stmt->bindValue($i, $this->offset($porPagina), \PDO::PARAM_INT);
        $stmt->execute();

        View::render('eventos/index', [
            'titulo'    => 'Eventos',
            'subtitulo' => 'Ciclo completo: prospecto, cotización, confirmación, producción y cierre.',
            'eventos'   => $stmt->fetchAll(),
            'tipos'     => $pdo->query('SELECT * FROM tipos_evento WHERE activo = 1 ORDER BY nombre')->fetchAll(),
            'total'     => $total,
            'q'         => $q,
            'estado'    => $estado,
            'tipo'      => $tipo,
            'desde'     => $desde,
            'hasta'     => $hasta,
            'porPagina' => $porPagina,
        ], 'app');
    }

    public function crear(): void
    {
        $pdo = Database::pdo();
        View::render('eventos/form', [
            'titulo'    => 'Nuevo evento',
            'migas'     => [['titulo' => 'Eventos', 'url' => '/eventos'], ['titulo' => 'Nuevo']],
            'evento'    => null,
            'clientes'  => $pdo->query("SELECT * FROM clientes WHERE estado = 'activo' ORDER BY nombre")->fetchAll(),
            'tipos'     => $pdo->query('SELECT * FROM tipos_evento WHERE activo = 1 ORDER BY nombre')->fetchAll(),
            'espacios'  => $pdo->query("SELECT * FROM espacios WHERE estado = 'activo' ORDER BY capacidad DESC")->fetchAll(),
            'errores'   => errores(),
        ], 'app');
    }

    public function guardar(): void
    {
        $datos = $this->datos();
        if ($datos['fecha_inicio'] === null || $datos['fecha_fin'] === null) {
            recordar_viejos($_POST);
            $this->error('Las fechas de inicio y fin son obligatorias.', '/eventos/crear');
        }

        try {
            $id = Eventos::crear($datos);
        } catch (\RuntimeException $e) {
            recordar_viejos($_POST);
            $this->error($e->getMessage(), '/eventos/crear');
        }

        limpiar_viejos();
        $this->exito('Evento creado. Ahora puedes cotizarlo y reservar recursos.', '/eventos/' . $id);
    }

    public function ver(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $pdo = Database::pdo();
        $evento = $this->evento($id);

        $stmt = $pdo->prepare(
            "SELECT re.*, esp.nombre AS espacio, esp.capacidad, esp.tipo AS tipo_espacio, esp.costo_referencia
               FROM reservas_espacio re JOIN espacios esp ON esp.id_espacio = re.id_espacio
              WHERE re.id_evento = ? AND re.estado = 'confirmada' ORDER BY re.id_reserva_espacio"
        );
        $stmt->execute([$id]);
        $espacios = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            "SELECT rr.*, r.nombre, r.unidad, r.costo_referencia, p.nombre AS proveedor
               FROM reservas_recurso rr
               JOIN recursos r ON r.id_recurso = rr.id_recurso
               LEFT JOIN proveedores p ON p.id_proveedor = r.id_proveedor
              WHERE rr.id_evento = ? AND rr.estado = 'confirmada' ORDER BY r.categoria, r.nombre"
        );
        $stmt->execute([$id]);
        $recursos = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            "SELECT em.*, m.nombre, m.tipo_servicio, m.precio_persona,
                    (SELECT COUNT(*) FROM menu_platillos mp WHERE mp.id_menu = m.id_menu) AS platillos
               FROM evento_menus em JOIN menus m ON m.id_menu = em.id_menu
              WHERE em.id_evento = ?"
        );
        $stmt->execute([$id]);
        $menus = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            "SELECT estado_rsvp, COUNT(*) total FROM invitados WHERE id_evento = ? GROUP BY estado_rsvp"
        );
        $stmt->execute([$id]);
        $invitadosPorEstado = ['pendiente' => 0, 'confirmado' => 0, 'declinado' => 0, 'lista_espera' => 0];
        foreach ($stmt->fetchAll() as $fila) {
            $invitadosPorEstado[$fila['estado_rsvp']] = (int) $fila['total'];
        }
        $totalInvitados = array_sum($invitadosPorEstado);

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM invitados WHERE id_evento = ? AND checkin_en IS NOT NULL');
        $stmt->execute([$id]);
        $checkins = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT c.*, v.pagado, v.saldo FROM cotizaciones c
               LEFT JOIN v_cotizacion_saldo v ON v.id_cotizacion = c.id_cotizacion
              WHERE c.id_evento = ? ORDER BY c.version DESC, c.id_cotizacion DESC LIMIT 1"
        );
        $stmt->execute([$id]);
        $cotizacion = $stmt->fetch();

        $pagos = [];
        if ($cotizacion) {
            $stmt = $pdo->prepare('SELECT * FROM pagos WHERE id_cotizacion = ? ORDER BY fecha_pago DESC');
            $stmt->execute([(int) $cotizacion['id_cotizacion']]);
            $pagos = $stmt->fetchAll();
        }

        $stmt = $pdo->prepare('SELECT * FROM evento_programa WHERE id_evento = ? ORDER BY inicio');
        $stmt->execute([$id]);
        $programa = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            'SELECT es.*, s.nombre, s.categoria FROM evento_servicios es
               JOIN servicios s ON s.id_servicio = es.id_servicio WHERE es.id_evento = ? ORDER BY s.categoria'
        );
        $stmt->execute([$id]);
        $servicios = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            "SELECT ri.*, i.nombre, i.unidad, i.stock_fisico, i.stock_comprometido
               FROM reservas_insumo ri JOIN insumos_menu i ON i.id_insumo = ri.id_insumo
              WHERE ri.id_evento = ? AND ri.estado = 'confirmada' ORDER BY i.nombre"
        );
        $stmt->execute([$id]);
        $insumos = $stmt->fetchAll();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM mesas WHERE id_evento = ?');
        $stmt->execute([$id]);
        $mesas = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM invitados WHERE id_evento = ? AND restriccion_alimenticia <> \'ninguna\'');
        $stmt->execute([$id]);
        $restricciones = (int) $stmt->fetchColumn();

        $transiciones = Eventos::TRANSICIONES[$evento['estado']] ?? [];

        View::render('eventos/ver', [
            'titulo'             => $evento['nombre'],
            'migas'              => [['titulo' => 'Eventos', 'url' => '/eventos'], ['titulo' => $evento['nombre']]],
            'evento'             => $evento,
            'espacios'           => $espacios,
            'recursos'           => $recursos,
            'menus'              => $menus,
            'invitadosPorEstado' => $invitadosPorEstado,
            'totalInvitados'     => $totalInvitados,
            'checkins'           => $checkins,
            'cotizacion'         => $cotizacion,
            'pagos'              => $pagos,
            'programa'           => $programa,
            'servicios'          => $servicios,
            'insumos'            => $insumos,
            'mesas'              => $mesas,
            'restricciones'      => $restricciones,
            'transiciones'       => $transiciones,
            'menusDisponibles'   => $pdo->query('SELECT * FROM menus WHERE activo = 1 ORDER BY nombre')->fetchAll(),
            'catalogoServicios'  => $pdo->query('SELECT * FROM servicios WHERE activo = 1 ORDER BY categoria, nombre')->fetchAll(),
            'insumosDisponibles' => $pdo->query(
                "SELECT id_insumo, nombre, unidad, stock_fisico - stock_comprometido AS disponible
                   FROM insumos_menu WHERE estado = 'activo' ORDER BY nombre"
            )->fetchAll(),
        ], 'app');
    }

    public function editar(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $pdo = Database::pdo();
        $evento = $this->evento($id);
        if ($evento['estado'] === 'cancelado') {
            $this->error('Un evento cancelado ya no se puede editar.', '/eventos/' . $id);
        }

        View::render('eventos/form', [
            'titulo'   => 'Editar ' . $evento['nombre'],
            'migas'    => [['titulo' => 'Eventos', 'url' => '/eventos'], ['titulo' => $evento['nombre'], 'url' => '/eventos/' . $id], ['titulo' => 'Editar']],
            'evento'   => $evento,
            'clientes' => $pdo->query("SELECT * FROM clientes WHERE estado = 'activo' OR id_cliente = " . (int) $evento['id_cliente'] . ' ORDER BY nombre')->fetchAll(),
            'tipos'    => $pdo->query('SELECT * FROM tipos_evento WHERE activo = 1 ORDER BY nombre')->fetchAll(),
            'espacios' => $pdo->query("SELECT * FROM espacios WHERE estado = 'activo' ORDER BY capacidad DESC")->fetchAll(),
            'errores'  => errores(),
        ], 'app');
    }

    public function actualizar(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $datos = $this->datos();
        if ($datos['fecha_inicio'] === null || $datos['fecha_fin'] === null) {
            recordar_viejos($_POST);
            $this->error('Las fechas de inicio y fin son obligatorias.', '/eventos/' . $id . '/editar');
        }

        try {
            Eventos::actualizar($id, $datos);
        } catch (\RuntimeException $e) {
            recordar_viejos($_POST);
            $this->error($e->getMessage(), '/eventos/' . $id . '/editar');
        }

        limpiar_viejos();
        $this->exito('Evento actualizado.', '/eventos/' . $id);
    }

    public function cambiarFecha(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $inicio = $this->fechaHora('fecha_inicio');
        $fin = $this->fechaHora('fecha_fin');
        $motivo = $this->texto('motivo');

        if ($inicio === null || $fin === null || $motivo === '') {
            $this->error('Indica las nuevas fechas y el motivo del cambio.', '/eventos/' . $id);
        }

        try {
            Eventos::cambiarFecha($id, $inicio, $fin, $motivo);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage(), '/eventos/' . $id);
        }

        $this->exito('Fechas reprogramadas y reservas movidas en una sola transacción.', '/eventos/' . $id);
    }

    public function estado(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $nuevo = $this->texto('estado');
        $motivo = $this->texto('motivo');

        try {
            Eventos::cambiarEstado($id, $nuevo, $motivo);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage(), '/eventos/' . $id);
        }

        $this->exito('Estado del evento actualizado a ' . str_replace('_', ' ', $nuevo) . '.', '/eventos/' . $id);
    }

    public function cancelar(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $motivo = $this->texto('motivo');
        if ($motivo === '') {
            $this->error('El motivo de cancelación es obligatorio.', '/eventos/' . $id);
        }

        try {
            $resultado = Eventos::cancelar($id, $motivo);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage(), '/eventos/' . $id);
        }

        $this->exito(
            'Evento cancelado en cascada. Penalización calculada: ' . money($resultado['penalizacion']) . '.',
            '/eventos/' . $id
        );
    }

    public function historial(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $evento = $this->evento($id);
        $pdo = Database::pdo();

        $stmt = $pdo->prepare(
            'SELECT h.*, u.nombre AS usuario FROM historial_cambios h
               LEFT JOIN usuarios u ON u.id_usuario = h.id_usuario
              WHERE h.entidad = ? AND h.id_registro = ? ORDER BY h.fecha_cambio DESC, h.id_historial DESC'
        );
        $stmt->execute(['eventos', $id]);
        $historial = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            'SELECT b.*, u.nombre AS usuario FROM bitacora b
               LEFT JOIN usuarios u ON u.id_usuario = b.id_usuario
              WHERE b.entidad = ? AND b.id_registro = ? ORDER BY b.fecha DESC, b.id_bitacora DESC LIMIT 80'
        );
        $stmt->execute(['eventos', $id]);
        $bitacora = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            "SELECT h.*, u.nombre AS usuario FROM historial_cambios h
               LEFT JOIN usuarios u ON u.id_usuario = h.id_usuario
              WHERE h.entidad IN ('reservas_espacio','reservas_recurso','cotizaciones','reservas_insumo')
                AND h.id_registro IN (
                    SELECT id_reserva_espacio FROM reservas_espacio WHERE id_evento = ?
                    UNION SELECT id_reserva_recurso FROM reservas_recurso WHERE id_evento = ?
                    UNION SELECT id_cotizacion FROM cotizaciones WHERE id_evento = ?
                    UNION SELECT id_reserva_insumo FROM reservas_insumo WHERE id_evento = ?
                )
              ORDER BY h.fecha_cambio DESC LIMIT 60"
        );
        $stmt->execute([$id, $id, $id, $id]);
        $relacionados = $stmt->fetchAll();

        View::render('eventos/historial', [
            'titulo'      => 'Historial · ' . $evento['nombre'],
            'migas'       => [['titulo' => 'Eventos', 'url' => '/eventos'], ['titulo' => $evento['nombre'], 'url' => '/eventos/' . $id], ['titulo' => 'Historial']],
            'evento'      => $evento,
            'historial'   => $historial,
            'bitacora'    => $bitacora,
            'relacionados'=> $relacionados,
        ], 'app');
    }

    public function programa(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $this->evento($id);
        $inicio = $this->fechaHora('inicio');
        $fin = $this->fechaHora('fin');
        $actividad = $this->texto('actividad');
        $responsable = $this->texto('responsable');

        if ($inicio === null || $actividad === '') {
            $this->error('La actividad y su hora de inicio son obligatorias.', '/eventos/' . $id);
        }

        Database::pdo()->prepare(
            'INSERT INTO evento_programa (id_evento, inicio, fin, actividad, responsable) VALUES (?, ?, ?, ?, ?)'
        )->execute([$id, $inicio, $fin, mb_substr($actividad, 0, 150), $responsable !== '' ? mb_substr($responsable, 0, 100) : null]);

        registrar_bitacora('AGREGAR_PROGRAMA', 'eventos', $id, $actividad);
        $this->exito('Actividad agregada al programa.', '/eventos/' . $id);
    }

    public function servicios(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $this->evento($id);
        $servicios = array_map('intval', (array) ($_POST['servicios'] ?? []));
        $servicios = array_values(array_filter($servicios, static fn (int $v): bool => $v > 0));

        Eventos::sincronizarServicios($id, $servicios);
        $this->exito('Servicios del evento actualizados.', '/eventos/' . $id);
    }

    private function datos(): array
    {
        return [
            'id_cliente'     => $this->entero('id_cliente'),
            'id_tipo_evento' => $this->entero('id_tipo_evento'),
            'nombre'         => $this->texto('nombre'),
            'descripcion'    => $this->texto('descripcion'),
            'fecha_inicio'   => $this->fechaHora('fecha_inicio'),
            'fecha_fin'      => $this->fechaHora('fecha_fin'),
            'aforo_estimado' => $this->entero('aforo_estimado', 1),
            'aforo_maximo'   => $this->entero('aforo_maximo', 1),
            'notas'          => $this->texto('notas'),
            'id_espacio'     => $this->entero('id_espacio') ?: null,
        ];
    }

    private function fechaGet(string $campo): ?string
    {
        $valor = trim((string) ($_GET[$campo] ?? ''));
        if ($valor === '') {
            return null;
        }
        $ts = strtotime($valor);
        return $ts !== false ? date('Y-m-d', $ts) : null;
    }
}
