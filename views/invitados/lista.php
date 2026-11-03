<?php
/** @var array $evento */
/** @var array $invitados */
/** @var array $mesas */
/** @var array $menus */
/** @var array $stats */
$id = (int) $evento['id_evento'];
$confirmados = (int) $stats['confirmado'];
$avance = (int) $evento['aforo_maximo'] > 0 ? min(100, round(($confirmados / (int) $evento['aforo_maximo']) * 100)) : 0;
$operar = puede(['administrador', 'coordinador', 'operaciones']);
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Lista de invitados · <?= e(fecha($evento['fecha_inicio'], 'd M Y')) ?></p>
        <h1><?= e($evento['nombre']) ?></h1>
        <p class="page-head__sub"><?= (int) $stats['total'] ?> registrados · <?= $confirmados ?> confirmados de <?= (int) $evento['aforo_maximo'] ?> lugares · <?= (int) $stats['restricciones'] ?> con restricción alimenticia.</p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--fantasma" href="<?= e(url('/eventos/' . $id)) ?>"><?= icono('atras') ?> Evento</a>
        <a class="btn btn--claro" href="<?= e(url('/eventos/' . $id . '/mesas')) ?>"><?= icono('espacios') ?> Mesas</a>
        <button class="btn btn--lima" type="button" data-imprimir><?= icono('imprimir') ?> Imprimir lista</button>
    </div>
</div>

<div class="grid grid-4 mb-3">
    <div class="mini-kpi"><?= icono('check') ?><div><b><?= $confirmados ?></b><span>Confirmados</span></div></div>
    <div class="mini-kpi"><?= icono('reloj') ?><div><b><?= (int) $stats['pendiente'] ?></b><span>Pendientes</span></div></div>
    <div class="mini-kpi"><?= icono('x') ?><div><b><?= (int) $stats['declinado'] ?></b><span>Declinados</span></div></div>
    <div class="mini-kpi"><?= icono('cubiertos') ?><div><b><?= (int) $stats['restricciones'] ?></b><span>Restricciones</span></div></div>
</div>

<section class="card mb-3">
    <div class="card__head">
        <div><h2>Avance del aforo</h2><p>El sistema no permite confirmar por encima del máximo.</p></div>
        <span class="badge badge--acento"><?= $avance ?>%</span>
    </div>
    <div class="card__body">
        <div class="progreso progreso--lima"><span data-barra="<?= $avance ?>" style="width:0"></span></div>
        <div class="fila fila--entre mt-1">
            <span class="texto-muted texto-xs"><?= $confirmados ?> confirmados</span>
            <span class="texto-muted texto-xs"><?= max(0, (int) $evento['aforo_maximo'] - $confirmados) ?> lugares disponibles</span>
        </div>
    </div>
</section>

<div class="grid-detalle">
    <div class="stack">
        <section class="card">
            <div class="card__head">
                <div><h2>Lista completa</h2><p>Ordenada por categoría y nombre.</p></div>
            </div>
            <?php if ($invitados === []): ?>
                <div class="empty">
                    <div class="empty__icono"><?= icono('invitados') ?></div>
                    <h3>Aún no hay invitados</h3>
                    <p>Agrega el primero con el formulario de la derecha o importa una lista completa.</p>
                </div>
            <?php else: ?>
                <div class="tabla-wrap">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>Invitado</th>
                                <th>Categoría</th>
                                <th>Mesa</th>
                                <th>Menú</th>
                                <th>Restricción</th>
                                <th>RSVP</th>
                                <th>Enlace</th>
                                <?php if ($operar): ?><th>Cambiar</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($invitados as $invitado): ?>
                            <tr>
                                <td>
                                    <span class="tabla__principal">
                                        <span class="tabla__avatar"><?= e(iniciales($invitado['nombre'])) ?></span>
                                        <span>
                                            <b><?= e($invitado['nombre']) ?></b>
                                            <span><?= e($invitado['correo'] ?? 'Sin correo') ?><?= $invitado['telefono'] ? ' · ' . e($invitado['telefono']) : '' ?></span>
                                        </span>
                                    </span>
                                </td>
                                <td><?= badge(ucfirst($invitado['categoria']), $invitado['categoria'] === 'vip' ? 'acento' : 'neutro') ?></td>
                                <td><?= $invitado['mesa'] ? e($invitado['mesa']) : '<span class="texto-muted">—</span>' ?></td>
                                <td class="texto-sm"><?= e($invitado['menu'] ?? '—') ?></td>
                                <td class="texto-sm">
                                    <?= $invitado['restriccion_alimenticia'] === 'ninguna' ? '<span class="texto-muted">Ninguna</span>' : badge(ucfirst(str_replace('_', ' ', $invitado['restriccion_alimenticia'])), 'aviso') ?>
                                    <?php if (!empty($invitado['alergias'])): ?><br><span class="texto-error texto-xs"><?= icono('alerta') ?> <?= e($invitado['alergias']) ?></span><?php endif; ?>
                                </td>
                                <td><?= badge_estado($invitado['estado_rsvp']) ?></td>
                                <td>
                                    <?php if (puede(['administrador', 'coordinador'])): ?>
                                        <button class="btn btn--fantasma btn--xs" type="button" data-copiar="<?= e(url('/rsvp?token=' . $invitado['token_acceso'])) ?>"><?= icono('externo') ?> Copiar RSVP</button>
                                    <?php else: ?>
                                        <span class="texto-muted texto-xs" title="Solo coordinación comparte el enlace de invitación"><?= e(substr($invitado['token_acceso'], 0, 6)) ?>…</span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($operar): ?>
                                    <td>
                                        <form method="post" action="<?= e(url('/invitados/' . (int) $invitado['id_invitado'] . '/estado')) ?>">
                                            <?= csrf_field() ?>
                                            <select class="select select--mini" name="estado" data-autoenviar aria-label="Estado de <?= e($invitado['nombre']) ?>">
                                                <?php foreach (['pendiente' => 'Pendiente', 'confirmado' => 'Confirmar', 'declinado' => 'Declinar', 'lista_espera' => 'Espera'] as $clave => $etiqueta): ?>
                                                    <option value="<?= e($clave) ?>" <?= $invitado['estado_rsvp'] === $clave ? 'selected' : '' ?>><?= e($etiqueta) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="card">
            <div class="card__head"><div><h2>Restricciones y notas</h2><p>Información crítica para cocina y recepción.</p></div></div>
            <div class="card__body">
                <?php
                $conNotas = array_filter($invitados, static fn (array $i): bool => $i['restriccion_alimenticia'] !== 'ninguna' || !empty($i['alergias']) || !empty($i['notas']));
                ?>
                <?php if ($conNotas === []): ?>
                    <p class="texto-muted texto-sm">Ningún invitado registró restricciones ni notas especiales.</p>
                <?php else: ?>
                    <div class="lista-datos">
                        <?php foreach ($conNotas as $invitado): ?>
                            <div class="lista-datos__fila">
                                <span>
                                    <b><?= e($invitado['nombre']) ?></b>
                                    <?php if ($invitado['restriccion_alimenticia'] !== 'ninguna'): ?> · <?= e(str_replace('_', ' ', $invitado['restriccion_alimenticia'])) ?><?php endif; ?>
                                    <?php if (!empty($invitado['alergias'])): ?> · <span class="texto-error">Alergias: <?= e($invitado['alergias']) ?></span><?php endif; ?>
                                    <?php if (!empty($invitado['notas'])): ?> · <?= e($invitado['notas']) ?><?php endif; ?>
                                </span>
                                <b><?= e($invitado['mesa'] ?? 'Sin mesa') ?></b>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <aside class="grid-detalle__lado">
        <?php if ($operar): ?>
            <section class="card">
                <div class="card__head"><div><h2>Agregar invitado</h2><p>Se genera su enlace RSVP automáticamente.</p></div></div>
                <div class="card__body">
                    <form class="formulario" method="post" action="<?= e(url('/eventos/' . $id . '/invitados')) ?>">
                        <?= csrf_field() ?>
                        <div class="campo"><label for="nombre">Nombre <span class="campo__req">*</span></label><input class="input" type="text" id="nombre" name="nombre" maxlength="120" required></div>
                        <div class="campo"><label for="correo">Correo</label><input class="input" type="email" id="correo" name="correo" maxlength="120"></div>
                        <div class="campo"><label for="telefono">Teléfono</label><input class="input" type="text" id="telefono" name="telefono" maxlength="25"></div>
                        <div class="campo">
                            <label for="categoria">Categoría</label>
                            <select class="select" id="categoria" name="categoria">
                                <?php foreach (['general' => 'General', 'vip' => 'VIP', 'familia' => 'Familia', 'proveedor' => 'Proveedor', 'prensa' => 'Prensa', 'staff' => 'Staff'] as $clave => $etiqueta): ?>
                                    <option value="<?= e($clave) ?>"><?= e($etiqueta) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="campo">
                            <label for="id_mesa">Mesa</label>
                            <select class="select" id="id_mesa" name="id_mesa">
                                <option value="">Sin asignar</option>
                                <?php foreach ($mesas as $mesa): ?>
                                    <option value="<?= (int) $mesa['id_mesa'] ?>">Mesa <?= e($mesa['numero']) ?> · <?= (int) $mesa['capacidad'] ?> lugares</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="campo">
                            <label for="restriccion_alimenticia">Restricción alimenticia</label>
                            <select class="select" id="restriccion_alimenticia" name="restriccion_alimenticia">
                                <?php foreach (['ninguna' => 'Ninguna', 'vegetariano' => 'Vegetariano', 'vegano' => 'Vegano', 'sin_gluten' => 'Sin gluten', 'otra' => 'Otra'] as $clave => $etiqueta): ?>
                                    <option value="<?= e($clave) ?>"><?= e($etiqueta) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="campo"><label for="alergias">Alergias</label><input class="input" type="text" id="alergias" name="alergias" maxlength="220" placeholder="Nueces, mariscos…"></div>
                        <div class="campo"><label for="notas">Notas</label><input class="input" type="text" id="notas" name="notas" maxlength="255"></div>
                        <button class="btn btn--lima btn--bloque" type="submit"><?= icono('mas') ?> Agregar invitado</button>
                    </form>
                </div>
                <div class="card__foot">
                    <a class="btn btn--claro btn--sm btn--bloque" href="<?= e(url('/invitados/importar')) ?>"><?= icono('descargar') ?> Importar varios</a>
                </div>
            </section>
        <?php endif; ?>

        <section class="card">
            <div class="card__head"><div><h2>Menús contratados</h2><p>Para asignar el menú de cada invitado.</p></div></div>
            <div class="card__body">
                <?php if ($menus === []): ?>
                    <p class="texto-muted texto-sm">El evento no tiene menús contratados. Se asignan desde el expediente del evento.</p>
                <?php else: ?>
                    <div class="lista-datos">
                        <?php foreach ($menus as $menu): ?>
                            <div class="lista-datos__fila"><span><?= e($menu['nombre']) ?></span><b><?= e(money($menu['precio_persona'])) ?></b></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card__head"><div><h2>Resumen RSVP</h2><p>Estado actual de la lista.</p></div></div>
            <div class="card__body">
                <div class="distribucion-lista">
                    <?php foreach (['confirmado' => 'Confirmados', 'pendiente' => 'Pendientes', 'declinado' => 'Declinados', 'lista_espera' => 'Lista de espera'] as $clave => $etiqueta): ?>
                        <div class="distribucion-lista__fila">
                            <span><?= e($etiqueta) ?></span>
                            <div class="progreso"><span data-barra="<?= porcentaje((int) $stats[$clave], max(1, (int) $stats['total'])) ?>" style="width:0"></span></div>
                            <b><?= (int) $stats[$clave] ?></b>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </aside>
</div>
