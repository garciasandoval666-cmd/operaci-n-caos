<?php
/** @var string $token */
/** @var array|null $invitado */
/** @var array $menus */
/** @var string|null $error */

$restricciones = [
    'ninguna'     => 'Sin restricciones',
    'vegetariano' => 'Vegetariano',
    'vegano'      => 'Vegano',
    'sin_gluten'  => 'Sin gluten',
    'otra'        => 'Otra (especificar en alergias)',
];
$esLocal = (string) env('APP_ENV', 'production') === 'local';
$estadoActual = $invitado['estado_rsvp'] ?? 'pendiente';
?>
<div class="b-rsvp">
    <aside class="b-rsvp__visual">
        <img src="https://images.pexels.com/photos/1024993/pexels-photo-1024993.jpeg?auto=compress&cs=tinysrgb&w=1400"
             alt="Mesa preparada para una celebración en el recinto">
        <div class="b-rsvp__leyenda">
            <span><?= e(marca()) ?> · Invitación</span>
            <h2>Nos encantará<br>verte ahí.</h2>
            <p>Confirma tu lugar, elige tu menú y cuéntanos si necesitas alguna consideración especial.</p>
        </div>
    </aside>

    <section class="b-rsvp__panel">
        <div class="b-rsvp__caja">
            <a href="<?= e(url('/boda')) ?>" class="b-eyebrow" style="text-decoration:none">
                <?= icono('atras') ?> Volver al sitio
            </a>

            <?php if ($error !== null): ?>
                <h1>No pudimos abrir tu invitación</h1>
                <p><?= e($error) ?></p>

                <div class="b-aviso b-aviso--error mt-3">
                    <?= icono('alerta') ?>
                    Si el enlace venía en tu invitación, verifica que esté completo. También puedes pegar el código de 32 caracteres.
                </div>

                <form class="b-form mt-3" method="get" action="<?= e(url('/rsvp')) ?>">
                    <div class="b-campo">
                        <label for="token">Código de invitación</label>
                        <input class="b-input" id="token" name="token" type="text" required
                               maxlength="64" value="<?= e($token) ?>" placeholder="Ej. 7f3a9c2e5b1d8046af27c9e13d5b6a01"
                               autocomplete="off" spellcheck="false">
                    </div>
                    <button class="b-btn b-btn--bloque" type="submit">Buscar mi invitación</button>
                </form>

            <?php elseif ($invitado === null): ?>
                <h1>Confirma tu<br>asistencia.</h1>
                <p>Escribe el código único que recibiste con tu invitación. Con él podemos identificar tu evento, tu mesa y tu menú.</p>

                <form class="b-form mt-3" method="get" action="<?= e(url('/rsvp')) ?>">
                    <div class="b-campo">
                        <label for="token">Código de invitación</label>
                        <input class="b-input" id="token" name="token" type="text" required
                               maxlength="64" value="<?= e($token) ?>" placeholder="Pega aquí tu código"
                               autocomplete="off" spellcheck="false">
                        <span class="b-rsvp__pie" style="text-align:left">Lo encontrarás al final de tu invitación digital.</span>
                    </div>
                    <button class="b-btn b-btn--bloque" type="submit">Continuar</button>
                </form>

                <div class="b-aviso mt-3">
                    <?= icono('correo') ?>
                    ¿No tienes tu código? Escribe a <a href="mailto:bodas@solera.mx" style="text-decoration:underline">bodas@solera.mx</a> con tu nombre completo.
                </div>

                <?php if ($esLocal): ?>
                    <p class="b-rsvp__pie">Demo local: <a href="<?= e(url('/rsvp?token=7f3a9c2e5b1d8046af27c9e13d5b6a01')) ?>">usar invitación de ejemplo</a></p>
                <?php endif; ?>

            <?php else: ?>
                <h1>Hola,<br><?= e($invitado['nombre']) ?>.</h1>
                <p>Tu invitación está lista. Confirma antes de la fecha límite para reservar tu lugar y tu menú.</p>

                <div class="b-rsvp__evento">
                    <?= icono('corazon') ?>
                    <div>
                        <b><?= e($invitado['evento']) ?></b>
                        <span><?= e(fecha_larga($invitado['fecha_inicio'])) ?> · <?= e(date('H:i', strtotime($invitado['fecha_inicio']))) ?> h</span>
                        <span><?= e($invitado['cliente']) ?> · <?= (int) $invitado['aforo_maximo'] ?> lugares</span>
                    </div>
                </div>

                <?php if ($estadoActual !== 'pendiente'): ?>
                    <div class="b-aviso <?= $estadoActual === 'confirmado' ? 'b-aviso--ok' : '' ?> mb-2">
                        <?= icono($estadoActual === 'confirmado' ? 'check' : 'reloj') ?>
                        Ya registramos tu respuesta como <b><?= e(str_replace('_', ' ', $estadoActual)) ?></b>. Puedes actualizarla aquí abajo.
                    </div>
                <?php endif; ?>

                <form class="b-form" method="post" action="<?= e(url('/rsvp')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= e($token) ?>">

                    <div class="b-campo">
                        <label>¿Asistirás?</label>
                        <div class="b-opciones">
                            <label class="b-opcion">
                                <input type="radio" name="respuesta" value="confirmado" required <?= $estadoActual === 'confirmado' ? 'checked' : '' ?>>
                                <span><?= icono('check') ?> Sí, asistiré</span>
                            </label>
                            <label class="b-opcion">
                                <input type="radio" name="respuesta" value="declinado" required <?= $estadoActual === 'declinado' ? 'checked' : '' ?>>
                                <span><?= icono('x') ?> No podré ir</span>
                            </label>
                        </div>
                    </div>

                    <?php if ($menus !== []): ?>
                        <div class="b-campo">
                            <label for="menu">Menú</label>
                            <select class="b-select" id="menu" name="id_evento_menu">
                                <option value="">Aún no lo decido</option>
                                <?php foreach ($menus as $menu): ?>
                                    <option value="<?= (int) $menu['id_evento_menu'] ?>"
                                        <?= (int) ($invitado['id_evento_menu'] ?? 0) === (int) $menu['id_evento_menu'] ? 'selected' : '' ?>>
                                        <?= e($menu['nombre']) ?> · <?= e(str_replace('_', ' ', (string) $menu['tipo_servicio'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="b-rsvp__pie" style="text-align:left">Puedes cambiarlo hasta una semana antes del evento.</span>
                        </div>
                    <?php endif; ?>

                    <div class="b-campo-fila">
                        <div class="b-campo">
                            <label for="restriccion">Restricción alimenticia</label>
                            <select class="b-select" id="restriccion" name="restriccion_alimenticia">
                                <?php foreach ($restricciones as $clave => $etiqueta): ?>
                                    <option value="<?= e($clave) ?>" <?= ($invitado['restriccion_alimenticia'] ?? 'ninguna') === $clave ? 'selected' : '' ?>>
                                        <?= e($etiqueta) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="b-campo">
                            <label for="alergias">Alergias</label>
                            <input class="b-input" id="alergias" name="alergias" type="text" maxlength="220"
                                   value="<?= e($invitado['alergias'] ?? '') ?>" placeholder="Nueces, mariscos, lactosa…">
                        </div>
                    </div>

                    <button class="b-btn b-btn--bloque" type="submit">Enviar mi respuesta</button>
                    <p class="b-rsvp__pie">Podrás modificar tu respuesta con este mismo enlace hasta el día del evento.</p>
                </form>
            <?php endif; ?>

            <p class="b-rsvp__pie">
                ¿Necesitas ayuda? <a href="mailto:bodas@solera.mx">bodas@solera.mx</a>
            </p>
        </div>
    </section>
</div>
