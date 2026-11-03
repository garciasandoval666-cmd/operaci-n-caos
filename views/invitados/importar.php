<?php
/** @var array $eventos */
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Carga masiva · Módulo A</p>
        <h1>Importar invitados</h1>
        <p class="page-head__sub">Pega una línea por invitado con el formato <b>Nombre | Correo | Teléfono | Categoría</b>. La importación corre en una transacción y omite duplicados.</p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--fantasma" href="<?= e(url('/invitados')) ?>"><?= icono('atras') ?> Volver a invitados</a>
    </div>
</div>

<div class="grid-detalle">
    <div class="stack">
        <section class="card">
            <div class="card__head"><div><h2>Datos de la lista</h2><p>El evento destino define el aforo y los menús disponibles.</p></div></div>
            <div class="card__body">
                <form class="formulario" method="post" action="<?= e(url('/eventos')) ?>" id="form-importar">
                    <?= csrf_field() ?>
                    <div class="campo">
                        <label for="id_evento">Evento destino <span class="campo__req">*</span></label>
                        <select class="select" id="id_evento" name="id_evento" required>
                            <option value="">Selecciona el evento…</option>
                            <?php foreach ($eventos as $evento): ?>
                                <option value="<?= (int) $evento['id_evento'] ?>"><?= e($evento['nombre']) ?> · <?= e(fecha($evento['fecha_inicio'], 'd M Y')) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="campo__ayuda">El formulario enviará la lista al expediente del evento seleccionado.</p>
                    </div>
                    <div class="campo mt-2">
                        <label for="datos">Lista de invitados <span class="campo__req">*</span></label>
                        <textarea class="textarea" id="datos" name="datos" rows="14" required placeholder="Ana Sofía Ríos | ana.rios@example.com | +52 55 1000 0001 | vip&#10;Carlos Ríos | carlos.rios@example.com | +52 55 1000 0002 | familia&#10;Mariana Ríos | mariana.rios@example.com | | general"></textarea>
                        <p class="campo__ayuda">Separa los campos con <b>|</b>, punto y coma o tabulador. El correo y el teléfono son opcionales, pero el nombre es obligatorio.</p>
                    </div>
                    <div class="fila fila--fin mt-3">
                        <button class="btn btn--lima" type="submit"><?= icono('descargar') ?> Importar invitados</button>
                    </div>
                </form>
            </div>
        </section>
    </div>

    <aside class="grid-detalle__lado">
        <section class="card">
            <div class="card__head"><div><h2>Formato aceptado</h2><p>Una línea por persona.</p></div></div>
            <div class="card__body">
                <div class="lista-datos">
                    <div class="lista-datos__fila"><span>1 · Nombre</span><b>Obligatorio</b></div>
                    <div class="lista-datos__fila"><span>2 · Correo</span><b>Opcional</b></div>
                    <div class="lista-datos__fila"><span>3 · Teléfono</span><b>Opcional</b></div>
                    <div class="lista-datos__fila"><span>4 · Categoría</span><b>general, vip, familia…</b></div>
                </div>
                <div class="aviso-inline aviso-inline--info mt-3"><?= icono('alerta') ?> <span>Las líneas sin nombre o con correo inválido se omiten. Los correos repetidos en el mismo evento también se omiten.</span></div>
            </div>
        </section>

        <section class="card">
            <div class="card__head"><div><h2>Ejemplo</h2><p>Puedes copiarlo y adaptarlo.</p></div></div>
            <div class="card__body">
                <pre class="mono texto-xs" style="white-space:pre-wrap;line-height:1.7">Lucía Méndez | lucia@example.com | +52 55 1234 5678 | vip
Jorge Villanueva | jorge@example.com | | general
Familia Aguirre | familia@example.com | +52 55 8765 4321 | familia</pre>
                <p class="texto-muted texto-xs mt-2">Cada invitado recibe un token único de 32 caracteres para su enlace RSVP.</p>
            </div>
        </section>
    </aside>
</div>

