<?php
/** @var array $evento */
/** @var array $historial */
/** @var array $bitacora */
/** @var array $relacionados */
$id = (int) $evento['id_evento'];
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Trazabilidad · Evento #<?= $id ?></p>
        <h1>Historial de <?= e($evento['nombre']) ?></h1>
        <p class="page-head__sub">Nada se borra: cada cambio de estado, fecha, precio o reserva queda registrado con usuario, motivo y fecha.</p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--fantasma" href="<?= e(url('/eventos/' . $id)) ?>"><?= icono('atras') ?> Volver al evento</a>
        <button class="btn btn--claro" type="button" data-imprimir><?= icono('imprimir') ?> Imprimir</button>
    </div>
</div>

<div class="grid grid-4 mb-3">
    <div class="mini-kpi"><?= icono('bitacora') ?><div><b><?= count($historial) ?></b><span>Cambios de campos</span></div></div>
    <div class="mini-kpi"><?= icono('destello') ?><div><b><?= count($bitacora) ?></b><span>Acciones en bitácora</span></div></div>
    <div class="mini-kpi"><?= icono('reservas') ?><div><b><?= count($relacionados) ?></b><span>Cambios relacionados</span></div></div>
    <div class="mini-kpi"><?= icono('reloj') ?><div><b><?= e(fecha($evento['creado_en'], 'd M Y')) ?></b><span>Alta del evento</span></div></div>
</div>

<div class="grid-detalle">
    <div class="stack">
        <section class="card">
            <div class="card__head">
                <div>
                    <h2>Cambios de campos</h2>
                    <p>Valor anterior, valor nuevo y motivo declarado.</p>
                </div>
                <span class="badge badge--neutro"><?= count($historial) ?> registros</span>
            </div>
            <?php if ($historial === []): ?>
                <div class="card__body">
                    <div class="aviso-inline aviso-inline--info"><?= icono('alerta') ?> <span>Aún no hay cambios de campo registrados para este evento.</span></div>
                </div>
            <?php else: ?>
                <div class="tabla-wrap">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Campo</th>
                                <th>Anterior</th>
                                <th>Nuevo</th>
                                <th>Motivo</th>
                                <th>Usuario</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($historial as $cambio): ?>
                            <tr>
                                <td class="texto-sm"><?= e(fecha_hora($cambio['fecha_cambio'])) ?></td>
                                <td><b class="mono"><?= e($cambio['campo']) ?></b></td>
                                <td class="texto-sm texto-muted"><?= e($cambio['valor_anterior'] ?? '—') ?></td>
                                <td class="texto-sm"><b><?= e($cambio['valor_nuevo'] ?? '—') ?></b></td>
                                <td class="texto-sm"><?= e($cambio['motivo'] !== '' ? $cambio['motivo'] : '—') ?></td>
                                <td class="texto-sm"><?= e($cambio['usuario'] ?? 'Sistema') ?></td>
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
                    <h2>Cambios en reservas y cotizaciones</h2>
                    <p>Registros relacionados con el expediente del evento.</p>
                </div>
            </div>
            <?php if ($relacionados === []): ?>
                <div class="card__body"><p class="texto-muted texto-sm">Sin cambios relacionados registrados.</p></div>
            <?php else: ?>
                <div class="tabla-wrap">
                    <table class="tabla tabla--compacta">
                        <thead><tr><th>Fecha</th><th>Entidad</th><th>Registro</th><th>Campo</th><th>Nuevo</th><th>Usuario</th></tr></thead>
                        <tbody>
                        <?php foreach ($relacionados as $cambio): ?>
                            <tr>
                                <td class="texto-sm"><?= e(fecha_hora($cambio['fecha_cambio'])) ?></td>
                                <td><?= badge(ucfirst(str_replace('_', ' ', $cambio['entidad'])), 'neutro') ?></td>
                                <td class="mono">#<?= (int) $cambio['id_registro'] ?></td>
                                <td class="texto-sm"><?= e($cambio['campo']) ?></td>
                                <td class="texto-sm"><b><?= e($cambio['valor_nuevo'] ?? '—') ?></b></td>
                                <td class="texto-sm"><?= e($cambio['usuario'] ?? 'Sistema') ?></td>
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
            <div class="card__head">
                <div><h2>Bitácora del evento</h2><p>Acciones registradas sobre este expediente.</p></div>
            </div>
            <div class="card__body">
                <?php if ($bitacora === []): ?>
                    <p class="texto-muted texto-sm">Sin acciones registradas todavía.</p>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($bitacora as $movimiento): ?>
                            <div class="timeline__item">
                                <span class="timeline__punto"><?= icono(accion_icono((string) $movimiento['accion'])) ?></span>
                                <div class="timeline__cuerpo">
                                    <b><?= e(accion_legible((string) $movimiento['accion'])) ?></b>
                                    <span><?= e($movimiento['usuario'] ?? 'Sistema') ?> · <?= e(fecha_hora($movimiento['fecha'])) ?></span>
                                    <?php if (($movimiento['detalle'] ?? '') !== ''): ?>
                                        <p><?= e(recortar($movimiento['detalle'], 120)) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card__head"><div><h2>Datos del expediente</h2><p>Referencia rápida.</p></div></div>
            <div class="card__body">
                <div class="lista-datos">
                    <div class="lista-datos__fila"><span>Evento</span><b><?= e($evento['nombre']) ?></b></div>
                    <div class="lista-datos__fila"><span>Cliente</span><b><?= e($evento['cliente']) ?></b></div>
                    <div class="lista-datos__fila"><span>Estado</span><b><?= badge_estado($evento['estado']) ?></b></div>
                    <div class="lista-datos__fila"><span>Inicio</span><b><?= e(fecha_hora($evento['fecha_inicio'])) ?></b></div>
                    <div class="lista-datos__fila"><span>Cierre</span><b><?= e(fecha_hora($evento['fecha_fin'])) ?></b></div>
                </div>
            </div>
        </section>
    </aside>
</div>
