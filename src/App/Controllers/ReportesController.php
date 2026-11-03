<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;

/**
 * Módulo D · reportes operativos, financieros y de auditoría.
 */
final class ReportesController extends Controller
{
    public function index(): void
    {
        $pdo = Database::pdo();

        $kpis = [
            'eventos'    => (int) $pdo->query('SELECT COUNT(*) FROM eventos')->fetchColumn(),
            'activos'    => (int) $pdo->query("SELECT COUNT(*) FROM eventos WHERE estado NOT IN ('cancelado','cerrado')")->fetchColumn(),
            'ingresos'   => (float) $pdo->query(
                "SELECT COALESCE(SUM(monto),0) FROM pagos
                  WHERE estado <> 'revertido' AND tipo <> 'devolucion'
                    AND YEAR(fecha_pago) = YEAR(CURDATE())"
            )->fetchColumn(),
            'por_cobrar' => (float) $pdo->query(
                "SELECT COALESCE(SUM(saldo),0) FROM v_cotizacion_saldo WHERE estado = 'aceptada'"
            )->fetchColumn(),
            'ticket'     => (float) $pdo->query(
                "SELECT COALESCE(AVG(total),0) FROM cotizaciones WHERE estado = 'aceptada'"
            )->fetchColumn(),
            'cotizaciones' => (int) $pdo->query('SELECT COUNT(*) FROM cotizaciones')->fetchColumn(),
            'aceptadas'  => (int) $pdo->query("SELECT COUNT(*) FROM cotizaciones WHERE estado = 'aceptada'")->fetchColumn(),
            'leads'      => (int) $pdo->query("SELECT COUNT(*) FROM leads WHERE estado = 'nuevo'")->fetchColumn(),
        ];
        $kpis['conversion'] = porcentaje($kpis['aceptadas'], max(1, $kpis['cotizaciones']));

        $meses = [];
        $etiquetas = ['01'=>'Ene','02'=>'Feb','03'=>'Mar','04'=>'Abr','05'=>'May','06'=>'Jun','07'=>'Jul','08'=>'Ago','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dic'];
        for ($i = 5; $i >= 0; $i--) {
            $clave = date('Y-m', strtotime('first day of -' . $i . ' month'));
            $meses[$clave] = [
                'etiqueta' => $etiquetas[date('m', strtotime($clave . '-01'))] . ' ' . date('y', strtotime($clave . '-01')),
                'ingresos' => 0.0,
                'eventos'  => 0,
            ];
        }
        foreach ($pdo->query(
            "SELECT DATE_FORMAT(fecha_pago, '%Y-%m') periodo, SUM(monto) total
               FROM pagos WHERE estado <> 'revertido' AND tipo <> 'devolucion'
                AND fecha_pago >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
              GROUP BY periodo"
        )->fetchAll() as $fila) {
            if (isset($meses[$fila['periodo']])) {
                $meses[$fila['periodo']]['ingresos'] = (float) $fila['total'];
            }
        }
        foreach ($pdo->query(
            "SELECT DATE_FORMAT(fecha_inicio, '%Y-%m') periodo, COUNT(*) total
               FROM eventos WHERE fecha_inicio >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
              GROUP BY periodo"
        )->fetchAll() as $fila) {
            if (isset($meses[$fila['periodo']])) {
                $meses[$fila['periodo']]['eventos'] = (int) $fila['total'];
            }
        }
        $maxIngreso = max(1.0, max(array_column($meses, 'ingresos')));

        $estados = [];
        foreach ($pdo->query('SELECT estado, COUNT(*) total FROM eventos GROUP BY estado')->fetchAll() as $fila) {
            $estados[$fila['estado']] = (int) $fila['total'];
        }

        $clientes = $pdo->query(
            "SELECT cl.nombre, cl.tipo_cliente, COUNT(DISTINCT e.id_evento) eventos,
                    COALESCE(SUM(CASE WHEN p.tipo = 'devolucion' THEN -p.monto ELSE p.monto END), 0) pagado
               FROM clientes cl
               LEFT JOIN eventos e ON e.id_cliente = cl.id_cliente
               LEFT JOIN cotizaciones c ON c.id_evento = e.id_evento
               LEFT JOIN pagos p ON p.id_cotizacion = c.id_cotizacion AND p.estado <> 'revertido'
              GROUP BY cl.id_cliente, cl.nombre, cl.tipo_cliente
             HAVING pagado > 0
              ORDER BY pagado DESC LIMIT 6"
        )->fetchAll();
        $maxCliente = max(1.0, (float) ($clientes[0]['pagado'] ?? 1));

        $tipos = $pdo->query(
            "SELECT t.nombre, COUNT(e.id_evento) total
               FROM tipos_evento t LEFT JOIN eventos e ON e.id_tipo_evento = t.id_tipo_evento
              GROUP BY t.id_tipo_evento, t.nombre HAVING total > 0 ORDER BY total DESC"
        )->fetchAll();
        $maxTipo = max(1, (int) ($tipos[0]['total'] ?? 1));

        $pagos = $pdo->query(
            "SELECT p.*, c.folio, e.nombre AS evento, cl.nombre AS cliente
               FROM pagos p
               JOIN cotizaciones c ON c.id_cotizacion = p.id_cotizacion
               JOIN eventos e ON e.id_evento = c.id_evento
               JOIN clientes cl ON cl.id_cliente = e.id_cliente
              WHERE p.estado <> 'revertido'
              ORDER BY p.fecha_pago DESC LIMIT 8"
        )->fetchAll();

        View::render('reportes/index', [
            'titulo'    => 'Reportes',
            'subtitulo' => 'Lectura ejecutiva del recinto: ingresos, cartera, conversión y ocupación.',
            'kpis'      => $kpis,
            'meses'     => $meses,
            'maxIngreso'=> $maxIngreso,
            'estados'   => $estados,
            'clientes'  => $clientes,
            'maxCliente'=> $maxCliente,
            'tipos'     => $tipos,
            'maxTipo'   => $maxTipo,
            'pagos'     => $pagos,
        ], 'app');
    }

    public function ocupacion(): void
    {
        $pdo = Database::pdo();
        $mes = (string) ($_GET['mes'] ?? date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
            $mes = date('Y-m');
        }
        $inicio = new \DateTimeImmutable($mes . '-01');
        $fin = $inicio->modify('last day of this month');
        $diasMes = (int) $fin->format('j');

        $stmt = $pdo->prepare(
            "SELECT e.*,
                    (SELECT COUNT(DISTINCT d.fecha) FROM reserva_espacio_dias d
                      WHERE d.id_espacio = e.id_espacio AND d.activa = 1
                        AND d.fecha BETWEEN ? AND ?) AS dias_ocupados,
                    (SELECT COUNT(*) FROM reservas_espacio re
                      WHERE re.id_espacio = e.id_espacio AND re.estado = 'confirmada'
                        AND re.fecha_inicio <= ? AND re.fecha_fin >= ?) AS reservas,
                    (SELECT COALESCE(SUM(esp2.costo_referencia), 0) FROM reservas_espacio re2
                       JOIN espacios esp2 ON esp2.id_espacio = re2.id_espacio
                      WHERE re2.id_espacio = e.id_espacio AND re2.estado = 'confirmada'
                        AND re2.fecha_inicio <= ? AND re2.fecha_fin >= ?) AS ingresos
               FROM espacios e
              ORDER BY dias_ocupados DESC, e.capacidad DESC"
        );
        $stmt->execute([
            $inicio->format('Y-m-d'), $fin->format('Y-m-d'),
            $fin->format('Y-m-d 23:59:59'), $inicio->format('Y-m-d 00:00:00'),
            $fin->format('Y-m-d 23:59:59'), $inicio->format('Y-m-d 00:00:00'),
        ]);
        $espacios = $stmt->fetchAll();

        foreach ($espacios as &$espacio) {
            $espacio['ocupacion'] = $diasMes > 0 ? round(((int) $espacio['dias_ocupados'] / $diasMes) * 100, 1) : 0.0;
        }
        unset($espacio);

        $stmt = $pdo->prepare(
            "SELECT re.*, e.nombre AS evento, e.id_evento, e.estado AS estado_evento,
                    esp.nombre AS espacio, c.nombre AS cliente
               FROM reservas_espacio re
               JOIN eventos e ON e.id_evento = re.id_evento
               JOIN clientes c ON c.id_cliente = e.id_cliente
               JOIN espacios esp ON esp.id_espacio = re.id_espacio
              WHERE re.estado = 'confirmada'
                AND re.fecha_inicio <= ? AND re.fecha_fin >= ?
              ORDER BY re.fecha_inicio"
        );
        $stmt->execute([$fin->format('Y-m-d 23:59:59'), $inicio->format('Y-m-d 00:00:00')]);
        $reservas = $stmt->fetchAll();

        $diasOcupados = array_sum(array_column($espacios, 'dias_ocupados'));
        $capacidadDias = max(1, count($espacios) * $diasMes);

        View::render('reportes/ocupacion', [
            'titulo'    => 'Ocupación de espacios',
            'subtitulo' => 'Días bloqueados por salón, ingresos comprometidos y eventos del periodo.',
            'migas'     => [['titulo' => 'Reportes', 'url' => '/reportes'], ['titulo' => 'Ocupación']],
            'espacios'  => $espacios,
            'reservas'  => $reservas,
            'etiqueta'  => fecha($inicio->format('Y-m-d'), 'F Y'),
            'anterior'  => $inicio->modify('-1 month')->format('Y-m'),
            'siguiente' => $inicio->modify('+1 month')->format('Y-m'),
            'diasMes'   => $diasMes,
            'global'    => round(($diasOcupados / $capacidadDias) * 100, 1),
            'ingresos'  => array_sum(array_map(static fn (array $e): float => (float) $e['ingresos'], $espacios)),
        ], 'app');
    }

    public function produccion(): void
    {
        $pdo = Database::pdo();
        $dias = (int) ($_GET['dias'] ?? 120);
        if (!in_array($dias, [30, 60, 90, 120, 180], true)) {
            $dias = 120;
        }

        $stmt = $pdo->prepare(
            "SELECT e.*, c.nombre AS cliente, t.nombre AS tipo, t.requiere_menu,
                    (SELECT COUNT(*) FROM evento_menus em WHERE em.id_evento = e.id_evento) AS menus,
                    (SELECT COALESCE(SUM(em.personas), 0) FROM evento_menus em WHERE em.id_evento = e.id_evento) AS personas,
                    (SELECT COUNT(*) FROM invitados i WHERE i.id_evento = e.id_evento AND i.estado_rsvp = 'confirmado') AS confirmados,
                    (SELECT COUNT(*) FROM invitados i WHERE i.id_evento = e.id_evento
                      AND i.estado_rsvp = 'confirmado' AND i.restriccion_alimenticia <> 'ninguna') AS restricciones,
                    (SELECT COUNT(*) FROM reservas_insumo ri WHERE ri.id_evento = e.id_evento AND ri.estado = 'confirmada') AS insumos,
                    (SELECT COUNT(*) FROM reservas_espacio re WHERE re.id_evento = e.id_evento AND re.estado = 'confirmada') AS espacios
               FROM eventos e
               JOIN clientes c ON c.id_cliente = e.id_cliente
               JOIN tipos_evento t ON t.id_tipo_evento = e.id_tipo_evento
              WHERE e.fecha_inicio BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ? DAY)
                AND e.estado NOT IN ('cancelado','cerrado')
              ORDER BY e.fecha_inicio"
        );
        $stmt->bindValue(1, $dias, \PDO::PARAM_INT);
        $stmt->execute();
        $eventos = $stmt->fetchAll();

        $totales = [
            'eventos'      => count($eventos),
            'personas'     => array_sum(array_map(static fn (array $e): int => (int) $e['personas'], $eventos)),
            'confirmados'  => array_sum(array_map(static fn (array $e): int => (int) $e['confirmados'], $eventos)),
            'restricciones'=> array_sum(array_map(static fn (array $e): int => (int) $e['restricciones'], $eventos)),
            'sin_menu'     => count(array_filter($eventos, static fn (array $e): bool => (int) $e['requiere_menu'] === 1 && (int) $e['menus'] === 0)),
            'sin_espacio'  => count(array_filter($eventos, static fn (array $e): bool => (int) $e['espacios'] === 0)),
        ];

        $restricciones = $pdo->query(
            "SELECT i.restriccion_alimenticia, COUNT(*) total
               FROM invitados i JOIN eventos e ON e.id_evento = i.id_evento
              WHERE i.estado_rsvp = 'confirmado' AND e.fecha_inicio >= NOW()
                AND e.estado NOT IN ('cancelado','cerrado')
              GROUP BY i.restriccion_alimenticia ORDER BY total DESC"
        )->fetchAll();

        View::render('reportes/produccion', [
            'titulo'       => 'Producción próxima',
            'subtitulo'    => 'Menús, personas, restricciones e insumos de los eventos que vienen.',
            'migas'        => [['titulo' => 'Reportes', 'url' => '/reportes'], ['titulo' => 'Producción']],
            'eventos'      => $eventos,
            'totales'      => $totales,
            'restricciones'=> $restricciones,
            'dias'         => $dias,
        ], 'app');
    }

    public function auditoria(): void
    {
        $pdo = Database::pdo();
        $dias = (int) ($_GET['dias'] ?? 30);
        if (!in_array($dias, [7, 30, 90, 365], true)) {
            $dias = 30;
        }

        $stmt = $pdo->prepare(
            'SELECT accion, COUNT(*) total, MAX(fecha) ultima
               FROM bitacora WHERE fecha >= DATE_SUB(NOW(), INTERVAL ? DAY)
              GROUP BY accion ORDER BY total DESC LIMIT 18'
        );
        $stmt->bindValue(1, $dias, \PDO::PARAM_INT);
        $stmt->execute();
        $acciones = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            'SELECT u.nombre, r.nombre AS rol, COUNT(b.id_bitacora) total, MAX(b.fecha) ultima
               FROM bitacora b
               JOIN usuarios u ON u.id_usuario = b.id_usuario
               JOIN roles r ON r.id_rol = u.id_rol
              WHERE b.fecha >= DATE_SUB(NOW(), INTERVAL ? DAY)
              GROUP BY u.id_usuario, u.nombre, r.nombre ORDER BY total DESC'
        );
        $stmt->bindValue(1, $dias, \PDO::PARAM_INT);
        $stmt->execute();
        $usuarios = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            'SELECT h.*, u.nombre AS usuario FROM historial_cambios h
               LEFT JOIN usuarios u ON u.id_usuario = h.id_usuario
              WHERE h.fecha_cambio >= DATE_SUB(NOW(), INTERVAL ? DAY)
              ORDER BY h.fecha_cambio DESC, h.id_historial DESC LIMIT 30'
        );
        $stmt->bindValue(1, $dias, \PDO::PARAM_INT);
        $stmt->execute();
        $cambios = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            'SELECT hp.*, u.nombre AS usuario FROM historial_precios hp
               LEFT JOIN usuarios u ON u.id_usuario = hp.id_usuario
              WHERE hp.fecha >= DATE_SUB(NOW(), INTERVAL ? DAY)
              ORDER BY hp.fecha DESC LIMIT 15'
        );
        $stmt->bindValue(1, $dias, \PDO::PARAM_INT);
        $stmt->execute();
        $precios = $stmt->fetchAll();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM bitacora WHERE fecha >= DATE_SUB(NOW(), INTERVAL ? DAY)');
        $stmt->bindValue(1, $dias, \PDO::PARAM_INT);
        $stmt->execute();
        $total = (int) $stmt->fetchColumn();

        View::render('reportes/auditoria', [
            'titulo'   => 'Auditoría de operación',
            'subtitulo'=> 'Quién hizo qué, cuándo y sobre qué registro. La bitácora no se elimina.',
            'migas'    => [['titulo' => 'Reportes', 'url' => '/reportes'], ['titulo' => 'Auditoría']],
            'acciones' => $acciones,
            'usuarios' => $usuarios,
            'cambios'  => $cambios,
            'precios'  => $precios,
            'dias'     => $dias,
            'total'    => $total,
        ], 'app');
    }

    public function inconsistencias(): void
    {
        $pdo = Database::pdo();
        $hallazgos = [];

        $consultas = [
            [
                'titulo' => 'Eventos confirmados sin cotización aceptada',
                'detalle'=> 'El flujo exige cotización aceptada antes de confirmar. Revisa estos casos.',
                'tono'   => 'error',
                'sql'    => "SELECT e.id_evento, e.nombre, e.fecha_inicio, e.estado
                               FROM eventos e
                              WHERE e.estado IN ('confirmado','en_preparacion','en_curso')
                                AND NOT EXISTS (SELECT 1 FROM cotizaciones c WHERE c.id_evento = e.id_evento AND c.estado = 'aceptada')
                              ORDER BY e.fecha_inicio",
                'url'    => '/eventos/',
            ],
            [
                'titulo' => 'Eventos confirmados sin espacio bloqueado',
                'detalle'=> 'Sin reserva de espacio no hay calendario ni control de aforo.',
                'tono'   => 'error',
                'sql'    => "SELECT e.id_evento, e.nombre, e.fecha_inicio, e.estado
                               FROM eventos e
                              WHERE e.estado IN ('confirmado','en_preparacion','en_curso')
                                AND NOT EXISTS (SELECT 1 FROM reservas_espacio re WHERE re.id_evento = e.id_evento AND re.estado = 'confirmada')
                              ORDER BY e.fecha_inicio",
                'url'    => '/reservas/crear?evento=',
            ],
            [
                'titulo' => 'Eventos que requieren menú y no lo tienen',
                'detalle'=> 'El tipo de evento exige banquete; cocina necesita la hoja de producción.',
                'tono'   => 'aviso',
                'sql'    => "SELECT e.id_evento, e.nombre, e.fecha_inicio, t.nombre AS tipo
                               FROM eventos e JOIN tipos_evento t ON t.id_tipo_evento = e.id_tipo_evento
                              WHERE t.requiere_menu = 1 AND e.estado IN ('confirmado','en_preparacion')
                                AND NOT EXISTS (SELECT 1 FROM evento_menus em WHERE em.id_evento = e.id_evento)
                              ORDER BY e.fecha_inicio",
                'url'    => '/eventos/',
            ],
            [
                'titulo' => 'Invitados confirmados por encima del aforo',
                'detalle'=> 'El RSVP y el panel validan aforo, pero una importación o cambio manual pudo excederlo.',
                'tono'   => 'error',
                'sql'    => "SELECT e.id_evento, e.nombre, e.aforo_maximo, COUNT(i.id_invitado) confirmados
                               FROM eventos e JOIN invitados i ON i.id_evento = e.id_evento AND i.estado_rsvp = 'confirmado'
                              GROUP BY e.id_evento, e.nombre, e.aforo_maximo
                             HAVING confirmados > e.aforo_maximo",
                'url'    => '/eventos/',
            ],
            [
                'titulo' => 'Mesas por encima de su capacidad',
                'detalle'=> 'La asignación valida capacidad; revisa ajustes hechos fuera del flujo.',
                'tono'   => 'aviso',
                'sql'    => "SELECT m.id_evento, m.numero, m.capacidad, COUNT(i.id_invitado) ocupados
                               FROM mesas m JOIN invitados i ON i.id_mesa = m.id_mesa
                              GROUP BY m.id_mesa, m.id_evento, m.numero, m.capacidad
                             HAVING ocupados > m.capacidad",
                'url'    => '/eventos/',
            ],
            [
                'titulo' => 'Reservas fuera de la ventana del evento',
                'detalle'=> 'Las reservas deben caer dentro de las fechas del evento; si no, el calendario miente.',
                'tono'   => 'aviso',
                'sql'    => "SELECT re.id_reserva_espacio, e.id_evento, e.nombre, re.fecha_inicio, re.fecha_fin, e.fecha_inicio AS evento_inicio
                               FROM reservas_espacio re JOIN eventos e ON e.id_evento = re.id_evento
                              WHERE re.estado = 'confirmada'
                                AND (re.fecha_inicio < e.fecha_inicio OR re.fecha_fin > e.fecha_fin)",
                'url'    => '/reservas',
            ],
            [
                'titulo' => 'Pagos que exceden el total cotizado',
                'detalle'=> 'El registro de pagos valida el saldo; verifica devoluciones y ajustes manuales.',
                'tono'   => 'error',
                'sql'    => "SELECT c.id_cotizacion, c.folio, c.total,
                                    COALESCE(SUM(CASE WHEN p.tipo = 'devolucion' THEN -p.monto ELSE p.monto END), 0) pagado
                               FROM cotizaciones c JOIN pagos p ON p.id_cotizacion = c.id_cotizacion AND p.estado <> 'revertido'
                              GROUP BY c.id_cotizacion, c.folio, c.total
                             HAVING pagado > c.total + 0.01",
                'url'    => '/cotizaciones/',
            ],
            [
                'titulo' => 'Eventos ya concluidos que siguen abiertos',
                'detalle'=> 'Si la fecha terminó, el evento debería pasar a realizado o cerrarse con finanzas.',
                'tono'   => 'aviso',
                'sql'    => "SELECT id_evento, nombre, fecha_fin, estado
                               FROM eventos
                              WHERE fecha_fin < NOW() AND estado IN ('confirmado','en_preparacion','en_curso')
                              ORDER BY fecha_fin",
                'url'    => '/eventos/',
            ],
            [
                'titulo' => 'Insumos con stock comprometido inconsistente',
                'detalle'=> 'El CHECK de la base impide comprometer más de lo físico; esto detecta datos migrados.',
                'tono'   => 'error',
                'sql'    => "SELECT id_insumo, nombre, stock_fisico, stock_comprometido
                               FROM insumos_menu WHERE stock_comprometido > stock_fisico",
                'url'    => '/insumos',
            ],
            [
                'titulo' => 'Cotizaciones aceptadas sin ningún pago',
                'detalle'=> 'Un evento confirmado debería tener al menos el anticipo registrado.',
                'tono'   => 'aviso',
                'sql'    => "SELECT c.id_cotizacion, c.folio, c.total, e.nombre AS evento
                               FROM cotizaciones c JOIN eventos e ON e.id_evento = c.id_evento
                              WHERE c.estado = 'aceptada'
                                AND NOT EXISTS (SELECT 1 FROM pagos p WHERE p.id_cotizacion = c.id_cotizacion AND p.estado <> 'revertido' AND p.tipo <> 'devolucion')
                              ORDER BY e.fecha_inicio",
                'url'    => '/cotizaciones/',
            ],
        ];

        foreach ($consultas as $consulta) {
            $filas = $pdo->query($consulta['sql'])->fetchAll();
            $hallazgos[] = $consulta + ['filas' => $filas, 'total' => count($filas)];
        }

        $criticos = count(array_filter($hallazgos, static fn (array $h): bool => $h['total'] > 0 && $h['tono'] === 'error'));

        View::render('reportes/inconsistencias', [
            'titulo'    => 'Inconsistencias',
            'subtitulo' => 'Controles cruzados entre módulos. Cada hallazgo enlaza al registro para corregirlo.',
            'migas'     => [['titulo' => 'Reportes', 'url' => '/reportes'], ['titulo' => 'Inconsistencias']],
            'hallazgos' => $hallazgos,
            'criticos'  => $criticos,
            'total'     => array_sum(array_column($hallazgos, 'total')),
        ], 'app');
    }

    public function exportar(): void
    {
        $tipo = (string) ($_GET['tipo'] ?? 'eventos');
        $pdo = Database::pdo();

        $archivos = [
            'eventos' => [
                'nombre'   => 'eventos',
                'columnas' => ['ID', 'Evento', 'Cliente', 'Tipo', 'Inicio', 'Fin', 'Aforo', 'Estado', 'Cotizado', 'Pagado'],
                'sql'      => "SELECT e.id_evento, e.nombre, c.nombre AS cliente, t.nombre AS tipo,
                                      e.fecha_inicio, e.fecha_fin, e.aforo_maximo, e.estado,
                                      COALESCE(v.total, 0) AS cotizado, COALESCE(v.pagado, 0) AS pagado
                                 FROM eventos e
                                 JOIN clientes c ON c.id_cliente = e.id_cliente
                                 JOIN tipos_evento t ON t.id_tipo_evento = e.id_tipo_evento
                                 LEFT JOIN v_cotizacion_saldo v ON v.id_evento = e.id_evento AND v.estado = 'aceptada'
                                ORDER BY e.fecha_inicio",
            ],
            'pagos' => [
                'nombre'   => 'pagos',
                'columnas' => ['ID', 'Fecha', 'Folio', 'Evento', 'Cliente', 'Tipo', 'Método', 'Monto', 'Referencia', 'Estado'],
                'sql'      => "SELECT p.id_pago, p.fecha_pago, c.folio, e.nombre AS evento, cl.nombre AS cliente,
                                      p.tipo, p.metodo, p.monto, p.referencia, p.estado
                                 FROM pagos p
                                 JOIN cotizaciones c ON c.id_cotizacion = p.id_cotizacion
                                 JOIN eventos e ON e.id_evento = c.id_evento
                                 JOIN clientes cl ON cl.id_cliente = e.id_cliente
                                ORDER BY p.fecha_pago",
            ],
            'cotizaciones' => [
                'nombre'   => 'cotizaciones',
                'columnas' => ['ID', 'Folio', 'Versión', 'Evento', 'Cliente', 'Subtotal', 'Descuento', 'Impuestos', 'Total', 'Anticipo', 'Estado', 'Vigencia'],
                'sql'      => "SELECT c.id_cotizacion, c.folio, c.version, e.nombre AS evento, cl.nombre AS cliente,
                                      c.subtotal, c.descuento, c.impuestos, c.total, c.anticipo_requerido, c.estado, c.vigencia_hasta
                                 FROM cotizaciones c
                                 JOIN eventos e ON e.id_evento = c.id_evento
                                 JOIN clientes cl ON cl.id_cliente = e.id_cliente
                                ORDER BY c.creado_en",
            ],
            'inventario' => [
                'nombre'   => 'inventario',
                'columnas' => ['ID', 'Insumo', 'Categoría', 'Unidad', 'Físico', 'Comprometido', 'Disponible', 'Mínimo', 'Bajo mínimo'],
                'sql'      => "SELECT id_insumo, nombre, categoria, unidad, stock_fisico, stock_comprometido,
                                      stock_fisico - stock_comprometido AS disponible, stock_minimo,
                                      CASE WHEN (stock_fisico - stock_comprometido) <= stock_minimo THEN 'Sí' ELSE 'No' END AS bajo
                                 FROM insumos_menu ORDER BY categoria, nombre",
            ],
        ];

        if (!isset($archivos[$tipo])) {
            $tipo = 'eventos';
        }
        $definicion = $archivos[$tipo];
        $filas = $pdo->query($definicion['sql'])->fetchAll();

        $nombre = 'conectaeventos-' . $definicion['nombre'] . '-' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $salida = fopen('php://output', 'wb');
        fwrite($salida, "\xEF\xBB\xBF");
        fputcsv($salida, $definicion['columnas'], ';');
        foreach ($filas as $fila) {
            fputcsv($salida, array_map([$this, 'celdaCsv'], array_values($fila)), ';');
        }
        fclose($salida);

        registrar_bitacora('EXPORTAR_REPORTE', 'reportes', null, 'CSV ' . $tipo . ' · ' . count($filas) . ' filas');
        exit;
    }

    /**
     * Neutraliza la inyección de fórmulas al abrir el CSV en Excel/LibreOffice.
     * Los valores numéricos se conservan tal cual.
     */
    private function celdaCsv(mixed $valor): string
    {
        if (is_int($valor) || is_float($valor)) {
            return (string) $valor;
        }
        $texto = (string) $valor;
        if ($texto === '' || is_numeric($texto)) {
            return $texto;
        }
        if (preg_match('/^[=+\-@\t\r]/', $texto) === 1) {
            return "'" . $texto;
        }
        return $texto;
    }
}
