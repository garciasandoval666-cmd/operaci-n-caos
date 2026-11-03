<?php
/** @var array $insumo */
/** @var array $movimientos */
/** @var array $reservas */

$fisico = (float) $insumo['stock_fisico'];
$comprometido = (float) $insumo['stock_comprometido'];
$disponible = (float) $insumo['disponible'];
$nivel = $fisico > 0 ? min(100, round(($disponible / $fisico) * 100)) : 0;
$entradas = array_sum(array_map(static fn (array $m): float => $m['tipo'] === 'entrada' ? (float) $m['cantidad'] : 0.0, $movimientos));
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Módulo B · Kardex de insumo</p>
        <h1><?= e($insumo['nombre']) ?></h1>
        <p class="page-head__sub"><?= e(ucfirst(str_replace('_', ' ', $insumo['categoria']))) ?> · se controla por <?= e($insumo['unidad']) ?>. Cada movimiento queda firmado por usuario y fecha.</p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--fantasma" href="<?= e(url('/insumos')) ?>"><?= icono('atras') ?> Insumos</a>
        <?php if (puede(['administrador', 'banquetes'])): ?>
            <a class="btn btn--lima" href="<?= e(url('/insumos')) ?>"><?= icono('mas') ?> Nuevo movimiento</a>
        <?php endif; ?>
    </div>
</div>

<div class="kpis">
    <div class="kpi kpi--oscura">
        <div class="kpi__top"><?= icono('insumos') ?> Stock físico</div>
        <p class="kpi__valor"><?= number_format($fisico, 1) ?></p>
        <p class="kpi__nota"><?= e($insumo['unidad']) ?> en bodega</p>
    </div>
    <div class="kpi">
        <div class="kpi__top"><?= icono('candado') ?> Comprometido</div>
        <p class="kpi__valor"><?= number_format($comprometido, 1) ?></p>
        <p class="kpi__nota">apartado para eventos</p>
    </div>
    <div class="kpi <?= $disponible <= (float) $insumo['stock_minimo'] ? '' : 'kpi--lima' ?>">
        <div class="kpi__top"><?= icono('paquete') ?> Disponible</div>
        <p class="kpi__valor"><?= number_format($disponible, 1) ?></p>
        <p class="kpi__nota">mínimo <?= number_format((float) $insumo['stock_minimo'], 1) ?></p>
    </div>
    <div class="kpi">
        <div class="kpi__top"><?= icono('reloj') ?> Movimientos</div>
        <p class="kpi__valor"><?= count($movimientos) ?></p>
        <p class="kpi__nota"><?= number_format($entradas, 1) ?> sumados por entradas</p>
    </div>
</div>

<div class="grid-detalle">
    <div class="stack">
        <section class="card">
            <div class="card__head">
                <div><h2>Movimientos del kardex</h2><p>Orden cronológico inverso: el último movimiento aparece primero.</p></div>
                <span class="badge badge--<?= $disponible <= (float) $insumo['stock_minimo'] ? 'aviso' : 'ok' ?>"><?= $disponible <= (float) $insumo['stock_minimo'] ? 'Reponer' : 'Stock saludable' ?></span>
            </div>
            <?php if ($movimientos === []): ?>
                <div class="empty">
                    <div class="empty__icono"><?= icono('bitacora') ?></div>
                    <h3>Sin movimientos</h3>
                    <p>El stock inicial se registró sin movimiento. Registra una entrada o un ajuste para iniciar el kardex.</p>
                </div>
            <?php else: ?>
                <div class="tabla-wrap">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th class="num">Cantidad</th>
                                <th class="num">Físico resultante</th>
                                <th class="num">Comprometido</th>
                                <th>Evento</th>
                                <th>Motivo</th>
                                <th>Usuario</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($movimientos as $movimiento): ?>
                            <tr>
                                <td class="texto-sm ahorarap"><?= e(fecha_hora($movimiento['fecha_movimiento'])) ?></td>
                                <td><?= badge_estado($movimiento['tipo']) ?></td>
                                <td class="num"><?= number_format((float) $movimiento['cantidad'], 1) ?></td>
                                <td class="num"><b><?= number_format((float) $movimiento['stock_fisico_resultante'], 1) ?></b></td>
                                <td class="num"><?= number_format((float) $movimiento['stock_comprometido_resultante'], 1) ?></td>
                                <td class="texto-sm"><?= $movimiento['evento'] ? e($movimiento['evento']) : '<span class="texto-muted">—</span>' ?></td>
                                <td class="texto-sm"><?= e(recortar($movimiento['motivo'], 52)) ?></td>
                                <td class="texto-sm texto-muted"><?= $movimiento['usuario'] ? e($movimiento['usuario']) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="card">
            <div class="card__head"><div><h2>Apartados del insumo</h2><p>Eventos que comprometen este stock.</p></div></div>
            <?php if ($reservas === []): ?>
                <div class="tabla__vacia">Este insumo no tiene apartados registrados.</div>
            <?php else: ?>
                <div class="tabla-wrap">
                    <table class="tabla tabla--compacta">
                        <thead><tr><th>Evento</th><th class="num">Cantidad</th><th>Estado</th><th>Fecha del evento</th><th>Registrado</th></tr></thead>
                        <tbody>
                        <?php foreach ($reservas as $reserva): ?>
                            <tr>
                                <td><b><?= e($reserva['evento']) ?></b></td>
                                <td class="num"><?= number_format((float) $reserva['cantidad'], 1) ?> <?= e($insumo['unidad']) ?></td>
                                <td><?= badge_estado($reserva['estado']) ?></td>
                                <td class="texto-sm"><?= e(fecha_hora($reserva['fecha_inicio'])) ?></td>
                                <td class="texto-sm texto-muted"><?= e(fecha_hora($reserva['creado_en'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <aside class="grid-detalle__lado">
        <section class="card">
            <div class="card__head"><div><h2>Balance actual</h2><p>Lectura rápida del insumo.</p></div></div>
            <div class="card__body">
                <div class="lista-datos">
                    <div class="lista-datos__fila"><span>Físico</span><b><?= number_format($fisico, 2) ?> <?= e($insumo['unidad']) ?></b></div>
                    <div class="lista-datos__fila"><span>Comprometido</span><b><?= number_format($comprometido, 2) ?></b></div>
                    <div class="lista-datos__fila"><span>Disponible</span><b><?= number_format($disponible, 2) ?></b></div>
                    <div class="lista-datos__fila"><span>Mínimo</span><b><?= number_format((float) $insumo['stock_minimo'], 2) ?></b></div>
                    <div class="lista-datos__fila"><span>Costo referencia</span><b><?= e(money($insumo['costo_referencia'])) ?></b></div>
                    <div class="lista-datos__fila"><span>Estado</span><b><?= badge_estado($insumo['estado']) ?></b></div>
                </div>
                <div class="progreso mt-3 <?= $disponible <= (float) $insumo['stock_minimo'] ? 'progreso--aviso' : 'progreso--ok' ?>">
                    <span style="width:<?= $nivel ?>%"></span>
                </div>
                <p class="texto-muted texto-xs mt-1"><?= $nivel ?>% del stock físico está libre.</p>
            </div>
        </section>

        <section class="card">
            <div class="card__head"><div><h2>Cómo leer el kardex</h2><p>Movimientos que explican el saldo.</p></div></div>
            <div class="card__body">
                <div class="timeline">
                    <div class="timeline__item timeline__item--ok"><span class="timeline__punto"><?= icono('arriba') ?></span><div class="timeline__cuerpo"><b>Entrada</b><span>Compra o producción: sube el físico.</span></div></div>
                    <div class="timeline__item"><span class="timeline__punto"><?= icono('candado') ?></span><div class="timeline__cuerpo"><b>Reserva</b><span>Sube el comprometido sin tocar el físico.</span></div></div>
                    <div class="timeline__item"><span class="timeline__punto"><?= icono('abajo') ?></span><div class="timeline__cuerpo"><b>Consumo</b><span>Baja físico y comprometido a la vez.</span></div></div>
                    <div class="timeline__item timeline__item--aviso"><span class="timeline__punto"><?= icono('ajuste') ?></span><div class="timeline__cuerpo"><b>Ajuste</b><span>Corrige el físico tras un conteo, con motivo.</span></div></div>
                </div>
            </div>
        </section>
    </aside>
</div>
