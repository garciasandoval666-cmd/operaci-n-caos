<?php
declare(strict_types=1);

namespace App\Domain;

use App\Core\Database;
use PDO;
use RuntimeException;

/**
 * Solicitudes que llegan del sitio publico.
 *
 * El formulario publico guardaba una fila en `leads` y ahi se acababa todo: no
 * habia pantalla que las mostrara y el campo `estado` nunca salia de `nuevo`.
 * El embudo del recinto se rompia en su primer eslabon, justo donde entra el
 * dinero.
 *
 * Esta clase cierra ese hueco. La pieza central es `convertir()`, que convierte
 * una solicitud en un expediente real —cliente, evento y, si procede, la
 * reserva del espacio— en una sola transaccion. Si cualquier paso falla, no
 * queda un cliente huerfano ni un evento sin fecha: no queda nada.
 */
final class Solicitudes
{
    /** Estados por los que puede pasar una solicitud. */
    public const ESTADOS = ['nuevo', 'contactado', 'convertido', 'descartado'];

    /**
     * Convierte una solicitud en cliente + evento.
     *
     * El cliente se busca por correo antes de crearlo: una persona que pide
     * presupuesto tres veces es un cliente con tres eventos, no tres clientes.
     * La columna `clientes.correo` es UNIQUE, asi que insertar a ciegas
     * fallaria de todos modos; buscar primero convierte ese choque en el
     * comportamiento correcto.
     *
     * @param  array{id_tipo_evento:int, nombre:string, fecha_inicio:string, fecha_fin:string, aforo:int, id_espacio:int|null} $datos
     * @return array{id_cliente:int, id_evento:int, cliente_nuevo:bool}
     */
    public static function convertir(int $idLead, array $datos): array
    {
        return Database::transaccion(static function (PDO $pdo) use ($idLead, $datos): array {
            // FOR UPDATE: dos personas de comercial abriendo la misma bandeja
            // no pueden convertir la misma solicitud dos veces.
            $stmt = $pdo->prepare('SELECT * FROM leads WHERE id_lead = ? FOR UPDATE');
            $stmt->execute([$idLead]);
            $lead = $stmt->fetch();

            if (!$lead) {
                throw new RuntimeException('La solicitud no existe.');
            }
            if ($lead['estado'] === 'convertido') {
                throw new RuntimeException('Esta solicitud ya se convirtió en un evento.');
            }
            if ($lead['estado'] === 'descartado') {
                throw new RuntimeException('Esta solicitud está descartada. Reábrela antes de convertirla.');
            }

            $correo = mb_strtolower(trim((string) $lead['correo']));

            $stmt = $pdo->prepare('SELECT id_cliente, estado FROM clientes WHERE correo = ? LIMIT 1');
            $stmt->execute([$correo]);
            $cliente = $stmt->fetch();

            $clienteNuevo = $cliente === false;

            if ($clienteNuevo) {
                $stmt = $pdo->prepare(
                    'INSERT INTO clientes (tipo_cliente, nombre, correo, telefono, notas)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    // Un lead corporativo casi siempre es una empresa; el resto,
                    // una persona. Es una suposicion, y por eso queda escrita en
                    // las notas del expediente en vez de darse por sentada.
                    $lead['origen'] === 'corporativo' ? 'empresa' : 'persona',
                    mb_substr(trim((string) $lead['nombre']), 0, 120),
                    mb_substr($correo, 0, 120),
                    $lead['telefono'] !== null ? mb_substr((string) $lead['telefono'], 0, 25) : null,
                    mb_substr('Alta automática desde la solicitud #' . $idLead . ' del sitio web.', 0, 500),
                ]);
                $idCliente = (int) $pdo->lastInsertId();
                registrar_bitacora('CREAR_CLIENTE', 'clientes', $idCliente, $lead['nombre'] . ' · desde solicitud web');
            } else {
                $idCliente = (int) $cliente['id_cliente'];
                // Un cliente dado de baja que vuelve a escribir esta volviendo:
                // se reactiva, porque si no el evento colgaria de un expediente
                // inactivo y no aparecria en los listados normales.
                if ($cliente['estado'] !== 'activo') {
                    $pdo->prepare('UPDATE clientes SET estado = ? WHERE id_cliente = ?')
                        ->execute(['activo', $idCliente]);
                    registrar_historial('clientes', $idCliente, 'estado', $cliente['estado'], 'activo', 'Reactivado por una solicitud nueva');
                }
            }

            $aforo = max(1, (int) $datos['aforo']);

            $idEvento = Eventos::crear([
                'id_cliente'     => $idCliente,
                'id_tipo_evento' => (int) $datos['id_tipo_evento'],
                'nombre'         => $datos['nombre'],
                'descripcion'    => (string) ($lead['mensaje'] ?? ''),
                'fecha_inicio'   => $datos['fecha_inicio'],
                'fecha_fin'      => $datos['fecha_fin'],
                'aforo_estimado' => $aforo,
                'aforo_maximo'   => $aforo,
                'estado'         => 'prospecto',
                'notas'          => 'Origen: solicitud #' . $idLead . ' recibida el '
                    . date('d/m/Y', strtotime((string) $lead['creado_en'])) . ' desde el sitio.',
                'id_espacio'     => $datos['id_espacio'] ?: null,
            ]);

            $pdo->prepare('UPDATE leads SET estado = ? WHERE id_lead = ?')
                ->execute(['convertido', $idLead]);

            registrar_bitacora(
                'CONVERTIR_SOLICITUD',
                'leads',
                $idLead,
                $lead['nombre'] . ' → evento #' . $idEvento
            );

            return [
                'id_cliente'    => $idCliente,
                'id_evento'     => $idEvento,
                'cliente_nuevo' => $clienteNuevo,
            ];
        });
    }

    /**
     * Cambia el estado de una solicitud.
     *
     * `convertido` no se pone a mano: lo escribe `convertir()` cuando de
     * verdad existe un evento detras. Dejar que alguien lo marcara desde un
     * desplegable haria que el embudo contara conversiones que no ocurrieron.
     */
    public static function cambiarEstado(int $idLead, string $estado): void
    {
        if (!in_array($estado, ['nuevo', 'contactado', 'descartado'], true)) {
            throw new RuntimeException('Ese estado no es válido para una solicitud.');
        }

        Database::transaccion(static function (PDO $pdo) use ($idLead, $estado): void {
            $stmt = $pdo->prepare('SELECT estado, nombre FROM leads WHERE id_lead = ? FOR UPDATE');
            $stmt->execute([$idLead]);
            $lead = $stmt->fetch();

            if (!$lead) {
                throw new RuntimeException('La solicitud no existe.');
            }
            if ($lead['estado'] === 'convertido') {
                throw new RuntimeException('Una solicitud ya convertida no cambia de estado: su historia está en el evento.');
            }
            if ($lead['estado'] === $estado) {
                return;
            }

            $pdo->prepare('UPDATE leads SET estado = ? WHERE id_lead = ?')->execute([$estado, $idLead]);
            registrar_historial('leads', $idLead, 'estado', $lead['estado'], $estado, 'Seguimiento comercial');
            registrar_bitacora('ESTADO_SOLICITUD', 'leads', $idLead, $lead['nombre'] . ': ' . $lead['estado'] . ' → ' . $estado);
        });
    }

    /**
     * Espacios libres para lo que pide una solicitud.
     *
     * Es lo que convierte la bandeja en una herramienta y no en una lista:
     * quien atiende ve, en la misma fila, si la fecha que pidieron esta libre
     * y en que espacios cabe su aforo, sin abrir el calendario en otra
     * pestana. Una solicitud sin fecha no se puede comprobar, y se dice.
     *
     * @return array{comprobable:bool, libres:list<array<string,mixed>>, ocupados:int, cabe:bool}
     */
    public static function disponibilidadPara(?string $fecha, ?int $aforo): array
    {
        if ($fecha === null || $fecha === '' || strtotime($fecha) === false) {
            return ['comprobable' => false, 'libres' => [], 'ocupados' => 0, 'cabe' => false];
        }

        $dia = date('Y-m-d', strtotime($fecha));

        // Ojo con el nombre: `espaciosLibres()` devuelve TODOS los espacios
        // activos, cada uno con una bandera `libre`. No es una lista de los
        // que estan libres, y darlo por hecho hacia que esta bandeja
        // anunciara como disponible un salon ya reservado.
        $espacios = Disponibilidad::espaciosLibres($dia . ' 00:00:00', $dia . ' 23:59:59');

        $libres = array_values(array_filter(
            $espacios,
            static fn (array $e): bool => (bool) $e['libre']
        ));

        $conAforo = $aforo !== null && $aforo > 0
            ? array_values(array_filter($libres, static fn (array $e): bool => (int) $e['capacidad'] >= $aforo))
            : $libres;

        return [
            'comprobable' => true,
            'libres'      => $conAforo,
            'ocupados'    => count($espacios) - count($libres),
            'cabe'        => $conAforo !== [],
        ];
    }

    /**
     * Tipo de evento que mejor encaja con lo que escribio el visitante.
     *
     * El formulario publico guarda `tipo_evento` como texto libre, asi que hay
     * que traducirlo al catalogo. Se compara sin acentos ni mayusculas para
     * que «graduacion», «Graduación» y «GRADUACION» caigan en el mismo sitio.
     * Lo que no se reconoce se deja en manos de quien atiende, con el
     * desplegable del formulario.
     */
    public static function tipoSugerido(?string $texto): ?int
    {
        if ($texto === null || trim($texto) === '') {
            return null;
        }

        $normalizar = static function (string $v): string {
            $v = mb_strtolower(trim($v));
            return strtr($v, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);
        };

        $buscado = $normalizar($texto);

        $tipos = Database::pdo()->query('SELECT id_tipo_evento, nombre FROM tipos_evento')->fetchAll();
        foreach ($tipos as $tipo) {
            $nombre = $normalizar((string) $tipo['nombre']);
            if ($nombre === $buscado || str_contains($buscado, $nombre) || str_contains($nombre, $buscado)) {
                return (int) $tipo['id_tipo_evento'];
            }
        }

        return null;
    }
}
