<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Flash;
use App\Core\View;
use App\Domain\Disponibilidad;

final class SitioController extends Controller
{
    public function inicio(): void
    {
        $pdo = Database::pdo();
        $mes = (string) ($_GET['mes'] ?? date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
            $mes = date('Y-m');
        }
        $calendario = $this->calendario($mes);

        $proximos = $pdo->query(
            "SELECT e.id_evento, e.nombre, e.fecha_inicio, e.fecha_fin, e.aforo_maximo,
                    c.nombre AS cliente, t.nombre AS tipo, t.icono,
                    (SELECT esp.nombre FROM reservas_espacio re
                       JOIN espacios esp ON esp.id_espacio = re.id_espacio
                      WHERE re.id_evento = e.id_evento AND re.estado = 'confirmada'
                      ORDER BY re.id_reserva_espacio LIMIT 1) AS espacio
               FROM eventos e
               JOIN clientes c ON c.id_cliente = e.id_cliente
               JOIN tipos_evento t ON t.id_tipo_evento = e.id_tipo_evento
              WHERE e.fecha_inicio >= NOW() AND e.estado IN ('confirmado','en_preparacion')
              ORDER BY e.fecha_inicio LIMIT 4"
        )->fetchAll();

        $espacios = $pdo->query("SELECT * FROM espacios WHERE estado = 'activo' ORDER BY capacidad DESC")->fetchAll();

        // El recinto no vive de un solo tipo de evento: el catalogo completo
        // es el eje del sitio. Se acompana del numero de eventos realizados de
        // cada tipo, que es un dato real y no una promesa de folleto.
        $tipos = $pdo->query(
            "SELECT t.id_tipo_evento, t.nombre, t.descripcion, t.icono,
                    COUNT(e.id_evento) AS realizados
               FROM tipos_evento t
               LEFT JOIN eventos e
                      ON e.id_tipo_evento = t.id_tipo_evento
                     AND e.estado IN ('realizado', 'cerrado')
              GROUP BY t.id_tipo_evento, t.nombre, t.descripcion, t.icono
              ORDER BY t.id_tipo_evento"
        )->fetchAll();

        $stats = [
            'eventos'    => (int) $pdo->query("SELECT COUNT(*) FROM eventos WHERE estado IN ('realizado','cerrado')")->fetchColumn(),
            'clientes'   => (int) $pdo->query('SELECT COUNT(*) FROM clientes')->fetchColumn(),
            'espacios'   => (int) $pdo->query('SELECT COUNT(*) FROM espacios')->fetchColumn(),
            'proveedores'=> (int) $pdo->query('SELECT COUNT(*) FROM proveedores')->fetchColumn(),
            'aforo'      => (int) $pdo->query('SELECT COALESCE(MAX(capacidad),0) FROM espacios')->fetchColumn(),
        ];

        View::render('sitio/inicio', [
            'titulo'     => 'Un recinto para cada celebración',
            'calendario' => $calendario,
            'proximos'   => $proximos,
            'espacios'   => $espacios,
            'tipos'      => $tipos,
            'stats'      => $stats,
            'mes'        => $mes,
        ], 'sitio');
    }

    /**
     * Pagina de historias del recinto.
     *
     * La ruta conserva el nombre `/boda` porque hay enlaces publicados que
     * apuntan ahi, pero el contenido ya no es de un solo tipo de evento: el
     * recinto hace bodas, congresos, graduaciones y conciertos, y la pagina
     * muestra lo que de verdad ha pasado en el, sea del tipo que sea.
     */
    public function boda(): void
    {
        $pdo = Database::pdo();

        $menus = $pdo->query(
            "SELECT * FROM menus WHERE activo = 1 ORDER BY precio_persona DESC LIMIT 3"
        )->fetchAll();

        $espacios = $pdo->query(
            "SELECT * FROM espacios WHERE estado = 'activo' ORDER BY capacidad ASC"
        )->fetchAll();

        // Eventos ya celebrados, uno por tipo como maximo, para que la pagina
        // muestre variedad y no cuatro bodas seguidas.
        $historias = $pdo->query(
            "SELECT e.id_evento, e.nombre, e.fecha_inicio, e.aforo_maximo,
                    t.nombre AS tipo, t.icono,
                    (SELECT COUNT(*) FROM invitados i
                      WHERE i.id_evento = e.id_evento AND i.estado_rsvp = 'confirmado') AS confirmados,
                    (SELECT esp.nombre FROM reservas_espacio re
                       JOIN espacios esp ON esp.id_espacio = re.id_espacio
                      WHERE re.id_evento = e.id_evento AND re.estado = 'confirmada'
                      ORDER BY re.id_reserva_espacio LIMIT 1) AS espacio
               FROM eventos e
               JOIN tipos_evento t ON t.id_tipo_evento = e.id_tipo_evento
               JOIN (SELECT id_tipo_evento, MAX(fecha_inicio) AS ultima
                       FROM eventos
                      WHERE estado IN ('realizado', 'cerrado', 'confirmado', 'en_preparacion')
                      GROUP BY id_tipo_evento) u
                 ON u.id_tipo_evento = e.id_tipo_evento AND u.ultima = e.fecha_inicio
              ORDER BY e.fecha_inicio DESC
              LIMIT 6"
        )->fetchAll();

        View::render('sitio/boda', [
            'titulo'    => 'Historias del recinto',
            'menus'     => $menus,
            'espacios'  => $espacios,
            'historias' => $historias,
        ], 'sitio');
    }

    public function contacto(): void
    {
        $nombre = $this->texto('nombre');
        $correo = mb_strtolower($this->texto('correo'));
        $telefono = $this->texto('telefono');
        $tipo = $this->texto('tipo_evento');
        $fecha = $this->fecha('fecha_estimada');
        $aforo = $this->entero('aforo');
        $mensaje = $this->texto('mensaje');
        $origen = (string) ($_POST['origen'] ?? 'contacto');
        if (!in_array($origen, ['corporativo', 'bodas', 'contacto'], true)) {
            $origen = 'contacto';
        }
        $destino = $origen === 'bodas' ? '/boda#contacto' : '/#contacto';

        // Trampa de miel: el campo va oculto por CSS y una persona nunca lo
        // rellena, pero los robots que envian todos los campos del formulario
        // si. Se responde con el mismo mensaje de exito para no darles pistas,
        // y no se guarda nada.
        if (trim((string) ($_POST['sitio_web'] ?? '')) !== '') {
            Flash::guardar('ok', 'Recibimos tu solicitud. Te contactaremos en menos de 24 horas habiles.');
            redirect($destino);
        }

        // Limite por IP: el formulario es publico y cada envio inserta un lead
        // y una fila de bitacora, asi que sin tope es una forma barata de
        // inflar ambas tablas.
        if ($this->solicitudesRecientes() >= 5) {
            Flash::guardar('aviso', 'Ya recibimos varias solicitudes desde esta conexion. Espera unos minutos antes de enviar otra.');
            redirect($destino);
        }

        $errores = [];
        if ($nombre === '' || mb_strlen($nombre) < 3) {
            $errores['nombre'] = 'Escribe tu nombre completo.';
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $errores['correo'] = 'Necesitamos un correo válido para responderte.';
        }
        if ($mensaje !== '' && mb_strlen($mensaje) > 1000) {
            $errores['mensaje'] = 'El mensaje no puede exceder 1000 caracteres.';
        }

        if ($errores !== []) {
            recordar_viejos($_POST);
            guardar_errores($errores);
            Flash::guardar('error', 'Revisa los datos marcados e inténtalo de nuevo.');
            redirect($destino);
        }

        $stmt = Database::pdo()->prepare(
            'INSERT INTO leads (nombre, correo, telefono, tipo_evento, fecha_estimada, aforo, mensaje, origen)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            mb_substr($nombre, 0, 120),
            mb_substr($correo, 0, 120),
            $telefono !== '' ? mb_substr($telefono, 0, 25) : null,
            $tipo !== '' ? mb_substr($tipo, 0, 60) : null,
            $fecha,
            $aforo > 0 ? $aforo : null,
            $mensaje !== '' ? mb_substr($mensaje, 0, 1000) : null,
            $origen,
        ]);
        registrar_bitacora('LEAD_SITIO', 'leads', (int) Database::pdo()->lastInsertId(), $nombre . ' · ' . $tipo);

        limpiar_viejos();
        Flash::guardar('ok', 'Recibimos tu solicitud. Te contactaremos en menos de 24 horas hábiles.');
        redirect($destino);
    }

    /** Solicitudes de contacto enviadas desde esta IP en la ultima hora. */
    private function solicitudesRecientes(): int
    {
        try {
            $stmt = Database::pdo()->prepare(
                "SELECT COUNT(*) FROM bitacora
                  WHERE accion = 'LEAD_SITIO'
                    AND ip = ?
                    AND fecha >= (NOW() - INTERVAL 1 HOUR)"
            );
            $stmt->execute([$_SERVER['REMOTE_ADDR'] ?? '']);
            return (int) $stmt->fetchColumn();
        } catch (\Throwable) {
            return 0;
        }
    }

    public function disponibilidad(): void
    {
        $inicio = $this->fecha('inicio') ?? date('Y-m-d');
        $fin = $this->rangoAcotado($inicio, $this->fecha('fin') ?? date('Y-m-d', strtotime($inicio . ' +1 day')));
        if (strtotime($fin) < strtotime($inicio)) {
            $fin = $inicio;
        }

        $espacios = Disponibilidad::espaciosLibres($inicio, $fin);
        $recursos = Disponibilidad::recursosDisponibles($inicio, $fin);

        $this->json([
            'ok'      => true,
            'inicio'  => $inicio,
            'fin'     => $fin,
            'dias'    => count(Disponibilidad::dias($inicio, $fin)),
            'espacios'=> array_map(static fn (array $e): array => [
                'id'       => (int) $e['id_espacio'],
                'nombre'   => $e['nombre'],
                'tipo'     => $e['tipo'],
                'capacidad'=> (int) $e['capacidad'],
                'libre'    => (bool) $e['libre'],
            ], $espacios),
            'recursos'=> [
                'catalogo'    => count($recursos),
                'disponibles' => count(array_filter($recursos, static fn (array $r): bool => (int) $r['disponible'] > 0)),
            ],
        ]);
    }

    public function salud(): void
    {
        $pdo = Database::pdo();
        $basico = [
            'ok'      => true,
            'app'     => 'ConectaEventos',
            'version' => APP_VERSION,
            'hora'    => date('c'),
        ];

        // El detalle de infraestructura solo se expone a administración.
        if (!Auth::puede('administrador')) {
            $this->json($basico);
            return;
        }

        $this->json($basico + [
            'entorno'  => (string) env('APP_ENV', 'production'),
            'base'     => [
                'motor'    => $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION),
                'eventos'  => (int) $pdo->query('SELECT COUNT(*) FROM eventos')->fetchColumn(),
                'usuarios' => (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn(),
            ],
        ]);
    }

    /**
     * Construye las celdas del calendario público.
     */
    private function calendario(string $mes): array
    {
        $inicio = new \DateTimeImmutable($mes . '-01');
        $fin = $inicio->modify('last day of this month');
        $pdo = Database::pdo();

        $stmt = $pdo->prepare(
            "SELECT d.fecha, e.nombre, esp.nombre AS espacio
               FROM reserva_espacio_dias d
               JOIN reservas_espacio re ON re.id_reserva_espacio = d.id_reserva_espacio
               JOIN espacios esp ON esp.id_espacio = d.id_espacio
               JOIN eventos e ON e.id_evento = re.id_evento
              WHERE d.activa = 1 AND d.fecha BETWEEN ? AND ?
              ORDER BY d.fecha"
        );
        $stmt->execute([$inicio->format('Y-m-d'), $fin->format('Y-m-d')]);
        $ocupados = [];
        foreach ($stmt->fetchAll() as $fila) {
            $ocupados[$fila['fecha']] = $fila['nombre'];
        }

        $celdas = [];
        $desplazamiento = (int) $inicio->format('N') - 1;
        for ($i = 0; $i < $desplazamiento; $i++) {
            $celdas[] = ['dia' => null, 'ocupado' => false, 'hoy' => false, 'evento' => null];
        }
        $hoy = date('Y-m-d');
        for ($dia = 1; $dia <= (int) $fin->format('j'); $dia++) {
            $fecha = $inicio->setDate((int) $inicio->format('Y'), (int) $inicio->format('n'), $dia)->format('Y-m-d');
            $celdas[] = [
                'dia'     => $dia,
                'fecha'   => $fecha,
                'ocupado' => isset($ocupados[$fecha]),
                'hoy'     => $fecha === $hoy,
                'evento'  => $ocupados[$fecha] ?? null,
            ];
        }

        return [
            'mes'      => $mes,
            'etiqueta' => fecha($inicio->format('Y-m-d'), 'F Y'),
            'anterior' => $inicio->modify('-1 month')->format('Y-m'),
            'siguiente'=> $inicio->modify('+1 month')->format('Y-m'),
            'celdas'   => $celdas,
            'ocupados' => count($ocupados),
        ];
    }
}
