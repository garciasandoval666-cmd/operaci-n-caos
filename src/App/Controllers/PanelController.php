<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

final class PanelController extends Controller
{
    public function index(): void
    {
        $pdo = Database::pdo();
        $hoy = date('Y-m-d');

        $kpis = [
            'activos' => (int) $pdo->query(
                "SELECT COUNT(*) FROM eventos WHERE estado NOT IN ('cancelado','cerrado')"
            )->fetchColumn(),
            'proximos' => (int) $pdo->query(
                "SELECT COUNT(*) FROM eventos WHERE estado NOT IN ('cancelado','cerrado')
                   AND fecha_inicio BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)"
            )->fetchColumn(),
            'ingresos_mes' => (float) $pdo->query(
                "SELECT COALESCE(SUM(monto),0) FROM pagos
                  WHERE estado <> 'revertido' AND tipo <> 'devolucion'
                    AND DATE_FORMAT(fecha_pago, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')"
            )->fetchColumn(),
            'por_cobrar' => (float) $pdo->query(
                "SELECT COALESCE(SUM(saldo),0) FROM v_cotizacion_saldo WHERE estado = 'aceptada' AND saldo > 0.01"
            )->fetchColumn(),
            'leads' => (int) $pdo->query("SELECT COUNT(*) FROM leads WHERE estado = 'nuevo'")->fetchColumn(),
            'confirmados' => (int) $pdo->query(
                "SELECT COUNT(*) FROM invitados WHERE estado_rsvp = 'confirmado'"
            )->fetchColumn(),
        ];

        $diasOcupados = (int) $pdo->query(
            "SELECT COUNT(*) FROM reserva_espacio_dias
              WHERE activa = 1 AND fecha BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)"
        )->fetchColumn();
        $espaciosActivos = max(1, (int) $pdo->query("SELECT COUNT(*) FROM espacios WHERE estado = 'activo'")->fetchColumn());
        $kpis['ocupacion'] = min(100, round(($diasOcupados / ($espaciosActivos * 31)) * 100, 1));

        $proximos = $pdo->query(
            "SELECT e.*, c.nombre AS cliente, t.nombre AS tipo, t.icono,
                    (SELECT esp.nombre FROM reservas_espacio re
                       JOIN espacios esp ON esp.id_espacio = re.id_espacio
                      WHERE re.id_evento = e.id_evento AND re.estado = 'confirmada'
                      ORDER BY re.id_reserva_espacio LIMIT 1) AS espacio,
                    (SELECT COUNT(*) FROM invitados i
                      WHERE i.id_evento = e.id_evento AND i.estado_rsvp = 'confirmado') AS confirmados,
                    (SELECT COALESCE(SUM(v.saldo), 0) FROM v_cotizacion_saldo v
                      WHERE v.id_evento = e.id_evento AND v.estado = 'aceptada') AS saldo
               FROM eventos e
               JOIN clientes c ON c.id_cliente = e.id_cliente
               JOIN tipos_evento t ON t.id_tipo_evento = e.id_tipo_evento
              WHERE e.fecha_inicio >= CURDATE() AND e.estado NOT IN ('cancelado','cerrado')
              ORDER BY e.fecha_inicio LIMIT 6"
        )->fetchAll();

        $pipeline = [];
        foreach ($pdo->query('SELECT estado, COUNT(*) total FROM eventos GROUP BY estado')->fetchAll() as $fila) {
            $pipeline[$fila['estado']] = (int) $fila['total'];
        }

        $cobranza = $pdo->query(
            "SELECT v.*, e.nombre AS evento, e.fecha_inicio, c.nombre AS cliente
               FROM v_cotizacion_saldo v
               JOIN eventos e ON e.id_evento = v.id_evento
               JOIN clientes c ON c.id_cliente = e.id_cliente
              WHERE v.estado = 'aceptada' AND v.saldo > 0.01
              ORDER BY e.fecha_inicio LIMIT 5"
        )->fetchAll();

        $alertas = [];

        foreach ($pdo->query("SELECT nombre, disponible, unidad FROM v_disponibilidad_insumo WHERE bajo_minimo = 1 ORDER BY disponible LIMIT 4")->fetchAll() as $fila) {
            $alertas[] = [
                'tono' => 'error',
                'icono'=> 'insumos',
                'titulo' => 'Stock bajo: ' . $fila['nombre'],
                'detalle' => 'Disponible ' . number_format((float) $fila['disponible'], 2) . ' ' . $fila['unidad'] . '. Conviene reponer.',
                'url' => '/insumos',
            ];
        }

        foreach ($pdo->query("SELECT id_cotizacion, folio, vigencia_hasta FROM cotizaciones WHERE estado = 'enviada' AND vigencia_hasta < CURDATE() ORDER BY vigencia_hasta LIMIT 3")->fetchAll() as $fila) {
            $alertas[] = [
                'tono' => 'aviso',
                'icono'=> 'cotizacion',
                'titulo' => 'Cotización vencida ' . $fila['folio'],
                'detalle' => 'Venció el ' . fecha($fila['vigencia_hasta']) . ' y sigue sin respuesta.',
                'url' => '/cotizaciones/' . (int) $fila['id_cotizacion'],
            ];
        }

        $sinMenu = $pdo->query(
            "SELECT e.id_evento, e.nombre, e.fecha_inicio
               FROM eventos e
               JOIN tipos_evento t ON t.id_tipo_evento = e.id_tipo_evento
              WHERE t.requiere_menu = 1 AND e.estado IN ('confirmado','en_preparacion')
                AND e.fecha_inicio > NOW()
                AND NOT EXISTS (SELECT 1 FROM evento_menus em WHERE em.id_evento = e.id_evento)
              ORDER BY e.fecha_inicio LIMIT 3"
        )->fetchAll();
        foreach ($sinMenu as $fila) {
            $alertas[] = [
                'tono' => 'aviso',
                'icono'=> 'cubiertos',
                'titulo' => 'Menú pendiente: ' . $fila['nombre'],
                'detalle' => 'El evento es el ' . fecha($fila['fecha_inicio']) . ' y aún no tiene menú contratado.',
                'url' => '/eventos/' . (int) $fila['id_evento'],
            ];
        }

        $sinEspacio = $pdo->query(
            "SELECT e.id_evento, e.nombre, e.fecha_inicio
               FROM eventos e
              WHERE e.estado IN ('confirmado','en_preparacion') AND e.fecha_inicio > NOW()
                AND NOT EXISTS (SELECT 1 FROM reservas_espacio re WHERE re.id_evento = e.id_evento AND re.estado = 'confirmada')
              ORDER BY e.fecha_inicio LIMIT 3"
        )->fetchAll();
        foreach ($sinEspacio as $fila) {
            $alertas[] = [
                'tono' => 'error',
                'icono'=> 'espacios',
                'titulo' => 'Sin espacio: ' . $fila['nombre'],
                'detalle' => 'Falta bloquear un espacio para el ' . fecha($fila['fecha_inicio']) . '.',
                'url' => '/reservas/crear',
            ];
        }

        $actividad = $pdo->query(
            "SELECT b.*, u.nombre AS usuario
               FROM bitacora b LEFT JOIN usuarios u ON u.id_usuario = b.id_usuario
              ORDER BY b.fecha DESC, b.id_bitacora DESC LIMIT 8"
        )->fetchAll();

        $meses = [];
        $etiquetas = ['01'=>'Ene','02'=>'Feb','03'=>'Mar','04'=>'Abr','05'=>'May','06'=>'Jun','07'=>'Jul','08'=>'Ago','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dic'];
        for ($i = 0; $i < 6; $i++) {
            $clave = date('Y-m', strtotime('first day of +' . $i . ' month'));
            $meses[$clave] = ['etiqueta' => $etiquetas[date('m', strtotime($clave . '-01'))] . ' ' . date('y', strtotime($clave . '-01')), 'total' => 0];
        }
        $stmt = $pdo->query(
            "SELECT DATE_FORMAT(fecha_inicio, '%Y-%m') periodo, COUNT(*) total
               FROM eventos
              WHERE fecha_inicio >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND estado <> 'cancelado'
              GROUP BY periodo"
        );
        foreach ($stmt->fetchAll() as $fila) {
            if (isset($meses[$fila['periodo']])) {
                $meses[$fila['periodo']]['total'] = (int) $fila['total'];
            }
        }
        $maxMes = max(1, max(array_column($meses, 'total')));

        $tipos = $pdo->query(
            "SELECT t.nombre, COUNT(e.id_evento) total
               FROM tipos_evento t LEFT JOIN eventos e ON e.id_tipo_evento = t.id_tipo_evento
              GROUP BY t.id_tipo_evento, t.nombre HAVING total > 0 ORDER BY total DESC LIMIT 6"
        )->fetchAll();
        $maxTipo = max(1, (int) ($tipos[0]['total'] ?? 1));

        View::render('panel/index', [
            'titulo'    => 'Panel de operación',
            'subtitulo' => 'Todo el recinto en una sola vista: eventos, recursos, cobranza y alertas.',
            'kpis'      => $kpis,
            'proximos'  => $proximos,
            'pipeline'  => $pipeline,
            'cobranza'  => $cobranza,
            'alertas'   => $alertas,
            'actividad' => $actividad,
            'meses'     => $meses,
            'maxMes'    => $maxMes,
            'tipos'     => $tipos,
            'maxTipo'   => $maxTipo,
            'hoy'       => $hoy,
        ], 'app');
    }

    public function buscar(): void
    {
        $q = $this->termino();
        $resultados = ['clientes' => [], 'eventos' => [], 'cotizaciones' => [], 'invitados' => []];

        // La búsqueda global no puede ser una puerta trasera: cada grupo de
        // resultados se consulta solo si el rol puede abrir ese módulo. Sin
        // esto, un rol con /clientes y /cotizaciones en 403 los vería aquí.
        $veClientes = puede(roles_grupo('clientes'));
        $veDinero = puede(roles_grupo('dinero'));

        if (mb_strlen($q) >= 2) {
            $pdo = Database::pdo();
            $like = '%' . $q . '%';

            if ($veClientes) {
                $stmt = $pdo->prepare(
                    "SELECT id_cliente, nombre, correo, tipo_cliente FROM clientes
                      WHERE nombre LIKE ? OR correo LIKE ? OR COALESCE(razon_social,'') LIKE ?
                      ORDER BY nombre LIMIT 8"
                );
                $stmt->execute([$like, $like, $like]);
                $resultados['clientes'] = $stmt->fetchAll();
            }

            $stmt = $pdo->prepare(
                "SELECT e.id_evento, e.nombre, e.fecha_inicio, e.estado, c.nombre AS cliente
                   FROM eventos e JOIN clientes c ON c.id_cliente = e.id_cliente
                  WHERE e.nombre LIKE ? OR c.nombre LIKE ? OR CAST(e.id_evento AS CHAR) = ?
                  ORDER BY e.fecha_inicio DESC LIMIT 8"
            );
            $stmt->execute([$like, $like, $q]);
            $resultados['eventos'] = $stmt->fetchAll();

            if ($veDinero) {
                $stmt = $pdo->prepare(
                    "SELECT co.id_cotizacion, co.folio, co.total, co.estado, e.nombre AS evento
                       FROM cotizaciones co JOIN eventos e ON e.id_evento = co.id_evento
                      WHERE co.folio LIKE ? OR e.nombre LIKE ?
                      ORDER BY co.creado_en DESC LIMIT 8"
                );
                $stmt->execute([$like, $like]);
                $resultados['cotizaciones'] = $stmt->fetchAll();
            }

            $stmt = $pdo->prepare(
                "SELECT i.id_invitado, i.nombre, i.correo, i.estado_rsvp, e.nombre AS evento, e.id_evento
                   FROM invitados i JOIN eventos e ON e.id_evento = i.id_evento
                  WHERE i.nombre LIKE ? OR i.correo LIKE ?
                  ORDER BY i.nombre LIMIT 8"
            );
            $stmt->execute([$like, $like]);
            $resultados['invitados'] = $stmt->fetchAll();
        }

        View::render('panel/buscar', [
            'titulo'     => 'Búsqueda',
            'subtitulo'  => $q !== '' ? 'Resultados para “' . $q . '”' : 'Escribe al menos dos caracteres.',
            'q'          => $q,
            'resultados' => $resultados,
        ], 'app');
    }
}
