<?php
/** @var array $espacios */
/** @var array $reservas */
/** @var string $etiqueta */
/** @var string $anterior */
/** @var string $siguiente */
/** @var int $diasMes */
/** @var float $global */
/** @var float $ingresos */
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Módulo D · Ocupación</p>
        <h1>Ocupación de espacios · <?= e($etiqueta) ?></h1>
        <p class="page-head__sub"><?= e($subtitulo ?? '') ?></p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--fantasma" href="<?= e(url('/reportes')) ?>"><?= icono('atras') ?> Reportes</a>
        <a class="btn-icono" href="<?= e(url('/reportes/ocupacion?mes=' . $anterior)) ?>" aria-label="Mes anterior"><?= icono('atras') ?></a>
        <a class="btn btn--claro" href="<?= e(url('/reportes/ocupacion')) ?>">Mes actual</a>
        <a class="btn-icono" href="<?= e(url('/reportes/ocupacion?mes=' . $siguiente)) ?>" aria-label="Mes siguiente"><?= icono('flecha') ?></a>
    </div>
</div>

<div class="kpis">
    <div class="kpi kpi--oscura">
        <div class="kpi__top"><?= icono('grafico') ?> Ocupación global</div>
        <p class="kpi__valor"><?= number_format($global, 1) ?>%</p>
        <p class="kpi__nota"><?= $diasMes ?> días del mes</p>
    </div>
    <div class="kpi kpi--lima">
        <div class="kpi__top"><?= icono('pagos') ?> Ingresos comprometidos</div>
        <p class="kpi__valor"><?= e(money_corto($ingresos)) ?></p>
        <p class="kpi__nota"><?= e(money($ingresos)) ?> por reservas del mes</p>
    </div>
    <div class="kpi">
        <div class="kpi__top"><?= icono('reservas') ?> Reservas del periodo</div>
        <p class="kpi__valor"><?= count($reservas) ?></p>
        <p class="kpi__nota">confirmadas que tocan el mes</p>
    </div>
    <div class="kpi">
        <div class="kpi__top"><?= icono('espacios') ?> Espacios medidos</div>
        <p class="kpi__valor"><?= count($espacios) ?></p>
        <p class="kpi__nota"><?= array_sum(array_column($espacios, 'dias_ocupados')) ?> días bloqueados en total</p>
    </div>
</div>

<section class="card mb-2">
    <div class="card__head">
        <div><h2>Días bloqueados por espacio</h2><p>Un espacio puede tener más de una reserva en el mes, pero no el mismo día.</p></div>
        <span class="badge badge--neutro"><?= e($etiqueta) ?></span>
    </div>
    <?php if ($espacios === []): ?>
        <div class="tabla__vacia">No hay espacios registrados.</div>
    <?php else: ?>
        <div class="tabla-wrap">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Espacio</th>
                        <th>Tipo</th>
                        <th class="num">Capacidad</th>
                        <th class="num">Reservas</th>
                        <th class="num">Días ocupados</th>
                        <th style="min-width:160px">Ocupación del mes</th>
                        <th class="num">Ingreso referencia</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($espacios as $espacio): ?>
                    <tr>
                        <td><a class="tabla__principal" href="<?= e(url('/espacios/' . (int) $espacio['id_espacio'] . '/calendario?mes=' . ($_GET['mes'] ?? date('Y-m')))) ?>"><span class="tabla__avatar"><?= icono('espacios') ?></span><span><b><?= e($espacio['nombre']) ?></b><span><?= e(ucfirst(str_replace('_', ' ', $espacio['tipo']))) ?></span></span></a></td>
                        <td class="texto-sm"><?= e(ucfirst(str_replace('_', ' ', $espacio['tipo']))) ?></td>
                        <td class="num"><?= number_format((int) $espacio['capacidad']) ?></td>
                        <td class="num"><?= (int) $espacio['reservas'] ?></td>
                        <td class="num"><?= (int) $espacio['dias_ocupados'] ?> / <?= $diasMes ?></td>
                        <td>
                            <div class="progreso <?= (float) $espacio['ocupacion'] >= 60 ? 'progreso--lima' : 'progreso--ok' ?>">
                                <span style="width:<?= min(100, (float) $espacio['ocupacion']) ?>%"></span>
                            </div>
                            <span class="texto-muted texto-xs"><?= number_format((float) $espacio['ocupacion'], 1) ?>%</span>
                        </td>
                        <td class="num"><?= e(money($espacio['ingresos'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="card">
    <div class="card__head"><div><h2>Eventos del periodo</h2><p>Reservas confirmadas que inician o terminan dentro del mes.</p></div><span class="badge badge--neutro"><?= count($reservas) ?> reservas</span></div>
    <?php if ($reservas === []): ?>
        <div class="tabla__vacia">No hay eventos reservados en <?= e($etiqueta) ?>.</div>
    <?php else: ?>
        <div class="tabla-wrap">
            <table class="tabla tabla--compacta">
                <thead><tr><th>Evento</th><th>Cliente</th><th>Espacio</th><th>Inicio</th><th>Fin</th><th>Estado del evento</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($reservas as $reserva): ?>
                    <tr>
                        <td><b><?= e($reserva['evento']) ?></b></td>
                        <td class="texto-sm"><?= e($reserva['cliente']) ?></td>
                        <td class="texto-sm"><?= e($reserva['espacio']) ?></td>
                        <td class="texto-sm"><?= e(fecha_hora($reserva['fecha_inicio'])) ?></td>
                        <td class="texto-sm"><?= e(fecha_hora($reserva['fecha_fin'])) ?></td>
                        <td><?= badge_estado($reserva['estado_evento']) ?></td>
                        <td><div class="acciones"><a class="btn btn--fantasma btn--xs" href="<?= e(url('/eventos/' . (int) $reserva['id_evento'])) ?>"><?= icono('ver') ?></a></div></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
