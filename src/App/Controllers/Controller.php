<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Flash;
use App\Core\View;

/**
 * Utilidades compartidas por los controladores.
 */
abstract class Controller
{
    protected function texto(string $campo, string $defecto = ''): string
    {
        return trim((string) ($_POST[$campo] ?? $defecto));
    }

    protected function entero(string $campo, int $defecto = 0): int
    {
        $valor = $_POST[$campo] ?? $defecto;
        return is_numeric($valor) ? (int) $valor : $defecto;
    }

    protected function decimal(string $campo, float $defecto = 0.0): float
    {
        $valor = str_replace([',', '$', ' '], '', (string) ($_POST[$campo] ?? ''));
        return is_numeric($valor) ? (float) $valor : $defecto;
    }

    /**
     * Lee un campo de fecha de POST o, si no está ahí, de GET.
     *
     * Las pantallas de disponibilidad (`/sitio/disponibilidad`,
     * `/espacios/disponibilidad`, `/recursos/disponibilidad`) son GET y pasaban
     * `inicio` y `fin` por la URL, pero estos ayudantes solo miraban `$_POST`:
     * el rango que elegía el usuario se descartaba en silencio y siempre se
     * mostraba «hoy y mañana».
     */
    private function valorFecha(string $campo): string
    {
        $valor = $_POST[$campo] ?? $_GET[$campo] ?? '';
        return trim((string) (is_array($valor) ? '' : $valor));
    }

    protected function fechaHora(string $campo): ?string
    {
        $valor = $this->valorFecha($campo);
        if ($valor === '') {
            return null;
        }
        $ts = strtotime($valor);
        return $ts !== false ? date('Y-m-d H:i:s', $ts) : null;
    }

    protected function fecha(string $campo): ?string
    {
        $valor = $this->valorFecha($campo);
        if ($valor === '') {
            return null;
        }
        $ts = strtotime($valor);
        return $ts !== false ? date('Y-m-d', $ts) : null;
    }

    /**
     * Acota un rango de fechas a un máximo de días.
     *
     * Ahora que `inicio` y `fin` sí se leen de la URL, un rango enorme haría
     * que las vistas de disponibilidad expandieran decenas de miles de días.
     * El tope lo convierte en un cálculo acotado en vez de una forma fácil de
     * tumbar la página.
     */
    protected function rangoAcotado(string $inicio, string $fin, int $maxDias = 366): string
    {
        $tsInicio = strtotime($inicio);
        $tsFin = strtotime($fin);
        if ($tsInicio === false || $tsFin === false || $tsFin < $tsInicio) {
            return date('Y-m-d', strtotime($inicio . ' +1 day'));
        }
        $dias = (int) floor(($tsFin - $tsInicio) / 86400);
        if ($dias > $maxDias) {
            return date('Y-m-d', $tsInicio + ($maxDias * 86400));
        }
        return date('Y-m-d', $tsFin);
    }

    protected function exito(string $mensaje, string $ruta): never
    {
        Flash::guardar('ok', $mensaje);
        redirect($ruta);
    }

    protected function aviso(string $mensaje, string $ruta): never
    {
        Flash::guardar('aviso', $mensaje);
        redirect($ruta);
    }

    protected function error(string $mensaje, string $ruta): never
    {
        Flash::guardar('error', $mensaje);
        redirect($ruta);
    }

    protected function noEncontrado(string $mensaje = 'No encontramos el registro solicitado.'): never
    {
        http_response_code(404);
        View::render('errores/404', [
            'titulo' => 'Registro no encontrado',
            'detalle' => $mensaje,
        ], 'app');
        exit;
    }

    protected function evento(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT e.*, c.nombre AS cliente, c.correo AS cliente_correo, c.telefono AS cliente_telefono,
                    c.tipo_cliente, t.nombre AS tipo_evento, t.icono AS tipo_icono,
                    t.requiere_menu, t.requiere_mesas, t.requiere_rsvp
               FROM eventos e
               JOIN clientes c ON c.id_cliente = e.id_cliente
               JOIN tipos_evento t ON t.id_tipo_evento = e.id_tipo_evento
              WHERE e.id_evento = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $evento = $stmt->fetch();
        if (!$evento) {
            $this->noEncontrado('El evento no existe o fue eliminado.');
        }
        return $evento;
    }

    protected function offset(int $porPagina = 15): int
    {
        return (pagina_actual() - 1) * $porPagina;
    }

    protected function termino(): string
    {
        return trim((string) ($_GET['q'] ?? ''));
    }

    protected function filtro(string $campo, array $permitidos, string $defecto = ''): string
    {
        $valor = (string) ($_GET[$campo] ?? '');
        return in_array($valor, $permitidos, true) ? $valor : $defecto;
    }

    protected function json(array $datos, int $codigo = 200): never
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function soloPost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->error('La acción requiere enviar el formulario.', '/panel');
        }
    }
}
