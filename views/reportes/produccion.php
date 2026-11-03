<?php
/** @var array $eventos */
/** @var array $totales */
/** @var array $restricciones */
/** @var int $dias */

$etiquetasRestriccion = [
    'ninguna'     => 'Sin restricción',
    'vegetariano' => 'Vegetariano',
    'vegano'      => 'Vegano',
    'sin_gluten'  => 'Sin gluten',
    'otra'        => 'Otra',
];
$totalRestricciones = max(1, array_sum(array_map(static fn (array $r): int => (int) $r['total'], $restricciones)));
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Módulo D · Producción</p>
        <h1>Producción próxima</h1>
        <p class="page-head__sub"><?= e($subtitulo ?? '') ?> Horizonte de <?= $dias ?> días.</p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--fantasma" href="<?= e(url('/reportes')) ?>"><?= icono('atras') ?> Reportes</a>
        <a class="btn btn--claro" href="<?= e(url('/menus')) ?>"><?= icono('cubiertos') ?> Menús</a>
        <a class="btn btn--claro" href="<?= e(url('/insumos')) ?>"><?= icono('insumos') ?> Insumos</a>
    </div>
</div>

<form class="toolbar" method="get" action="<?= e(url('/reportes/produccion')) ?>">
    <div class="campo">
        <label for="dias">Horizonte</label>
        <select class="select" id="dias" name="dias" data-autoenviar>
            <?php foreach ([30, 60, 90, 120, 180] as $opcion): ?>
                <option value="<?= $opcion ?>" <?= $dias === $opcion ? 'selected' : '' ?>><?= $opcion ?> días</option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="toolbar__acciones">
        <button class="btn btn--primario" type="submit"><?= icono('filtrar') ?> Aplicar</button>
    </div>
</form>

<div class="kpis">
    <div class="kpi kpi--oscura">
        <div class="kpi__top"><?= icono('calendario') ?> Eventos por venir</div>
        <p class="kpi__valor"><?= (int) $totales['eventos'] ?></p>
        <p class="kpi__nota">en los próximos <?= $dias ?> días</p>
    </div>
    <div class="kpi kpi--lima">
        <div class="kpi__top"><?= icono('cubiertos') ?> Personas en menú</div>
        <p class="kpi__valor"><?= number_format((int) $totales['personas']) ?></p>
        <p class="kpi__nota"><?= number_format((int) $totales['confirmados']) ?> confirmados por RSVP</p>
    </div>
    <div class="kpi">
        <div class="kpi__top"><?= icono('alerta') ?> Restricciones</div>
        <p class="kpi__valor"><?= number_format((int) $totales['restricciones']) ?></p>
        <p class="kpi__nota">invitados con dieta especial</p>
    </div>
    <div class="kpi <?= (int) $totales['sin_menu'] + (int) $totales['sin_espacio'] > 0 ? '' : 'kpi--lima' ?>">
        <div class="kpi__top"><?= icono('x') ?> Pendientes críticos</div>
        <p class="kpi__valor"><?= (int) $totales['sin_menu'] + (int) $totales['sin_espacio'] ?></p>
        <p class="kpi__nota"><?= (int) $totales['sin_menu'] ?> sin menú · <?= (int) $totales['sin_espacio'] ?> sin espacio</p>
    </div>
</div>

<div class="grid-detalle">
    <div class="stack">
        <section class="card">
            <div class="card__head"><div><h2>Hoja de producción</h2><p>Un renglón por evento con lo que cocina y montaje necesitan saber.</p></div><span class="badge badge--neutro"><?= count($eventos) ?> eventos</span></div>
            <?php if ($eventos === []): ?>
                <div class="empty">
                    <div class="empty__icono"><?= icono('cubiertos') ?></div>
                    <h3>Sin eventos en el horizonte</h3>
                    <p>Amplía el rango de días o revisa las fechas de los eventos confirmados.</p>
                </div>
            <?php else: ?>
                <div class="tabla-wrap">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>Evento</th>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th class="num">Personas</th>
                                <th class="num">Confirmados</th>
                                <th class="num">Restricciones</th>
                                <th class="num">Menús</th>
                                <th class="num">Insumos</th>
                                <th>Alertas</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($eventos as $evento): ?>
                            <tr>
                                <td>
                                    <a class="tabla__principal" href="<?= e(url('/eventos/' . (int) $evento['id_evento'])) ?>">
                                        <span class="tabla__avatar"><?= icono(tipo_evento_icono($evento['tipo'])) ?></span>
                                        <span><b><?= e($evento['nombre']) ?></b><span><?= e($evento['cliente']) ?></span></span>
                                    </a>
                                </td>
                                <td class="texto-sm ahorarap"><?= e(fecha($evento['fecha_inicio'], 'd M Y')) ?><span class="texto-muted texto-xs" style="display:block"><?= e(date('H:i', strtotime($evento['fecha_inicio']))) ?> h</span></td>
                                <td class="texto-sm"><?= e($evento['tipo']) ?></td>
                                <td class="num"><?= number_format((int) $evento['personas']) ?></td>
                                <td class="num"><?= number_format((int) $evento['confirmados']) ?> / <?= number_format((int) $evento['aforo_maximo']) ?></td>
                                <td class="num"><?= (int) $evento['restricciones'] ?></td>
                                <td class="num"><?= (int) $evento['menus'] ?></td>
                                <td class="num"><?= (int) $evento['insumos'] ?></td>
                                <td>
                                    <div class="fila" style="gap:4px">
                                        <?php if ((int) $evento['requiere_menu'] === 1 && (int) $evento['menus'] === 0): ?>
                                            <span class="badge badge--error">Sin menú</span>
                                        <?php endif; ?>
                                        <?php if ((int) $evento['espacios'] === 0): ?>
                                            <span class="badge badge--aviso">Sin espacio</span>
                                        <?php endif; ?>
                                        <?php if ((int) $evento['requiere_menu'] === 1 && (int) $evento['menus'] > 0 && (int) $evento['insumos'] === 0): ?>
                                            <span class="badge badge--aviso">Sin insumos</span>
                                        <?php endif; ?>
                                        <?php if ((int) $evento['requiere_menu'] === 1 && (int) $evento['menus'] > 0 && (int) $evento['insumos'] > 0 && (int) $evento['espacios'] > 0): ?>
                                            <span class="badge badge--ok">Listo</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><div class="acciones"><a class="btn btn--fantasma btn--xs" href="<?= e(url('/eventos/' . (int) $evento['id_evento'] . '/produccion')) ?>"><?= icono('imprimir') ?> Hoja</a></div></td>
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
            <div class="card__head"><div><h2>Restricciones alimenticias</h2><p>Solo invitados confirmados de eventos abiertos.</p></div></div>
            <div class="card__body">
                <?php if ($restricciones === []): ?>
                    <p class="texto-sm texto-muted">Sin restricciones registradas en los próximos eventos.</p>
                <?php else: ?>
                    <div class="distribucion-lista">
                        <?php foreach ($restricciones as $restriccion): ?>
                            <div class="distribucion-lista__fila">
                                <span><?= e($etiquetasRestriccion[$restriccion['restriccion_alimenticia']] ?? ucfirst($restriccion['restriccion_alimenticia'])) ?></span>
                                <div class="progreso progreso--lima"><span style="width:<?= porcentaje((int) $restriccion['total'], $totalRestricciones) ?>%"></span></div>
                                <b><?= (int) $restriccion['total'] ?></b>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card__head"><div><h2>Checklist de cocina</h2><p>Antes de cada evento.</p></div></div>
            <div class="card__body">
                <div class="timeline">
                    <div class="timeline__item timeline__item--ok"><span class="timeline__punto"><?= icono('check') ?></span><div class="timeline__cuerpo"><b>Menú contratado</b><span>Precio por persona y mínimo validados.</span></div></div>
                    <div class="timeline__item timeline__item--ok"><span class="timeline__punto"><?= icono('check') ?></span><div class="timeline__cuerpo"><b>Insumos apartados</b><span>Stock comprometido sin consumir.</span></div></div>
                    <div class="timeline__item timeline__item--aviso"><span class="timeline__punto"><?= icono('alerta') ?></span><div class="timeline__cuerpo"><b>Restricciones</b><span>Alergias y dietas especiales por invitado.</span></div></div>
                    <div class="timeline__item"><span class="timeline__punto"><?= icono('reloj') ?></span><div class="timeline__cuerpo"><b>Cierre de cambios</b><span><?= (string) configuracion('dias_limite_menu', 7) ?> días antes del evento.</span></div></div>
                </div>
            </div>
        </section>
    </aside>
</div>
