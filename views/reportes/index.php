<?php
/** @var array $kpis */
/** @var array $meses */
/** @var float $maxIngreso */
/** @var array $estados */
/** @var array $clientes */
/** @var float $maxCliente */
/** @var array $tipos */
/** @var int $maxTipo */
/** @var array $pagos */

$estadosEtiquetas = [
    'prospecto'      => 'Prospecto',
    'cotizado'       => 'Cotizado',
    'confirmado'     => 'Confirmado',
    'en_preparacion' => 'En preparación',
    'en_curso'       => 'En curso',
    'realizado'      => 'Realizado',
    'cerrado'        => 'Cerrado',
    'cancelado'      => 'Cancelado',
];
$totalEventos = max(1, array_sum($estados));
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Módulo D · Inteligencia operativa</p>
        <h1>Reportes</h1>
        <p class="page-head__sub"><?= e($subtitulo ?? '') ?></p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--claro" href="<?= e(url('/reportes/ocupacion')) ?>"><?= icono('espacios') ?> Ocupación</a>
        <a class="btn btn--claro" href="<?= e(url('/reportes/produccion')) ?>"><?= icono('cubiertos') ?> Producción</a>
        <a class="btn btn--fantasma" href="<?= e(url('/reportes/auditoria')) ?>"><?= icono('bitacora') ?> Auditoría</a>
        <a class="btn btn--fantasma" href="<?= e(url('/reportes/inconsistencias')) ?>"><?= icono('alerta') ?> Inconsistencias</a>
    </div>
</div>

<div class="kpis">
    <div class="kpi kpi--oscura">
        <div class="kpi__top"><?= icono('grafico') ?> Ingresos del año</div>
        <p class="kpi__valor"><?= e(money_corto($kpis['ingresos'] ?? 0)) ?></p>
        <p class="kpi__nota"><?= e(money($kpis['ingresos'] ?? 0)) ?> cobrados</p>
    </div>
    <div class="kpi kpi--lima">
        <div class="kpi__top"><?= icono('reloj') ?> Por cobrar</div>
        <p class="kpi__valor"><?= e(money_corto($kpis['por_cobrar'] ?? 0)) ?></p>
        <p class="kpi__nota">cartera de aceptadas</p>
    </div>
    <div class="kpi">
        <div class="kpi__top"><?= icono('cotizacion') ?> Ticket promedio</div>
        <p class="kpi__valor"><?= e(money_corto($kpis['ticket'] ?? 0)) ?></p>
        <p class="kpi__nota"><?= (int) ($kpis['aceptadas'] ?? 0) ?> cotizaciones aceptadas</p>
    </div>
    <div class="kpi">
        <div class="kpi__top"><?= icono('destello') ?> Conversión</div>
        <p class="kpi__valor"><?= number_format((float) ($kpis['conversion'] ?? 0), 1) ?>%</p>
        <p class="kpi__nota"><?= (int) ($kpis['leads'] ?? 0) ?> leads nuevos por atender</p>
    </div>
</div>

<div class="grid grid-2 mb-2">
    <section class="card">
        <div class="card__head">
            <div><h2>Ingresos por mes</h2><p>Últimos 6 meses, sin devoluciones.</p></div>
            <span class="badge badge--neutro">Máximo <?= e(money_corto($maxIngreso)) ?></span>
        </div>
        <div class="card__body">
            <div class="chart">
                <?php foreach ($meses as $mes): ?>
                    <?php $alto = $maxIngreso > 0 ? max(2, round(((float) $mes['ingresos'] / $maxIngreso) * 100)) : 2; ?>
                    <div class="chart__col">
                        <div class="chart__barra" title="<?= e(money($mes['ingresos'])) ?>"><span style="height:<?= $alto ?>%"></span></div>
                        <b><?= e($mes['etiqueta']) ?></b>
                        <small><?= (int) $mes['eventos'] ?> ev.</small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="card__head"><div><h2>Eventos por estado</h2><p>Foto del pipeline completo.</p></div><span class="badge badge--neutro"><?= array_sum($estados) ?> eventos</span></div>
        <div class="card__body">
            <div class="distribucion-lista">
                <?php foreach ($estadosEtiquetas as $clave => $etiqueta): ?>
                    <?php $cantidad = (int) ($estados[$clave] ?? 0); ?>
                    <div class="distribucion-lista__fila">
                        <span><?= e($etiqueta) ?></span>
                        <div class="progreso"><span style="width:<?= porcentaje($cantidad, $totalEventos) ?>%"></span></div>
                        <b><?= $cantidad ?></b>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</div>

<div class="grid grid-3 mb-2">
    <section class="card">
        <div class="card__head"><div><h2>Top clientes</h2><p>Por monto cobrado.</p></div></div>
        <div class="card__body">
            <?php if ($clientes === []): ?>
                <p class="texto-sm texto-muted">Sin pagos registrados todavía.</p>
            <?php else: ?>
                <div class="distribucion-lista">
                    <?php foreach ($clientes as $cliente): ?>
                        <div class="distribucion-lista__fila">
                            <span title="<?= e($cliente['nombre']) ?>"><?= e(recortar($cliente['nombre'], 18)) ?></span>
                            <div class="progreso progreso--lima"><span style="width:<?= min(100, round(((float) $cliente['pagado'] / max(1, $maxCliente)) * 100)) ?>%"></span></div>
                            <b><?= e(money_corto($cliente['pagado'])) ?></b>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="card">
        <div class="card__head"><div><h2>Tipos de evento</h2><p>Mezcla del recinto.</p></div></div>
        <div class="card__body">
            <?php if ($tipos === []): ?>
                <p class="texto-sm texto-muted">Sin eventos registrados.</p>
            <?php else: ?>
                <div class="distribucion-lista">
                    <?php foreach ($tipos as $tipo): ?>
                        <div class="distribucion-lista__fila">
                            <span><?= e($tipo['nombre']) ?></span>
                            <div class="progreso"><span style="width:<?= min(100, round(((int) $tipo['total'] / max(1, $maxTipo)) * 100)) ?>%"></span></div>
                            <b><?= (int) $tipo['total'] ?></b>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="card">
        <div class="card__head"><div><h2>Salud del negocio</h2><p>Indicadores de un vistazo.</p></div></div>
        <div class="card__body">
            <div class="lista-datos">
                <div class="lista-datos__fila"><span>Eventos totales</span><b><?= (int) ($kpis['eventos'] ?? 0) ?></b></div>
                <div class="lista-datos__fila"><span>Eventos activos</span><b><?= (int) ($kpis['activos'] ?? 0) ?></b></div>
                <div class="lista-datos__fila"><span>Cotizaciones</span><b><?= (int) ($kpis['cotizaciones'] ?? 0) ?></b></div>
                <div class="lista-datos__fila"><span>Aceptadas</span><b><?= (int) ($kpis['aceptadas'] ?? 0) ?></b></div>
                <div class="lista-datos__fila"><span>Leads nuevos</span><b><?= (int) ($kpis['leads'] ?? 0) ?></b></div>
            </div>
            <div class="aviso-inline mt-3"><?= icono('alerta') ?><span>El reporte de inconsistencias cruza los cuatro módulos y enlaza cada hallazgo al registro que debe corregirse.</span></div>
            <a class="btn btn--peligro btn--bloque mt-2" href="<?= e(url('/reportes/inconsistencias')) ?>"><?= icono('alerta') ?> Revisar inconsistencias</a>
        </div>
    </section>
</div>

<section class="card">
    <div class="card__head">
        <div><h2>Últimos pagos</h2><p>Movimientos recientes de la cartera.</p></div>
        <a class="btn btn--fantasma btn--sm" href="<?= e(url('/pagos')) ?>">Ver cobranza</a>
    </div>
    <?php if ($pagos === []): ?>
        <div class="tabla__vacia">Sin pagos registrados.</div>
    <?php else: ?>
        <div class="tabla-wrap">
            <table class="tabla tabla--compacta">
                <thead><tr><th>Fecha</th><th>Folio</th><th>Evento</th><th>Cliente</th><th>Tipo</th><th>Método</th><th class="num">Monto</th></tr></thead>
                <tbody>
                <?php foreach ($pagos as $pago): ?>
                    <tr>
                        <td class="texto-sm"><?= e(fecha_hora($pago['fecha_pago'])) ?></td>
                        <td class="mono"><?= e($pago['folio']) ?></td>
                        <td><?= e($pago['evento']) ?></td>
                        <td class="texto-sm"><?= e($pago['cliente']) ?></td>
                        <td class="texto-sm"><?= e(ucfirst($pago['tipo'])) ?></td>
                        <td class="texto-sm"><?= e(metodo_pago_etiqueta($pago['metodo'])) ?></td>
                        <td class="num"><b><?= e(money($pago['monto'])) ?></b></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
