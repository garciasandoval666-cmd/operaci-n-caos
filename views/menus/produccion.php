<?php
/** @var array $evento */
/** @var array $menus */
/** @var array $restricciones */
/** @var array $platillos */
/** @var array $insumos */
/** @var array $casos */
/** @var int $personas */
$id = (int) $evento['id_evento'];
$etiquetas = ['ninguna' => 'Sin restricción', 'vegetariano' => 'Vegetariano', 'vegano' => 'Vegano', 'sin_gluten' => 'Sin gluten', 'otra' => 'Otra'];
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Cocina · Hoja de producción</p>
        <h1><?= e($evento['nombre']) ?></h1>
        <p class="page-head__sub"><?= e(fecha_larga($evento['fecha_inicio'])) ?> · <?= $personas ?> personas en menús · <?= count($casos) ?> casos especiales · <?= count($insumos) ?> insumos apartados.</p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--fantasma" href="<?= e(url('/eventos/' . $id)) ?>"><?= icono('atras') ?> Evento</a>
        <button class="btn btn--lima" type="button" data-imprimir><?= icono('imprimir') ?> Imprimir hoja</button>
    </div>
</div>

<div class="aviso-print">
    <b>ConectaEventos · Hoja de producción</b>
    <span><?= e($evento['nombre']) ?> · <?= e(fecha_larga($evento['fecha_inicio'])) ?></span>
</div>

<div class="grid grid-4 mb-3">
    <div class="mini-kpi"><?= icono('cubiertos') ?><div><b><?= $personas ?></b><span>Personas en menús</span></div></div>
    <div class="mini-kpi"><?= icono('menu') ?><div><b><?= count($menus) ?></b><span>Menús contratados</span></div></div>
    <div class="mini-kpi"><?= icono('alerta') ?><div><b><?= count($casos) ?></b><span>Casos especiales</span></div></div>
    <div class="mini-kpi"><?= icono('paquete') ?><div><b><?= count($insumos) ?></b><span>Insumos apartados</span></div></div>
</div>

<div class="grid-detalle">
    <div class="stack">
        <section class="card">
            <div class="card__head"><div><h2>Menús contratados</h2><p>Personas y precio por persona.</p></div></div>
            <?php if ($menus === []): ?>
                <div class="card__body"><div class="aviso-inline aviso-inline--error"><?= icono('alerta') ?> <span>Este evento no tiene menú contratado. Cocina no puede producir sin esta información.</span></div></div>
            <?php else: ?>
                <div class="tabla-wrap">
                    <table class="tabla">
                        <thead><tr><th>Menú</th><th>Servicio</th><th class="num">Personas</th><th class="num">Precio persona</th><th class="num">Importe</th><th>Notas</th></tr></thead>
                        <tbody>
                        <?php foreach ($menus as $menu): ?>
                            <tr>
                                <td><b><?= e($menu['nombre']) ?></b></td>
                                <td class="texto-sm"><?= e(ucfirst(str_replace('_', ' ', $menu['tipo_servicio']))) ?></td>
                                <td class="num"><?= (int) $menu['personas'] ?></td>
                                <td class="num"><?= e(money($menu['precio_persona'])) ?></td>
                                <td class="num"><b><?= e(money((float) $menu['precio_persona'] * (int) $menu['personas'])) ?></b></td>
                                <td class="texto-sm"><?= e($menu['notas'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="card">
            <div class="card__head"><div><h2>Platillos a producir</h2><p>Consolidado de todos los menús del evento.</p></div></div>
            <?php if ($platillos === []): ?>
                <div class="card__body"><p class="texto-muted texto-sm">Sin platillos vinculados a los menús contratados.</p></div>
            <?php else: ?>
                <div class="tabla-wrap">
                    <table class="tabla">
                        <thead><tr><th>Platillo</th><th>Tipo</th><th>Menú</th><th>Dieta</th><th>Alérgenos</th></tr></thead>
                        <tbody>
                        <?php foreach ($platillos as $platillo): ?>
                            <tr>
                                <td><b><?= e($platillo['nombre']) ?></b></td>
                                <td class="texto-sm"><?= e(str_replace('_', ' ', $platillo['tipo'])) ?></td>
                                <td class="texto-sm"><?= e($platillo['menu']) ?></td>
                                <td>
                                    <?php if ((int) $platillo['es_vegano'] === 1): ?><?= badge('Vegano', 'ok') ?>
                                    <?php elseif ((int) $platillo['es_vegetariano'] === 1): ?><?= badge('Vegetariano', 'ok') ?>
                                    <?php else: ?><span class="texto-muted texto-sm">Estándar</span><?php endif; ?>
                                </td>
                                <td class="texto-sm"><?= !empty($platillo['alergenos']) ? '<span class="texto-error">' . e($platillo['alergenos']) . '</span>' : '<span class="texto-muted">—</span>' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="card">
            <div class="card__head"><div><h2>Casos especiales</h2><p>Invitados confirmados con restricción o alergia.</p></div></div>
            <?php if ($casos === []): ?>
                <div class="card__body"><div class="aviso-inline"><?= icono('check') ?> <span>Sin restricciones alimenticias entre los confirmados.</span></div></div>
            <?php else: ?>
                <div class="tabla-wrap">
                    <table class="tabla">
                        <thead><tr><th>Invitado</th><th>Mesa</th><th>Restricción</th><th>Alergias</th></tr></thead>
                        <tbody>
                        <?php foreach ($casos as $caso): ?>
                            <tr>
                                <td><b><?= e($caso['nombre']) ?></b></td>
                                <td><?= $caso['mesa'] ? e($caso['mesa']) : '<span class="texto-muted">—</span>' ?></td>
                                <td><?= badge($etiquetas[$caso['restriccion_alimenticia']] ?? $caso['restriccion_alimenticia'], 'aviso') ?></td>
                                <td class="texto-sm"><?= !empty($caso['alergias']) ? '<span class="texto-error">' . icono('alerta') . ' ' . e($caso['alergias']) . '</span>' : '<span class="texto-muted">—</span>' ?></td>
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
            <div class="card__head"><div><h2>Resumen de restricciones</h2><p>Conteo por tipo.</p></div></div>
            <div class="card__body">
                <?php if ($restricciones === []): ?>
                    <p class="texto-muted texto-sm">Sin restricciones registradas.</p>
                <?php else: ?>
                    <div class="distribucion-lista">
                        <?php foreach ($restricciones as $clave => $cantidad): ?>
                            <div class="distribucion-lista__fila">
                                <span><?= e($etiquetas[$clave] ?? $clave) ?></span>
                                <div class="progreso"><span data-barra="<?= porcentaje((int) $cantidad, max(1, array_sum($restricciones))) ?>" style="width:0"></span></div>
                                <b><?= (int) $cantidad ?></b>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card__head"><div><h2>Insumos apartados</h2><p>Stock comprometido para el evento.</p></div></div>
            <div class="card__body">
                <?php if ($insumos === []): ?>
                    <p class="texto-muted texto-sm">Sin insumos apartados.</p>
                <?php else: ?>
                    <div class="lista-datos">
                        <?php foreach ($insumos as $insumo): ?>
                            <div class="lista-datos__fila">
                                <span><?= e($insumo['nombre']) ?></span>
                                <b><?= e(number_format((float) $insumo['cantidad'], 2)) ?> <?= e($insumo['unidad']) ?></b>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card__head"><div><h2>Checklist de cocina</h2><p>Antes de montar.</p></div></div>
            <div class="card__body">
                <ul class="lista-check">
                    <li><span class="marca"><?= icono('check') ?></span><span>Confirmar número final de personas.</span></li>
                    <li><span class="marca"><?= icono('check') ?></span><span>Separar platos para restricciones y alergias.</span></li>
                    <li><span class="marca"><?= icono('check') ?></span><span>Verificar insumos comprometidos.</span></li>
                    <li><span class="marca"><?= icono('check') ?></span><span>Etiquetar alérgenos en cada estación.</span></li>
                </ul>
            </div>
        </section>
    </aside>
</div>
