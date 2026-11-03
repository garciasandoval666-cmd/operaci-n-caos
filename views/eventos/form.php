<?php
/** @var array|null $evento */
/** @var array $clientes */
/** @var array $tipos */
/** @var array $espacios */
/** @var array $errores */
$esEdicion = $evento !== null;
$accion = $esEdicion ? url('/eventos/' . (int) $evento['id_evento']) : url('/eventos');
$valor = static function (string $campo, string $defecto = '') use ($evento): string {
    return viejo($campo, (string) ($evento[$campo] ?? $defecto));
};
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow"><?= $esEdicion ? 'Expediente del evento' : 'Alta de evento' ?></p>
        <h1><?= $esEdicion ? 'Editar ' . e($evento['nombre']) : 'Nuevo evento' ?></h1>
        <p class="page-head__sub">El aforo máximo debe caber en el espacio reservado. Al guardar se valida el empalme de fechas.</p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--fantasma" href="<?= e($esEdicion ? url('/eventos/' . (int) $evento['id_evento']) : url('/eventos')) ?>"><?= icono('atras') ?> Cancelar</a>
    </div>
</div>

<form class="formulario" method="post" action="<?= e($accion) ?>" novalidate>
    <?= csrf_field() ?>

    <div class="grid-detalle">
        <div class="stack">
            <section class="card">
                <div class="card__head"><div><h2>Identificación</h2><p>Cliente, tipo de evento y nombre operativo.</p></div></div>
                <div class="card__body">
                    <div class="campo-fila">
                        <div class="campo<?= clase_error('id_cliente', $errores) ?>">
                            <label for="id_cliente">Cliente <span class="campo__req">*</span></label>
                            <select class="select" id="id_cliente" name="id_cliente" required>
                                <option value="">Selecciona un cliente</option>
                                <?php foreach ($clientes as $cliente): ?>
                                    <option value="<?= (int) $cliente['id_cliente'] ?>" <?= (int) ($evento['id_cliente'] ?? 0) === (int) $cliente['id_cliente'] ? 'selected' : '' ?>>
                                        <?= e($cliente['nombre']) ?><?= $cliente['tipo_cliente'] === 'empresa' ? ' · empresa' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?= error_de('id_cliente', $errores) ?>
                            <p class="campo__ayuda">¿No aparece? <a href="<?= e(url('/clientes/crear')) ?>">Registra un cliente nuevo</a>.</p>
                        </div>
                        <div class="campo<?= clase_error('id_tipo_evento', $errores) ?>">
                            <label for="id_tipo_evento">Tipo de evento <span class="campo__req">*</span></label>
                            <select class="select" id="id_tipo_evento" name="id_tipo_evento" required>
                                <option value="">Selecciona el tipo</option>
                                <?php foreach ($tipos as $t): ?>
                                    <option value="<?= (int) $t['id_tipo_evento'] ?>" <?= (int) ($evento['id_tipo_evento'] ?? 0) === (int) $t['id_tipo_evento'] ? 'selected' : '' ?>>
                                        <?= e($t['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?= error_de('id_tipo_evento', $errores) ?>
                        </div>
                    </div>

                    <div class="campo mt-2<?= clase_error('nombre', $errores) ?>">
                        <label for="nombre">Nombre del evento <span class="campo__req">*</span></label>
                        <input class="input" type="text" id="nombre" name="nombre" value="<?= $valor('nombre') ?>" maxlength="140" placeholder="Ej. Boda Fernanda &amp; Diego" required>
                        <?= error_de('nombre', $errores) ?>
                    </div>

                    <div class="campo mt-2<?= clase_error('descripcion', $errores) ?>">
                        <label for="descripcion">Descripción</label>
                        <textarea class="textarea" id="descripcion" name="descripcion" maxlength="500" placeholder="Concepto, montaje, requerimientos especiales…"><?= $valor('descripcion') ?></textarea>
                        <?= error_de('descripcion', $errores) ?>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card__head"><div><h2>Fechas y aforo</h2><p>El sistema bloquea el espacio por cada día ocupado.</p></div></div>
                <div class="card__body">
                    <div class="campo-fila">
                        <div class="campo<?= clase_error('fecha_inicio', $errores) ?>">
                            <label for="fecha_inicio">Inicio <span class="campo__req">*</span></label>
                            <input class="input" type="datetime-local" id="fecha_inicio" name="fecha_inicio" value="<?= e(fecha_input($evento['fecha_inicio'] ?? null)) ?>" required>
                            <?= error_de('fecha_inicio', $errores) ?>
                        </div>
                        <div class="campo<?= clase_error('fecha_fin', $errores) ?>">
                            <label for="fecha_fin">Fin <span class="campo__req">*</span></label>
                            <input class="input" type="datetime-local" id="fecha_fin" name="fecha_fin" value="<?= e(fecha_input($evento['fecha_fin'] ?? null)) ?>" required>
                            <?= error_de('fecha_fin', $errores) ?>
                        </div>
                    </div>

                    <div class="campo-fila mt-2">
                        <div class="campo<?= clase_error('aforo_estimado', $errores) ?>">
                            <label for="aforo_estimado">Aforo estimado <span class="campo__req">*</span></label>
                            <input class="input" type="number" id="aforo_estimado" name="aforo_estimado" min="1" value="<?= $valor('aforo_estimado', '80') ?>" required>
                            <?= error_de('aforo_estimado', $errores) ?>
                        </div>
                        <div class="campo<?= clase_error('aforo_maximo', $errores) ?>">
                            <label for="aforo_maximo">Aforo máximo <span class="campo__req">*</span></label>
                            <input class="input" type="number" id="aforo_maximo" name="aforo_maximo" min="1" value="<?= $valor('aforo_maximo', '120') ?>" required>
                            <p class="campo__ayuda">No puede superar la capacidad del espacio.</p>
                            <?= error_de('aforo_maximo', $errores) ?>
                        </div>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card__head"><div><h2>Notas internas</h2><p>Visibles solo para el equipo.</p></div></div>
                <div class="card__body">
                    <div class="campo<?= clase_error('notas', $errores) ?>">
                        <textarea class="textarea" id="notas" name="notas" maxlength="2000" placeholder="Acuerdos, pendientes, contactos clave…"><?= $valor('notas') ?></textarea>
                        <?= error_de('notas', $errores) ?>
                    </div>
                </div>
            </section>
        </div>

        <aside class="stack">
            <section class="card">
                <div class="card__head"><div><h2>Espacio inicial</h2><p>Opcional. Se reserva en la misma transacción.</p></div></div>
                <div class="card__body">
                    <div class="campo">
                        <label for="id_espacio">Espacio</label>
                        <select class="select" id="id_espacio" name="id_espacio">
                            <option value="">Sin reserva por ahora</option>
                            <?php foreach ($espacios as $espacio): ?>
                                <option value="<?= (int) $espacio['id_espacio'] ?>">
                                    <?= e($espacio['nombre']) ?> · <?= (int) $espacio['capacidad'] ?> personas
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="campo__ayuda">Si el espacio está ocupado en esas fechas, el alta se rechaza completa.</p>
                    </div>
                    <?php if ($esEdicion): ?>
                        <div class="aviso-inline aviso-inline--info mt-2"><?= icono('alerta') ?> <span>Cambiar fechas aquí mueve también las reservas existentes. Para dejar motivo explícito usa “Reprogramar” en el expediente.</span></div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="card">
                <div class="card__head"><div><h2>Validaciones automáticas</h2><p>El sistema no permite estados imposibles.</p></div></div>
                <div class="card__body">
                    <ul class="lista-check">
                        <li><span class="marca"><?= icono('check') ?></span><span>La fecha de fin debe ser posterior al inicio.</span></li>
                        <li><span class="marca"><?= icono('check') ?></span><span>El aforo máximo no puede ser menor al estimado.</span></li>
                        <li><span class="marca"><?= icono('check') ?></span><span>El espacio se bloquea por día con índice único.</span></li>
                        <li><span class="marca"><?= icono('check') ?></span><span>Confirmar exige cotización aceptada y anticipo.</span></li>
                        <li><span class="marca"><?= icono('check') ?></span><span>Cerrar exige saldo cero.</span></li>
                    </ul>
                </div>
                <div class="card__foot">
                    <button class="btn btn--lima btn--bloque" type="submit"><?= icono('check') ?> <?= $esEdicion ? 'Guardar cambios' : 'Crear evento' ?></button>
                </div>
            </section>
        </aside>
    </div>
</form>
