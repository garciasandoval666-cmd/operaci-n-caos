<?php
/** @var array $eventos */
/** @var array|null $evento */
/** @var array $invitados */
/** @var array $recientes */
/** @var array $stats */
/** @var string $q */
$idEvento = $evento ? (int) $evento['id_evento'] : 0;
$avance = (int) ($stats['confirmados'] ?? 0) > 0 ? min(100, round(((int) $stats['checkin'] / (int) $stats['confirmados']) * 100)) : 0;
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Recepción · control de acceso</p>
        <h1>Check-in</h1>
        <p class="page-head__sub">Busca por nombre o pega el código del invitado. Cada entrada se registra una sola vez y queda en bitácora.</p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--fantasma" href="<?= e(url('/invitados')) ?>"><?= icono('invitados') ?> Invitados</a>
    </div>
</div>

<form class="toolbar" method="get" action="<?= e(url('/checkin')) ?>">
    <div class="campo">
        <label for="evento">Evento</label>
        <select class="select" id="evento" name="evento" data-autoenviar>
            <?php if ($eventos === []): ?><option value="">Sin eventos disponibles</option><?php endif; ?>
            <?php foreach ($eventos as $fila): ?>
                <option value="<?= (int) $fila['id_evento'] ?>" <?= $idEvento === (int) $fila['id_evento'] ? 'selected' : '' ?>>
                    <?= e($fila['nombre']) ?> · <?= e(fecha($fila['fecha_inicio'], 'd M Y')) ?> · <?= (int) $fila['confirmados'] ?> confirmados
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="campo campo--buscar">
        <label for="q">Buscar invitado</label>
        <input class="input" type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Nombre, correo o código">
    </div>
    <div class="toolbar__acciones">
        <button class="btn btn--primario" type="submit"><?= icono('buscar') ?> Buscar</button>
    </div>
</form>

<?php if (!$evento): ?>
    <section class="card">
        <div class="empty">
            <div class="empty__icono"><?= icono('calendario') ?></div>
            <h3>No hay eventos para check-in</h3>
            <p>El check-in está disponible para eventos confirmados, en preparación, en curso o realizados.</p>
        </div>
    </section>
<?php else: ?>
    <div class="grid grid-4 mb-3">
        <div class="kpi kpi--oscura">
            <p class="kpi__top"><?= icono('check') ?> Entradas registradas</p>
            <p class="kpi__valor"><?= (int) $stats['checkin'] ?></p>
            <p class="kpi__nota">de <?= (int) $stats['confirmados'] ?> confirmados</p>
        </div>
        <div class="kpi">
            <p class="kpi__top"><?= icono('invitados') ?> Asistencia</p>
            <p class="kpi__valor"><?= $avance ?>%</p>
            <div class="progreso progreso--lima mt-2"><span data-barra="<?= $avance ?>" style="width:0"></span></div>
        </div>
        <div class="kpi">
            <p class="kpi__top"><?= icono('reloj') ?> Por llegar</p>
            <p class="kpi__valor"><?= max(0, (int) $stats['confirmados'] - (int) $stats['checkin']) ?></p>
            <p class="kpi__nota">Invitados confirmados sin entrada</p>
        </div>
        <div class="kpi">
            <p class="kpi__top"><?= icono('calendario') ?> Evento</p>
            <p class="kpi__valor" style="font-size:1.3rem"><?= e(fecha($evento['fecha_inicio'], 'd M')) ?></p>
            <p class="kpi__nota"><?= e($evento['nombre']) ?></p>
        </div>
    </div>

    <div class="grid-detalle">
        <div class="stack">
            <section class="card">
                <div class="card__head">
                    <div><h2>Registro rápido por código</h2><p>Ideal para escanear el QR del enlace RSVP.</p></div>
                </div>
                <div class="card__body">
                    <form class="fila" method="post" action="<?= e(url('/checkin')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id_evento" value="<?= $idEvento ?>">
                        <input class="input" type="text" name="codigo" placeholder="Código de 32 caracteres o número de invitado" required autofocus style="flex:1">
                        <button class="btn btn--lima" type="submit"><?= icono('check') ?> Registrar entrada</button>
                    </form>
                </div>
            </section>

            <section class="card">
                <div class="card__head">
                    <div><h2>Lista de confirmados</h2><p>Los que ya entraron aparecen al final.</p></div>
                    <span class="badge badge--neutro"><?= count($invitados) ?> en pantalla</span>
                </div>
                <?php if ($invitados === []): ?>
                    <div class="empty">
                        <div class="empty__icono"><?= icono('buscar') ?></div>
                        <h3>Sin invitados confirmados</h3>
                        <p><?= $q !== '' ? 'No hay coincidencias para “' . e($q) . '”.' : 'Este evento todavía no tiene confirmaciones.' ?></p>
                    </div>
                <?php else: ?>
                    <div class="tabla-wrap">
                        <table class="tabla">
                            <thead><tr><th>Invitado</th><th>Mesa</th><th>Menú</th><th>Restricción</th><th>Entrada</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach ($invitados as $invitado): ?>
                                <tr>
                                    <td>
                                        <span class="tabla__principal">
                                            <span class="tabla__avatar"><?= e(iniciales($invitado['nombre'])) ?></span>
                                            <span>
                                                <b><?= e($invitado['nombre']) ?></b>
                                                <span class="mono texto-xs"><?= e(substr($invitado['token_acceso'], 0, 8)) ?>…</span>
                                            </span>
                                        </span>
                                    </td>
                                    <td><?= $invitado['mesa'] ? e($invitado['mesa']) : '<span class="texto-muted">—</span>' ?></td>
                                    <td class="texto-sm"><?= e($invitado['menu'] ?? '—') ?></td>
                                    <td class="texto-sm"><?= $invitado['restriccion_alimenticia'] === 'ninguna' ? '<span class="texto-muted">—</span>' : e(str_replace('_', ' ', $invitado['restriccion_alimenticia'])) ?></td>
                                    <td>
                                        <?php if ($invitado['checkin_en']): ?>
                                            <span class="texto-ok texto-sm"><?= icono('check') ?> <?= e(date('H:i', strtotime($invitado['checkin_en']))) ?> h</span>
                                        <?php else: ?>
                                            <span class="texto-muted texto-sm">Pendiente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$invitado['checkin_en']): ?>
                                            <form method="post" action="<?= e(url('/checkin')) ?>">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id_evento" value="<?= $idEvento ?>">
                                                <input type="hidden" name="codigo" value="<?= e($invitado['token_acceso']) ?>">
                                                <button class="btn btn--lima btn--xs" type="submit"><?= icono('check') ?> Entrada</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge badge--ok">Dentro</span>
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

        <aside class="grid-detalle__lado">
            <section class="card">
                <div class="card__head"><div><h2>Últimas entradas</h2><p>Registro más reciente del evento.</p></div></div>
                <div class="card__body">
                    <?php if ($recientes === []): ?>
                        <p class="texto-muted texto-sm">Todavía no se registró ninguna entrada.</p>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($recientes as $entrada): ?>
                                <div class="timeline__item timeline__item--ok">
                                    <span class="timeline__punto"><?= icono('check') ?></span>
                                    <div class="timeline__cuerpo">
                                        <b><?= e($entrada['nombre']) ?></b>
                                        <span><?= e(date('H:i', strtotime($entrada['checkin_en']))) ?> h · <?= e($entrada['usuario'] ?? 'Recepción') ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="card">
                <div class="card__head"><div><h2>Buenas prácticas</h2><p>Para una fila ágil.</p></div></div>
                <div class="card__body">
                    <ul class="lista-check">
                        <li><span class="marca"><?= icono('check') ?></span><span>Pide el enlace RSVP o el nombre completo.</span></li>
                        <li><span class="marca"><?= icono('check') ?></span><span>Solo se registra entrada a confirmados.</span></li>
                        <li><span class="marca"><?= icono('check') ?></span><span>Una segunda lectura avisa que ya entró.</span></li>
                        <li><span class="marca"><?= icono('check') ?></span><span>Las restricciones se ven antes de dejar pasar.</span></li>
                    </ul>
                </div>
            </section>
        </aside>
    </div>
<?php endif; ?>
