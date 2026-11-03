<?php
/** @var array $evento */
/** @var array $mesas */
/** @var array $porMesa */
/** @var array $sinMesa */
$id = (int) $evento['id_evento'];
$totalLugares = array_sum(array_map(static fn (array $m): int => (int) $m['capacidad'], $mesas));
$asignados = 0;
foreach ($porMesa as $grupo) {
    $asignados += count($grupo);
}
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Montaje · <?= e($evento['nombre']) ?></p>
        <h1>Mesas y seating</h1>
        <p class="page-head__sub"><?= count($mesas) ?> mesas · <?= $totalLugares ?> lugares · <?= $asignados ?> invitados asignados · <?= count($sinMesa) ?> sin mesa.</p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--fantasma" href="<?= e(url('/eventos/' . $id . '/invitados')) ?>"><?= icono('atras') ?> Invitados</a>
        <button class="btn btn--claro" type="button" data-imprimir><?= icono('imprimir') ?> Imprimir croquis</button>
    </div>
</div>

<div class="grid-detalle">
    <div class="stack">
        <section class="card">
            <div class="card__head">
                <div><h2>Distribución de mesas</h2><p>Capacidad validada al asignar cada invitado.</p></div>
                <span class="badge badge--neutro"><?= $asignados ?> / <?= $totalLugares ?> lugares</span>
            </div>
            <div class="card__body">
                <?php if ($mesas === []): ?>
                    <div class="empty">
                        <div class="empty__icono"><?= icono('espacios') ?></div>
                        <h3>Sin mesas creadas</h3>
                        <p>Crea la primera mesa con el formulario de la derecha y luego asigna invitados.</p>
                    </div>
                <?php else: ?>
                    <div class="mesas-grid">
                        <?php foreach ($mesas as $mesa): ?>
                            <?php
                            $ocupantes = $porMesa[(int) $mesa['id_mesa']] ?? [];
                            $ocupacion = (int) $mesa['capacidad'] > 0 ? min(100, round((count($ocupantes) / (int) $mesa['capacidad']) * 100)) : 0;
                            ?>
                            <article class="mesa-card">
                                <div class="mesa-card__top">
                                    <b>Mesa <?= e($mesa['numero']) ?></b>
                                    <span><?= e($mesa['zona'] ?? 'Sin zona') ?></span>
                                </div>
                                <div class="mesa-card__lista">
                                    <?php if ($ocupantes === []): ?>
                                        <span class="texto-muted texto-xs">Mesa vacía</span>
                                    <?php else: ?>
                                        <?php foreach ($ocupantes as $ocupante): ?>
                                            <span><?= e($ocupante['nombre']) ?><?= $ocupante['estado_rsvp'] !== 'confirmado' ? ' · ' . e($ocupante['estado_rsvp']) : '' ?></span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="mesa-card__pie">
                                    <div class="progreso"><span data-barra="<?= $ocupacion ?>" style="width:0"></span></div>
                                    <span class="mesa-card__num"><?= count($ocupantes) ?> / <?= (int) $mesa['capacidad'] ?></span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card__head">
                <div><h2>Invitados sin mesa</h2><p>Asigna un lugar disponible sin exceder la capacidad.</p></div>
                <span class="badge badge--aviso"><?= count($sinMesa) ?></span>
            </div>
            <?php if ($sinMesa === []): ?>
                <div class="card__body"><div class="aviso-inline"><?= icono('check') ?> <span>Todos los invitados tienen mesa asignada.</span></div></div>
            <?php else: ?>
                <div class="tabla-wrap">
                    <table class="tabla">
                        <thead><tr><th>Invitado</th><th>Categoría</th><th>RSVP</th><th>Asignar mesa</th></tr></thead>
                        <tbody>
                        <?php foreach ($sinMesa as $invitado): ?>
                            <tr>
                                <td>
                                    <span class="tabla__principal">
                                        <span class="tabla__avatar"><?= e(iniciales($invitado['nombre'])) ?></span>
                                        <span><b><?= e($invitado['nombre']) ?></b><span><?= e($invitado['correo'] ?? 'Sin correo') ?></span></span>
                                    </span>
                                </td>
                                <td><?= badge(ucfirst($invitado['categoria']), 'neutro') ?></td>
                                <td><?= badge_estado($invitado['estado_rsvp']) ?></td>
                                <td>
                                    <form class="fila" method="post" action="<?= e(url('/invitados/' . (int) $invitado['id_invitado'] . '/mesa')) ?>">
                                        <?= csrf_field() ?>
                                        <select class="select select--mini" name="id_mesa" required>
                                            <option value="">Mesa…</option>
                                            <?php foreach ($mesas as $mesa): ?>
                                                <?php $libres = (int) $mesa['capacidad'] - count($porMesa[(int) $mesa['id_mesa']] ?? []); ?>
                                                <option value="<?= (int) $mesa['id_mesa'] ?>" <?= $libres <= 0 ? 'disabled' : '' ?>>
                                                    <?= e($mesa['numero']) ?> · <?= max(0, $libres) ?> libres
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn--claro btn--xs" type="submit"><?= icono('check') ?></button>
                                    </form>
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
            <div class="card__head"><div><h2>Nueva mesa</h2><p>Define número, capacidad y zona.</p></div></div>
            <div class="card__body">
                <form class="formulario" method="post" action="<?= e(url('/eventos/' . $id . '/mesas')) ?>">
                    <?= csrf_field() ?>
                    <div class="campo"><label for="numero">Número o nombre <span class="campo__req">*</span></label><input class="input" type="text" id="numero" name="numero" maxlength="12" placeholder="M5" required></div>
                    <div class="campo"><label for="capacidad">Capacidad <span class="campo__req">*</span></label><input class="input" type="number" id="capacidad" name="capacidad" min="1" max="40" value="10" required></div>
                    <div class="campo"><label for="zona">Zona</label><input class="input" type="text" id="zona" name="zona" maxlength="40" placeholder="Familia, amigos, honor…"></div>
                    <button class="btn btn--lima btn--bloque" type="submit"><?= icono('mas') ?> Crear mesa</button>
                </form>
            </div>
        </section>

        <section class="card">
            <div class="card__head"><div><h2>Reglas de asignación</h2><p>El servidor valida todo.</p></div></div>
            <div class="card__body">
                <ul class="lista-check">
                    <li><span class="marca"><?= icono('check') ?></span><span>La mesa debe pertenecer al mismo evento.</span></li>
                    <li><span class="marca"><?= icono('check') ?></span><span>No se puede exceder la capacidad de la mesa.</span></li>
                    <li><span class="marca"><?= icono('check') ?></span><span>Reasignar libera el lugar anterior.</span></li>
                    <li><span class="marca"><?= icono('check') ?></span><span>El cambio queda en historial y bitácora.</span></li>
                </ul>
            </div>
        </section>
    </aside>
</div>
