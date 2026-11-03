<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use App\Domain\Cotizaciones;

/**
 * Módulo C · cobranza: anticipos, abonos, saldos y devoluciones.
 */
final class PagosController extends Controller
{
    public function index(): void
    {
        $pdo = Database::pdo();
        $q = $this->termino();
        $metodo = $this->filtro('metodo', ['efectivo', 'transferencia', 'tarjeta', 'cheque']);
        $desde = $this->fechaGet('desde');
        $hasta = $this->fechaGet('hasta');

        $where = [];
        $params = [];
        if ($q !== '') {
            $where[] = '(c.folio LIKE ? OR e.nombre LIKE ? OR cl.nombre LIKE ? OR COALESCE(p.referencia, \'\') LIKE ?)';
            $like = '%' . $q . '%';
            $params = [$like, $like, $like, $like];
        }
        if ($metodo !== '') {
            $where[] = 'p.metodo = ?';
            $params[] = $metodo;
        }
        if ($desde !== null) {
            $where[] = 'p.fecha_pago >= ?';
            $params[] = $desde . ' 00:00:00';
        }
        if ($hasta !== null) {
            $where[] = 'p.fecha_pago <= ?';
            $params[] = $hasta . ' 23:59:59';
        }
        $filtroSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';

        $total = $pdo->prepare(
            'SELECT COUNT(*) FROM pagos p
               JOIN cotizaciones c ON c.id_cotizacion = p.id_cotizacion
               JOIN eventos e ON e.id_evento = c.id_evento
               JOIN clientes cl ON cl.id_cliente = e.id_cliente' . $filtroSql
        );
        $total->execute($params);
        $total = (int) $total->fetchColumn();

        $porPagina = 20;
        $sql = "SELECT p.*, c.folio, c.total AS total_cotizacion, e.nombre AS evento, e.id_evento,
                       cl.nombre AS cliente, u.nombre AS usuario
                  FROM pagos p
                  JOIN cotizaciones c ON c.id_cotizacion = p.id_cotizacion
                  JOIN eventos e ON e.id_evento = c.id_evento
                  JOIN clientes cl ON cl.id_cliente = e.id_cliente
                  LEFT JOIN usuarios u ON u.id_usuario = p.id_usuario" . $filtroSql . '
                 ORDER BY p.fecha_pago DESC, p.id_pago DESC
                 LIMIT ? OFFSET ?';
        $stmt = $pdo->prepare($sql);
        $i = 1;
        foreach ($params as $param) {
            $stmt->bindValue($i++, $param, \PDO::PARAM_STR);
        }
        $stmt->bindValue($i++, $porPagina, \PDO::PARAM_INT);
        $stmt->bindValue($i, $this->offset($porPagina), \PDO::PARAM_INT);
        $stmt->execute();
        $pagos = $stmt->fetchAll();

        $stats = $pdo->query(
            "SELECT COALESCE(SUM(CASE WHEN tipo = 'devolucion' THEN -monto ELSE monto END), 0) total,
                    COALESCE(SUM(CASE WHEN DATE_FORMAT(fecha_pago, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
                                       AND tipo <> 'devolucion' THEN monto ELSE 0 END), 0) mes,
                    COUNT(*) movimientos
               FROM pagos WHERE estado <> 'revertido'"
        )->fetch();
        $stmt = $pdo->query("SELECT COALESCE(SUM(saldo), 0) FROM v_cotizacion_saldo WHERE estado = 'aceptada' AND saldo > 0.01");
        $stats['por_cobrar'] = (float) $stmt->fetchColumn();

        $pendientes = $pdo->query(
            "SELECT v.*, e.nombre AS evento, e.fecha_inicio, cl.nombre AS cliente
               FROM v_cotizacion_saldo v
               JOIN eventos e ON e.id_evento = v.id_evento
               JOIN clientes cl ON cl.id_cliente = e.id_cliente
              WHERE v.estado = 'aceptada' AND v.saldo > 0.01
              ORDER BY e.fecha_inicio LIMIT 25"
        )->fetchAll();

        $stmt = $pdo->query(
            "SELECT c.id_cotizacion, c.folio, c.total, c.anticipo_requerido, v.pagado, v.saldo,
                    e.nombre AS evento, e.id_evento, cl.nombre AS cliente
               FROM cotizaciones c
               JOIN eventos e ON e.id_evento = c.id_evento
               JOIN clientes cl ON cl.id_cliente = e.id_cliente
               JOIN v_cotizacion_saldo v ON v.id_cotizacion = c.id_cotizacion
              WHERE c.estado = 'aceptada'
              ORDER BY e.fecha_inicio"
        );
        $cuentas = $stmt->fetchAll();

        View::render('pagos/index', [
            'titulo'     => 'Pagos y cobranza',
            'subtitulo'  => 'Cada movimiento queda ligado a una cotización aceptada, con saldo y trazabilidad.',
            'pagos'      => $pagos,
            'pendientes' => $pendientes,
            'cuentas'    => $cuentas,
            'stats'      => $stats,
            'total'      => $total,
            'q'          => $q,
            'metodo'     => $metodo,
            'desde'      => $desde,
            'hasta'      => $hasta,
            'porPagina'  => $porPagina,
        ], 'app');
    }

    public function registrar(): void
    {
        $idCotizacion = $this->entero('id_cotizacion');
        $monto = $this->decimal('monto');
        $tipo = $this->texto('tipo');
        $metodo = $this->texto('metodo');
        $referencia = $this->texto('referencia');
        $notas = $this->texto('notas');

        if ($idCotizacion <= 0) {
            $this->error('Selecciona la cotización a la que se aplicará el pago.', '/pagos');
        }
        if (!in_array($tipo, ['anticipo', 'abono', 'saldo', 'devolucion', 'penalizacion'], true)) {
            $this->error('Selecciona un tipo de movimiento válido.', '/pagos');
        }
        if (!in_array($metodo, ['efectivo', 'transferencia', 'tarjeta', 'cheque'], true)) {
            $this->error('Selecciona un método de pago válido.', '/pagos');
        }

        try {
            Cotizaciones::registrarPago($idCotizacion, $monto, $tipo, $metodo, $referencia, $notas);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage(), '/pagos');
        }

        $this->exito('Pago registrado por ' . money($monto) . '. El saldo se recalculó automáticamente.', '/cotizaciones/' . $idCotizacion);
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
