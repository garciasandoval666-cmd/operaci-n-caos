<?php
/** @var array|null $cliente */
/** @var array $errores */
$esEdicion = $cliente !== null;
$accion = $esEdicion ? url('/clientes/' . (int) $cliente['id_cliente']) : url('/clientes');
$valor = static function (string $campo, string $defecto = '') use ($cliente): string {
    return viejo($campo, (string) ($cliente[$campo] ?? $defecto));
};
$tipo = (string) ($cliente['tipo_cliente'] ?? 'persona');
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow"><?= $esEdicion ? 'Edición de expediente' : 'Alta de cliente' ?></p>
        <h1><?= $esEdicion ? 'Editar ' . e($cliente['nombre']) : 'Nuevo cliente' ?></h1>
        <p class="page-head__sub">Los datos de contacto se reutilizan en cotizaciones, invitaciones y reportes de cobranza.</p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--fantasma" href="<?= e($esEdicion ? url('/clientes/' . (int) $cliente['id_cliente']) : url('/clientes')) ?>"><?= icono('atras') ?> Cancelar</a>
    </div>
</div>

<div class="grid grid-2">
    <section class="card">
        <div class="card__head">
            <div><h2>Datos generales</h2><p>Nombre comercial y clasificación fiscal.</p></div>
        </div>
        <div class="card__body">
            <form class="formulario" method="post" action="<?= e($accion) ?>" novalidate>
                <?= csrf_field() ?>

                <div class="campo-fila">
                    <div class="campo<?= clase_error('tipo_cliente', $errores) ?>">
                        <label for="tipo_cliente">Tipo de cliente</label>
                        <select class="select" id="tipo_cliente" name="tipo_cliente">
                            <option value="persona" <?= $tipo === 'persona' ? 'selected' : '' ?>>Persona física</option>
                            <option value="empresa" <?= $tipo === 'empresa' ? 'selected' : '' ?>>Empresa</option>
                        </select>
                    </div>
                    <div class="campo<?= clase_error('estado', $errores) ?>">
                        <label for="estado">Estado</label>
                        <select class="select" id="estado" name="estado">
                            <option value="activo" <?= ($cliente['estado'] ?? 'activo') === 'activo' ? 'selected' : '' ?>>Activo</option>
                            <option value="inactivo" <?= ($cliente['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="campo<?= clase_error('nombre', $errores) ?>">
                    <label for="nombre">Nombre o contacto principal <span class="campo__req">*</span></label>
                    <input class="input" type="text" id="nombre" name="nombre" value="<?= $valor('nombre') ?>" maxlength="120" required>
                    <?= error_de('nombre', $errores) ?>
                </div>

                <div class="campo<?= clase_error('razon_social', $errores) ?>">
                    <label for="razon_social">Razón social</label>
                    <input class="input" type="text" id="razon_social" name="razon_social" value="<?= $valor('razon_social') ?>" maxlength="150" placeholder="Obligatoria para empresas">
                    <?= error_de('razon_social', $errores) ?>
                </div>

                <div class="campo-fila">
                    <div class="campo<?= clase_error('correo', $errores) ?>">
                        <label for="correo">Correo <span class="campo__req">*</span></label>
                        <input class="input" type="email" id="correo" name="correo" value="<?= $valor('correo') ?>" maxlength="120" required>
                        <?= error_de('correo', $errores) ?>
                    </div>
                    <div class="campo<?= clase_error('telefono', $errores) ?>">
                        <label for="telefono">Teléfono</label>
                        <input class="input" type="tel" id="telefono" name="telefono" value="<?= $valor('telefono') ?>" maxlength="25">
                        <?= error_de('telefono', $errores) ?>
                    </div>
                </div>

                <div class="campo<?= clase_error('direccion', $errores) ?>">
                    <label for="direccion">Dirección</label>
                    <input class="input" type="text" id="direccion" name="direccion" value="<?= $valor('direccion') ?>" maxlength="220">
                    <?= error_de('direccion', $errores) ?>
                </div>

                <div class="campo<?= clase_error('notas', $errores) ?>">
                    <label for="notas">Notas internas</label>
                    <textarea class="textarea" id="notas" name="notas" maxlength="500" placeholder="Preferencias, condiciones de facturación, historial…"><?= $valor('notas') ?></textarea>
                    <?= error_de('notas', $errores) ?>
                </div>

                <div class="fila fila--fin mt-2">
                    <a class="btn btn--fantasma" href="<?= e($esEdicion ? url('/clientes/' . (int) $cliente['id_cliente']) : url('/clientes')) ?>">Cancelar</a>
                    <button class="btn btn--lima" type="submit"><?= icono('check') ?> <?= $esEdicion ? 'Guardar cambios' : 'Registrar cliente' ?></button>
                </div>
            </form>
        </div>
    </section>

    <aside class="stack">
        <section class="card">
            <div class="card__head"><div><h2>Buenas prácticas</h2><p>Un expediente limpio evita errores en la operación.</p></div></div>
            <div class="card__body">
                <ul class="lista-check">
                    <li><span class="marca"><?= icono('check') ?></span><span>Usa el correo al que llegarán cotizaciones y confirmaciones.</span></li>
                    <li><span class="marca"><?= icono('check') ?></span><span>Para empresas, la razón social debe coincidir con la factura.</span></li>
                    <li><span class="marca"><?= icono('check') ?></span><span>No se eliminan clientes: se inactivan para conservar el historial.</span></li>
                    <li><span class="marca"><?= icono('check') ?></span><span>Un cliente con eventos activos no puede inactivarse.</span></li>
                </ul>
            </div>
        </section>

        <?php if ($esEdicion): ?>
            <section class="card">
                <div class="card__head"><div><h2>Ficha</h2><p>Referencia rápida del registro.</p></div></div>
                <div class="card__body">
                    <div class="lista-datos">
                        <div class="lista-datos__fila"><span>ID interno</span><b class="mono">#<?= (int) $cliente['id_cliente'] ?></b></div>
                        <div class="lista-datos__fila"><span>Alta</span><b><?= e(fecha_hora($cliente['creado_en'])) ?></b></div>
                        <div class="lista-datos__fila"><span>Última edición</span><b><?= e($cliente['actualizado_en'] ? fecha_hora($cliente['actualizado_en']) : 'Sin cambios') ?></b></div>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </aside>
</div>
