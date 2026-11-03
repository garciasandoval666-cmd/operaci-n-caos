<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use App\Domain\Insumos;

final class InsumosController extends Controller
{
    public function index(): void
    {
        $pdo = Database::pdo();
        $insumos = $pdo->query(
            "SELECT i.*, i.stock_fisico - i.stock_comprometido AS disponible,
                    (i.stock_fisico - i.stock_comprometido) <= i.stock_minimo AS bajo_minimo
               FROM insumos_menu i ORDER BY i.estado, i.categoria, i.nombre"
        )->fetchAll();

        $reservas = $pdo->query(
            "SELECT ri.*, i.nombre, i.unidad, e.nombre AS evento, e.id_evento
               FROM reservas_insumo ri
               JOIN insumos_menu i ON i.id_insumo = ri.id_insumo
               JOIN eventos e ON e.id_evento = ri.id_evento
              WHERE ri.estado = 'confirmada' ORDER BY e.fecha_inicio, i.nombre"
        )->fetchAll();

        $movimientos = $pdo->query(
            "SELECT m.*, i.nombre, i.unidad, e.nombre AS evento, u.nombre AS usuario
               FROM movimientos_insumo m
               JOIN insumos_menu i ON i.id_insumo = m.id_insumo
               LEFT JOIN eventos e ON e.id_evento = m.id_evento
               LEFT JOIN usuarios u ON u.id_usuario = m.id_usuario
              ORDER BY m.fecha_movimiento DESC, m.id_movimiento DESC LIMIT 10"
        )->fetchAll();

        View::render('insumos/index', [
            'titulo'      => 'Insumos y kardex',
            'subtitulo'   => 'Stock físico, comprometido y disponible con trazabilidad de cada movimiento.',
            'insumos'     => $insumos,
            'reservas'    => $reservas,
            'movimientos' => $movimientos,
            'eventos'     => $pdo->query("SELECT id_evento, nombre, fecha_inicio FROM eventos WHERE estado NOT IN ('cancelado','cerrado') ORDER BY fecha_inicio")->fetchAll(),
            'bajos'       => count(array_filter($insumos, static fn (array $i): bool => (int) $i['bajo_minimo'] === 1)),
        ], 'app');
    }

    public function guardar(): void
    {
        $nombre = $this->texto('nombre');
        $categoria = $this->texto('categoria');
        $unidad = $this->texto('unidad', 'porcion');
        $stock = $this->decimal('stock_fisico');
        $minimo = $this->decimal('stock_minimo');
        $costo = $this->decimal('costo_referencia');

        if ($nombre === '' || $stock < 0 || $minimo < 0) {
            $this->error('Completa nombre y stocks válidos.', '/insumos');
        }
        if (!in_array($categoria, ['entrada', 'plato_fuerte', 'postre', 'bebida', 'abarrote', 'otro'], true)) {
            $this->error('Selecciona una categoría válida.', '/insumos');
        }

        try {
            Database::pdo()->prepare(
                'INSERT INTO insumos_menu (nombre, categoria, unidad, stock_fisico, stock_minimo, costo_referencia) VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([mb_substr($nombre, 0, 120), $categoria, mb_substr($unidad, 0, 20), $stock, $minimo, $costo]);
        } catch (\PDOException) {
            $this->error('Ya existe un insumo con ese nombre.', '/insumos');
        }

        registrar_bitacora('CREAR_INSUMO', 'insumos_menu', (int) Database::pdo()->lastInsertId(), $nombre . ' · stock ' . $stock);
        $this->exito('Insumo agregado al inventario.', '/insumos');
    }

    public function kardex(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT i.*, i.stock_fisico - i.stock_comprometido AS disponible FROM insumos_menu i WHERE i.id_insumo = ?'
        );
        $stmt->execute([$id]);
        $insumo = $stmt->fetch();
        if (!$insumo) {
            $this->noEncontrado('El insumo no existe.');
        }

        $stmt = $pdo->prepare(
            "SELECT m.*, e.nombre AS evento, u.nombre AS usuario
               FROM movimientos_insumo m
               LEFT JOIN eventos e ON e.id_evento = m.id_evento
               LEFT JOIN usuarios u ON u.id_usuario = m.id_usuario
              WHERE m.id_insumo = ? ORDER BY m.fecha_movimiento DESC, m.id_movimiento DESC"
        );
        $stmt->execute([$id]);
        $movimientos = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            "SELECT ri.*, e.nombre AS evento, e.fecha_inicio FROM reservas_insumo ri
               JOIN eventos e ON e.id_evento = ri.id_evento
              WHERE ri.id_insumo = ? ORDER BY ri.creado_en DESC"
        );
        $stmt->execute([$id]);
        $reservas = $stmt->fetchAll();

        View::render('insumos/kardex', [
            'titulo'      => 'Kardex · ' . $insumo['nombre'],
            'migas'       => [['titulo' => 'Insumos', 'url' => '/insumos'], ['titulo' => $insumo['nombre']]],
            'insumo'      => $insumo,
            'movimientos' => $movimientos,
            'reservas'    => $reservas,
        ], 'app');
    }

    public function movimiento(): void
    {
        $id = $this->entero('id_insumo');
        $tipo = $this->texto('tipo');
        $motivo = $this->texto('motivo');

        if ($id <= 0 || $motivo === '') {
            $this->error('Selecciona el insumo y explica el motivo del movimiento.', '/insumos');
        }

        try {
            if ($tipo === 'entrada') {
                $cantidad = $this->decimal('cantidad');
                Insumos::entrada($id, $cantidad, $motivo);
                $this->exito('Entrada de stock registrada.', '/insumos/' . $id . '/kardex');
            }
            if ($tipo === 'ajuste') {
                $nuevo = $this->decimal('nuevo_stock');
                Insumos::ajuste($id, $nuevo, $motivo);
                $this->exito('Ajuste de inventario registrado.', '/insumos/' . $id . '/kardex');
            }
            $this->error('Selecciona el tipo de movimiento.', '/insumos');
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage(), '/insumos/' . $id . '/kardex');
        }
    }

    public function apartar(array $params): void
    {
        $idEvento = (int) ($params['id'] ?? 0);
        $idInsumo = $this->entero('id_insumo');
        $cantidad = $this->decimal('cantidad');
        $motivo = $this->texto('motivo');

        try {
            Insumos::apartar($idEvento, $idInsumo, $cantidad, $motivo);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage(), '/eventos/' . $idEvento);
        }

        $this->exito('Insumo apartado y comprometido para el evento.', '/eventos/' . $idEvento);
    }
}
