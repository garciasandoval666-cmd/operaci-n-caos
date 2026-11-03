<?php
/** @var array $espacios */
/** @var array $reservas */

$tipos = [
    'salon_fiesta'    => 'Salón de fiesta',
    'salon_gala'      => 'Salón de gala',
    'sala_exhibicion' => 'Sala de exhibición',
    'terraza'         => 'Terraza',
    'jardin'          => 'Jardín',
];

$activos = array_filter($espacios, static fn (array $e): bool => $e['estado'] === 'activo');
$aforoTotal = array_sum(array_map(static fn (array $e): int => (int) $e['capacidad'], $activos));
$ocupacionMedia = $espacios !== []
    ? round(array_sum(array_map(static fn (array $e): float => (float) $e['ocupacion'], $espacios)) / count($espacios), 1)
    : 0.0;
$enMantenimiento = count(array_filter($espacios, static fn (array $e): bool => $e['estado'] === 'mantenimiento'));
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Módulo B · Recintos</p>
        <h1>Espacios</h1>
        <p class="page-head__sub"><?= e($subtitulo ?? '') ?></p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--claro" href="<?= e(url('/reportes/ocupacion')) ?>"><?= icono('grafico') ?> Ocupación</a>
        <a class="btn btn--fantasma" href="<?= e(url('/reservas')) ?>"><?= icono('reservas') ?> Reservas</a>
    </div>
</div>

<div class="kpis">
    <div class="kpi kpi--oscura">
        <div class="kpi__top"><?= icono('espacios') ?> Espacios registrados</div>
        <p class="kpi__valor"><?= count($espacios) ?></p>
        <p class="kpi__nota"><?= count($activos) ?> activos · <?= $enMantenimiento ?> en mantenimiento</p>
    </div>
    <div class="kpi">
        <div class="kpi__top"><?= icono('clientes') ?> Aforo disponible</div>
        <p class="kpi__valor"><?= number_format($aforoTotal) ?></p>
        <p class="kpi__nota">personas en espacios activos</p>
    </div>
    <div class="kpi">
        <div class="kpi__top"><?= icono('calendario') ?> Ocupación 90 días</div>
        <p class="kpi__valor"><?= number_format($ocupacionMedia, 1) ?>%</p>
        <p class="kpi__nota">promedio de días bloqueados</p>
    </div>
    <div class="kpi kpi--lima">
        <div class="kpi__top"><?= icono('reservas') ?> Reservas vigentes</div>
        <p class="kpi__valor"><?= count($reservas) ?></p>
        <p class="kpi__nota">bloqueos confirmados por venir</p>
    </div>
</div>

<div class="grid-detalle">
    <div class="stack">
        <section class="card">
            <div class="card__head">
                <div>
                    <h2>Inventario de espacios</h2>
                    <p>Capacidad, costo de referencia y ocupación de los próximos 90 días.</p>
                </div>
                <span class="badge badge--neutro"><?= count($espacios) ?> recintos</span>
            </div>

            <?php if ($espacios === []): ?>
                <div class="empty">
                    <div class="empty__icono"><?= icono('espacios') ?></div>
                    <h3>Sin espacios registrados</h3>
                    <p>Da de alta el primer salón para poder bloquear fechas y cotizar.</p>
                </div>
            <?php else: ?>
                <div class="tabla-wrap">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>Espacio</th>
                                <th>Tipo</th>
                                <th class="num">Capacidad</th>
                                <th class="num">Costo base</th>
                                <th class="num">Reservas</th>
                                <th style="min-width:130px">Ocupación 90 d</th>
                                <th>Estado</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($espacios as $espacio): ?>
                            <tr>
                                <td>
                                    <a class="tabla__principal" href="<?= e(url('/espacios/' . (int) $espacio['id_espacio'] . '/calendario')) ?>">
                                        <span class="tabla__avatar"><?= icono('espacios') ?></span>
                                        <span>
                                            <b><?= e($espacio['nombre']) ?></b>
                                            <span><?= e(recortar($espacio['descripcion'] ?? '', 54)) ?></span>
                                        </span>
                                    </a>
                                </td>
                                <td class="texto-sm"><?= e($tipos[$espacio['tipo']] ?? ucfirst($espacio['tipo'])) ?></td>
                                <td class="num"><?= number_format((int) $espacio['capacidad']) ?></td>
                                <td class="num"><?= e(money($espacio['costo_referencia'])) ?></td>
                                <td class="num"><?= (int) $espacio['reservas'] ?></td>
                                <td>
                                    <div class="progreso <?= (float) $espacio['ocupacion'] >= 70 ? 'progreso--aviso' : 'progreso--lima' ?>">
                                        <span style="width:<?= min(100, (float) $espacio['ocupacion']) ?>%"></span>
                                    </div>
                                    <span class="texto-muted texto-xs"><?= number_format((float) $espacio['ocupacion'], 1) ?>% · <?= e(plural((int) $espacio['dias_ocupados'], 'día', 'días')) ?></span>
                                </td>
                                <td><?= badge_estado($espacio['estado']) ?></td>
                                <td>
                                    <div class="acciones">
                                        <a class="btn btn--fantasma btn--xs" href="<?= e(url('/espacios/' . (int) $espacio['id_espacio'] . '/calendario')) ?>"><?= icono('calendario') ?> Calendario</a>
                                    </div>
                                </td>
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
                    <h2>Próximos bloqueos confirmados</h2>
                    <p>Cada reserva genera un día bloqueado por espacio; no se puede duplicar la fecha.</p>
                </div>
                <a class="btn btn--fantasma btn--sm" href="<?= e(url('/reservas')) ?>">Ver todas</a>
            </div>
            <?php if ($reservas === []): ?>
                <div class="tabla__vacia">No hay reservas de espacio vigentes.</div>
            <?php else: ?>
                <div class="tabla-wrap">
                    <table class="tabla tabla--compacta">
                        <thead>
                            <tr>
                                <th>Espacio</th>
                                <th>Evento</th>
                                <th>Inicio</th>
                                <th>Fin</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($reservas as $reserva): ?>
                            <tr>
                                <td><b><?= e($reserva['espacio']) ?></b></td>
                                <td><?= e($reserva['evento']) ?></td>
                                <td class="texto-sm"><?= e(fecha_hora($reserva['fecha_inicio'])) ?></td>
                                <td class="texto-sm"><?= e(fecha_hora($reserva['fecha_fin'])) ?></td>
                                <td>
                                    <div class="acciones">
                                        <a class="btn btn--fantasma btn--xs" href="<?= e(url('/eventos/' . (int) $reserva['id_evento'])) ?>"><?= icono('ver') ?> Evento</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <aside class="grid-detalle__lado">
        <?php if (puede(['administrador', 'operaciones'])): ?>
            <section class="card">
                <div class="card__head"><div><h2>Nuevo espacio</h2><p>Se agrega al inventario del recinto.</p></div></div>
                <div class="card__body">
                    <form class="formulario" method="post" action="<?= e(url('/espacios')) ?>">
                        <?= csrf_field() ?>
                        <div class="campo">
                            <label for="nombre">Nombre <span class="campo__req">*</span></label>
                            <input class="input" type="text" id="nombre" name="nombre" maxlength="100" required placeholder="Salón Aurora">
                        </div>
                        <div class="campo">
                            <label for="tipo">Tipo <span class="campo__req">*</span></label>
                            <select class="select" id="tipo" name="tipo" required>
                                <?php foreach ($tipos as $clave => $etiqueta): ?>
                                    <option value="<?= e($clave) ?>"><?= e($etiqueta) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="campo-fila">
                            <div class="campo">
                                <label for="capacidad">Capacidad <span class="campo__req">*</span></label>
                                <input class="input" type="number" id="capacidad" name="capacidad" min="1" step="1" required>
                            </div>
                            <div class="campo">
                                <label for="costo_referencia">Costo base</label>
                                <input class="input" type="number" id="costo_referencia" name="costo_referencia" min="0" step="0.01" placeholder="0.00">
                            </div>
                        </div>
                        <div class="campo">
                            <label for="descripcion">Descripción</label>
                            <textarea class="textarea" id="descripcion" name="descripcion" maxlength="255" rows="2" placeholder="Montaje, vista, equipamiento…"></textarea>
                        </div>
                        <div class="campo">
                            <label for="estado">Estado</label>
                            <select class="select" id="estado" name="estado">
                                <option value="activo">Activo</option>
                                <option value="mantenimiento">Mantenimiento</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <button class="btn btn--lima btn--bloque" type="submit"><?= icono('mas') ?> Registrar espacio</button>
                    </form>
                </div>
            </section>
        <?php endif; ?>

        <section class="card">
            <div class="card__head"><div><h2>Regla de oro</h2><p>Un espacio, una fecha.</p></div></div>
            <div class="card__body">
                <p class="texto-sm texto-muted">La tabla <span class="mono">reserva_espacio_dias</span> tiene un índice único por espacio y fecha activa. Si dos coordinadores intentan bloquear el mismo día, la base rechaza el segundo registro y la aplicación muestra el conflicto.</p>
                <div class="aviso-inline mt-3"><?= icono('candado') ?><span>Los bloqueos se cancelan con motivo, nunca se borran: el historial conserva quién liberó la fecha.</span></div>
            </div>
        </section>
    </aside>
</div>
