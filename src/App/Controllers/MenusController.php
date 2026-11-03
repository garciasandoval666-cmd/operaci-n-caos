<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;

final class MenusController extends Controller
{
    public function index(): void
    {
        $pdo = Database::pdo();
        $menus = $pdo->query(
            "SELECT m.*,
                    (SELECT COUNT(*) FROM menu_platillos mp WHERE mp.id_menu = m.id_menu) AS platillos,
                    (SELECT COUNT(*) FROM evento_menus em WHERE em.id_menu = m.id_menu) AS eventos,
                    (SELECT COALESCE(SUM(em.personas),0) FROM evento_menus em WHERE em.id_menu = m.id_menu) AS personas
               FROM menus m ORDER BY m.activo DESC, m.precio_persona DESC"
        )->fetchAll();

        View::render('menus/index', [
            'titulo'    => 'Menús y banquetes',
            'subtitulo' => 'Catálogo de menús, precios por persona y producción por evento.',
            'menus'     => $menus,
            'platillos' => $pdo->query('SELECT COUNT(*) FROM platillos WHERE activo = 1')->fetchColumn(),
        ], 'app');
    }

    public function platillos(): void
    {
        $pdo = Database::pdo();
        $platillos = $pdo->query(
            "SELECT p.*, GROUP_CONCAT(a.nombre ORDER BY a.nombre SEPARATOR ', ') AS alergenos
               FROM platillos p
               LEFT JOIN platillo_alergenos pa ON pa.id_platillo = p.id_platillo
               LEFT JOIN alergenos a ON a.id_alergeno = pa.id_alergeno
              GROUP BY p.id_platillo ORDER BY p.tipo, p.nombre"
        )->fetchAll();

        View::render('menus/platillos', [
            'titulo'    => 'Platillos y alérgenos',
            'subtitulo' => 'Fichas técnicas para cocina, restricciones y etiquetado.',
            'platillos' => $platillos,
            'alergenos' => $pdo->query('SELECT * FROM alergenos ORDER BY nombre')->fetchAll(),
            'menus'     => $pdo->query('SELECT id_menu, nombre FROM menus WHERE activo = 1 ORDER BY nombre')->fetchAll(),
        ], 'app');
    }

    public function guardarMenu(): void
    {
        $nombre = $this->texto('nombre');
        $tipo = $this->texto('tipo_servicio');
        $descripcion = $this->texto('descripcion');
        $precio = $this->decimal('precio_persona');
        $minimo = $this->entero('minimo_personas', 1);
        $platillos = array_map('intval', (array) ($_POST['platillos'] ?? []));
        $platillos = array_values(array_filter($platillos, static fn (int $v): bool => $v > 0));

        if ($nombre === '' || $precio < 0 || $minimo < 1) {
            $this->error('Completa nombre, precio y mínimo de personas.', '/menus');
        }
        if (!in_array($tipo, ['plato_servido', 'buffet', 'coctel', 'coffee_break', 'barra_libre'], true)) {
            $this->error('Selecciona un tipo de servicio válido.', '/menus');
        }

        $pdo = Database::pdo();
        $pdo->prepare(
            'INSERT INTO menus (nombre, tipo_servicio, descripcion, precio_persona, minimo_personas) VALUES (?, ?, ?, ?, ?)'
        )->execute([mb_substr($nombre, 0, 100), $tipo, $descripcion !== '' ? mb_substr($descripcion, 0, 255) : null, $precio, $minimo]);
        $id = (int) $pdo->lastInsertId();

        if ($platillos !== []) {
            $stmt = $pdo->prepare('INSERT INTO menu_platillos (id_menu, id_platillo) VALUES (?, ?)');
            foreach ($platillos as $idPlatillo) {
                $stmt->execute([$id, $idPlatillo]);
            }
        }

        registrar_bitacora('CREAR_MENU', 'menus', $id, $nombre . ' · ' . money($precio));
        $this->exito('Menú creado con ' . count($platillos) . ' platillos.', '/menus');
    }

    public function guardarPlatillo(): void
    {
        $nombre = $this->texto('nombre');
        $tipo = $this->texto('tipo');
        $descripcion = $this->texto('descripcion');
        $vegetariano = isset($_POST['es_vegetariano']) ? 1 : 0;
        $vegano = isset($_POST['es_vegano']) ? 1 : 0;
        $alergenos = array_map('intval', (array) ($_POST['alergenos'] ?? []));

        if ($nombre === '') {
            $this->error('El nombre del platillo es obligatorio.', '/menus/platillos');
        }
        if (!in_array($tipo, ['entrada', 'sopa', 'plato_fuerte', 'postre', 'bebida', 'snack'], true)) {
            $this->error('Selecciona un tipo de platillo válido.', '/menus/platillos');
        }

        $pdo = Database::pdo();
        $pdo->prepare(
            'INSERT INTO platillos (nombre, tipo, descripcion, es_vegetariano, es_vegano) VALUES (?, ?, ?, ?, ?)'
        )->execute([mb_substr($nombre, 0, 100), $tipo, $descripcion !== '' ? mb_substr($descripcion, 0, 255) : null, $vegetariano, $vegano]);
        $id = (int) $pdo->lastInsertId();

        if ($alergenos !== []) {
            $stmt = $pdo->prepare('INSERT IGNORE INTO platillo_alergenos (id_platillo, id_alergeno) VALUES (?, ?)');
            foreach ($alergenos as $idAlergeno) {
                $stmt->execute([$id, $idAlergeno]);
            }
        }

        registrar_bitacora('CREAR_PLATILLO', 'platillos', $id, $nombre);
        $this->exito('Platillo agregado al recetario.', '/menus/platillos');
    }

    public function produccion(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $evento = $this->evento($id);
        $pdo = Database::pdo();

        $stmt = $pdo->prepare(
            "SELECT em.*, m.nombre, m.tipo_servicio, m.precio_persona
               FROM evento_menus em JOIN menus m ON m.id_menu = em.id_menu WHERE em.id_evento = ?"
        );
        $stmt->execute([$id]);
        $menus = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            "SELECT restriccion_alimenticia, COUNT(*) total FROM invitados
              WHERE id_evento = ? AND estado_rsvp = 'confirmado'
              GROUP BY restriccion_alimenticia"
        );
        $stmt->execute([$id]);
        $restricciones = [];
        foreach ($stmt->fetchAll() as $fila) {
            $restricciones[$fila['restriccion_alimenticia']] = (int) $fila['total'];
        }

        $stmt = $pdo->prepare(
            "SELECT p.nombre, p.tipo, p.es_vegetariano, p.es_vegano, m.nombre AS menu,
                    GROUP_CONCAT(a.nombre ORDER BY a.nombre SEPARATOR ', ') AS alergenos
               FROM evento_menus em
               JOIN menus m ON m.id_menu = em.id_menu
               JOIN menu_platillos mp ON mp.id_menu = m.id_menu
               JOIN platillos p ON p.id_platillo = mp.id_platillo
               LEFT JOIN platillo_alergenos pa ON pa.id_platillo = p.id_platillo
               LEFT JOIN alergenos a ON a.id_alergeno = pa.id_alergeno
              WHERE em.id_evento = ?
              GROUP BY p.id_platillo, p.nombre, p.tipo, p.es_vegetariano, p.es_vegano, m.nombre
              ORDER BY m.nombre, p.tipo"
        );
        $stmt->execute([$id]);
        $platillos = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            "SELECT ri.*, i.nombre, i.unidad FROM reservas_insumo ri
               JOIN insumos_menu i ON i.id_insumo = ri.id_insumo
              WHERE ri.id_evento = ? AND ri.estado = 'confirmada' ORDER BY i.nombre"
        );
        $stmt->execute([$id]);
        $insumos = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            "SELECT i.nombre, i.restriccion_alimenticia, i.alergias, m.numero AS mesa
               FROM invitados i LEFT JOIN mesas m ON m.id_mesa = i.id_mesa
              WHERE i.id_evento = ? AND i.estado_rsvp = 'confirmado'
                AND (i.restriccion_alimenticia <> 'ninguna' OR COALESCE(i.alergias,'') <> '')
              ORDER BY i.nombre"
        );
        $stmt->execute([$id]);
        $casos = $stmt->fetchAll();

        $personas = 0;
        foreach ($menus as $menu) {
            $personas += (int) $menu['personas'];
        }

        View::render('menus/produccion', [
            'titulo'       => 'Hoja de producción · ' . $evento['nombre'],
            'migas'        => [['titulo' => 'Eventos', 'url' => '/eventos'], ['titulo' => $evento['nombre'], 'url' => '/eventos/' . $id], ['titulo' => 'Producción']],
            'evento'       => $evento,
            'menus'        => $menus,
            'restricciones'=> $restricciones,
            'platillos'    => $platillos,
            'insumos'      => $insumos,
            'casos'        => $casos,
            'personas'     => $personas,
        ], 'app');
    }

    public function contratar(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $evento = $this->evento($id);
        $idMenu = $this->entero('id_menu');
        $personas = $this->entero('personas');
        $notas = $this->texto('notas');

        if ($idMenu <= 0 || $personas <= 0) {
            $this->error('Selecciona un menú e indica el número de personas.', '/eventos/' . $id);
        }
        if ($personas > (int) $evento['aforo_maximo']) {
            $this->error('Las personas del menú no pueden superar el aforo máximo (' . $evento['aforo_maximo'] . ').', '/eventos/' . $id);
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT nombre, minimo_personas FROM menus WHERE id_menu = ? AND activo = 1');
        $stmt->execute([$idMenu]);
        $menu = $stmt->fetch();
        if (!$menu) {
            $this->error('El menú no está disponible.', '/eventos/' . $id);
        }
        if ($personas < (int) $menu['minimo_personas']) {
            $this->error('El menú ' . $menu['nombre'] . ' requiere un mínimo de ' . $menu['minimo_personas'] . ' personas.', '/eventos/' . $id);
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM evento_menus WHERE id_evento = ? AND id_menu = ?');
        $stmt->execute([$id, $idMenu]);
        if ((int) $stmt->fetchColumn() > 0) {
            $this->error('Ese menú ya está contratado para el evento.', '/eventos/' . $id);
        }

        $pdo->prepare('INSERT INTO evento_menus (id_evento, id_menu, personas, notas) VALUES (?, ?, ?, ?)')
            ->execute([$id, $idMenu, $personas, $notas !== '' ? mb_substr($notas, 0, 200) : null]);

        registrar_bitacora('CONTRATAR_MENU', 'evento_menus', (int) $pdo->lastInsertId(), $evento['nombre'] . ' · ' . $menu['nombre'] . ' × ' . $personas);
        $this->exito('Menú contratado para ' . $personas . ' personas.', '/eventos/' . $id);
    }
}
