<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use App\Domain\Cotizaciones;

/**
 * Módulo C · cotizaciones versionadas con precio congelado y control de vigencia.
 */
final class CotizacionesController extends Controller
{
    public function index(): void
    {
        $pdo = Database::pdo();
        $q = $this->termino();
        $estado = $this->filtro('estado', ['borrador', 'enviada', 'aceptada', 'rechazada', 'cancelada']);

        $where = [];
        $params = [];
        if ($q !== '') {
            $where[] = '(c.folio LIKE ? OR e.nombre LIKE ? OR cl.nombre LIKE ?)';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }
        if ($estado !== '') {
            $where[] = 'c.estado = ?';
            $params[] = $estado;
        }
        $filtroSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';

        $total = $pdo->prepare(
            'SELECT COUNT(*) FROM cotizaciones c
               JOIN eventos e ON e.id_evento = c.id_evento
               JOIN clientes cl ON cl.id_cliente = e.id_cliente' . $filtroSql
        );
        $total->execute($params);
        $total = (int) $total->fetchColumn();

        $porPagina = 15;
        $sql = "SELECT c.*, e.nombre AS evento, e.fecha_inicio, e.estado AS estado_evento,
                       cl.nombre AS cliente, v.pagado, v.saldo,
                       (SELECT COUNT(*) FROM cotizacion_detalle d WHERE d.id_cotizacion = c.id_cotizacion) AS partidas
                  FROM cotizaciones c
                  JOIN eventos e ON e.id_evento = c.id_evento
                  JOIN clientes cl ON cl.id_cliente = e.id_cliente
                  LEFT JOIN v_cotizacion_saldo v ON v.id_cotizacion = c.id_cotizacion" . $filtroSql . '
                 ORDER BY CASE c.estado WHEN \'aceptada\' THEN 0 WHEN \'enviada\' THEN 1 ELSE 2 END,
                          c.creado_en DESC
                 LIMIT ? OFFSET ?';
        $stmt = $pdo->prepare($sql);
        $i = 1;
        foreach ($params as $param) {
            $stmt->bindValue($i++, $param, \PDO::PARAM_STR);
        }
        $stmt->bindValue($i++, $porPagina, \PDO::PARAM_INT);
        $stmt->bindValue($i, $this->offset($porPagina), \PDO::PARAM_INT);
        $stmt->execute();
        $cotizaciones = $stmt->fetchAll();

        $stats = $pdo->query(
            "SELECT COUNT(*) total,
                    SUM(estado = 'enviada') enviadas,
                    SUM(estado = 'aceptada') aceptadas,
                    SUM(estado = 'cancelada') canceladas,
                    COALESCE(SUM(CASE WHEN estado = 'aceptada' THEN total ELSE 0 END), 0) cartera
               FROM cotizaciones"
        )->fetch();
        $stmt = $pdo->query("SELECT COALESCE(SUM(saldo), 0) FROM v_cotizacion_saldo WHERE estado = 'aceptada'");
        $stats['por_cobrar'] = (float) $stmt->fetchColumn();
        $stmt = $pdo->query(
            "SELECT COUNT(*) FROM cotizaciones WHERE estado = 'enviada' AND vigencia_hasta < CURDATE()"
        );
        $stats['vencidas'] = (int) $stmt->fetchColumn();

        View::render('cotizaciones/index', [
            'titulo'    => 'Cotizaciones',
            'subtitulo' => 'Propuestas con precios congelados al aceptar, anticipo y saldo siempre visibles.',
            'cotizaciones' => $cotizaciones,
            'stats'     => $stats,
            'total'     => $total,
            'q'         => $q,
            'estado'    => $estado,
            'porPagina' => $porPagina,
        ], 'app');
    }

    public function crear(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $evento = $this->evento($id);
        $pdo = Database::pdo();

        $stmt = $pdo->prepare(
            "SELECT re.*, esp.nombre, esp.costo_referencia,
                    DATEDIFF(DATE(re.fecha_fin), DATE(re.fecha_inicio)) + 1 AS dias
               FROM reservas_espacio re JOIN espacios esp ON esp.id_espacio = re.id_espacio
              WHERE re.id_evento = ? AND re.estado = 'confirmada'"
        );
        $stmt->execute([$id]);
        $espacios = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            'SELECT em.*, m.nombre, m.precio_persona, m.tipo_servicio
               FROM evento_menus em JOIN menus m ON m.id_menu = em.id_menu
              WHERE em.id_evento = ?'
        );
        $stmt->execute([$id]);
        $menus = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            'SELECT es.*, s.nombre, s.categoria FROM evento_servicios es
               JOIN servicios s ON s.id_servicio = es.id_servicio
              WHERE es.id_evento = ? ORDER BY s.categoria, s.nombre'
        );
        $stmt->execute([$id]);
        $servicios = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            'SELECT rr.*, r.nombre, r.unidad, r.costo_referencia
               FROM reservas_recurso rr JOIN recursos r ON r.id_recurso = rr.id_recurso
              WHERE rr.id_evento = ? AND rr.estado = \'confirmada\' ORDER BY r.nombre'
        );
        $stmt->execute([$id]);
        $recursos = $stmt->fetchAll();

        $sugerencias = [];
        foreach ($espacios as $e) {
            $sugerencias[] = [
                'descripcion' => 'Renta de ' . $e['nombre'] . ' (' . $e['dias'] . ' día' . ((int) $e['dias'] === 1 ? '' : 's') . ')',
                'precio'      => (float) $e['costo_referencia'] * max(1, (int) $e['dias']),
                'tipo'        => 'espacio',
                'referencia'  => (int) $e['id_espacio'],
            ];
        }
        foreach ($menus as $m) {
            $sugerencias[] = [
                'descripcion' => 'Menú ' . $m['nombre'] . ' · ' . (int) $m['personas'] . ' personas',
                'precio'      => (float) $m['precio_persona'],
                'tipo'        => 'menu',
                'referencia'  => (int) $m['id_menu'],
            ];
        }
        foreach ($servicios as $s) {
            $sugerencias[] = [
                'descripcion' => $s['nombre'],
                'precio'      => (float) $s['precio_unitario'],
                'tipo'        => 'servicio',
                'referencia'  => (int) $s['id_servicio'],
            ];
        }
        foreach ($recursos as $r) {
            $sugerencias[] = [
                'descripcion' => $r['nombre'] . ' × ' . (int) $r['cantidad'] . ' ' . $r['unidad'],
                'precio'      => (float) $r['costo_referencia'],
                'tipo'        => 'recurso',
                'referencia'  => (int) $r['id_recurso'],
            ];
        }

        $stmt = $pdo->prepare(
            'SELECT COALESCE(MAX(version), 0) FROM cotizaciones WHERE id_evento = ?'
        );
        $stmt->execute([$id]);

        View::render('cotizaciones/crear', [
            'titulo'     => 'Nueva cotización · ' . $evento['nombre'],
            'migas'      => [
                ['titulo' => 'Eventos', 'url' => '/eventos'],
                ['titulo' => $evento['nombre'], 'url' => '/eventos/' . $id],
                ['titulo' => 'Cotizar'],
            ],
            'evento'     => $evento,
            'espacios'   => $espacios,
            'menus'      => $menus,
            'servicios'  => $servicios,
            'recursos'   => $recursos,
            'sugerencias'=> $sugerencias,
            'version'    => (int) $stmt->fetchColumn() + 1,
            'iva'        => (float) configuracion('iva_porcentaje', 16),
            'anticipo'   => (float) configuracion('anticipo_porcentaje', 30),
        ], 'app');
    }

    public function guardar(): void
    {
        $idEvento = $this->entero('id_evento');
        $descuento = $this->decimal('descuento');
        $vigencia = $this->fecha('vigencia_hasta');
        $notas = $this->texto('notas');

        $descripciones = (array) ($_POST['descripcion'] ?? []);
        $cantidades = (array) ($_POST['cantidad'] ?? []);
        $precios = (array) ($_POST['precio_unitario'] ?? []);
        $tipos = (array) ($_POST['tipo_concepto'] ?? []);
        $referencias = (array) ($_POST['id_referencia'] ?? []);
        $catalogos = (array) ($_POST['precio_catalogo'] ?? []);

        $lineas = [];
        foreach ($descripciones as $indice => $descripcion) {
            $descripcion = trim((string) $descripcion);
            $cantidad = (float) ($cantidades[$indice] ?? 0);
            $precio = (float) ($precios[$indice] ?? 0);
            if ($descripcion === '' && $cantidad <= 0 && $precio <= 0) {
                continue;
            }
            $tipo = (string) ($tipos[$indice] ?? 'otro');
            if (!in_array($tipo, ['espacio', 'menu', 'servicio', 'recurso', 'otro'], true)) {
                $tipo = 'otro';
            }
            $lineas[] = [
                'tipo_concepto'  => $tipo,
                'id_referencia'  => ((int) ($referencias[$indice] ?? 0)) ?: null,
                'descripcion'    => mb_substr($descripcion, 0, 200),
                'cantidad'       => $cantidad,
                'precio_catalogo'=> (float) ($catalogos[$indice] ?? $precio),
                'precio_unitario'=> $precio,
            ];
        }

        if ($lineas === []) {
            $this->error('Agrega al menos un concepto con descripción, cantidad y precio.', '/eventos/' . $idEvento . '/cotizacion/crear');
        }

        try {
            $idCotizacion = Cotizaciones::crear($idEvento, $lineas, $descuento, $vigencia, $notas);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage(), '/eventos/' . $idEvento . '/cotizacion/crear');
        }

        $this->exito('Cotización generada con precios congelados al momento de aceptarla.', '/cotizaciones/' . $idCotizacion);
    }

    public function ver(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT c.*, e.nombre AS evento, e.fecha_inicio, e.fecha_fin, e.aforo_maximo, e.estado AS estado_evento,
                    e.id_evento, cl.nombre AS cliente, cl.correo AS cliente_correo, cl.telefono AS cliente_telefono,
                    cl.razon_social, cl.tipo_cliente, u.nombre AS usuario
               FROM cotizaciones c
               JOIN eventos e ON e.id_evento = c.id_evento
               JOIN clientes cl ON cl.id_cliente = e.id_cliente
               LEFT JOIN usuarios u ON u.id_usuario = c.id_usuario
              WHERE c.id_cotizacion = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $cotizacion = $stmt->fetch();
        if (!$cotizacion) {
            $this->noEncontrado('La cotización no existe.');
        }

        $stmt = $pdo->prepare('SELECT * FROM cotizacion_detalle WHERE id_cotizacion = ? ORDER BY id_detalle');
        $stmt->execute([$id]);
        $detalle = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            'SELECT p.*, u.nombre AS usuario FROM pagos p
               LEFT JOIN usuarios u ON u.id_usuario = p.id_usuario
              WHERE p.id_cotizacion = ? ORDER BY p.fecha_pago, p.id_pago'
        );
        $stmt->execute([$id]);
        $pagos = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            'SELECT h.*, u.nombre AS usuario FROM historial_cambios h
               LEFT JOIN usuarios u ON u.id_usuario = h.id_usuario
              WHERE h.entidad = \'cotizaciones\' AND h.id_registro = ?
              ORDER BY h.fecha_cambio DESC, h.id_historial DESC LIMIT 30'
        );
        $stmt->execute([$id]);
        $historial = $stmt->fetchAll();

        $saldo = saldo_cotizacion($id);

        View::render('cotizaciones/ver', [
            'titulo'     => 'Cotización ' . $cotizacion['folio'],
            'migas'      => [
                ['titulo' => 'Cotizaciones', 'url' => '/cotizaciones'],
                ['titulo' => $cotizacion['folio']],
            ],
            'cotizacion' => $cotizacion,
            'detalle'    => $detalle,
            'pagos'      => $pagos,
            'historial'  => $historial,
            'saldo'      => $saldo,
            'iva'        => (float) configuracion('iva_porcentaje', 16),
        ], 'app');
    }

    public function aceptar(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $motivo = $this->texto('motivo');

        try {
            Cotizaciones::aceptar($id, $motivo);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage(), '/cotizaciones/' . $id);
        }

        $this->exito('Cotización aceptada: los precios quedaron congelados y ya puedes registrar el anticipo.', '/cotizaciones/' . $id);
    }

    public function cancelar(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $motivo = $this->texto('motivo');

        try {
            Cotizaciones::cancelar($id, $motivo);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage(), '/cotizaciones/' . $id);
        }

        $this->exito('Cotización cancelada. El historial conserva la versión y el motivo.', '/cotizaciones/' . $id);
    }
}
