<?php
/** @var array $kpis */
/** @var array $proximos */
/** @var array $pipeline */
/** @var array $cobranza */
/** @var array $alertas */
/** @var array $actividad */
/** @var array $meses */
/** @var int $maxMes */
/** @var array $tipos */
/** @var int $maxTipo */
/** @var string $hoy */
$etapas = [
    'prospecto'      => 'Prospecto',
    'cotizado'       => 'Cotizado',
    'confirmado'     => 'Confirmado',
    'en_preparacion' => 'Preparación',
    'en_curso'       => 'En curso',
    'realizado'      => 'Realizado',
    'cerrado'        => 'Cerrado',
];
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Panel de operación · <?= e(fecha_larga($hoy)) ?></p>
        <h1>Todo el recinto, en una vista.</h1>
        <p class="page-head__sub">Eventos, espacios, cocina, cobranza y alertas conectados por la misma base de datos.</p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--claro" href="<?= e(url('/reportes')) ?>"><?= icono('reportes') ?> Reportes</a>
        <?php if (puede(['administrador', 'comercial', 'coordinador'])): ?>
            <a class="btn btn--lima" href="<?= e(url('/eventos/crear')) ?>"><?= icono('mas') ?> Nuevo evento</a>
        <?php endif; ?>
    </div>
</div>

<div class="kpis">
    <article class="kpi kpi--oscura">
        <p class="kpi__top"><?= icono('calendario') ?> Eventos activos</p>
        <p class="kpi__valor"><?= (int) $kpis['activos'] ?></p>
        <p class="kpi__nota"><b><?= (int) $kpis['proximos'] ?></b> en los próximos 30 días</p>
    </article>
    <article class="kpi">
        <p class="kpi__top"><?= icono('espacios') ?> Ocupación 30 días</p>
        <p class="kpi__valor"><?= number_format((float) $kpis['ocupacion'], 1) ?>%</p>
        <?php if ((float) $kpis['ocupacion'] > 0): ?>
            <div class="progreso progreso--lima mt-2"><span data-barra="<?= (float) $kpis['ocupacion'] ?>" style="width:0"></span></div>
            <p class="kpi__nota">Días con bloqueo sobre el total del periodo</p>
        <?php else: ?>
            <p class="kpi__nota">Sin días bloqueados en los próximos 30</p>
        <?php endif; ?>
    </article>
    <article class="kpi">
        <p class="kpi__top"><?= icono('pagos') ?> Ingresos del mes</p>
        <p class="kpi__valor"><?= e(money_corto($kpis['ingresos_mes'])) ?></p>
        <p class="kpi__nota">Pagos aplicados y no revertidos</p>
    </article>
    <article class="kpi">
        <p class="kpi__top"><?= icono('cotizacion') ?> Por cobrar</p>
        <p class="kpi__valor"><?= e(money_corto($kpis['por_cobrar'])) ?></p>
        <p class="kpi__nota">Saldo de cotizaciones aceptadas</p>
    </article>
</div>

<div class="grid grid-4 mb-3">
    <div class="mini-kpi"><?= icono('invitados') ?><div><b><?= (int) $kpis['confirmados'] ?></b><span>Invitados confirmados</span></div></div>
    <div class="mini-kpi"><?= icono('destello') ?><div><b><?= (int) $kpis['leads'] ?></b><span>Leads nuevos del sitio</span></div></div>
    <div class="mini-kpi"><?= icono('cubiertos') ?><div><b><?= count($proximos) ?></b><span>Eventos próximos listados</span></div></div>
    <div class="mini-kpi"><?= icono('alerta') ?><div><b><?= count($alertas) ?></b><span>Alertas operativas</span></div></div>
</div>

<div class="grid-detalle">
    <div class="stack">
        <section class="card">
            <div class="card__head">
                <div>
                    <h2>Próximos eventos</h2>
                    <p>Ordenados por fecha. Abre el expediente para operar el evento completo.</p>
                </div>
                <div class="card__head__acciones">
                    <a class="btn btn--claro btn--sm" href="<?= e(url('/eventos')) ?>">Ver todos <?= icono('flecha') ?></a>
                </div>
            </div>
            <?php if ($proximos === []): ?>
                <div class="empty">
                    <div class="empty__icono"><?= icono('calendario') ?></div>
                    <h3>Sin eventos próximos</h3>
                    <p>Cuando registres un evento con fecha futura aparecerá aquí con su avance operativo.</p>
                    <a class="btn btn--lima" href="<?= e(url('/eventos/crear')) ?>"><?= icono('mas') ?> Crear evento</a>
                </div>
            <?php else: ?>
                <div class="tabla-wrap">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>Evento</th>
                                <th>Fecha</th>
                                <th>Espacio</th>
                                <th class="num">Confirmados</th>
                                <th class="num">Saldo</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($proximos as $evento): ?>
                            <tr>
                                <td>
                                    <a class="tabla__principal" href="<?= e(url('/eventos/' . (int) $evento['id_evento'])) ?>">
                                        <span class="tabla__avatar"><?= icono(tipo_evento_icono($evento['tipo'])) ?></span>
                                        <span>
                                            <b><?= e($evento['nombre']) ?></b>
                                            <span><?= e($evento['cliente']) ?> · <?= e($evento['tipo']) ?></span>
                                        </span>
                                    </a>
                                </td>
                                <td class="ahorarap">
                                    <b><?= e(fecha($evento['fecha_inicio'], 'd M Y')) ?></b>
                                    <span class="texto-muted texto-xs"><?= e(date('H:i', strtotime($evento['fecha_inicio']))) ?> h</span>
                                </td>
                                <td><?= $evento['espacio'] ? e($evento['espacio']) : '<span class="texto-aviso">Sin espacio</span>' ?></td>
                                <td class="num"><?= (int) $evento['confirmados'] ?> / <?= (int) $evento['aforo_maximo'] ?></td>
                                <td class="num"><?= e(money($evento['saldo'])) ?></td>
                                <td><?= badge_estado($evento['estado']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="card">
            <div class="card__head">
                <div>
                    <h2>Embudo de eventos</h2>
                    <p>Del primer contacto al cierre financiero, sin saltos de estado.</p>
                </div>
            </div>
            <div class="card__body">
                <div class="pipeline">
                    <?php foreach ($etapas as $clave => $etiqueta): ?>
                        <div class="pipeline__etapa">
                            <b><?= (int) ($pipeline[$clave] ?? 0) ?></b>
                            <span><?= e($etiqueta) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="fila fila--entre mt-3">
                    <span class="texto-muted texto-sm">Cancelados: <b><?= (int) ($pipeline['cancelado'] ?? 0) ?></b></span>
                    <a class="btn btn--fantasma btn--sm" href="<?= e(url('/eventos')) ?>">Abrir tablero <?= icono('flecha') ?></a>
                </div>
            </div>
        </section>

        <div class="grid grid-2">
            <section class="card">
                <div class="card__head">
                    <div><h2>Eventos por mes</h2><p>Próximos seis meses.</p></div>
                </div>
                <div class="card__body">
                    <div class="chart">
                        <?php foreach ($meses as $mes): ?>
                            <div class="chart__col">
                                <div class="chart__barra"><span style="height: <?= max(3, round(((int) $mes['total'] / max(1, $maxMes)) * 100)) ?>%"></span></div>
                                <b><?= e($mes['etiqueta']) ?></b>
                                <small><?= (int) $mes['total'] ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card__head">
                    <div><h2>Mezcla de tipos</h2><p>Dónde se concentra la operación.</p></div>
                </div>
                <div class="card__body">
                    <?php if ($tipos === []): ?>
                        <p class="texto-muted texto-sm">Aún no hay eventos registrados.</p>
                    <?php else: ?>
                        <div class="distribucion-lista">
                            <?php foreach ($tipos as $tipo): ?>
                                <div class="distribucion-lista__fila">
                                    <span><?= e($tipo['nombre']) ?></span>
                                    <div class="progreso"><span data-barra="<?= round(((int) $tipo['total'] / max(1, $maxTipo)) * 100) ?>" style="width:0"></span></div>
                                    <b><?= (int) $tipo['total'] ?></b>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>

    <aside class="grid-detalle__lado">
        <section class="card">
            <div class="card__head">
                <div><h2>Alertas operativas</h2><p>Lo que requiere atención hoy.</p></div>
            </div>
            <div class="card__body">
                <?php if ($alertas === []): ?>
                    <div class="aviso-inline"><?= icono('check') ?> <span>Sin alertas. Stock, menús, espacios y vigencias en orden.</span></div>
                <?php else: ?>
                    <?php foreach (array_slice($alertas, 0, 8) as $alerta): ?>
                        <a class="alerta-item" href="<?= e(url($alerta['url'])) ?>">
                            <span class="alerta-item__icono<?= $alerta['tono'] === 'error' ? ' alerta-item__icono--error' : ($alerta['tono'] === 'info' ? ' alerta-item__icono--info' : '') ?>"><?= icono($alerta['icono']) ?></span>
                            <span>
                                <b><?= e($alerta['titulo']) ?></b>
                                <span><?= e($alerta['detalle']) ?></span>
                            </span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card__head">
                <div><h2>Cobranza pendiente</h2><p>Cotizaciones aceptadas con saldo.</p></div>
            </div>
            <div class="card__body card__body--sin">
                <?php if ($cobranza === []): ?>
                    <div class="card__body"><div class="aviso-inline"><?= icono('check') ?> <span>Todo cobrado. No hay saldos abiertos.</span></div></div>
                <?php else: ?>
                    <table class="tabla tabla--compacta">
                        <tbody>
                        <?php foreach ($cobranza as $cuenta): ?>
                            <tr>
                                <td colspan="2">
                                    <a class="cobro-fila" href="<?= e(url('/cotizaciones/' . (int) $cuenta['id_cotizacion'])) ?>">
                                        <span>
                                            <b class="texto-sm"><?= e($cuenta['evento']) ?></b>
                                            <span class="texto-muted texto-xs"><?= e($cuenta['folio']) ?> · <?= e(fecha($cuenta['fecha_inicio'], 'd M')) ?></span>
                                        </span>
                                        <b class="cobro-fila__monto"><?= e(money($cuenta['saldo'])) ?></b>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="card__foot">
                        <a class="btn btn--claro btn--sm btn--bloque" href="<?= e(url('/pagos')) ?>">Ir a cobranza <?= icono('flecha') ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card__head">
                <div><h2>Actividad reciente</h2><p>Últimos movimientos registrados.</p></div>
            </div>
            <div class="card__body">
                <div class="timeline">
                    <?php foreach ($actividad as $movimiento): ?>
                        <div class="timeline__item">
                            <span class="timeline__punto"><?= icono('destello') ?></span>
                            <div class="timeline__cuerpo">
                                <b><?= e(str_replace('_', ' ', $movimiento['accion'])) ?></b>
                                <span><?= e($movimiento['usuario'] ?? 'Sistema') ?> · <?= e(fecha_hora($movimiento['fecha'])) ?></span>
                                <?php if ($movimiento['detalle'] !== ''): ?>
                                    <p><?= e(recortar($movimiento['detalle'], 90)) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </aside>
</div>
