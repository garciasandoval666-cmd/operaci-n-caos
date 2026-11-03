<?php
/** @var array $espacio */
/** @var array $celdas */
/** @var string $etiqueta */
/** @var string $anterior */
/** @var string $siguiente */
/** @var array $reservas */

$tipos = [
    'salon_fiesta'    => 'Salón de fiesta',
    'salon_gala'      => 'Salón de gala',
    'sala_exhibicion' => 'Sala de exhibición',
    'terraza'         => 'Terraza',
    'jardin'          => 'Jardín',
];
$diasOcupados = count(array_filter($celdas, static fn (array $c): bool => !empty($c['ocupado'])));
$diasMes = count(array_filter($celdas, static fn (array $c): bool => !empty($c['dia'])));
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Módulo B · Calendario de espacio</p>
        <h1><?= e($espacio['nombre']) ?></h1>
        <p class="page-head__sub"><?= e($tipos[$espacio['tipo']] ?? ucfirst($espacio['tipo'])) ?> · capacidad para <?= number_format((int) $espacio['capacidad']) ?> personas · costo base <?= e(money($espacio['costo_referencia'])) ?>.</p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--fantasma" href="<?= e(url('/espacios')) ?>"><?= icono('atras') ?> Espacios</a>
        <a class="btn btn--lima" href="<?= e(url('/reservas/crear?evento=' . (int) ($reservas[0]['id_evento'] ?? 0))) ?>"><?= icono('mas') ?> Reservar</a>
    </div>
</div>

<div class="grid-detalle">
    <div class="stack">
        <section class="cal">
            <div class="cal__head">
                <h2><?= e($etiqueta) ?></h2>
                <span class="badge badge--<?= $diasOcupados > 0 ? 'acento' : 'neutro' ?>"><?= $diasOcupados ?> de <?= $diasMes ?> días bloqueados</span>
                <div class="cal__head__acciones">
                    <a class="btn-icono" href="<?= e(url('/espacios/' . (int) $espacio['id_espacio'] . '/calendario?mes=' . $anterior)) ?>" aria-label="Mes anterior"><?= icono('atras') ?></a>
                    <a class="btn btn--fantasma btn--xs" href="<?= e(url('/espacios/' . (int) $espacio['id_espacio'] . '/calendario')) ?>">Hoy</a>
                    <a class="btn-icono" href="<?= e(url('/espacios/' . (int) $espacio['id_espacio'] . '/calendario?mes=' . $siguiente)) ?>" aria-label="Mes siguiente"><?= icono('flecha') ?></a>
                </div>
            </div>

            <div class="cal__grid">
                <?php foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $dia): ?>
                    <div class="cal__dow"><?= e($dia) ?></div>
                <?php endforeach; ?>

                <?php foreach ($celdas as $celda): ?>
                    <?php if (empty($celda['dia'])): ?>
                        <div class="cal__dia fuera"></div>
                    <?php else: ?>
                        <div class="cal__dia<?= !empty($celda['ocupado']) ? ' ocupado' : '' ?><?= !empty($celda['hoy']) ? ' hoy' : '' ?>">
                            <span class="cal__num"><?= (int) $celda['dia'] ?></span>
                            <?php if (!empty($celda['evento'])): ?>
                                <a class="cal__evento" href="<?= e(url('/eventos/' . (int) $celda['evento']['id_evento'])) ?>" title="<?= e($celda['evento']['evento']) ?>"><?= e($celda['evento']['evento']) ?></a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="cal__leyenda">
                <span><i style="background:var(--lime-soft);border:1px solid var(--menta-2)"></i> Día bloqueado por reserva confirmada</span>
                <span><i style="background:var(--ink)"></i> Hoy</span>
                <span><i style="background:var(--surface);border:1px solid var(--line)"></i> Disponible</span>
            </div>
        </section>

        <section class="card">
            <div class="card__head">
                <div><h2>Reservas vigentes de este espacio</h2><p>Bloqueos confirmados con fecha de fin posterior a hoy.</p></div>
                <a class="btn btn--fantasma btn--sm" href="<?= e(url('/reservas?q=' . urlencode($espacio['nombre']))) ?>">Ver en reservas</a>
            </div>
            <?php if ($reservas === []): ?>
                <div class="tabla__vacia">Este espacio no tiene reservas vigentes.</div>
            <?php else: ?>
                <div class="tabla-wrap">
                    <table class="tabla tabla--compacta">
                        <thead>
                            <tr><th>Evento</th><th>Inicio</th><th>Fin</th><th>Estado</th><th></th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($reservas as $reserva): ?>
                            <tr>
                                <td><b><?= e($reserva['evento']) ?></b></td>
                                <td class="texto-sm"><?= e(fecha_hora($reserva['fecha_inicio'])) ?></td>
                                <td class="texto-sm"><?= e(fecha_hora($reserva['fecha_fin'])) ?></td>
                                <td><?= badge_estado($reserva['estado']) ?></td>
                                <td><div class="acciones"><a class="btn btn--fantasma btn--xs" href="<?= e(url('/eventos/' . (int) $reserva['id_evento'])) ?>"><?= icono('ver') ?> Abrir</a></div></td>
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
            <div class="card__head"><div><h2>Ficha del espacio</h2><p>Datos usados al cotizar.</p></div></div>
            <div class="card__body">
                <div class="ficha">
                    <div class="ficha__item"><span>Capacidad</span><b><?= number_format((int) $espacio['capacidad']) ?> personas</b></div>
                    <div class="ficha__item"><span>Costo base</span><b><?= e(money($espacio['costo_referencia'])) ?></b></div>
                    <div class="ficha__item"><span>Tipo</span><b><?= e($tipos[$espacio['tipo']] ?? $espacio['tipo']) ?></b></div>
                    <div class="ficha__item"><span>Estado</span><b><?= badge_estado($espacio['estado']) ?></b></div>
                </div>
                <?php if (!empty($espacio['descripcion'])): ?>
                    <p class="texto-sm texto-muted mt-3"><?= e($espacio['descripcion']) ?></p>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card__head"><div><h2>Cómo se bloquea</h2><p>Validación antes de escribir.</p></div></div>
            <div class="card__body">
                <div class="timeline">
                    <div class="timeline__item timeline__item--ok">
                        <span class="timeline__punto"><?= icono('check') ?></span>
                        <div class="timeline__cuerpo"><b>Se valida el rango</b><span>Inicio y fin dentro de las fechas del evento.</span></div>
                    </div>
                    <div class="timeline__item timeline__item--ok">
                        <span class="timeline__punto"><?= icono('candado') ?></span>
                        <div class="timeline__cuerpo"><b>Se bloquea cada día</b><span>Un registro por fecha con índice único.</span></div>
                    </div>
                    <div class="timeline__item">
                        <span class="timeline__punto"><?= icono('alerta') ?></span>
                        <div class="timeline__cuerpo"><b>Si hay empalme</b><span>La transacción se revierte y se informa el conflicto.</span></div>
                    </div>
                </div>
            </div>
        </section>
    </aside>
</div>
