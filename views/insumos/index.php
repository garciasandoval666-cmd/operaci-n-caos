<?php
/** @var array $insumos */
/** @var array $reservas */
/** @var array $movimientos */
/** @var array $eventos */
/** @var int $bajos */

$categorias = [
    'entrada'     => 'Entrada',
    'plato_fuerte'=> 'Plato fuerte',
    'postre'      => 'Postre',
    'bebida'      => 'Bebida',
    'abarrote'    => 'Abarrote',
    'otro'        => 'Otro',
];
$totalFisico = array_sum(array_map(static fn (array $i): float => (float) $i['stock_fisico'], $insumos));
$totalComprometido = array_sum(array_map(static fn (array $i): float => (float) $i['stock_comprometido'], $insumos));
$bajoMinimo = array_values(array_filter($insumos, static fn (array $i): bool => (int) $i['bajo_minimo'] === 1));
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Módulo B · Cocina y bodega</p>
        <h1>Insumos y kardex</h1>
        <p class="page-head__sub"><?= e($subtitulo ?? '') ?></p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--claro" href="<?= e(url('/reportes/produccion')) ?>"><?= icono('reportes') ?> Producción</a>
        <a class="btn btn--fantasma" href="<?= e(url('/menus')) ?>"><?= icono('cubiertos') ?> Menús</a>
    </div>
</div>

<div class="kpis">
    <div class="kpi kpi--oscura">
        <div class="kpi__top"><?= icono('insumos') ?> Insumos</div>
        <p class="kpi__valor"><?= count($insumos) ?></p>
        <p class="kpi__nota"><?= number_format($totalFisico, 1) ?> unidades físicas</p>
    </div>
    <div class="kpi">
        <div class="kpi__top"><?= icono('candado') ?> Comprometido</div>
        <p class="kpi__valor"><?= number_format($totalComprometido, 1) ?></p>
        <p class="kpi__nota">apartado para eventos</p>
    </div>
    <div class="kpi kpi--lima">
        <div class="kpi__top"><?= icono('paquete') ?> Disponible</div>
        <p class="kpi__valor"><?= number_format(max(0, $totalFisico - $totalComprometido), 1) ?></p>
        <p class="kpi__nota">listo para apartar</p>
    </div>
    <div class="kpi <?= $bajos > 0 ? '' : 'kpi--lima' ?>">
        <div class="kpi__top"><?= icono('alerta') ?> Bajo mínimo</div>
        <p class="kpi__valor"><?= $bajos ?></p>
        <p class="kpi__nota"><?= $bajos > 0 ? 'requieren reposición' : 'inventario saludable' ?></p>
    </div>
</div>

<?php if ($bajoMinimo !== []): ?>
    <div class="aviso-inline aviso-inline--error mb-2">
        <?= icono('alerta') ?>
        <span><b>Reposición pendiente:</b> <?= e(implode(', ', array_map(static fn (array $i): string => $i['nombre'], $bajoMinimo))) ?>.</span>
    </div>
<?php endif; ?>

<div class="grid-detalle">
    <div class="stack">
        <section class="card">
            <div class="card__head">
                <div><h2>Inventario de insumos</h2><p>Físico menos comprometido: lo que realmente puede usarse hoy.</p></div>
                <a class="btn btn--fantasma btn--sm" href="<?= e(url('/reportes/exportar?tipo=inventario')) ?>"><?= icono('descargar') ?> CSV</a>
            </div>

            <?php if ($insumos === []): ?>
                <div class="empty">
                    <div class="empty__icono"><?= icono('insumos') ?></div>
                    <h3>Sin insumos registrados</h3>
                    <p>Agrega insumos para controlar stock, mínimos y apartados por evento.</p>
                </div>
            <?php else: ?>
                <div class="tabla-wrap">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>Insumo</th>
                                <th>Categoría</th>
                                <th class="num">Físico</th>
                                <th class="num">Comprometido</th>
                                <th class="num">Disponible</th>
                                <th class="num">Mínimo</th>
                                <th style="min-width:120px">Nivel</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($insumos as $insumo): ?>
                            <?php
                            $fisico = (float) $insumo['stock_fisico'];
                            $disponible = (float) $insumo['disponible'];
                            $nivel = $fisico > 0 ? min(100, round(($disponible / $fisico) * 100)) : 0;
                            ?>
                            <tr>
                                <td>
                                    <a class="tabla__principal" href="<?= e(url('/insumos/' . (int) $insumo['id_insumo'] . '/kardex')) ?>">
                                        <span class="tabla__avatar"><?= icono('insumos') ?></span>
                                        <span><b><?= e($insumo['nombre']) ?></b><span>por <?= e($insumo['unidad']) ?></span></span>
                                    </a>
                                </td>
                                <td class="texto-sm"><?= e($categorias[$insumo['categoria']] ?? ucfirst($insumo['categoria'])) ?></td>
                                <td class="num"><?= number_format($fisico, 1) ?></td>
                                <td class="num"><?= number_format((float) $insumo['stock_comprometido'], 1) ?></td>
                                <td class="num"><b class="<?= $disponible <= (float) $insumo['stock_minimo'] ? 'texto-error' : 'texto-ok' ?>"><?= number_format($disponible, 1) ?></b></td>
                                <td class="num texto-muted"><?= number_format((float) $insumo['stock_minimo'], 1) ?></td>
                                <td>
                                    <div class="progreso <?= (int) $insumo['bajo_minimo'] === 1 ? 'progreso--aviso' : 'progreso--ok' ?>">
                                        <span style="width:<?= $nivel ?>%"></span>
                                    </div>
                                    <span class="texto-muted texto-xs"><?= $nivel ?>% libre</span>
                                </td>
                                <td><div class="acciones"><a class="btn btn--fantasma btn--xs" href="<?= e(url('/insumos/' . (int) $insumo['id_insumo'] . '/kardex')) ?>"><?= icono('bitacora') ?> Kardex</a></div></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <div class="grid grid-2">
            <section class="card">
                <div class="card__head"><div><h2>Apartados por evento</h2><p>Compromisos confirmados.</p></div></div>
                <?php if ($reservas === []): ?>
                    <div class="tabla__vacia">Sin apartados de insumo.</div>
                <?php else: ?>
                    <div class="tabla-wrap">
                        <table class="tabla tabla--compacta">
                            <thead><tr><th>Insumo</th><th>Evento</th><th class="num">Cantidad</th><th>Estado</th></tr></thead>
                            <tbody>
                            <?php foreach ($reservas as $reserva): ?>
                                <tr>
                                    <td><b><?= e($reserva['nombre']) ?></b></td>
                                    <td class="texto-sm"><a href="<?= e(url('/eventos/' . (int) $reserva['id_evento'])) ?>"><?= e($reserva['evento']) ?></a></td>
                                    <td class="num"><?= number_format((float) $reserva['cantidad'], 1) ?> <?= e($reserva['unidad']) ?></td>
                                    <td><?= badge_estado($reserva['estado']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <section class="card">
                <div class="card__head"><div><h2>Últimos movimientos</h2><p>Trazabilidad del kardex.</p></div></div>
                <?php if ($movimientos === []): ?>
                    <div class="tabla__vacia">Sin movimientos registrados.</div>
                <?php else: ?>
                    <div class="tabla-wrap">
                        <table class="tabla tabla--compacta">
                            <thead><tr><th>Insumo</th><th>Tipo</th><th class="num">Cantidad</th><th>Fecha</th></tr></thead>
                            <tbody>
                            <?php foreach ($movimientos as $movimiento): ?>
                                <tr>
                                    <td><b><?= e($movimiento['nombre']) ?></b><span class="texto-muted texto-xs"><?= e(recortar($movimiento['motivo'], 38)) ?></span></td>
                                    <td><?= badge_estado($movimiento['tipo']) ?></td>
                                    <td class="num"><?= number_format((float) $movimiento['cantidad'], 1) ?></td>
                                    <td class="texto-sm texto-muted"><?= e(fecha_hora($movimiento['fecha_movimiento'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <aside class="grid-detalle__lado">
        <?php if (puede(['administrador', 'banquetes'])): ?>
            <section class="card">
                <div class="card__head"><div><h2>Movimiento de stock</h2><p>Entrada o ajuste con motivo.</p></div></div>
                <div class="card__body">
                    <form class="formulario" method="post" action="<?= e(url('/insumos/movimiento')) ?>">
                        <?= csrf_field() ?>
                        <div class="campo">
                            <label for="id_insumo">Insumo <span class="campo__req">*</span></label>
                            <select class="select" id="id_insumo" name="id_insumo" required>
                                <option value="">Selecciona…</option>
                                <?php foreach ($insumos as $insumo): ?>
                                    <option value="<?= (int) $insumo['id_insumo'] ?>"><?= e($insumo['nombre']) ?> · <?= number_format((float) $insumo['disponible'], 1) ?> <?= e($insumo['unidad']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="campo">
                            <label for="tipo_mov">Tipo <span class="campo__req">*</span></label>
                            <select class="select" id="tipo_mov" name="tipo" required data-tipo-mov>
                                <option value="entrada">Entrada de stock</option>
                                <option value="ajuste">Ajuste de inventario</option>
                            </select>
                        </div>
                        <div class="campo" data-campo="entrada">
                            <label for="cantidad">Cantidad a sumar</label>
                            <input class="input" type="number" id="cantidad" name="cantidad" min="0.01" step="0.01">
                        </div>
                        <div class="campo" data-campo="ajuste" hidden>
                            <label for="nuevo_stock">Nuevo stock físico</label>
                            <input class="input" type="number" id="nuevo_stock" name="nuevo_stock" min="0" step="0.01">
                        </div>
                        <div class="campo">
                            <label for="motivo_mov">Motivo <span class="campo__req">*</span></label>
                            <input class="input" type="text" id="motivo_mov" name="motivo" maxlength="220" required placeholder="Compra, merma, conteo físico…">
                        </div>
                        <button class="btn btn--lima btn--bloque" type="submit"><?= icono('refrescar') ?> Registrar movimiento</button>
                    </form>
                </div>
            </section>

            <section class="card">
                <div class="card__head"><div><h2>Apartar para evento</h2><p>Compromete stock sin consumirlo.</p></div></div>
                <div class="card__body">
                    <form class="formulario" method="post" action="<?= e(url('/eventos/' . (int) ($eventos[0]['id_evento'] ?? 0) . '/insumos')) ?>" id="form-apartar">
                        <?= csrf_field() ?>
                        <div class="campo">
                            <label for="evento_insumo">Evento <span class="campo__req">*</span></label>
                            <select class="select" id="evento_insumo" name="evento_destino" required data-evento-insumo>
                                <?php foreach ($eventos as $evento): ?>
                                    <option value="<?= (int) $evento['id_evento'] ?>"><?= e($evento['nombre']) ?> · <?= e(fecha($evento['fecha_inicio'], 'd M Y')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="campo">
                            <label for="insumo_evento">Insumo <span class="campo__req">*</span></label>
                            <select class="select" id="insumo_evento" name="id_insumo" required>
                                <?php foreach ($insumos as $insumo): ?>
                                    <option value="<?= (int) $insumo['id_insumo'] ?>"><?= e($insumo['nombre']) ?> · <?= number_format((float) $insumo['disponible'], 1) ?> libres</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="campo">
                            <label for="cantidad_evento">Cantidad <span class="campo__req">*</span></label>
                            <input class="input" type="number" id="cantidad_evento" name="cantidad" min="0.01" step="0.01" value="1" required>
                        </div>
                        <div class="campo">
                            <label for="motivo_evento">Motivo</label>
                            <input class="input" type="text" id="motivo_evento" name="motivo" maxlength="220" placeholder="Menú del evento">
                        </div>
                        <button class="btn btn--primario btn--bloque" type="submit" <?= $eventos === [] ? 'disabled' : '' ?>><?= icono('candado') ?> Apartar insumo</button>
                        <?php if ($eventos === []): ?><p class="campo__ayuda">No hay eventos abiertos para apartar.</p><?php endif; ?>
                    </form>
                </div>
            </section>

            <section class="card">
                <div class="card__head"><div><h2>Nuevo insumo</h2><p>Alta en el catálogo.</p></div></div>
                <div class="card__body">
                    <form class="formulario" method="post" action="<?= e(url('/insumos')) ?>">
                        <?= csrf_field() ?>
                        <div class="campo">
                            <label for="nombre_insumo">Nombre <span class="campo__req">*</span></label>
                            <input class="input" type="text" id="nombre_insumo" name="nombre" maxlength="120" required>
                        </div>
                        <div class="campo-fila">
                            <div class="campo">
                                <label for="categoria_insumo">Categoría</label>
                                <select class="select" id="categoria_insumo" name="categoria" required>
                                    <?php foreach ($categorias as $clave => $etiqueta): ?>
                                        <option value="<?= e($clave) ?>"><?= e($etiqueta) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="campo">
                                <label for="unidad_insumo">Unidad</label>
                                <input class="input" type="text" id="unidad_insumo" name="unidad" maxlength="20" value="porcion">
                            </div>
                        </div>
                        <div class="campo-fila">
                            <div class="campo">
                                <label for="stock_fisico">Stock inicial</label>
                                <input class="input" type="number" id="stock_fisico" name="stock_fisico" min="0" step="0.01" value="0">
                            </div>
                            <div class="campo">
                                <label for="stock_minimo">Mínimo</label>
                                <input class="input" type="number" id="stock_minimo" name="stock_minimo" min="0" step="0.01" value="0">
                            </div>
                        </div>
                        <div class="campo">
                            <label for="costo_insumo">Costo de referencia</label>
                            <input class="input" type="number" id="costo_insumo" name="costo_referencia" min="0" step="0.01" placeholder="0.00">
                        </div>
                        <button class="btn btn--lima btn--bloque" type="submit"><?= icono('mas') ?> Agregar insumo</button>
                    </form>
                </div>
            </section>
        <?php endif; ?>
    </aside>
</div>

