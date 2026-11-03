<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;

/**
 * Módulo D · bitácora inmutable de operaciones.
 */
final class BitacoraController extends Controller
{
    public function index(): void
    {
        $pdo = Database::pdo();
        $q = $this->termino();
        $usuarioId = (int) ($_GET['usuario'] ?? 0);
        $entidad = trim((string) ($_GET['entidad'] ?? ''));
        $accion = trim((string) ($_GET['accion'] ?? ''));
        $desde = $this->fechaGet('desde');
        $hasta = $this->fechaGet('hasta');

        $where = [];
        $params = [];
        if ($q !== '') {
            $where[] = '(b.accion LIKE ? OR b.detalle LIKE ? OR b.entidad LIKE ? OR u.nombre LIKE ?)';
            $like = '%' . $q . '%';
            $params = [$like, $like, $like, $like];
        }
        if ($usuarioId > 0) {
            $where[] = 'b.id_usuario = ?';
            $params[] = $usuarioId;
        }
        if ($entidad !== '') {
            $where[] = 'b.entidad = ?';
            $params[] = $entidad;
        }
        if ($accion !== '') {
            $where[] = 'b.accion = ?';
            $params[] = $accion;
        }
        if ($desde !== null) {
            $where[] = 'b.fecha >= ?';
            $params[] = $desde . ' 00:00:00';
        }
        if ($hasta !== null) {
            $where[] = 'b.fecha <= ?';
            $params[] = $hasta . ' 23:59:59';
        }
        $filtroSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';

        $total = $pdo->prepare(
            'SELECT COUNT(*) FROM bitacora b LEFT JOIN usuarios u ON u.id_usuario = b.id_usuario' . $filtroSql
        );
        $total->execute($params);
        $total = (int) $total->fetchColumn();

        $porPagina = 30;
        $sql = 'SELECT b.*, u.nombre AS usuario, r.nombre AS rol
                  FROM bitacora b
                  LEFT JOIN usuarios u ON u.id_usuario = b.id_usuario
                  LEFT JOIN roles r ON r.id_rol = u.id_rol' . $filtroSql . '
                 ORDER BY b.fecha DESC, b.id_bitacora DESC
                 LIMIT ? OFFSET ?';
        $stmt = $pdo->prepare($sql);
        $i = 1;
        foreach ($params as $param) {
            $stmt->bindValue($i++, $param, is_int($param) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $stmt->bindValue($i++, $porPagina, \PDO::PARAM_INT);
        $stmt->bindValue($i, $this->offset($porPagina), \PDO::PARAM_INT);
        $stmt->execute();

        $stats = $pdo->query(
            "SELECT COUNT(*) total,
                    COUNT(DISTINCT id_usuario) usuarios,
                    SUM(fecha >= CURDATE()) hoy,
                    SUM(fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY)) semana
               FROM bitacora"
        )->fetch();

        View::render('bitacora/index', [
            'titulo'    => 'Bitácora',
            'subtitulo' => 'Registro cronológico e inmutable de cada acción sobre el sistema.',
            'registros' => $stmt->fetchAll(),
            'usuarios'  => $pdo->query('SELECT id_usuario, nombre FROM usuarios ORDER BY nombre')->fetchAll(),
            'entidades' => $pdo->query("SELECT DISTINCT entidad FROM bitacora WHERE entidad <> '' ORDER BY entidad")->fetchAll(\PDO::FETCH_COLUMN),
            'acciones'  => $pdo->query('SELECT DISTINCT accion FROM bitacora ORDER BY accion')->fetchAll(\PDO::FETCH_COLUMN),
            'stats'     => $stats,
            'total'     => $total,
            'q'         => $q,
            'usuarioId' => $usuarioId,
            'entidad'   => $entidad,
            'accion'    => $accion,
            'desde'     => $desde,
            'hasta'     => $hasta,
            'porPagina' => $porPagina,
        ], 'app');
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
