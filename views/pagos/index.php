<?php
/** @var array $pagos */
/** @var array $pendientes */
/** @var array $cuentas */
/** @var array $stats */
/** @var int $total */
/** @var string $q */
/** @var string $metodo */
/** @var string|null $desde */
/** @var string|null $hasta */
/** @var int $porPagina */

$tiposPago = [
    'anticipo'     => 'Anticipo',
    'abono'        => 'Abono',
    'saldo'        => 'Liquidación',
    'devolucion'   => 'Devolución',
    'penalizacion' => 'Penalización',
];
$metodos = [
    'efectivo'      => 'Efectivo',
    'transferencia' => 'Transferencia',
    'tarjeta'       => 'Tarjeta',
    'cheque'        => 'Cheque',
];
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Módulo C · Cobranza</p>
        <h1>Pagos y cobranza</h1>
        <p class="page-head__sub"><?= e($subtitulo ?? '') ?></p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--claro" href="<?= e(url('/cotizaciones')) ?>"><?= icono('cotizacion') ?> Cotizaciones</a>
        <a class="btn btn--fantasma" href="<?= e(url('/reportes')) ?>"><?= icono('reportes') ?> Reportes</a>
    </div>
</div>

<div class="kpis">
    <div class="kpi kpi--oscura">
        <div class="kpi__top"><?= icono('pagos') ?> Recaudado histórico</div>
        <p class="kpi__valor"><?= e(money_corto($stats['total'] ?? 0)) ?></p>
        <p class="kpi__nota"><?= (int) ($stats['movimientos'] ?? 0) ?> movimientos registrados</p>
    </div>
    <div class="kpi kpi--lima">
        <div class="kpi__top"><?= icono('calendario') ?> Este mes</div>
        <p class="kpi__valor"><?= e(money_corto($stats['mes'] ?? 0)) ?></p>
        <p class="kpi__nota"><?= e(fecha(date('Y-m-01'), 'F Y')) ?></p>
    </div>
    <div class="kpi">
        <div class="kpi__top"><?= icono('reloj') ?> Por cobrar</div>
        <p class="kpi__valor"><?= e(money_corto($stats['por_cobrar'] ?? 0)) ?></p>
        <p class="kpi__nota">saldo de cotizaciones aceptadas</p>
    </div>
    <div class="kpi">
        <div class="kpi__top"><?= icono('alerta') ?> Cuentas pendientes</div>
        <p class="kpi__valor"><?= count($pendientes) ?></p>
        <p class="kpi__nota">eventos con saldo abierto</p>
    </div>
</div>

<form class="toolbar" method="get" action="<?= e(url('/pagos')) ?>">
    <div class="campo campo--buscar">
        <label for="q">Buscar</label>
        <input class="input" type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Folio, evento, cliente o referencia">
    </div>
    <div class="campo">
        <label for="metodo">Método</label>
        <select class="select" id="metodo" name="metodo" data-autoenviar>
            <option value="">Todos</option>
            <?php foreach ($metodos as $clave => $etiqueta): ?>
                <option value="<?= e($clave) ?>" <?= $metodo === $clave ? 'selected' : '' ?>><?= e($etiqueta) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="campo campo--periodo">
        <label for="desde">Periodo</label>
        <div class="campo__rango">
            <input class="input" type="date" id="desde" name="desde" aria-label="Desde" value="<?= e($desde ?? '') ?>">
            <input class="input" type="date" id="hasta" name="hasta" aria-label="Hasta" value="<?= e($hasta ?? '') ?>">
        </div>
    </div>
    <div class="toolbar__acciones">
        <button class="btn btn--primario" type="submit"><?= icono('filtrar') ?> Filtrar</button>
        <a class="btn btn--fantasma" href="<?= e(url('/pagos')) ?>">Limpiar</a>
        <a class="btn btn--fantasma" href="<?= e(url('/reportes/exportar?tipo=pagos')) ?>"><?= icono('descargar') ?> CSV</a>
    </div>
</form>

<div class="grid-detalle">
    <div class="stack">
        <section class="card">
            <div class="card__head">
                <div><h2>Movimientos de cobranza</h2><p><?= (int) $total ?> pago<?= (int) $total === 1 ? '' : 's' ?> en el filtro actual.</p></div>
            </div>
            <?php if ($pagos === []): ?>
                <div class="empty">
                    <div class="empty__icono"><?= icono('pagos') ?></div>
                    <h3>Sin pagos registrados</h3>
                    <p>Cuando aceptes una cotización podrás registrar el anticipo desde aquí.</p>
                </div>
            <?php else: ?>
                <div class="tabla-wrap">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Folio</th>
                                <th>Evento / cliente</th>
                                <th>Tipo</th>
                                <th>Método</th>
                                <th>Referencia</th>
                                <th class="num">Monto</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($pagos as $pago): ?>
                            <tr>
                                <td class="texto-sm ahorarap"><?= e(fecha_hora($pago['fecha_pago'])) ?></td>
                                <td class="ahorarap"><a class="mono" href="<?= e(url('/cotizaciones/' . (int) $pago['id_cotizacion'])) ?>"><?= e($pago['folio']) ?></a></td>
                                <td>
                                    <a class="tabla__principal" href="<?= e(url('/eventos/' . (int) $pago['id_evento'])) ?>">
                                        <span class="tabla__avatar"><?= icono('pagos') ?></span>
                                        <span><b><?= e($pago['evento']) ?></b><span><?= e($pago['cliente']) ?></span></span>
                                    </a>
                                </td>
                                <td><?= e($tiposPago[$pago['tipo']] ?? ucfirst($pago['tipo'])) ?></td>
                                <td class="texto-sm"><?= e($metodos[$pago['metodo']] ?? ucfirst($pago['metodo'])) ?></td>
                                <td class="texto-sm texto-muted"><?= $pago['referencia'] ? e($pago['referencia']) : '—' ?></td>
                                <td class="num"><b class="<?= $pago['tipo'] === 'devolucion' ? 'texto-error' : '' ?>"><?= $pago['tipo'] === 'devolucion' ? '− ' : '' ?><?= e(money($pago['monto'])) ?></b></td>
                                <td><?= badge_estado($pago['estado']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?= paginacion((int) $total, (int) $porPagina) ?>
            <?php endif; ?>
        </section>

        <section class="card">
            <div class="card__head"><div><h2>Cuentas con saldo pendiente</h2><p>Ordenadas por fecha del evento: primero lo que está por ocurrir.</p></div></div>
            <?php if ($pendientes === []): ?>
                <div class="tabla__vacia">Todas las cotizaciones aceptadas están liquidadas.</div>
            <?php else: ?>
                <div class="tabla-wrap">
                    <table class="tabla tabla--compacta">
                        <thead><tr><th>Folio</th><th>Evento</th><th>Cliente</th><th>Fecha</th><th class="num">Total</th><th class="num">Pagado</th><th class="num">Saldo</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($pendientes as $cuenta): ?>
                            <tr>
                                <td class="mono"><?= e($cuenta['folio']) ?></td>
                                <td><b><?= e($cuenta['evento']) ?></b></td>
                                <td class="texto-sm"><?= e($cuenta['cliente']) ?></td>
                                <td class="texto-sm"><?= e(fecha($cuenta['fecha_inicio'], 'd M Y')) ?></td>
                                <td class="num"><?= e(money($cuenta['total'])) ?></td>
                                <td class="num"><?= e(money($cuenta['pagado'])) ?></td>
                                <td class="num"><b class="texto-aviso"><?= e(money($cuenta['saldo'])) ?></b></td>
                                <td><div class="acciones"><a class="btn btn--fantasma btn--xs" href="<?= e(url('/cotizaciones/' . (int) $cuenta['id_cotizacion'])) ?>"><?= icono('ver') ?> Abrir</a></div></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <aside class="grid-detalle__lado">
        <?php if (puede(['administrador', 'finanzas'])): ?>
            <section class="card">
                <div class="card__head"><div><h2>Registrar pago</h2><p>Solo cotizaciones aceptadas.</p></div></div>
                <div class="card__body">
                    <form class="formulario" method="post" action="<?= e(url('/pagos')) ?>">
                        <?= csrf_field() ?>
                        <div class="campo">
                            <label for="id_cotizacion">Cuenta <span class="campo__req">*</span></label>
                            <select class="select" id="id_cotizacion" name="id_cotizacion" required>
                                <option value="">Selecciona una cotización…</option>
                                <?php foreach ($cuentas as $cuenta): ?>
                                    <option value="<?= (int) $cuenta['id_cotizacion'] ?>">
                                        <?= e($cuenta['folio']) ?> · <?= e($cuenta['evento']) ?> · saldo <?= e(money($cuenta['saldo'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="campo__ayuda"><?= count($cuentas) ?> cotizaciones aceptadas disponibles.</p>
                        </div>
                        <div class="campo-fila">
                            <div class="campo">
                                <label for="monto">Monto <span class="campo__req">*</span></label>
                                <input class="input" type="number" id="monto" name="monto" min="0.01" step="0.01" required>
                            </div>
                            <div class="campo">
                                <label for="tipo_pago">Tipo <span class="campo__req">*</span></label>
                                <select class="select" id="tipo_pago" name="tipo" required>
                                    <option value="anticipo">Anticipo</option>
                                    <option value="abono" selected>Abono</option>
                                    <option value="saldo">Liquidación</option>
                                    <option value="devolucion">Devolución</option>
                                    <option value="penalizacion">Penalización</option>
                                </select>
                            </div>
                        </div>
                        <div class="campo">
                            <label for="metodo_pago">Método <span class="campo__req">*</span></label>
                            <select class="select" id="metodo_pago" name="metodo" required>
                                <?php foreach ($metodos as $clave => $etiqueta): ?>
                                    <option value="<?= e($clave) ?>" <?= $clave === 'transferencia' ? 'selected' : '' ?>><?= e($etiqueta) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="campo">
                            <label for="referencia">Referencia</label>
                            <input class="input" type="text" id="referencia" name="referencia" maxlength="80" placeholder="Folio SPEI, autorización, cheque…">
                        </div>
                        <div class="campo">
                            <label for="notas_pago">Notas</label>
                            <textarea class="textarea" id="notas_pago" name="notas" maxlength="300" rows="2"></textarea>
                        </div>
                        <button class="btn btn--lima btn--bloque" type="submit"><?= icono('pagos') ?> Registrar movimiento</button>
                    </form>
                </div>
            </section>
        <?php endif; ?>

        <section class="card">
            <div class="card__head"><div><h2>Reglas de cobranza</h2><p>Contra errores y dobles cobros.</p></div></div>
            <div class="card__body">
                <div class="timeline">
                    <div class="timeline__item timeline__item--ok"><span class="timeline__punto"><?= icono('candado') ?></span><div class="timeline__cuerpo"><b>Solo cotizaciones aceptadas</b><span>No se cobra sobre borradores ni propuestas vencidas.</span></div></div>
                    <div class="timeline__item timeline__item--ok"><span class="timeline__punto"><?= icono('check') ?></span><div class="timeline__cuerpo"><b>El monto no supera el saldo</b><span>El dominio valida antes de insertar.</span></div></div>
                    <div class="timeline__item timeline__item--aviso"><span class="timeline__punto"><?= icono('alerta') ?></span><div class="timeline__cuerpo"><b>Devoluciones con signo</b><span>Restan del pagado y quedan en el historial.</span></div></div>
                </div>
            </div>
        </section>
    </aside>
</div>
