<?php
/** @var array $recursos */
/** @var array $proveedores */
/** @var array $reservas */

$categorias = [
    'mobiliario' => 'Mobiliario',
    'personal'   => 'Personal',
    'audio_video'=> 'Audio y video',
    'decoracion' => 'Decoración',
    'transporte' => 'Transporte',
    'otro'       => 'Otro',
];

$totalPiezas = array_sum(array_map(static fn (array $r): int => (int) $r['existencias_total'], $recursos));
$reservadas = array_sum(array_map(static fn (array $r): int => (int) $r['reservado'], $recursos));
$agotados = count(array_filter($recursos, static fn (array $r): bool => (int) $r['disponible'] === 0));
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Módulo B · Inventario operativo</p>
        <h1>Recursos</h1>
        <p class="page-head__sub"><?= e($subtitulo ?? '') ?></p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--claro" href="<?= e(url('/recursos/disponibilidad')) ?>"><?= icono('calendario') ?> Disponibilidad</a>
        <a class="btn btn--fantasma" href="<?= e(url('/proveedores')) ?>"><?= icono('proveedores') ?> Proveedores</a>
    </div>
</div>

<div class="kpis">
    <div class="kpi kpi--oscura">
        <div class="kpi__top"><?= icono('recursos') ?> Recursos activos</div>
        <p class="kpi__valor"><?= count($recursos) ?></p>
        <p class="kpi__nota"><?= number_format($totalPiezas) ?> unidades en total</p>
    </div>
    <div class="kpi">
        <div class="kpi__top"><?= icono('reservas') ?> Apartado hoy</div>
        <p class="kpi__valor"><?= number_format($reservadas) ?></p>
        <p class="kpi__nota">unidades comprometidas</p>
    </div>
    <div class="kpi">
        <div class="kpi__top"><?= icono('paquete') ?> Disponible hoy</div>
        <p class="kpi__valor"><?= number_format(max(0, $totalPiezas - $reservadas)) ?></p>
        <p class="kpi__nota">listas para asignar</p>
    </div>
    <div class="kpi <?= $agotados > 0 ? '' : 'kpi--lima' ?>">
        <div class="kpi__top"><?= icono('alerta') ?> Sin disponibilidad</div>
        <p class="kpi__valor"><?= $agotados ?></p>
        <p class="kpi__nota"><?= $agotados > 0 ? 'recursos al tope hoy' : 'todo el inventario libre' ?></p>
    </div>
</div>

<div class="grid-detalle">
    <div class="stack">
        <section class="card">
            <div class="card__head">
                <div><h2>Catálogo de recursos</h2><p>Existencias, costo de referencia y compromiso del día de hoy.</p></div>
                <span class="badge badge--neutro"><?= count($proveedores) ?> proveedores</span>
            </div>

            <?php if ($recursos === []): ?>
                <div class="empty">
                    <div class="empty__icono"><?= icono('recursos') ?></div>
                    <h3>Sin recursos en inventario</h3>
                    <p>Registra mobiliario, audio o personal para poder apartarlo en los eventos.</p>
                </div>
            <?php else: ?>
                <div class="tabla-wrap">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>Recurso</th>
                                <th>Categoría</th>
                                <th>Proveedor</th>
                                <th class="num">Total</th>
                                <th class="num">Apartado</th>
                                <th class="num">Disponible</th>
                                <th class="num">Costo</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recursos as $recurso): ?>
                            <tr>
                                <td>
                                    <div class="tabla__principal">
                                        <span class="tabla__avatar"><?= icono('paquete') ?></span>
                                        <span><b><?= e($recurso['nombre']) ?></b><span>por <?= e($recurso['unidad']) ?></span></span>
                                    </div>
                                </td>
                                <td class="texto-sm"><?= e($categorias[$recurso['categoria']] ?? ucfirst($recurso['categoria'])) ?></td>
                                <td class="texto-sm"><?= $recurso['proveedor'] ? e($recurso['proveedor']) : '<span class="texto-muted">Interno</span>' ?></td>
                                <td class="num"><?= number_format((int) $recurso['existencias_total']) ?></td>
                                <td class="num"><?= number_format((int) $recurso['reservado']) ?></td>
                                <td class="num"><b class="<?= (int) $recurso['disponible'] === 0 ? 'texto-error' : 'texto-ok' ?>"><?= number_format((int) $recurso['disponible']) ?></b></td>
                                <td class="num"><?= e(money($recurso['costo_referencia'])) ?></td>
                                <td><?= badge_estado($recurso['estado']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="card">
            <div class="card__head">
                <div><h2>Apartados vigentes</h2><p>Reservas confirmadas con fecha de fin posterior a hoy.</p></div>
                <a class="btn btn--fantasma btn--sm" href="<?= e(url('/reservas')) ?>">Gestionar reservas</a>
            </div>
            <?php if ($reservas === []): ?>
                <div class="tabla__vacia">No hay recursos apartados en este momento.</div>
            <?php else: ?>
                <div class="tabla-wrap">
                    <table class="tabla tabla--compacta">
                        <thead>
                            <tr><th>Recurso</th><th>Evento</th><th class="num">Cantidad</th><th>Desde</th><th>Hasta</th><th></th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($reservas as $reserva): ?>
                            <tr>
                                <td><b><?= e($reserva['nombre']) ?></b></td>
                                <td><?= e($reserva['evento']) ?></td>
                                <td class="num"><?= number_format((int) $reserva['cantidad']) ?> <?= e($reserva['unidad']) ?></td>
                                <td class="texto-sm"><?= e(fecha_hora($reserva['fecha_inicio'])) ?></td>
                                <td class="texto-sm"><?= e(fecha_hora($reserva['fecha_fin'])) ?></td>
                                <td><div class="acciones"><a class="btn btn--fantasma btn--xs" href="<?= e(url('/eventos/' . (int) $reserva['id_evento'])) ?>"><?= icono('ver') ?></a></div></td>
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
                <div class="card__head"><div><h2>Nuevo recurso</h2><p>Se suma al inventario del recinto.</p></div></div>
                <div class="card__body">
                    <form class="formulario" method="post" action="<?= e(url('/recursos')) ?>">
                        <?= csrf_field() ?>
                        <div class="campo">
                            <label for="nombre">Nombre <span class="campo__req">*</span></label>
                            <input class="input" type="text" id="nombre" name="nombre" maxlength="100" required placeholder="Silla Tiffany">
                        </div>
                        <div class="campo">
                            <label for="categoria">Categoría <span class="campo__req">*</span></label>
                            <select class="select" id="categoria" name="categoria" required>
                                <?php foreach ($categorias as $clave => $etiqueta): ?>
                                    <option value="<?= e($clave) ?>"><?= e($etiqueta) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="campo">
                            <label for="id_proveedor">Proveedor</label>
                            <select class="select" id="id_proveedor" name="id_proveedor">
                                <option value="0">Interno / sin proveedor</option>
                                <?php foreach ($proveedores as $proveedor): ?>
                                    <option value="<?= (int) $proveedor['id_proveedor'] ?>"><?= e($proveedor['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="campo-fila">
                            <div class="campo">
                                <label for="existencias_total">Existencias <span class="campo__req">*</span></label>
                                <input class="input" type="number" id="existencias_total" name="existencias_total" min="0" step="1" required>
                            </div>
                            <div class="campo">
                                <label for="unidad">Unidad</label>
                                <input class="input" type="text" id="unidad" name="unidad" maxlength="20" value="pieza">
                            </div>
                        </div>
                        <div class="campo">
                            <label for="costo_referencia">Costo de referencia</label>
                            <input class="input" type="number" id="costo_referencia" name="costo_referencia" min="0" step="0.01" placeholder="0.00">
                        </div>
                        <button class="btn btn--lima btn--bloque" type="submit"><?= icono('mas') ?> Agregar recurso</button>
                    </form>
                </div>
            </section>
        <?php endif; ?>

        <section class="card">
            <div class="card__head"><div><h2>Control de existencias</h2><p>Sin sobreventa de equipo.</p></div></div>
            <div class="card__body">
                <div class="lista-datos">
                    <div class="lista-datos__fila"><span>Unidades totales</span><b><?= number_format($totalPiezas) ?></b></div>
                    <div class="lista-datos__fila"><span>Comprometidas hoy</span><b><?= number_format($reservadas) ?></b></div>
                    <div class="lista-datos__fila"><span>Disponibles hoy</span><b><?= number_format(max(0, $totalPiezas - $reservadas)) ?></b></div>
                </div>
                <div class="aviso-inline mt-3"><?= icono('candado') ?><span>La reserva de recursos bloquea la fila del recurso con <span class="mono">FOR UPDATE</span> y suma las cantidades de cada día del evento.</span></div>
            </div>
        </section>
    </aside>
</div>
