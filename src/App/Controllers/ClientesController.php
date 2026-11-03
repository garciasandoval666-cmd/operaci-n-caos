<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Flash;
use App\Core\View;

final class ClientesController extends Controller
{
    public function index(): void
    {
        $pdo = Database::pdo();
        $q = $this->termino();
        $estado = $this->filtro('estado', ['activo', 'inactivo']);

        $where = [];
        $params = [];
        if ($q !== '') {
            $where[] = '(c.nombre LIKE ? OR c.correo LIKE ? OR COALESCE(c.razon_social, \'\') LIKE ?)';
            $like = '%' . $q . '%';
            $params = [$like, $like, $like];
        }
        if ($estado !== '') {
            $where[] = 'c.estado = ?';
            $params[] = $estado;
        }
        $filtroSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';

        $total = $pdo->prepare('SELECT COUNT(*) FROM clientes c' . $filtroSql);
        $total->execute($params);
        $total = (int) $total->fetchColumn();

        $porPagina = 15;
        $sql = "SELECT c.*,
                       (SELECT COUNT(*) FROM eventos e WHERE e.id_cliente = c.id_cliente) AS eventos,
                       (SELECT COUNT(*) FROM eventos e WHERE e.id_cliente = c.id_cliente AND e.estado IN ('confirmado','en_preparacion','en_curso')) AS activos,
                       (SELECT COALESCE(SUM(co.total),0) FROM cotizaciones co
                          JOIN eventos e2 ON e2.id_evento = co.id_evento
                         WHERE e2.id_cliente = c.id_cliente AND co.estado IN ('aceptada','enviada')) AS cotizado
                  FROM clientes c" . $filtroSql . '
                 ORDER BY c.nombre LIMIT ? OFFSET ?';
        $stmt = $pdo->prepare($sql);
        $i = 1;
        foreach ($params as $param) {
            $stmt->bindValue($i++, $param, \PDO::PARAM_STR);
        }
        $stmt->bindValue($i++, $porPagina, \PDO::PARAM_INT);
        $stmt->bindValue($i, $this->offset($porPagina), \PDO::PARAM_INT);
        $stmt->execute();

        View::render('clientes/index', [
            'titulo'    => 'Clientes',
            'subtitulo' => 'Personas y empresas con historial de eventos y cotizaciones.',
            'clientes'  => $stmt->fetchAll(),
            'total'     => $total,
            'q'         => $q,
            'estado'    => $estado,
            'porPagina' => $porPagina,
        ], 'app');
    }

    public function ver(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM clientes WHERE id_cliente = ?');
        $stmt->execute([$id]);
        $cliente = $stmt->fetch();
        if (!$cliente) {
            $this->noEncontrado('El cliente no existe.');
        }

        $stmt = $pdo->prepare(
            "SELECT e.*, t.nombre AS tipo,
                    (SELECT COALESCE(SUM(v.saldo),0) FROM v_cotizacion_saldo v WHERE v.id_evento = e.id_evento AND v.estado = 'aceptada') AS saldo
               FROM eventos e JOIN tipos_evento t ON t.id_tipo_evento = e.id_tipo_evento
              WHERE e.id_cliente = ? ORDER BY e.fecha_inicio DESC"
        );
        $stmt->execute([$id]);
        $eventos = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            "SELECT co.*, e.nombre AS evento FROM cotizaciones co
               JOIN eventos e ON e.id_evento = co.id_evento
              WHERE e.id_cliente = ? ORDER BY co.creado_en DESC LIMIT 10"
        );
        $stmt->execute([$id]);
        $cotizaciones = $stmt->fetchAll();

        $stmt = $pdo->prepare('SELECT * FROM leads WHERE correo = ? ORDER BY creado_en DESC LIMIT 5');
        $stmt->execute([$cliente['correo']]);
        $leads = $stmt->fetchAll();

        $totales = ['eventos' => count($eventos), 'cotizado' => 0.0, 'saldo' => 0.0];
        foreach ($eventos as $evento) {
            $totales['saldo'] += (float) $evento['saldo'];
        }
        foreach ($cotizaciones as $cotizacion) {
            $totales['cotizado'] += (float) $cotizacion['total'];
        }

        View::render('clientes/ver', [
            'titulo'       => $cliente['nombre'],
            'migas'        => [['titulo' => 'Clientes', 'url' => '/clientes'], ['titulo' => $cliente['nombre']]],
            'cliente'      => $cliente,
            'eventos'      => $eventos,
            'cotizaciones' => $cotizaciones,
            'leads'        => $leads,
            'totales'      => $totales,
        ], 'app');
    }

    public function crear(): void
    {
        View::render('clientes/form', [
            'titulo'    => 'Nuevo cliente',
            'migas'     => [['titulo' => 'Clientes', 'url' => '/clientes'], ['titulo' => 'Nuevo']],
            'cliente'   => null,
            'errores'   => errores(),
        ], 'app');
    }

    public function guardar(): void
    {
        $datos = $this->datos();
        $errores = $this->validar($datos);

        if ($errores !== []) {
            recordar_viejos($_POST);
            guardar_errores($errores);
            $this->error('Revisa los campos marcados.', '/clientes/crear');
        }

        try {
            $stmt = Database::pdo()->prepare(
                'INSERT INTO clientes (tipo_cliente, nombre, razon_social, correo, telefono, direccion, notas, estado)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $datos['tipo_cliente'], $datos['nombre'], $datos['razon_social'] ?: null,
                $datos['correo'], $datos['telefono'] ?: null, $datos['direccion'] ?: null,
                $datos['notas'] ?: null, $datos['estado'],
            ]);
        } catch (\PDOException) {
            recordar_viejos($_POST);
            $this->error('No se pudo guardar. Verifica que el correo no esté registrado.', '/clientes/crear');
        }

        $id = (int) Database::pdo()->lastInsertId();
        registrar_bitacora('CREAR_CLIENTE', 'clientes', $id, $datos['nombre']);
        limpiar_viejos();
        $this->exito('Cliente registrado correctamente.', '/clientes/' . $id);
    }

    public function editar(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $stmt = Database::pdo()->prepare('SELECT * FROM clientes WHERE id_cliente = ?');
        $stmt->execute([$id]);
        $cliente = $stmt->fetch();
        if (!$cliente) {
            $this->noEncontrado('El cliente no existe.');
        }

        View::render('clientes/form', [
            'titulo'  => 'Editar ' . $cliente['nombre'],
            'migas'   => [['titulo' => 'Clientes', 'url' => '/clientes'], ['titulo' => $cliente['nombre'], 'url' => '/clientes/' . $id], ['titulo' => 'Editar']],
            'cliente' => $cliente,
            'errores' => errores(),
        ], 'app');
    }

    public function actualizar(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM clientes WHERE id_cliente = ?');
        $stmt->execute([$id]);
        $anterior = $stmt->fetch();
        if (!$anterior) {
            $this->noEncontrado('El cliente no existe.');
        }

        $datos = $this->datos();
        $errores = $this->validar($datos, $id);
        if ($errores !== []) {
            recordar_viejos($_POST);
            guardar_errores($errores);
            $this->error('Revisa los campos marcados.', '/clientes/' . $id . '/editar');
        }

        $pdo->prepare(
            'UPDATE clientes SET tipo_cliente = ?, nombre = ?, razon_social = ?, correo = ?, telefono = ?,
                    direccion = ?, notas = ?, estado = ? WHERE id_cliente = ?'
        )->execute([
            $datos['tipo_cliente'], $datos['nombre'], $datos['razon_social'] ?: null,
            $datos['correo'], $datos['telefono'] ?: null, $datos['direccion'] ?: null,
            $datos['notas'] ?: null, $datos['estado'], $id,
        ]);

        foreach (['nombre', 'correo', 'telefono', 'direccion', 'notas', 'estado', 'tipo_cliente'] as $campo) {
            if ((string) ($anterior[$campo] ?? '') !== (string) ($datos[$campo] ?? '')) {
                registrar_historial('clientes', $id, $campo, $anterior[$campo] ?? null, $datos[$campo] ?? null, 'Edición de cliente');
            }
        }
        registrar_bitacora('EDITAR_CLIENTE', 'clientes', $id, $datos['nombre']);
        limpiar_viejos();
        $this->exito('Cliente actualizado.', '/clientes/' . $id);
    }

    public function estado(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM clientes WHERE id_cliente = ?');
        $stmt->execute([$id]);
        $cliente = $stmt->fetch();
        if (!$cliente) {
            $this->noEncontrado('El cliente no existe.');
        }

        $nuevo = $cliente['estado'] === 'activo' ? 'inactivo' : 'activo';
        if ($nuevo === 'inactivo') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM eventos WHERE id_cliente = ? AND estado NOT IN ('cancelado','cerrado')");
            $stmt->execute([$id]);
            if ((int) $stmt->fetchColumn() > 0) {
                $this->error('No puedes inactivar un cliente con eventos activos. Cancela o cierra esos eventos primero.', '/clientes/' . $id);
            }
        }

        $pdo->prepare('UPDATE clientes SET estado = ? WHERE id_cliente = ?')->execute([$nuevo, $id]);
        registrar_historial('clientes', $id, 'estado', $cliente['estado'], $nuevo, 'Cambio de estado');
        registrar_bitacora('ESTADO_CLIENTE', 'clientes', $id, $cliente['estado'] . ' → ' . $nuevo);
        $this->exito('Cliente ' . ($nuevo === 'activo' ? 'activado' : 'inactivado') . '.', '/clientes/' . $id);
    }

    private function datos(): array
    {
        return [
            'tipo_cliente' => $this->filtroPost('tipo_cliente', ['persona', 'empresa'], 'persona'),
            'nombre'       => $this->texto('nombre'),
            'razon_social' => $this->texto('razon_social'),
            'correo'       => mb_strtolower($this->texto('correo')),
            'telefono'     => $this->texto('telefono'),
            'direccion'    => $this->texto('direccion'),
            'notas'        => $this->texto('notas'),
            'estado'       => $this->filtroPost('estado', ['activo', 'inactivo'], 'activo'),
        ];
    }

    private function validar(array $datos, ?int $id = null): array
    {
        $errores = [];
        if (mb_strlen($datos['nombre']) < 3) {
            $errores['nombre'] = 'El nombre debe tener al menos 3 caracteres.';
        }
        if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            $errores['correo'] = 'Escribe un correo válido.';
        } else {
            $sql = 'SELECT COUNT(*) FROM clientes WHERE correo = ?' . ($id !== null ? ' AND id_cliente <> ?' : '');
            $stmt = Database::pdo()->prepare($sql);
            $stmt->execute($id !== null ? [$datos['correo'], $id] : [$datos['correo']]);
            if ((int) $stmt->fetchColumn() > 0) {
                $errores['correo'] = 'Ya existe un cliente con este correo.';
            }
        }
        if ($datos['tipo_cliente'] === 'empresa' && $datos['razon_social'] === '') {
            $errores['razon_social'] = 'Para empresas, indica la razón social.';
        }
        return $errores;
    }

    private function filtroPost(string $campo, array $permitidos, string $defecto): string
    {
        $valor = (string) ($_POST[$campo] ?? '');
        return in_array($valor, $permitidos, true) ? $valor : $defecto;
    }
}
