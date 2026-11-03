<?php
/** @var array $evento */
/** @var array $espacios */
/** @var array $menus */
/** @var array $servicios */
/** @var array $recursos */
/** @var array $sugerencias */
/** @var int $version */
/** @var float $iva */
/** @var float $anticipo */

$tiposConcepto = [
    'espacio'  => 'Espacio',
    'menu'     => 'Menú',
    'servicio' => 'Servicio',
    'recurso'  => 'Recurso',
    'otro'     => 'Otro',
];
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Módulo C · Nueva propuesta</p>
        <h1>Cotizar · <?= e($evento['nombre']) ?></h1>
        <p class="page-head__sub">Versión <?= $version ?> de la propuesta. Los precios se congelan al aceptar y el anticipo se calcula sobre el total con IVA.</p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--fantasma" href="<?= e(url('/eventos/' . (int) $evento['id_evento'])) ?>"><?= icono('atras') ?> Volver al evento</a>
    </div>
</div>

<form method="post" action="<?= e(url('/cotizaciones')) ?>" data-cotizador data-iva="<?= e((string) $iva) ?>" data-anticipo="<?= e((string) $anticipo) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id_evento" value="<?= (int) $evento['id_evento'] ?>">

    <div class="grid-detalle">
        <div class="stack">
            <section class="card">
                <div class="card__head">
                    <div><h2>Conceptos de la propuesta</h2><p>Elige un concepto del catálogo o escribe uno libre. La cantidad multiplica el precio unitario.</p></div>
                    <button class="btn btn--fantasma btn--sm" type="button" data-agregar-linea><?= icono('mas') ?> Agregar línea</button>
                </div>
                <div class="card__body">
                    <div class="lineas-cotizacion" data-lineas>
                        <div class="linea-cotizacion">
                            <div class="campo">
                                <label>Concepto</label>
                                <select class="select" data-concepto>
                                    <option value="">Libre / otro</option>
                                    <?php foreach ($sugerencias as $sugerencia): ?>
                                        <option
                                            value="<?= e($sugerencia['descripcion']) ?>"
                                            data-descripcion="<?= e($sugerencia['descripcion']) ?>"
                                            data-precio="<?= e((string) $sugerencia['precio']) ?>"
                                            data-tipo="<?= e($sugerencia['tipo']) ?>"
                                            data-referencia="<?= (int) $sugerencia['referencia'] ?>">
                                            <?= e($sugerencia['descripcion']) ?> · <?= e(money($sugerencia['precio'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="campo">
                                <label>Descripción <span class="campo__req">*</span></label>
                                <input class="input" type="text" name="descripcion[]" maxlength="200" data-campo="descripcion" required>
                            </div>
                            <div class="campo">
                                <label>Cantidad</label>
                                <input class="input" type="number" name="cantidad[]" min="0.01" step="0.01" value="1" data-campo="cantidad" required>
                            </div>
                            <div class="campo">
                                <label>Precio unitario</label>
                                <input class="input" type="number" name="precio_unitario[]" min="0" step="0.01" value="0" data-campo="precio" required>
                            </div>
                            <div class="campo">
                                <label>Tipo</label>
                                <select class="select" name="tipo_concepto[]" data-campo="tipo">
                                    <?php foreach ($tiposConcepto as $clave => $etiqueta): ?>
                                        <option value="<?= e($clave) ?>"><?= e($etiqueta) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <input type="hidden" name="id_referencia[]" value="" data-campo="referencia">
                            <div class="linea-cotizacion__importe" data-importe>$0.00</div>
                            <button class="btn-icono peligro" type="button" data-quitar-linea aria-label="Quitar línea"><?= icono('x') ?></button>
                        </div>
                    </div>

                    <template id="plantilla-linea">
                        <div class="linea-cotizacion">
                            <div class="campo">
                                <label>Concepto</label>
                                <select class="select" data-concepto>
                                    <option value="">Libre / otro</option>
                                    <?php foreach ($sugerencias as $sugerencia): ?>
                                        <option
                                            value="<?= e($sugerencia['descripcion']) ?>"
                                            data-descripcion="<?= e($sugerencia['descripcion']) ?>"
                                            data-precio="<?= e((string) $sugerencia['precio']) ?>"
                                            data-tipo="<?= e($sugerencia['tipo']) ?>"
                                            data-referencia="<?= (int) $sugerencia['referencia'] ?>">
                                            <?= e($sugerencia['descripcion']) ?> · <?= e(money($sugerencia['precio'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="campo">
                                <label>Descripción <span class="campo__req">*</span></label>
                                <input class="input" type="text" name="descripcion[]" maxlength="200" data-campo="descripcion" required>
                            </div>
                            <div class="campo">
                                <label>Cantidad</label>
                                <input class="input" type="number" name="cantidad[]" min="0.01" step="0.01" value="1" data-campo="cantidad" required>
                            </div>
                            <div class="campo">
                                <label>Precio unitario</label>
                                <input class="input" type="number" name="precio_unitario[]" min="0" step="0.01" value="0" data-campo="precio" required>
                            </div>
                            <div class="campo">
                                <label>Tipo</label>
                                <select class="select" name="tipo_concepto[]" data-campo="tipo">
                                    <?php foreach ($tiposConcepto as $clave => $etiqueta): ?>
                                        <option value="<?= e($clave) ?>"><?= e($etiqueta) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <input type="hidden" name="id_referencia[]" value="" data-campo="referencia">
                            <div class="linea-cotizacion__importe" data-importe>$0.00</div>
                            <button class="btn-icono peligro" type="button" data-quitar-linea aria-label="Quitar línea"><?= icono('x') ?></button>
                        </div>
                    </template>

                    <div class="resumen-cotizacion mt-4">
                        <div class="doc__fila"><span>Subtotal</span><b data-subtotal>$0.00</b></div>
                        <div class="doc__fila"><span>Descuento</span><b data-descuento-vista>$0.00</b></div>
                        <div class="doc__fila"><span>IVA (<?= e((string) $iva) ?>%)</span><b data-impuestos>$0.00</b></div>
                        <div class="doc__fila doc__fila--total"><span>Total</span><b data-total>$0.00</b></div>
                        <div class="doc__fila"><span>Anticipo (<?= e((string) $anticipo) ?>%)</span><b data-anticipo>$0.00</b></div>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card__head"><div><h2>Condiciones</h2><p>Vigencia y notas para el cliente.</p></div></div>
                <div class="card__body">
                    <div class="campo-fila">
                        <div class="campo">
                            <label for="vigencia_hasta">Vigente hasta</label>
                            <input class="input" type="date" id="vigencia_hasta" name="vigencia_hasta" value="<?= e(date('Y-m-d', strtotime('+30 days'))) ?>">
                        </div>
                        <div class="campo">
                            <label for="descuento">Descuento (MXN)</label>
                            <input class="input" type="number" id="descuento" name="descuento" min="0" step="0.01" value="0" data-descuento>
                        </div>
                    </div>
                    <div class="campo mt-2">
                        <label for="notas">Notas de la propuesta</label>
                        <textarea class="textarea" id="notas" name="notas" maxlength="500" rows="3" placeholder="Incluye montaje, tiempos de acceso, políticas de cancelación…"></textarea>
                    </div>
                </div>
            </section>
        </div>

        <aside class="grid-detalle__lado">
            <section class="card">
                <div class="card__head"><div><h2>Evento</h2><p>Datos que verá el cliente.</p></div></div>
                <div class="card__body">
                    <div class="ficha">
                        <div class="ficha__item"><span>Evento</span><b><?= e($evento['nombre']) ?></b></div>
                        <div class="ficha__item"><span>Cliente</span><b><?= e($evento['cliente']) ?></b></div>
                        <div class="ficha__item"><span>Fecha</span><b><?= e(fecha($evento['fecha_inicio'], 'd M Y')) ?></b></div>
                        <div class="ficha__item"><span>Aforo</span><b><?= number_format((int) $evento['aforo_maximo']) ?></b></div>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card__head"><div><h2>Resumen</h2><p>Se recalcula en vivo.</p></div></div>
                <div class="card__body">
                    <div class="lista-datos">
                        <div class="lista-datos__fila"><span>Subtotal</span><b data-subtotal>$0.00</b></div>
                        <div class="lista-datos__fila"><span>IVA</span><b data-impuestos>$0.00</b></div>
                        <div class="lista-datos__fila"><span>Total</span><b data-total>$0.00</b></div>
                        <div class="lista-datos__fila"><span>Anticipo</span><b data-anticipo>$0.00</b></div>
                    </div>
                    <div class="aviso-inline mt-3"><?= icono('candado') ?><span>Al aceptar, cada línea guarda el precio de catálogo y el precio pactado. Los cambios posteriores quedan en el historial de precios.</span></div>
                    <button class="btn btn--lima btn--bloque mt-3" type="submit"><?= icono('check') ?> Generar cotización</button>
                </div>
            </section>

            <section class="card">
                <div class="card__head"><div><h2>Sugerencias del evento</h2><p>Lo que ya está contratado.</p></div></div>
                <div class="card__body">
                    <div class="lista-datos">
                        <div class="lista-datos__fila"><span>Espacios reservados</span><b><?= count($espacios) ?></b></div>
                        <div class="lista-datos__fila"><span>Menús contratados</span><b><?= count($menus) ?></b></div>
                        <div class="lista-datos__fila"><span>Servicios</span><b><?= count($servicios) ?></b></div>
                        <div class="lista-datos__fila"><span>Recursos apartados</span><b><?= count($recursos) ?></b></div>
                    </div>
                    <p class="texto-muted texto-xs mt-2">Si falta algo, agrégalo como línea libre o vuelve al evento para contratarlo.</p>
                </div>
            </section>
        </aside>
    </div>
</form>
