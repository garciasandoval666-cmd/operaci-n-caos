<?php
declare(strict_types=1);

namespace App\Domain;

use App\Core\Auth;
use App\Core\Database;
use PDO;
use RuntimeException;

/**
 * Módulo C · cotizaciones con precio congelado, anticipos y saldos.
 */
final class Cotizaciones
{
    public static function crear(int $idEvento, array $lineas, float $descuento, ?string $vigencia, string $notas): int
    {
        if ($lineas === []) {
            throw new RuntimeException('Agrega al menos un concepto a la cotización.');
        }
        return Database::transaccion(static function (PDO $pdo) use ($idEvento, $lineas, $descuento, $vigencia, $notas): int {
            $evento = $pdo->prepare('SELECT * FROM eventos WHERE id_evento = ? FOR UPDATE');
            $evento->execute([$idEvento]);
            $ev = $evento->fetch();
            if (!$ev) {
                throw new RuntimeException('El evento no existe.');
            }
            if ($ev['estado'] === 'cancelado') {
                throw new RuntimeException('No se puede cotizar un evento cancelado.');
            }

            $subtotal = 0.0;
            $detalles = [];
            foreach ($lineas as $linea) {
                $cantidad = (float) ($linea['cantidad'] ?? 0);
                $precio = (float) ($linea['precio_unitario'] ?? 0);
                if ($cantidad <= 0 || $precio < 0) {
                    throw new RuntimeException('Cada concepto requiere cantidad positiva y precio válido.');
                }
                $importe = round($cantidad * $precio, 2);
                $subtotal += $importe;

                // El precio de catálogo se lee del servidor: el formulario no
                // puede falsear el dato con el que se audita la variación.
                $tipo = (string) ($linea['tipo_concepto'] ?? 'otro');
                $idReferencia = isset($linea['id_referencia']) && (int) $linea['id_referencia'] > 0
                    ? (int) $linea['id_referencia']
                    : null;
                $catalogo = $idReferencia !== null
                    ? self::precioCatalogo($pdo, $tipo, $idReferencia)
                    : null;
                if ($catalogo === null) {
                    $catalogo = (float) ($linea['precio_catalogo'] ?? $precio);
                }

                $detalles[] = [
                    'tipo' => $tipo,
                    'id_referencia' => $idReferencia,
                    'descripcion' => trim($linea['descripcion']),
                    'cantidad' => $cantidad,
                    'precio_catalogo' => $catalogo,
                    'precio_unitario' => $precio,
                    'subtotal' => $importe,
                ];
            }
            $descuento = max(0, min($descuento, $subtotal));
            $base = $subtotal - $descuento;
            $ivaPct = (float) configuracion('iva_porcentaje', 16);
            $impuestos = round($base * ($ivaPct / 100), 2);
            $total = round($base + $impuestos, 2);
            $anticipoPct = (float) configuracion('anticipo_porcentaje', 30);
            $anticipo = round($total * ($anticipoPct / 100), 2);

            $folio = self::folio($pdo);
            $pdo->prepare(
                "INSERT INTO cotizaciones (id_evento, folio, version, subtotal, descuento, impuestos, total, anticipo_requerido,
                                           estado, vigencia_hasta, notas, id_usuario)
                 VALUES (?, ?, 1, ?, ?, ?, ?, ?, 'enviada', ?, ?, ?)"
            )->execute([$idEvento, $folio, $subtotal, $descuento, $impuestos, $total, $anticipo, $vigencia, $notas, Auth::id() ?? 1]);
            $idCotizacion = (int) $pdo->lastInsertId();

            $detalle = $pdo->prepare(
                'INSERT INTO cotizacion_detalle (id_cotizacion, tipo_concepto, id_referencia, descripcion, cantidad, precio_catalogo, precio_unitario, subtotal)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($detalles as $d) {
                $detalle->execute([$idCotizacion, $d['tipo'], $d['id_referencia'], $d['descripcion'], $d['cantidad'], $d['precio_catalogo'], $d['precio_unitario'], $d['subtotal']]);

                // Si el ejecutivo negoció un precio distinto al de catálogo, queda rastro.
                if ($d['id_referencia'] !== null && $d['precio_catalogo'] > 0
                    && abs($d['precio_unitario'] - $d['precio_catalogo']) > 0.009) {
                    $pdo->prepare(
                        'INSERT INTO historial_precios (entidad, id_registro, precio_anterior, precio_nuevo, motivo, id_usuario)
                         VALUES (?, ?, ?, ?, ?, ?)'
                    )->execute([
                        self::entidadDe($d['tipo']),
                        (int) $d['id_referencia'],
                        $d['precio_catalogo'],
                        $d['precio_unitario'],
                        mb_substr('Ajuste comercial en cotización ' . $folio, 0, 220),
                        Auth::id(),
                    ]);
                }
            }

            if ($ev['estado'] === 'prospecto') {
                $pdo->prepare("UPDATE eventos SET estado = 'cotizado' WHERE id_evento = ?")->execute([$idEvento]);
                registrar_historial('eventos', $idEvento, 'estado', 'prospecto', 'cotizado', 'Cotización ' . $folio);
            }
            registrar_bitacora('CREAR_COTIZACION', 'cotizaciones', $idCotizacion, $folio . ' · ' . money($total));
            return $idCotizacion;
        });
    }

    public static function aceptar(int $idCotizacion, string $motivo = ''): void
    {
        Database::transaccion(static function (PDO $pdo) use ($idCotizacion, $motivo): void {
            $stmt = $pdo->prepare('SELECT * FROM cotizaciones WHERE id_cotizacion = ? FOR UPDATE');
            $stmt->execute([$idCotizacion]);
            $cot = $stmt->fetch();
            if (!$cot) {
                throw new RuntimeException('La cotización no existe.');
            }
            if (!in_array($cot['estado'], ['borrador', 'enviada'], true)) {
                throw new RuntimeException('Solo se puede aceptar una cotización enviada.');
            }
            if ($cot['vigencia_hasta'] && strtotime($cot['vigencia_hasta']) < strtotime(date('Y-m-d'))) {
                throw new RuntimeException('La cotización está vencida. Genera una nueva versión.');
            }
            $pdo->prepare("UPDATE cotizaciones SET estado = 'aceptada', congelada_en = NOW(), aceptada_en = NOW() WHERE id_cotizacion = ?")
                ->execute([$idCotizacion]);
            registrar_historial('cotizaciones', $idCotizacion, 'estado', $cot['estado'], 'aceptada', $motivo !== '' ? $motivo : 'Aceptada por el cliente');
            registrar_bitacora('ACEPTAR_COTIZACION', 'cotizaciones', $idCotizacion, $cot['folio'] . ' · precios congelados');
        });
    }

    public static function registrarPago(int $idCotizacion, float $monto, string $tipo, string $metodo, string $referencia, string $notas): void
    {
        if ($monto <= 0) {
            throw new RuntimeException('El monto debe ser mayor a cero.');
        }
        Database::transaccion(static function (PDO $pdo) use ($idCotizacion, $monto, $tipo, $metodo, $referencia, $notas): void {
            $stmt = $pdo->prepare('SELECT * FROM cotizaciones WHERE id_cotizacion = ? FOR UPDATE');
            $stmt->execute([$idCotizacion]);
            $cot = $stmt->fetch();
            if (!$cot) {
                throw new RuntimeException('La cotización no existe.');
            }
            if ($cot['estado'] !== 'aceptada') {
                throw new RuntimeException('Solo se registran pagos sobre cotizaciones aceptadas.');
            }

            $pagadoStmt = $pdo->prepare(
                "SELECT COALESCE(SUM(CASE WHEN tipo = 'devolucion' THEN -monto ELSE monto END), 0)
                   FROM pagos WHERE id_cotizacion = ? AND estado <> 'revertido'"
            );
            $pagadoStmt->execute([$idCotizacion]);
            $pagado = (float) $pagadoStmt->fetchColumn();
            $saldo = round((float) $cot['total'] - $pagado, 2);
            if ($monto > $saldo + 0.01) {
                throw new RuntimeException('El pago excede el saldo pendiente de ' . money($saldo) . '.');
            }

            $pdo->prepare(
                'INSERT INTO pagos (id_cotizacion, tipo, monto, metodo, referencia, estado, notas, id_usuario)
                 VALUES (?, ?, ?, ?, ?, \'aplicado\', ?, ?)'
            )->execute([$idCotizacion, $tipo, $monto, $metodo, $referencia, $notas, Auth::id() ?? 1]);

            $nuevoPagado = $pagado + $monto;
            if ($nuevoPagado + 0.01 >= (float) $cot['anticipo_requerido']) {
                $ev = $pdo->prepare('SELECT * FROM eventos WHERE id_evento = ? FOR UPDATE');
                $ev->execute([(int) $cot['id_evento']]);
                $evento = $ev->fetch();
                if ($evento && in_array($evento['estado'], ['prospecto', 'cotizado'], true)) {
                    $pdo->prepare("UPDATE eventos SET estado = 'confirmado' WHERE id_evento = ?")->execute([(int) $cot['id_evento']]);
                    registrar_historial('eventos', (int) $cot['id_evento'], 'estado', $evento['estado'], 'confirmado', 'Anticipo recibido');
                }
            }
            registrar_bitacora('REGISTRAR_PAGO', 'pagos', (int) $pdo->lastInsertId(), $cot['folio'] . ' · ' . money($monto) . ' · ' . $tipo);
        });
    }

    public static function cancelar(int $idCotizacion, string $motivo): void
    {
        if (trim($motivo) === '') {
            throw new RuntimeException('El motivo es obligatorio.');
        }
        Database::transaccion(static function (PDO $pdo) use ($idCotizacion, $motivo): void {
            $stmt = $pdo->prepare('SELECT * FROM cotizaciones WHERE id_cotizacion = ? FOR UPDATE');
            $stmt->execute([$idCotizacion]);
            $cot = $stmt->fetch();
            if (!$cot) {
                throw new RuntimeException('La cotización no existe.');
            }
            if ($cot['estado'] === 'cancelada') {
                throw new RuntimeException('La cotización ya está cancelada.');
            }
            $pdo->prepare("UPDATE cotizaciones SET estado = 'cancelada', motivo_cancelacion = ?, cancelada_en = NOW() WHERE id_cotizacion = ?")
                ->execute([$motivo, $idCotizacion]);
            registrar_historial('cotizaciones', $idCotizacion, 'estado', $cot['estado'], 'cancelada', $motivo);
            registrar_bitacora('CANCELAR_COTIZACION', 'cotizaciones', $idCotizacion, $motivo);
        });
    }

    /** Precio oficial del catálogo para un concepto referenciado. */
    private static function precioCatalogo(PDO $pdo, string $tipo, int $idReferencia): ?float
    {
        $consulta = match ($tipo) {
            'espacio'  => 'SELECT costo_referencia FROM espacios WHERE id_espacio = ?',
            'menu'     => 'SELECT precio_persona FROM menus WHERE id_menu = ?',
            'servicio' => 'SELECT precio_base FROM servicios WHERE id_servicio = ?',
            'recurso'  => 'SELECT costo_referencia FROM recursos WHERE id_recurso = ?',
            default    => null,
        };
        if ($consulta === null) {
            return null;
        }
        $stmt = $pdo->prepare($consulta);
        $stmt->execute([$idReferencia]);
        $valor = $stmt->fetchColumn();
        return $valor === false ? null : (float) $valor;
    }

    private static function entidadDe(string $tipo): string
    {
        return match ($tipo) {
            'espacio'  => 'espacios',
            'menu'     => 'menus',
            'servicio' => 'servicios',
            default    => 'recursos',
        };
    }

    private static function folio(PDO $pdo): string
    {
        $anio = date('Y');
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM cotizaciones WHERE folio LIKE ?');
        $stmt->execute(['COT-' . $anio . '-%']);
        $consecutivo = (int) $stmt->fetchColumn() + 1;
        do {
            $folio = 'COT-' . $anio . '-' . str_pad((string) $consecutivo, 4, '0', STR_PAD_LEFT);
            $existe = $pdo->prepare('SELECT COUNT(*) FROM cotizaciones WHERE folio = ?');
            $existe->execute([$folio]);
            $consecutivo++;
        } while ((int) $existe->fetchColumn() > 0);
        return $folio;
    }
}
