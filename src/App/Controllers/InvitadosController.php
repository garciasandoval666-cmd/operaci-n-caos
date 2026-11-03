<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Domain\Invitados;

final class InvitadosController extends Controller
{
    public function index(): void
    {
        $pdo = Database::pdo();
        $q = $this->termino();
        $eventoId = (int) ($_GET['evento'] ?? 0);
        $estado = $this->filtro('estado', ['pendiente', 'confirmado', 'declinado', 'lista_espera']);

        $where = [];
        $params = [];
        if ($q !== '') {
            $where[] = '(i.nombre LIKE ? OR i.correo LIKE ?)';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }
        if ($eventoId > 0) {
            $where[] = 'i.id_evento = ?';
            $params[] = $eventoId;
        }
        if ($estado !== '') {
            $where[] = 'i.estado_rsvp = ?';
            $params[] = $estado;
        }
        $filtroSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';

        $total = $pdo->prepare('SELECT COUNT(*) FROM invitados i' . $filtroSql);
        $total->execute($params);
        $total = (int) $total->fetchColumn();

        $porPagina = 20;
        $sql = "SELECT i.*, e.nombre AS evento, e.fecha_inicio, m.numero AS mesa
                  FROM invitados i
                  JOIN eventos e ON e.id_evento = i.id_evento
                  LEFT JOIN mesas m ON m.id_mesa = i.id_mesa" . $filtroSql . '
                 ORDER BY e.fecha_inicio DESC, i.nombre LIMIT ? OFFSET ?';
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
                    SUM(estado_rsvp = 'confirmado') confirmados,
                    SUM(estado_rsvp = 'pendiente') pendientes,
                    SUM(estado_rsvp = 'declinado') declinados,
                    SUM(estado_rsvp = 'lista_espera') espera,
                    SUM(checkin_en IS NOT NULL) checkins
               FROM invitados"
        )->fetch();

        View::render('invitados/index', [
            'titulo'    => 'Invitados',
            'subtitulo' => 'RSVP, aforo, mesas, restricciones alimenticias y check-in en un solo lugar.',
            'invitados' => $stmt->fetchAll(),
            'eventos'   => $pdo->query('SELECT id_evento, nombre, fecha_inicio FROM eventos ORDER BY fecha_inicio DESC')->fetchAll(),
            'stats'     => $stats,
            'total'     => $total,
            'q'         => $q,
            'eventoId'  => $eventoId,
            'estado'    => $estado,
            'porPagina' => $porPagina,
        ], 'app');
    }

    public function lista(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $evento = $this->evento($id);
        $pdo = Database::pdo();

        $stmt = $pdo->prepare(
            "SELECT i.*, m.numero AS mesa, m.capacidad AS mesa_capacidad, em.id_menu, me.nombre AS menu
               FROM invitados i
               LEFT JOIN mesas m ON m.id_mesa = i.id_mesa
               LEFT JOIN evento_menus em ON em.id_evento_menu = i.id_evento_menu
               LEFT JOIN menus me ON me.id_menu = em.id_menu
              WHERE i.id_evento = ? ORDER BY i.categoria, i.nombre"
        );
        $stmt->execute([$id]);
        $invitados = $stmt->fetchAll();

        $stmt = $pdo->prepare('SELECT * FROM mesas WHERE id_evento = ? ORDER BY numero');
        $stmt->execute([$id]);
        $mesas = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            "SELECT em.*, m.nombre, m.precio_persona FROM evento_menus em JOIN menus m ON m.id_menu = em.id_menu WHERE em.id_evento = ?"
        );
        $stmt->execute([$id]);
        $menus = $stmt->fetchAll();

        $stats = ['total' => count($invitados), 'confirmado' => 0, 'pendiente' => 0, 'declinado' => 0, 'lista_espera' => 0, 'checkin' => 0, 'restricciones' => 0];
        foreach ($invitados as $invitado) {
            $stats[$invitado['estado_rsvp']]++;
            if ($invitado['checkin_en'] !== null) {
                $stats['checkin']++;
            }
            if ($invitado['restriccion_alimenticia'] !== 'ninguna') {
                $stats['restricciones']++;
            }
        }

        View::render('invitados/lista', [
            'titulo'     => 'Invitados · ' . $evento['nombre'],
            'migas'      => [['titulo' => 'Eventos', 'url' => '/eventos'], ['titulo' => $evento['nombre'], 'url' => '/eventos/' . $id], ['titulo' => 'Invitados']],
            'evento'     => $evento,
            'invitados'  => $invitados,
            'mesas'      => $mesas,
            'menus'      => $menus,
            'stats'      => $stats,
        ], 'app');
    }

    public function guardar(array $params): void
    {
        $idEvento = (int) ($params['id'] ?? 0);
        $evento = $this->evento($idEvento);
        $nombre = $this->texto('nombre');
        $correo = mb_strtolower($this->texto('correo'));
        $telefono = $this->texto('telefono');
        $categoria = $this->filtroPost('categoria', ['general', 'vip', 'familia', 'proveedor', 'prensa', 'staff'], 'general');
        $restriccion = $this->filtroPost('restriccion_alimenticia', ['ninguna', 'vegetariano', 'vegano', 'sin_gluten', 'otra'], 'ninguna');
        $alergias = $this->texto('alergias');
        $notas = $this->texto('notas');
        $idMesa = $this->entero('id_mesa') ?: null;

        if (mb_strlen($nombre) < 2) {
            $this->error('El nombre del invitado es obligatorio.', '/eventos/' . $idEvento . '/invitados');
        }
        if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $this->error('El correo del invitado no es válido.', '/eventos/' . $idEvento . '/invitados');
        }
        if ($idMesa !== null) {
            $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM mesas WHERE id_mesa = ? AND id_evento = ?');
            $stmt->execute([$idMesa, $idEvento]);
            if ((int) $stmt->fetchColumn() === 0) {
                $this->error('La mesa seleccionada no pertenece al evento.', '/eventos/' . $idEvento . '/invitados');
            }
        }
        if ($correo !== '') {
            $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM invitados WHERE id_evento = ? AND correo = ?');
            $stmt->execute([$idEvento, $correo]);
            if ((int) $stmt->fetchColumn() > 0) {
                $this->error('Ya existe un invitado con ese correo en este evento.', '/eventos/' . $idEvento . '/invitados');
            }
        }

        $token = bin2hex(random_bytes(16));
        Database::pdo()->prepare(
            "INSERT INTO invitados (id_evento, nombre, correo, telefono, categoria, estado_rsvp, token_acceso,
                                    id_mesa, restriccion_alimenticia, alergias, notas)
             VALUES (?, ?, ?, ?, ?, 'pendiente', ?, ?, ?, ?, ?)"
        )->execute([
            $idEvento, mb_substr($nombre, 0, 120), $correo !== '' ? $correo : null,
            $telefono !== '' ? mb_substr($telefono, 0, 25) : null, $categoria, $token, $idMesa,
            $restriccion, $alergias !== '' ? mb_substr($alergias, 0, 220) : null,
            $notas !== '' ? mb_substr($notas, 0, 255) : null,
        ]);

        $id = (int) Database::pdo()->lastInsertId();
        registrar_bitacora('AGREGAR_INVITADO', 'invitados', $id, $nombre . ' · ' . $evento['nombre']);
        $this->exito('Invitado agregado. Ya puedes compartir su enlace RSVP.', '/eventos/' . $idEvento . '/invitados');
    }

    public function estado(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT i.*, e.aforo_maximo, e.nombre AS evento FROM invitados i
               JOIN eventos e ON e.id_evento = i.id_evento WHERE i.id_invitado = ?'
        );
        $stmt->execute([$id]);
        $invitado = $stmt->fetch();
        if (!$invitado) {
            $this->noEncontrado('El invitado no existe.');
        }

        $nuevo = $this->texto('estado');
        if (!in_array($nuevo, ['pendiente', 'confirmado', 'declinado', 'lista_espera'], true)) {
            $this->error('Estado de RSVP no válido.', '/eventos/' . (int) $invitado['id_evento'] . '/invitados');
        }

        if ($nuevo === 'confirmado') {
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM invitados WHERE id_evento = ? AND estado_rsvp = 'confirmado' AND id_invitado <> ?"
            );
            $stmt->execute([(int) $invitado['id_evento'], $id]);
            if ((int) $stmt->fetchColumn() >= (int) $invitado['aforo_maximo']) {
                $this->error('El aforo del evento ya está completo. El invitado puede quedar en lista de espera.', '/eventos/' . (int) $invitado['id_evento'] . '/invitados');
            }
        }

        $pdo->prepare('UPDATE invitados SET estado_rsvp = ? WHERE id_invitado = ?')->execute([$nuevo, $id]);
        registrar_historial('invitados', $id, 'estado_rsvp', $invitado['estado_rsvp'], $nuevo, 'Cambio manual desde el panel');
        registrar_bitacora('ESTADO_INVITADO', 'invitados', $id, $invitado['nombre'] . ': ' . $invitado['estado_rsvp'] . ' → ' . $nuevo);
        $this->exito('Estado de ' . $invitado['nombre'] . ' actualizado.', '/eventos/' . (int) $invitado['id_evento'] . '/invitados');
    }

    public function importarForm(): void
    {
        View::render('invitados/importar', [
            'titulo'  => 'Importar invitados',
            'migas'   => [['titulo' => 'Invitados', 'url' => '/invitados'], ['titulo' => 'Importar']],
            'eventos' => Database::pdo()->query('SELECT id_evento, nombre, fecha_inicio FROM eventos ORDER BY fecha_inicio DESC')->fetchAll(),
        ], 'app');
    }

    public function importar(array $params): void
    {
        $idEvento = (int) ($params['id'] ?? 0);
        $evento = $this->evento($idEvento);
        $texto = trim((string) ($_POST['datos'] ?? ''));
        if ($texto === '') {
            $this->error('Pega al menos una línea con datos.', '/invitados/importar');
        }

        $lineas = preg_split('/\r\n|\r|\n/', $texto) ?: [];
        $insertados = 0;
        $omitidos = 0;

        Database::transaccion(static function (\PDO $pdo) use ($lineas, $idEvento, $evento, &$insertados, &$omitidos): void {
            $stmt = $pdo->prepare(
                "INSERT INTO invitados (id_evento, nombre, correo, telefono, categoria, token_acceso)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $existe = $pdo->prepare('SELECT COUNT(*) FROM invitados WHERE id_evento = ? AND correo = ?');
            foreach ($lineas as $linea) {
                $linea = trim($linea);
                if ($linea === '') {
                    continue;
                }
                $partes = preg_split('/\s*[|;\t]\s*/', $linea) ?: [];
                $nombre = trim($partes[0] ?? '');
                $correo = mb_strtolower(trim($partes[1] ?? ''));
                $telefono = trim($partes[2] ?? '');
                $categoria = trim($partes[3] ?? 'general');
                if (!in_array($categoria, ['general', 'vip', 'familia', 'proveedor', 'prensa', 'staff'], true)) {
                    $categoria = 'general';
                }
                if (mb_strlen($nombre) < 2 || ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL))) {
                    $omitidos++;
                    continue;
                }
                if ($correo !== '') {
                    $existe->execute([$idEvento, $correo]);
                    if ((int) $existe->fetchColumn() > 0) {
                        $omitidos++;
                        continue;
                    }
                }
                $stmt->execute([
                    $idEvento,
                    mb_substr($nombre, 0, 120),
                    $correo !== '' ? $correo : null,
                    $telefono !== '' ? mb_substr($telefono, 0, 25) : null,
                    $categoria,
                    bin2hex(random_bytes(16)),
                ]);
                $insertados++;
            }
            registrar_bitacora('IMPORTAR_INVITADOS', 'eventos', $idEvento, $evento['nombre'] . ': ' . $insertados . ' importados, ' . $omitidos . ' omitidos');
        });

        $this->exito(
            'Importación terminada: ' . $insertados . ' invitados agregados' . ($omitidos > 0 ? ' y ' . $omitidos . ' omitidos por datos inválidos o duplicados.' : '.'),
            '/eventos/' . $idEvento . '/invitados'
        );
    }

    public function mesas(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $evento = $this->evento($id);
        $pdo = Database::pdo();

        $stmt = $pdo->prepare('SELECT * FROM mesas WHERE id_evento = ? ORDER BY numero');
        $stmt->execute([$id]);
        $mesas = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            "SELECT i.*, m.numero AS mesa FROM invitados i LEFT JOIN mesas m ON m.id_mesa = i.id_mesa
              WHERE i.id_evento = ? ORDER BY m.numero, i.nombre"
        );
        $stmt->execute([$id]);
        $invitados = $stmt->fetchAll();

        $porMesa = [];
        $sinMesa = [];
        foreach ($invitados as $invitado) {
            if ($invitado['id_mesa'] !== null) {
                $porMesa[(int) $invitado['id_mesa']][] = $invitado;
            } else {
                $sinMesa[] = $invitado;
            }
        }

        View::render('invitados/mesas', [
            'titulo'   => 'Mesas · ' . $evento['nombre'],
            'migas'    => [['titulo' => 'Eventos', 'url' => '/eventos'], ['titulo' => $evento['nombre'], 'url' => '/eventos/' . $id], ['titulo' => 'Mesas']],
            'evento'   => $evento,
            'mesas'    => $mesas,
            'porMesa'  => $porMesa,
            'sinMesa'  => $sinMesa,
        ], 'app');
    }

    public function crearMesa(array $params): void
    {
        $idEvento = (int) ($params['id'] ?? 0);
        $evento = $this->evento($idEvento);
        $numero = $this->texto('numero');
        $capacidad = $this->entero('capacidad');
        $zona = $this->texto('zona');

        if ($numero === '' || $capacidad < 1 || $capacidad > 40) {
            $this->error('Indica un número de mesa y una capacidad entre 1 y 40.', '/eventos/' . $idEvento . '/mesas');
        }

        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM mesas WHERE id_evento = ? AND numero = ?');
        $stmt->execute([$idEvento, $numero]);
        if ((int) $stmt->fetchColumn() > 0) {
            $this->error('Ya existe una mesa con ese número.', '/eventos/' . $idEvento . '/mesas');
        }

        Database::pdo()->prepare('INSERT INTO mesas (id_evento, numero, capacidad, zona) VALUES (?, ?, ?, ?)')
            ->execute([$idEvento, mb_substr($numero, 0, 12), $capacidad, $zona !== '' ? mb_substr($zona, 0, 40) : null]);

        registrar_bitacora('CREAR_MESA', 'mesas', (int) Database::pdo()->lastInsertId(), $evento['nombre'] . ' · Mesa ' . $numero);
        $this->exito('Mesa ' . $numero . ' creada.', '/eventos/' . $idEvento . '/mesas');
    }

    public function asignarMesa(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $idMesa = $this->entero('id_mesa') ?: null;
        try {
            Invitados::asignarMesa($id, $idMesa);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage(), '/invitados');
        }

        $stmt = Database::pdo()->prepare('SELECT id_evento FROM invitados WHERE id_invitado = ?');
        $stmt->execute([$id]);
        $idEvento = (int) $stmt->fetchColumn();
        $this->exito('Mesa actualizada.', '/eventos/' . $idEvento . '/mesas');
    }

    public function checkin(): void
    {
        $pdo = Database::pdo();
        $eventos = $pdo->query(
            "SELECT e.id_evento, e.nombre, e.fecha_inicio, e.fecha_fin,
                    (SELECT COUNT(*) FROM invitados i WHERE i.id_evento = e.id_evento AND i.estado_rsvp = 'confirmado') confirmados
               FROM eventos e
              WHERE e.estado IN ('confirmado','en_preparacion','en_curso','realizado')
              ORDER BY ABS(TIMESTAMPDIFF(HOUR, e.fecha_inicio, NOW())) LIMIT 12"
        )->fetchAll();

        $idEvento = (int) ($_GET['evento'] ?? ($eventos[0]['id_evento'] ?? 0));
        $q = $this->termino();
        $evento = $idEvento > 0 ? $this->evento($idEvento) : null;

        $invitados = [];
        $recientes = [];
        $stats = ['confirmados' => 0, 'checkin' => 0];
        if ($evento) {
            $where = "i.id_evento = ? AND i.estado_rsvp = 'confirmado'";
            $params = [$idEvento];
            if ($q !== '') {
                $where .= ' AND (i.nombre LIKE ? OR i.correo LIKE ? OR i.token_acceso LIKE ?)';
                $params[] = '%' . $q . '%';
                $params[] = '%' . $q . '%';
                $params[] = '%' . $q . '%';
            }
            $stmt = $pdo->prepare(
                "SELECT i.*, m.numero AS mesa, me.nombre AS menu
                   FROM invitados i
                   LEFT JOIN mesas m ON m.id_mesa = i.id_mesa
                   LEFT JOIN evento_menus em ON em.id_evento_menu = i.id_evento_menu
                   LEFT JOIN menus me ON me.id_menu = em.id_menu
                  WHERE " . $where . '
                  ORDER BY i.checkin_en IS NULL DESC, i.nombre LIMIT 120'
            );
            $stmt->execute($params);
            $invitados = $stmt->fetchAll();

            $stmt = $pdo->prepare(
                "SELECT i.nombre, i.checkin_en, u.nombre AS usuario FROM invitados i
                   LEFT JOIN usuarios u ON u.id_usuario = i.checkin_por
                  WHERE i.id_evento = ? AND i.checkin_en IS NOT NULL ORDER BY i.checkin_en DESC LIMIT 10"
            );
            $stmt->execute([$idEvento]);
            $recientes = $stmt->fetchAll();

            $stats['confirmados'] = count(array_filter($invitados, static fn (array $i): bool => $i['estado_rsvp'] === 'confirmado'));
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM invitados WHERE id_evento = ? AND checkin_en IS NOT NULL');
            $stmt->execute([$idEvento]);
            $stats['checkin'] = (int) $stmt->fetchColumn();
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM invitados WHERE id_evento = ? AND estado_rsvp = 'confirmado'");
            $stmt->execute([$idEvento]);
            $stats['confirmados'] = (int) $stmt->fetchColumn();
        }

        View::render('invitados/checkin', [
            'titulo'    => 'Check-in',
            'subtitulo' => 'Registro de llegada con búsqueda por nombre o código de invitación.',
            'eventos'   => $eventos,
            'evento'    => $evento,
            'invitados' => $invitados,
            'recientes' => $recientes,
            'stats'     => $stats,
            'q'         => $q,
        ], 'app');
    }

    public function registrarCheckin(): void
    {
        $codigo = $this->texto('codigo');
        $idEvento = $this->entero('id_evento');
        if ($codigo === '') {
            $this->error('Escanea o escribe el código del invitado.', '/checkin' . ($idEvento > 0 ? '?evento=' . $idEvento : ''));
        }

        $pdo = Database::pdo();

        // Solo el token sirve como código. Antes también se aceptaba el
        // id_invitado, que es secuencial: bastaba teclear «12» para registrar
        // la entrada de un desconocido, y «12abc» se convertía en 12 al
        // castear. El token es de 128 bits y no se puede adivinar.
        $stmt = $pdo->prepare(
            'SELECT id_invitado, id_evento, nombre FROM invitados
              WHERE token_acceso = ? LIMIT 1'
        );
        $stmt->execute([$codigo]);
        $invitado = $stmt->fetch();

        if (!$invitado) {
            $this->error('No encontramos un invitado con ese código.', '/checkin' . ($idEvento > 0 ? '?evento=' . $idEvento : ''));
        }

        // El invitado tiene que pertenecer al evento que se está atendiendo.
        // Sin esta comprobación, una recepción podía marcar la entrada de un
        // invitado de otro evento y descuadrar el aforo de ambos.
        if ($idEvento > 0 && (int) $invitado['id_evento'] !== $idEvento) {
            $this->error(
                'Ese invitado pertenece a otro evento. Cambia de evento en el selector antes de registrar su entrada.',
                '/checkin?evento=' . $idEvento
            );
        }

        try {
            Invitados::checkin((int) $invitado['id_invitado'], (int) (Auth::id() ?? 1));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage(), '/checkin?evento=' . (int) $invitado['id_evento'] . '&q=' . urlencode($invitado['nombre']));
        }

        $this->exito('Entrada registrada para ' . $invitado['nombre'] . '.', '/checkin?evento=' . (int) $invitado['id_evento'] . '&q=' . urlencode($invitado['nombre']));
    }

    private function filtroPost(string $campo, array $permitidos, string $defecto): string
    {
        $valor = (string) ($_POST[$campo] ?? '');
        return in_array($valor, $permitidos, true) ? $valor : $defecto;
    }
}
