<?php
/** @var array $eventos */
/** @var array|null $evento */
/** @var array $espacios */
/** @var array $recursos */

$categorias = [
    'mobiliario' => 'Mobiliario',
    'personal'   => 'Personal',
    'audio_video'=> 'Audio y video',
    'decoracion' => 'Decoración',
    'transporte' => 'Transporte',
    'otro'       => 'Otro',
];
$inicioSugerido = $evento ? fecha_input($evento['fecha_inicio']) : '';
$finSugerido = $evento ? fecha_input($evento['fecha_fin']) : '';
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Módulo B · Nueva reserva</p>
        <h1>Bloquear espacio o apartar recurso</h1>
        <p class="page-head__sub">La validación ocurre dentro de una transacción: si el espacio ya está ocupado o no alcanzan las existencias, no se escribe nada.</p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--fantasma" href="<?= e(url('/reservas')) ?>"><?= icono('atras') ?> Reservas</a>
    </div>
</div>

<div class="grid-detalle">
    <section class="card">
        <div class="card__head"><div><h2>Datos de la reserva</h2><p>Selecciona el evento y el recurso a comprometer.</p></div></div>
        <div class="card__body">
            <form class="formulario" method="post" action="<?= e(url('/reservas')) ?>" id="form-reserva">
                <?= csrf_field() ?>

                <div class="form-seccion">
                    <p class="form-seccion__titulo"><?= icono('calendario') ?> Evento y tipo</p>
                    <div class="campo">
                        <label for="id_evento">Evento <span class="campo__req">*</span></label>
                        <select class="select" id="id_evento" name="id_evento" required>
                            <?php if ($eventos === []): ?>
                                <option value="">No hay eventos disponibles</option>
                            <?php endif; ?>
                            <?php foreach ($eventos as $fila): ?>
                                <option value="<?= (int) $fila['id_evento'] ?>" <?= $evento && (int) $evento['id_evento'] === (int) $fila['id_evento'] ? 'selected' : '' ?>>
                                    <?= e($fila['nombre']) ?> · <?= e(fecha($fila['fecha_inicio'], 'd M Y')) ?> · <?= e($fila['cliente']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="campo mt-2">
                        <label for="tipo">¿Qué deseas reservar? <span class="campo__req">*</span></label>
                        <select class="select" id="tipo" name="tipo" required data-tipo-reserva>
                            <option value="espacio">Espacio (bloquea días en el calendario)</option>
                            <option value="recurso">Recurso (valida existencias por día)</option>
                        </select>
                    </div>
                </div>

                <div class="form-seccion" data-panel="espacio">
                    <p class="form-seccion__titulo"><?= icono('espacios') ?> Espacio a bloquear</p>
                    <div class="campo">
                        <label for="id_espacio">Espacio <span class="campo__req">*</span></label>
                        <select class="select" id="id_espacio" name="id_espacio">
                            <option value="0">Selecciona un espacio…</option>
                            <?php foreach ($espacios as $espacio): ?>
                                <option value="<?= (int) $espacio['id_espacio'] ?>">
                                    <?= e($espacio['nombre']) ?> · <?= number_format((int) $espacio['capacidad']) ?> personas · <?= e(money($espacio['costo_referencia'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="campo__ayuda">El aforo del evento no puede superar la capacidad del espacio. El espacio debe estar activo.</p>
                    </div>
                </div>

                <div class="form-seccion" data-panel="recurso" hidden>
                    <p class="form-seccion__titulo"><?= icono('recursos') ?> Recurso a apartar</p>
                    <div class="campo">
                        <label for="id_recurso">Recurso <span class="campo__req">*</span></label>
                        <select class="select" id="id_recurso" name="id_recurso">
                            <option value="0">Selecciona un recurso…</option>
                            <?php foreach ($recursos as $recurso): ?>
                                <option value="<?= (int) $recurso['id_recurso'] ?>">
                                    <?= e($recurso['nombre']) ?> · <?= e($categorias[$recurso['categoria']] ?? $recurso['categoria']) ?> · <?= number_format((int) $recurso['existencias_total']) ?> <?= e($recurso['unidad']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="campo">
                        <label for="cantidad">Cantidad <span class="campo__req">*</span></label>
                        <input class="input" type="number" id="cantidad" name="cantidad" min="1" step="1" value="1">
                        <p class="campo__ayuda">Se suma a los apartados existentes en cada día del rango.</p>
                    </div>
                </div>

                <div class="form-seccion">
                    <p class="form-seccion__titulo"><?= icono('reloj') ?> Ventana de la reserva</p>
                    <div class="campo-fila">
                        <div class="campo">
                            <label for="fecha_inicio">Inicio <span class="campo__req">*</span></label>
                            <input class="input" type="datetime-local" id="fecha_inicio" name="fecha_inicio" value="<?= e($inicioSugerido) ?>" required>
                        </div>
                        <div class="campo">
                            <label for="fecha_fin">Fin <span class="campo__req">*</span></label>
                            <input class="input" type="datetime-local" id="fecha_fin" name="fecha_fin" value="<?= e($finSugerido) ?>" required>
                        </div>
                    </div>
                    <div class="campo mt-2">
                        <label for="motivo">Motivo / nota</label>
                        <textarea class="textarea" id="motivo" name="motivo" maxlength="220" rows="2" placeholder="Montaje, ensayo, evento principal…"></textarea>
                    </div>
                </div>

                <div class="fila fila--fin">
                    <a class="btn btn--fantasma" href="<?= e(url('/reservas')) ?>">Cancelar</a>
                    <button class="btn btn--lima" type="submit"><?= icono('candado') ?> Confirmar reserva</button>
                </div>
            </form>
        </div>
    </section>

    <aside class="grid-detalle__lado">
        <?php if ($evento): ?>
            <section class="card">
                <div class="card__head"><div><h2>Evento seleccionado</h2><p>Datos que se validan al reservar.</p></div></div>
                <div class="card__body">
                    <div class="ficha">
                        <div class="ficha__item"><span>Evento</span><b><?= e($evento['nombre']) ?></b></div>
                        <div class="ficha__item"><span>Cliente</span><b><?= e($evento['cliente']) ?></b></div>
                        <div class="ficha__item"><span>Tipo</span><b><?= e($evento['tipo']) ?></b></div>
                        <div class="ficha__item"><span>Aforo máximo</span><b><?= number_format((int) $evento['aforo_maximo']) ?></b></div>
                        <div class="ficha__item"><span>Inicio</span><b><?= e(fecha_hora($evento['fecha_inicio'])) ?></b></div>
                        <div class="ficha__item"><span>Fin</span><b><?= e(fecha_hora($evento['fecha_fin'])) ?></b></div>
                    </div>
                    <a class="btn btn--fantasma btn--sm mt-3" href="<?= e(url('/eventos/' . (int) $evento['id_evento'])) ?>"><?= icono('externo') ?> Abrir evento</a>
                </div>
            </section>
        <?php endif; ?>

        <section class="card">
            <div class="card__head"><div><h2>Qué se valida</h2><p>Antes de escribir en la base.</p></div></div>
            <div class="card__body">
                <div class="timeline">
                    <div class="timeline__item timeline__item--ok"><span class="timeline__punto"><?= icono('check') ?></span><div class="timeline__cuerpo"><b>Evento no cancelado</b><span>Se bloquea la fila del evento con FOR UPDATE.</span></div></div>
                    <div class="timeline__item timeline__item--ok"><span class="timeline__punto"><?= icono('check') ?></span><div class="timeline__cuerpo"><b>Aforo vs. capacidad</b><span>El espacio debe poder recibir al evento completo.</span></div></div>
                    <div class="timeline__item timeline__item--ok"><span class="timeline__punto"><?= icono('check') ?></span><div class="timeline__cuerpo"><b>Sin empalme de días</b><span>Índice único por espacio y fecha activa.</span></div></div>
                    <div class="timeline__item timeline__item--aviso"><span class="timeline__punto"><?= icono('alerta') ?></span><div class="timeline__cuerpo"><b>Existencias suficientes</b><span>Se evalúa cada día del rango, no solo el primero.</span></div></div>
                </div>
            </div>
        </section>
    </aside>
</div>

