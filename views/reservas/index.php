<?php
/** @var array $espacios */
/** @var array $recursos */
/** @var array $eventos */
/** @var string $estado */
/** @var int $eventoId */
/** @var string $q */

$confirmadasEspacios = count(array_filter($espacios, static fn (array $r): bool => $r['estado'] === 'confirmada'));
$confirmadasRecursos = count(array_filter($recursos, static fn (array $r): bool => $r['estado'] === 'confirmada'));
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Módulo B · Bloqueos y apartados</p>
        <h1>Reservas</h1>
        <p class="page-head__sub"><?= e($subtitulo ?? '') ?></p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--claro" href="<?= e(url('/espacios')) ?>"><?= icono('espacios') ?> Espacios</a>
        <?php if (puede(['administrador', 'coordinador', 'operaciones'])): ?>
            <a class="btn btn--lima" href="<?= e(url('/reservas/crear' . ($eventoId > 0 ? '?evento=' . $eventoId : ''))) ?>"><?= icono('mas') ?> Nueva reserva</a>
        <?php endif; ?>
    </div>
</div>

<div class="kpis">
    <div class="kpi kpi--oscura">
        <div class="kpi__top"><?= icono('espacios') ?> Reservas de espacio</div>
        <p class="kpi__valor"><?= count($espacios) ?></p>
        <p class="kpi__nota"><?= $confirmadasEspacios ?> confirmadas en el filtro</p>
    </div>
    <div class="kpi">
        <div class="kpi__top"><?= icono('recursos') ?> Apartados de recurso</div>
        <p class="kpi__valor"><?= count($recursos) ?></p>
        <p class="kpi__nota"><?= $confirmadasRecursos ?> confirmados en el filtro</p>
    </div>
    <div class="kpi kpi--lima">
        <div class="kpi__top"><?= icono('calendario') ?> Eventos con reserva</div>
        <p class="kpi__valor"><?= count(array_unique(array_merge(array_column($espacios, 'id_evento'), array_column($recursos, 'id_evento')))) ?></p>
        <p class="kpi__nota">en la vista actual</p>
    </div>
    <div class="kpi">
        <div class="kpi__top"><?= icono('candado') ?> Sin empalmes</div>
        <p class="kpi__valor">100%</p>
        <p class="kpi__nota">índices únicos por día y recurso</p>
    </div>
</div>

<form class="toolbar" method="get" action="<?= e(url('/reservas')) ?>">
    <div class="campo campo--buscar">
        <label for="q">Buscar</label>
        <input class="input" type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Evento, espacio o recurso">
    </div>
    <div class="campo">
        <label for="evento">Evento</label>
        <select class="select" id="evento" name="evento" data-autoenviar>
            <option value="0">Todos</option>
            <?php foreach ($eventos as $evento): ?>
                <option value="<?= (int) $evento['id_evento'] ?>" <?= $eventoId === (int) $evento['id_evento'] ? 'selected' : '' ?>><?= e($evento['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="campo">
        <label for="estado">Estado</label>
        <select class="select" id="estado" name="estado" data-autoenviar>
            <option value="">Todos</option>
            <option value="confirmada" <?= $estado === 'confirmada' ? 'selected' : '' ?>>Confirmada</option>
            <option value="cancelada" <?= $estado === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
        </select>
    </div>
    <div class="toolbar__acciones">
        <button class="btn btn--primario" type="submit"><?= icono('filtrar') ?> Filtrar</button>
        <a class="btn btn--fantasma" href="<?= e(url('/reservas')) ?>">Limpiar</a>
    </div>
</form>

<div class="stack">
    <section class="card">
        <div class="card__head">
            <div><h2>Espacios bloqueados</h2><p>Un día bloqueado no puede reservarse dos veces; el calendario se alimenta de esta tabla.</p></div>
            <span class="badge badge--neutro"><?= count($espacios) ?> registros</span>
        </div>
        <?php if ($espacios === []): ?>
            <div class="tabla__vacia">No hay reservas de espacio con este filtro.</div>
        <?php else: ?>
            <div class="tabla-wrap">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Evento</th>
                            <th>Espacio</th>
                            <th>Inicio</th>
                            <th>Fin</th>
                            <th class="num">Aforo</th>
                            <th>Estado</th>
                            <th>Registró</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($espacios as $reserva): ?>
                        <tr>
                            <td>
                                <a class="tabla__principal" href="<?= e(url('/eventos/' . (int) $reserva['id_evento'])) ?>">
                                    <span class="tabla__avatar"><?= icono('calendario') ?></span>
                                    <span><b><?= e($reserva['evento']) ?></b><span><?= e($reserva['cliente']) ?></span></span>
                                </a>
                            </td>
                            <td><?= e($reserva['espacio']) ?></td>
                            <td class="texto-sm"><?= e(fecha_hora($reserva['fecha_inicio'])) ?></td>
                            <td class="texto-sm"><?= e(fecha_hora($reserva['fecha_fin'])) ?></td>
                            <td class="num"><?= number_format((int) $reserva['capacidad']) ?></td>
                            <td><?= badge_estado($reserva['estado']) ?></td>
                            <td class="texto-sm texto-muted"><?= $reserva['usuario'] ? e($reserva['usuario']) : '—' ?></td>
                            <td>
                                <?php if ($reserva['estado'] === 'confirmada' && puede(['administrador', 'coordinador', 'operaciones'])): ?>
                                    <details>
                                        <summary class="btn btn--peligro btn--xs"><?= icono('x') ?> Cancelar</summary>
                                        <form class="formulario mt-1" method="post" action="<?= e(url('/reservas/' . (int) $reserva['id_reserva_espacio'] . '/cancelar')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="tipo" value="espacio">
                                            <input class="input" type="text" name="motivo" maxlength="220" placeholder="Motivo obligatorio" required>
                                            <button class="btn btn--peligro btn--xs" type="submit">Confirmar cancelación</button>
                                        </form>
                                    </details>
                                <?php elseif ($reserva['estado'] === 'cancelada'): ?>
                                    <span class="texto-muted texto-xs" title="<?= e($reserva['motivo_cancelacion'] ?? '') ?>"><?= e(recortar($reserva['motivo_cancelacion'] ?? 'Cancelada', 34)) ?></span>
                                <?php endif; ?>
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
            <div><h2>Recursos apartados</h2><p>La cantidad comprometida se valida contra existencias en cada día del evento.</p></div>
            <span class="badge badge--neutro"><?= count($recursos) ?> registros</span>
        </div>
        <?php if ($recursos === []): ?>
            <div class="tabla__vacia">No hay apartados de recurso con este filtro.</div>
        <?php else: ?>
            <div class="tabla-wrap">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Evento</th>
                            <th>Recurso</th>
                            <th>Proveedor</th>
                            <th class="num">Cantidad</th>
                            <th>Inicio</th>
                            <th>Fin</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recursos as $reserva): ?>
                        <tr>
                            <td>
                                <a class="tabla__principal" href="<?= e(url('/eventos/' . (int) $reserva['id_evento'])) ?>">
                                    <span class="tabla__avatar"><?= icono('recursos') ?></span>
                                    <span><b><?= e($reserva['evento']) ?></b><span><?= e($reserva['cliente']) ?></span></span>
                                </a>
                            </td>
                            <td><?= e($reserva['nombre']) ?></td>
                            <td class="texto-sm"><?= $reserva['proveedor'] ? e($reserva['proveedor']) : '<span class="texto-muted">Interno</span>' ?></td>
                            <td class="num"><?= number_format((int) $reserva['cantidad']) ?> <?= e($reserva['unidad']) ?></td>
                            <td class="texto-sm"><?= e(fecha_hora($reserva['fecha_inicio'])) ?></td>
                            <td class="texto-sm"><?= e(fecha_hora($reserva['fecha_fin'])) ?></td>
                            <td><?= badge_estado($reserva['estado']) ?></td>
                            <td>
                                <?php if ($reserva['estado'] === 'confirmada' && puede(['administrador', 'coordinador', 'operaciones'])): ?>
                                    <details>
                                        <summary class="btn btn--peligro btn--xs"><?= icono('x') ?> Cancelar</summary>
                                        <form class="formulario mt-1" method="post" action="<?= e(url('/reservas/' . (int) $reserva['id_reserva_recurso'] . '/cancelar')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="tipo" value="recurso">
                                            <input class="input" type="text" name="motivo" maxlength="220" placeholder="Motivo obligatorio" required>
                                            <button class="btn btn--peligro btn--xs" type="submit">Confirmar cancelación</button>
                                        </form>
                                    </details>
                                <?php elseif ($reserva['estado'] === 'cancelada'): ?>
                                    <span class="texto-muted texto-xs" title="<?= e($reserva['motivo_cancelacion'] ?? '') ?>"><?= e(recortar($reserva['motivo_cancelacion'] ?? 'Cancelada', 34)) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
