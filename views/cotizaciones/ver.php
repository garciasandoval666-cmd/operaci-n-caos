<?php
/** @var array $cotizacion */
/** @var array $detalle */
/** @var array $pagos */
/** @var array $historial */
/** @var array $saldo */
/** @var float $iva */

$tiposPago = [
    'anticipo'     => 'Anticipo',
    'abono'        => 'Abono',
    'saldo'        => 'Liquidación',
    'devolucion'   => 'Devolución',
    'penalizacion' => 'Penalización',
];
$pagado = (float) ($saldo['pagado'] ?? 0);
$porPagar = (float) ($saldo['saldo'] ?? 0);
$total = (float) $cotizacion['total'];
$avance = $total > 0 ? min(100, round(($pagado / $total) * 100, 1)) : 0;
$vencida = $cotizacion['estado'] === 'enviada' && !empty($cotizacion['vigencia_hasta']) && strtotime($cotizacion['vigencia_hasta']) < strtotime(date('Y-m-d'));
$puedeGestionar = puede(['administrador', 'comercial', 'finanzas']);
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Módulo C · Cotización <?= e($cotizacion['folio']) ?></p>
        <h1>Propuesta <?= e($cotizacion['folio']) ?></h1>
        <p class="page-head__sub">Versión <?= (int) $cotizacion['version'] ?> · <?= e($cotizacion['estado_evento'] === 'cancelado' ? 'evento cancelado' : 'evento ' . str_replace('_', ' ', $cotizacion['estado_evento'])) ?>. Generada por <?= e($cotizacion['usuario'] ?? 'el equipo') ?>.</p>
    </div>
    <div class="page-head__acciones no-imprimir">
        <a class="btn btn--fantasma" href="<?= e(url('/cotizaciones')) ?>"><?= icono('atras') ?> Cotizaciones</a>
        <button class="btn btn--claro" type="button" data-imprimir><?= icono('imprimir') ?> Imprimir / PDF</button>
        <?php if ($cotizacion['estado'] === 'aceptada' && $porPagar > 0.01): ?>
            <a class="btn btn--lima" href="<?= e(url('/pagos?q=' . urlencode($cotizacion['folio']))) ?>"><?= icono('pagos') ?> Registrar pago</a>
        <?php endif; ?>
    </div>
</div>

<div style="max-width:940px;margin:0 auto">
    <article class="doc">
        <div class="doc__head">
            <div class="doc__marca">
                <span class="monograma">CE</span>
                <div>
                    <b>ConectaEventos</b>
                    <span>Recinto y productora de eventos</span>
                </div>
            </div>
            <div class="doc__folio">
                <span>Cotización</span>
                <b><?= e($cotizacion['folio']) ?></b>
                <span style="margin-top:6px">Versión <?= (int) $cotizacion['version'] ?> · <?= e(fecha($cotizacion['creado_en'], 'd M Y')) ?></span>
            </div>
        </div>

        <div class="doc__partes">
            <div class="doc__parte">
                <span>Cliente</span>
                <b><?= e($cotizacion['razon_social'] ?: $cotizacion['cliente']) ?></b>
                <?php if ($cotizacion['razon_social'] && $cotizacion['razon_social'] !== $cotizacion['cliente']): ?><p>Contacto: <?= e($cotizacion['cliente']) ?></p><?php endif; ?>
                <p><?= e($cotizacion['cliente_correo'] ?? '') ?></p>
                <p><?= e($cotizacion['cliente_telefono'] ?? '') ?></p>
            </div>
            <div class="doc__parte">
                <span>Evento</span>
                <b><?= e($cotizacion['evento']) ?></b>
                <p><?= e(fecha_larga($cotizacion['fecha_inicio'])) ?></p>
                <p><?= e(date('H:i', strtotime($cotizacion['fecha_inicio']))) ?> h · <?= e(fecha($cotizacion['fecha_fin'], 'd M Y')) ?></p>
                <p>Aforo máximo: <?= number_format((int) $cotizacion['aforo_maximo']) ?> personas</p>
            </div>
        </div>

        <div class="doc__tabla">
        <table class="doc__lineas">
            <thead>
                <tr>
                    <th style="width:52%">Concepto</th>
                    <th class="num">Cantidad</th>
                    <th class="num">Precio unitario</th>
                    <th class="num">Importe</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($detalle as $linea): ?>
                <tr>
                    <td>
                        <b><?= e($linea['descripcion']) ?></b>
                        <span class="texto-muted texto-xs" style="display:block;text-transform:uppercase;letter-spacing:.08em"><?= e(['espacio' => 'Espacio', 'menu' => 'Menú', 'servicio' => 'Servicio', 'recurso' => 'Recurso', 'otro' => 'Otro'][$linea['tipo_concepto']] ?? ucfirst((string) $linea['tipo_concepto'])) ?></span>
                    </td>
                    <td class="num"><?= number_format((float) $linea['cantidad'], 2) ?></td>
                    <td class="num"><?= e(money($linea['precio_unitario'])) ?></td>
                    <td class="num"><b><?= e(money($linea['subtotal'])) ?></b></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <div class="doc__totales">
            <div class="doc__fila"><span>Subtotal</span><b><?= e(money($cotizacion['subtotal'])) ?></b></div>
            <?php if ((float) $cotizacion['descuento'] > 0): ?>
                <div class="doc__fila"><span>Descuento</span><b>− <?= e(money($cotizacion['descuento'])) ?></b></div>
            <?php endif; ?>
            <div class="doc__fila"><span>IVA (<?= e((string) $iva) ?>%)</span><b><?= e(money($cotizacion['impuestos'])) ?></b></div>
            <div class="doc__fila doc__fila--total"><span>Total</span><b><?= e(money($cotizacion['total'])) ?></b></div>
            <div class="doc__fila"><span>Anticipo requerido</span><b><?= e(money($cotizacion['anticipo_requerido'])) ?></b></div>
            <div class="doc__fila"><span>Pagado</span><b><?= e(money($pagado)) ?></b></div>
            <div class="doc__fila"><span>Saldo</span><b class="<?= $porPagar > 0.01 ? 'texto-aviso' : 'texto-ok' ?>"><?= e(money($porPagar)) ?></b></div>
        </div>

        <div class="doc__sello">
            <?= icono('candado') ?>
            <?= $cotizacion['estado'] === 'aceptada' ? 'Precios congelados al aceptar' : 'Precios sujetos a vigencia' ?>
        </div>

        <?php if (!empty($cotizacion['notas'])): ?>
            <div class="doc__notas">
                <b>Notas</b>
                <p class="mt-1"><?= nl2br(e($cotizacion['notas'])) ?></p>
            </div>
        <?php endif; ?>

        <div class="doc__notas">
            <b>Condiciones</b>
            <p class="mt-1">
                Vigencia: <?= e(fecha($cotizacion['vigencia_hasta'], 'd M Y')) ?>.
                Anticipo del <?= (string) configuracion('anticipo_porcentaje', 30) ?>% para confirmar la fecha.
                Los cambios de menú se cierran <?= (string) configuracion('dias_limite_menu', 7) ?> días antes del evento.
                Cancelaciones sujetas a la política vigente y a la penalización registrada.
            </p>
        </div>

        <div class="doc__firma">
            <div>ConectaEventos · Coordinación</div>
            <div><?= e($cotizacion['cliente']) ?> · Cliente</div>
        </div>

        <p class="aviso-print">Documento generado por ConectaEventos · <?= e(date('d/m/Y H:i')) ?> · <?= e($cotizacion['folio']) ?></p>
    </article>

    <div class="grid grid-2 mt-3 no-imprimir">
        <section class="card">
            <div class="card__head">
                <div><h2>Cobranza de la cotización</h2><p><?= number_format($avance, 1) ?>% pagado · <?= count($pagos) ?> movimiento<?= count($pagos) === 1 ? '' : 's' ?></p></div>
                <?php if ($cotizacion['estado'] === 'aceptada' && $puedeGestionar): ?>
                    <a class="btn btn--fantasma btn--sm" href="<?= e(url('/pagos?q=' . urlencode($cotizacion['folio']))) ?>"><?= icono('pagos') ?> Cobrar</a>
                <?php endif; ?>
            </div>
            <div class="card__body">
                <div class="progreso progreso--ok mb-2"><span style="width:<?= $avance ?>%"></span></div>
                <?php if ($pagos === []): ?>
                    <p class="texto-sm texto-muted">Aún no hay pagos registrados para esta cotización.</p>
                <?php else: ?>
                    <div class="tabla-wrap">
                        <table class="tabla tabla--compacta">
                            <thead><tr><th>Fecha</th><th>Tipo</th><th>Método</th><th class="num">Monto</th><th>Estado</th></tr></thead>
                            <tbody>
                            <?php foreach ($pagos as $pago): ?>
                                <tr>
                                    <td class="texto-sm"><?= e(fecha($pago['fecha_pago'], 'd M Y')) ?></td>
                                    <td><?= e($tiposPago[$pago['tipo']] ?? ucfirst($pago['tipo'])) ?></td>
                                    <td class="texto-sm"><?= e(metodo_pago_etiqueta($pago['metodo'])) ?></td>
                                    <td class="num"><b><?= e(money($pago['monto'])) ?></b></td>
                                    <td><?= badge_estado($pago['estado']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card__head"><div><h2>Acciones de la propuesta</h2><p>El estado actual es <?= e($cotizacion['estado']) ?>.</p></div></div>
            <div class="card__body">
                <?php if ($cotizacion['estado'] === 'aceptada'): ?>
                    <div class="aviso-inline aviso-inline--info"><?= icono('check') ?><span>Aceptada el <?= e(fecha_hora($cotizacion['aceptada_en'])) ?>. Los precios quedaron congelados y el evento puede confirmarse.</span></div>
                <?php elseif ($cotizacion['estado'] === 'cancelada'): ?>
                    <div class="aviso-inline aviso-inline--error"><?= icono('x') ?><span>Cancelada el <?= e(fecha_hora($cotizacion['cancelada_en'])) ?>. Motivo: <?= e($cotizacion['motivo_cancelacion'] ?? 'sin motivo registrado') ?>.</span></div>
                <?php elseif ($cotizacion['estado'] === 'rechazada'): ?>
                    <div class="aviso-inline"><?= icono('alerta') ?><span>El cliente rechazó esta versión. Genera una nueva propuesta desde el evento.</span></div>
                <?php else: ?>
                    <?php if ($vencida): ?>
                        <div class="aviso-inline aviso-inline--error mb-2"><?= icono('alerta') ?><span>La vigencia terminó el <?= e(fecha($cotizacion['vigencia_hasta'], 'd M Y')) ?>. Confirma con el cliente antes de aceptar.</span></div>
                    <?php endif; ?>
                    <?php if ($puedeGestionar): ?>
                        <form class="formulario" method="post" action="<?= e(url('/cotizaciones/' . (int) $cotizacion['id_cotizacion'] . '/aceptar')) ?>">
                            <?= csrf_field() ?>
                            <div class="campo">
                                <label for="motivo_aceptar">Nota de aceptación</label>
                                <input class="input" type="text" id="motivo_aceptar" name="motivo" maxlength="220" placeholder="Aprobada por el cliente vía correo…">
                            </div>
                            <button class="btn btn--lima btn--bloque" type="submit"><?= icono('check') ?> Aceptar y congelar precios</button>
                        </form>
                        <details class="mt-2">
                            <summary class="btn btn--peligro btn--bloque"><?= icono('x') ?> Cancelar cotización</summary>
                            <form class="formulario mt-2" method="post" action="<?= e(url('/cotizaciones/' . (int) $cotizacion['id_cotizacion'] . '/cancelar')) ?>">
                                <?= csrf_field() ?>
                                <div class="campo">
                                    <label for="motivo_cancelar">Motivo de cancelación <span class="campo__req">*</span></label>
                                    <input class="input" type="text" id="motivo_cancelar" name="motivo" maxlength="220" required placeholder="El cliente eligió otra fecha…">
                                </div>
                                <button class="btn btn--peligro" type="submit">Confirmar cancelación</button>
                            </form>
                        </details>
                    <?php else: ?>
                        <p class="texto-sm texto-muted">Tu rol puede consultar la propuesta, pero no aceptarla ni cancelarla.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <section class="card mt-3 no-imprimir">
        <div class="card__head"><div><h2>Historial de cambios</h2><p>Cada ajuste de precio o estado queda registrado.</p></div></div>
        <?php if ($historial === []): ?>
            <div class="tabla__vacia">Sin cambios posteriores a la creación.</div>
        <?php else: ?>
            <div class="tabla-wrap">
                <table class="tabla tabla--compacta">
                    <thead><tr><th>Fecha</th><th>Campo</th><th>Antes</th><th>Después</th><th>Motivo</th><th>Usuario</th></tr></thead>
                    <tbody>
                    <?php foreach ($historial as $cambio): ?>
                        <tr>
                            <td class="texto-sm ahorarap"><?= e(fecha_hora($cambio['fecha_cambio'])) ?></td>
                            <td class="texto-sm"><b><?= e($cambio['campo']) ?></b></td>
                            <td class="texto-sm texto-muted"><?= e($cambio['valor_anterior'] ?? '—') ?></td>
                            <td class="texto-sm"><?= e($cambio['valor_nuevo'] ?? '—') ?></td>
                            <td class="texto-sm"><?= e($cambio['motivo'] ?? '—') ?></td>
                            <td class="texto-sm texto-muted"><?= e($cambio['usuario'] ?? 'sistema') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
