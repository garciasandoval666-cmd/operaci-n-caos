<?php
/** @var array $platillos */
/** @var array $alergenos */
/** @var array $menus */
$tipos = ['entrada' => 'Entrada', 'sopa' => 'Sopa', 'plato_fuerte' => 'Plato fuerte', 'postre' => 'Postre', 'bebida' => 'Bebida', 'snack' => 'Snack'];
$porTipo = [];
foreach ($platillos as $platillo) {
    $porTipo[$platillo['tipo']] = ($porTipo[$platillo['tipo']] ?? 0) + 1;
}
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Recetario · Fichas técnicas</p>
        <h1>Platillos y alérgenos</h1>
        <p class="page-head__sub"><?= count($platillos) ?> platillos · <?= count($alergenos) ?> alérgenos catalogados. Esta información alimenta la hoja de producción y las restricciones de los invitados.</p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--fantasma" href="<?= e(url('/menus')) ?>"><?= icono('atras') ?> Menús</a>
    </div>
</div>

<div class="grid grid-4 mb-3">
    <?php foreach ($tipos as $clave => $etiqueta): ?>
        <div class="mini-kpi"><?= icono('cubiertos') ?><div><b><?= (int) ($porTipo[$clave] ?? 0) ?></b><span><?= e($etiqueta) ?></span></div></div>
    <?php endforeach; ?>
</div>

<div class="grid-detalle">
    <div class="stack">
        <section class="card">
            <div class="card__head">
                <div><h2>Recetario</h2><p>Ficha por platillo con alérgenos y banderas vegetarianas.</p></div>
            </div>
            <?php if ($platillos === []): ?>
                <div class="empty">
                    <div class="empty__icono"><?= icono('cubiertos') ?></div>
                    <h3>Sin platillos</h3>
                    <p>Agrega el primer platillo para armar menús.</p>
                </div>
            <?php else: ?>
                <div class="tabla-wrap">
                    <table class="tabla">
                        <thead>
                            <tr><th>Platillo</th><th>Tipo</th><th>Dieta</th><th>Alérgenos</th><th>Estado</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($platillos as $platillo): ?>
                            <tr>
                                <td>
                                    <b><?= e($platillo['nombre']) ?></b>
                                    <span class="texto-muted texto-xs"><?= e($platillo['descripcion'] ?? '') ?></span>
                                </td>
                                <td><?= badge($tipos[$platillo['tipo']] ?? ucfirst($platillo['tipo']), 'neutro') ?></td>
                                <td>
                                    <?php if ((int) $platillo['es_vegano'] === 1): ?>
                                        <?= badge('Vegano', 'ok') ?>
                                    <?php elseif ((int) $platillo['es_vegetariano'] === 1): ?>
                                        <?= badge('Vegetariano', 'ok') ?>
                                    <?php else: ?>
                                        <span class="texto-muted texto-sm">Estándar</span>
                                    <?php endif; ?>
                                </td>
                                <td class="texto-sm">
                                    <?php if (!empty($platillo['alergenos'])): ?>
                                        <span class="texto-error"><?= icono('alerta') ?> <?= e($platillo['alergenos']) ?></span>
                                    <?php else: ?>
                                        <span class="texto-muted">Sin alérgenos registrados</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= badge_estado((int) $platillo['activo'] === 1 ? 'activo' : 'inactivo') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <aside class="grid-detalle__lado">
        <?php if (puede(['administrador', 'banquetes'])): ?>
            <section class="card">
                <div class="card__head"><div><h2>Nuevo platillo</h2><p>Queda disponible para los menús.</p></div></div>
                <div class="card__body">
                    <form class="formulario" method="post" action="<?= e(url('/menus/platillos')) ?>">
                        <?= csrf_field() ?>
                        <div class="campo"><label for="nombre">Nombre <span class="campo__req">*</span></label><input class="input" type="text" id="nombre" name="nombre" maxlength="100" required></div>
                        <div class="campo">
                            <label for="tipo">Tipo <span class="campo__req">*</span></label>
                            <select class="select" id="tipo" name="tipo" required>
                                <?php foreach ($tipos as $clave => $etiqueta): ?>
                                    <option value="<?= e($clave) ?>"><?= e($etiqueta) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="campo"><label for="descripcion">Descripción</label><textarea class="textarea" id="descripcion" name="descripcion" maxlength="255" rows="2"></textarea></div>
                        <div class="campo">
                            <label class="campo-check"><input type="checkbox" name="es_vegetariano" value="1"><span>Es vegetariano</span></label>
                            <label class="campo-check"><input type="checkbox" name="es_vegano" value="1"><span>Es vegano</span></label>
                        </div>
                        <div class="campo">
                            <label>Alérgenos</label>
                            <div class="stack" style="gap:6px">
                                <?php foreach ($alergenos as $alergeno): ?>
                                    <label class="campo-check">
                                        <input type="checkbox" name="alergenos[]" value="<?= (int) $alergeno['id_alergeno'] ?>">
                                        <span><?= e($alergeno['nombre']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <button class="btn btn--lima btn--bloque mt-2" type="submit"><?= icono('mas') ?> Agregar platillo</button>
                    </form>
                </div>
            </section>
        <?php endif; ?>

        <section class="card">
            <div class="card__head"><div><h2>Menús que lo usan</h2><p>Referencia rápida.</p></div></div>
            <div class="card__body">
                <?php if ($menus === []): ?>
                    <p class="texto-muted texto-sm">No hay menús activos.</p>
                <?php else: ?>
                    <div class="lista-datos">
                        <?php foreach ($menus as $menu): ?>
                            <div class="lista-datos__fila"><span><?= e($menu['nombre']) ?></span><b>Activo</b></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </aside>
</div>
