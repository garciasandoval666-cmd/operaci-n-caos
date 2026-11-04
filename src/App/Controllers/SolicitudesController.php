<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use App\Domain\Solicitudes;

/**
 * Bandeja de solicitudes del sitio publico.
 *
 * Es la puerta de entrada comercial del recinto: lo que el visitante envia por
 * el formulario aterriza aqui, y de aqui sale el expediente del evento.
 */
final class SolicitudesController extends Controller
{
    public function index(): void
    {
        $pdo = Database::pdo();
        $q = $this->termino();
        $estado = $this->filtro('estado', Solicitudes::ESTADOS);
        $origen = $this->filtro('origen', ['corporativo', 'bodas', 'contacto']);

        $where = [];
        $params = [];
        if ($q !== '') {
            $where[] = '(l.nombre LIKE ? OR l.correo LIKE ? OR COALESCE(l.tipo_evento, \'\') LIKE ?)';
            $like = '%' . $q . '%';
            $params = [$like, $like, $like];
        }
        if ($estado !== '') {
            $where[] = 'l.estado = ?';
            $params[] = $estado;
        }
        if ($origen !== '') {
            $where[] = 'l.origen = ?';
            $params[] = $origen;
        }
        $filtro = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';

        $total = $pdo->prepare('SELECT COUNT(*) FROM leads l' . $filtro);
        $total->execute($params);
        $total = (int) $total->fetchColumn();

        $porPagina = 15;
        // El LEFT JOIN con clientes responde en el listado una pregunta que
        // quien atiende se hace siempre: «¿este ya es cliente nuestro?».
        $stmt = $pdo->prepare(
            "SELECT l.*,
                    c.id_cliente,
                    c.nombre AS cliente_nombre,
                    (SELECT COUNT(*) FROM eventos e WHERE e.id_cliente = c.id_cliente) AS cliente_eventos
               FROM leads l
               LEFT JOIN clientes c ON c.correo = l.correo
               {$filtro}
              ORDER BY FIELD(l.estado, 'nuevo', 'contactado', 'convertido', 'descartado'),
                       l.creado_en DESC
              LIMIT {$porPagina} OFFSET " . $this->offset($porPagina)
        );
        $stmt->execute($params);
        $solicitudes = $stmt->fetchAll();

        // La disponibilidad se calcula aqui y no en la vista: la vista no debe
        // consultar la base, y asi una sola pasada deja cada fila lista.
        foreach ($solicitudes as $i => $fila) {
            $solicitudes[$i]['disponibilidad'] = Solicitudes::disponibilidadPara(
                $fila['fecha_estimada'],
                $fila['aforo'] !== null ? (int) $fila['aforo'] : null
            );
            $solicitudes[$i]['tipo_sugerido'] = Solicitudes::tipoSugerido($fila['tipo_evento']);
        }

        $conteos = [];
        foreach ($pdo->query('SELECT estado, COUNT(*) AS n FROM leads GROUP BY estado')->fetchAll() as $fila) {
            $conteos[(string) $fila['estado']] = (int) $fila['n'];
        }

        View::render('solicitudes/index', [
            'titulo'      => 'Solicitudes',
            'subtitulo'   => 'Lo que llega del sitio público. De aquí sale el expediente del evento.',
            'solicitudes' => $solicitudes,
            'conteos'     => $conteos,
            'total'       => $total,
            'porPagina'   => $porPagina,
            'q'           => $q,
            'estado'      => $estado,
            'origen'      => $origen,
            'tipos'       => $pdo->query('SELECT id_tipo_evento, nombre FROM tipos_evento ORDER BY nombre')->fetchAll(),
            'espacios'    => $pdo->query("SELECT id_espacio, nombre, capacidad FROM espacios WHERE estado = 'activo' ORDER BY capacidad DESC")->fetchAll(),
        ], 'app');
    }

    public function estado(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $nuevo = $this->texto('estado');

        try {
            Solicitudes::cambiarEstado($id, $nuevo);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage(), '/solicitudes');
        }

        $etiquetas = ['nuevo' => 'sin atender', 'contactado' => 'contactada', 'descartado' => 'descartada'];
        $this->exito('Solicitud marcada como ' . ($etiquetas[$nuevo] ?? $nuevo) . '.', '/solicitudes');
    }

    /**
     * Crea cliente, evento y —si se eligio— la reserva del espacio.
     *
     * Quien atiende confirma los datos antes de convertir: el visitante
     * escribio una fecha estimada y un tipo en texto libre, y eso no puede
     * entrar al calendario sin que una persona lo valide.
     */
    public function convertir(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        $inicio = $this->fechaHora('fecha_inicio');
        $fin = $this->fechaHora('fecha_fin');
        $nombre = $this->texto('nombre');
        $tipo = $this->entero('id_tipo_evento');
        $aforo = $this->entero('aforo');
        $espacio = $this->entero('id_espacio');

        if ($inicio === null || $fin === null) {
            $this->error('Confirma la fecha de inicio y la de fin antes de convertir la solicitud.', '/solicitudes');
        }
        if ($nombre === '') {
            $this->error('El evento necesita un nombre.', '/solicitudes');
        }
        if ($tipo <= 0) {
            $this->error('Elige el tipo de evento.', '/solicitudes');
        }

        try {
            $resultado = Solicitudes::convertir($id, [
                'id_tipo_evento' => $tipo,
                'nombre'         => $nombre,
                'fecha_inicio'   => $inicio,
                'fecha_fin'      => $fin,
                'aforo'          => $aforo,
                'id_espacio'     => $espacio > 0 ? $espacio : null,
            ]);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage(), '/solicitudes');
        }

        $aviso = $resultado['cliente_nuevo']
            ? 'Cliente y evento creados desde la solicitud.'
            : 'Evento creado y enlazado con el cliente que ya existía.';

        if ($espacio > 0) {
            $aviso .= ' El espacio quedó reservado.';
        }

        $this->exito($aviso . ' Ya puedes cotizarlo.', '/eventos/' . $resultado['id_evento']);
    }
}
