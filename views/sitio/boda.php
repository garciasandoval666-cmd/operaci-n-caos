<?php
/** @var array $menus */
/** @var array $espacios */
/** @var array $historias */

/* Esta página conserva la ruta /boda porque hay enlaces publicados que
   apuntan ahí, pero no es una página de bodas: muestra lo que de verdad ha
   pasado en el recinto, del tipo que sea. */
?>

<!-- =====================================================================
     Navegación
     ===================================================================== -->
<header class="l-nav">
    <div class="l-nav__caja">
        <a class="l-marca" href="<?= e(url('/')) ?>" aria-label="<?= e(marca()) ?>, inicio">
            <span class="l-marca__sello"><?= logo() ?></span>
            <span><?= e(marca()) ?></span>
        </a>

        <nav class="l-menu" aria-label="Secciones del sitio">
            <a href="<?= e(url('/#recinto')) ?>">El recinto</a>
            <a href="<?= e(url('/#celebraciones')) ?>">Celebraciones</a>
            <a href="#historias" class="activo">Historias</a>
            <a href="#menus">Menús</a>
            <a href="<?= e(url('/#agenda')) ?>">Agenda</a>
        </nav>

        <div class="l-nav__acciones">
            <button class="l-nav__hamburguesa" type="button" aria-expanded="false" aria-label="Abrir el menú">
                <?= icono('menu') ?>
            </button>
            <a class="l-nav__btn" href="#contacto">
                <span>Agendar visita</span>
                <span class="l-nav__flecha"><?= icono('flecha') ?></span>
            </a>
        </div>
    </div>
</header>

<!-- =====================================================================
     Héroe
     ===================================================================== -->
<section class="l-hero">
    <div class="l-hero__foto" style="background-image:url('<?= e(foto('social', 1800)) ?>')"></div>
    <div class="l-hero__velo"></div>

    <div class="l-hero__caja">
        <span class="l-eyebrow l-eyebrow--claro">Historias del recinto</span>
        <h1 class="l-display l-parte">Esto ya pasó aquí</h1>
        <div class="l-hero__acciones">
            <a class="l-btn l-btn--crema" href="#historias">Ver las historias</a>
            <a class="l-btn l-btn--fantasma" href="#menus">Conocer los menús</a>
        </div>
    </div>
</section>

<div class="l-sobre-hero">

    <!-- =================================================================
         Historias por tipo de evento
         ================================================================= -->
    <section class="l-seccion" id="historias">
        <div class="l-ancho">
            <div class="l-cabeza l-reveal">
                <span class="l-eyebrow">Lo que hemos montado</span>
                <h2 class="l-h2 l-parte" style="margin-top:14px">Una casa, muchas ocasiones</h2>
                <p>Cada una con su propio protocolo: aforo, menú, montaje y tiempos.
                    Estos son eventos reales del sistema, uno por tipo.</p>
            </div>

            <?php if ($historias === []): ?>
                <p style="text-align:center">Todavía no hay eventos celebrados que mostrar.</p>
            <?php else: ?>
                <div class="l-historias">
                    <?php foreach ($historias as $i => $h): ?>
                        <article class="l-historia l-reveal" style="--l-retraso:<?= (int) $i * 90 ?>ms">
                            <div class="l-historia__foto">
                                <img src="<?= e(foto_tipo($h['tipo'], 760)) ?>" alt="<?= e($h['tipo']) ?>" loading="lazy" decoding="async">
                                <span class="l-historia__tipo"><?= icono($h['icono'] ?: tipo_evento_icono($h['tipo'])) ?> <?= e($h['tipo']) ?></span>
                            </div>
                            <div class="l-historia__cuerpo">
                                <h3 class="l-h4"><?= e($h['nombre']) ?></h3>
                                <dl class="l-historia__datos">
                                    <div><dt>Fecha</dt><dd><?= e(fecha_larga($h['fecha_inicio'])) ?></dd></div>
                                    <div><dt>Espacio</dt><dd><?= e($h['espacio'] ?: 'Por asignar') ?></dd></div>
                                    <div><dt>Aforo</dt><dd><?= e(plural((int) $h['aforo_maximo'], 'invitado', 'invitados')) ?></dd></div>
                                    <div><dt>Confirmados</dt><dd><?= number_format((int) $h['confirmados']) ?></dd></div>
                                </dl>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- =================================================================
         Menús
         ================================================================= -->
    <section class="l-seccion l-seccion--arena" id="menus">
        <div class="l-ancho">
            <div class="l-cabeza l-reveal">
                <span class="l-eyebrow">La mesa</span>
                <h2 class="l-h2 l-parte" style="margin-top:14px"><?= e(ucfirst(plural(count($menus), 'forma de servir', 'formas de servir'))) ?></h2>
                <p>El menú se cotiza por persona y se congela al aceptar la cotización:
                    el precio que firmas es el que se cobra.</p>
            </div>

            <div class="l-menus">
                <?php foreach ($menus as $i => $menu): ?>
                    <article class="l-menu-card l-reveal" style="--l-retraso:<?= (int) $i * 110 ?>ms">
                        <div class="l-menu-card__top">
                            <h3 class="l-h4"><?= e($menu['nombre']) ?></h3>
                            <?= icono('cubiertos') ?>
                        </div>
                        <p><?= e($menu['descripcion'] ?: 'Montaje a medida.') ?></p>
                        <div class="l-menu-card__precio">
                            <b><?= e(money($menu['precio_persona'])) ?></b>
                            <span>por persona</span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- =================================================================
         Espacios
         ================================================================= -->
    <section class="l-seccion" id="espacios">
        <div class="l-ancho">
            <div class="l-cabeza l-reveal">
                <span class="l-eyebrow">Dónde</span>
                <h2 class="l-h2 l-parte" style="margin-top:14px">Elige la escala</h2>
                <p>Desde <?= number_format(min(array_map(static fn (array $e): int => (int) $e['capacidad'], $espacios))) ?>
                    hasta <?= number_format(max(array_map(static fn (array $e): int => (int) $e['capacidad'], $espacios))) ?>
                    invitados, según el espacio.</p>
            </div>

            <div class="l-escalas">
                <?php foreach ($espacios as $i => $espacio): ?>
                    <article class="l-escala l-reveal" style="--l-retraso:<?= (int) $i * 80 ?>ms">
                        <img src="<?= e(foto((string) $espacio['tipo'], 620)) ?>" alt="<?= e($espacio['nombre']) ?>" loading="lazy" decoding="async">
                        <div class="l-escala__pie">
                            <b><?= e($espacio['nombre']) ?></b>
                            <span><?= number_format((int) $espacio['capacidad']) ?> invitados</span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- =================================================================
         Contacto
         ================================================================= -->
    <section class="l-seccion l-seccion--apretada" id="contacto">
        <div class="l-ancho">
            <div class="l-cta">
                <div class="l-cta__foto" style="background-image:url('<?= e(foto('cta', 1600)) ?>')"></div>
                <div class="l-cta__velo"></div>
                <span class="l-eyebrow l-eyebrow--claro">Tu evento</span>
                <h2 class="l-h3" style="margin-top:14px">Cuéntanos qué celebras</h2>
                <p>Sea una boda, un congreso o un concierto, el proceso es el mismo:
                    visita, propuesta y fecha apartada.</p>
                <div class="l-cta__acciones">
                    <a class="l-btn l-btn--crema" href="<?= e(url('/#contacto')) ?>">Escribir al equipo</a>
                    <a class="l-btn l-btn--fantasma" href="<?= e(url('/#agenda')) ?>">Ver disponibilidad</a>
                </div>
            </div>
        </div>
    </section>

    <!-- =================================================================
         Pie
         ================================================================= -->
    <footer class="l-pie">
        <div class="l-ancho">
            <div class="l-pie__top">
                <div>
                    <h2 class="l-parte">De nuestro recinto a tu celebración</h2>
                    <div class="l-pie__chips">
                        <a href="<?= e(url('/#recinto')) ?>">El recinto</a>
                        <a href="<?= e(url('/#celebraciones')) ?>">Celebraciones</a>
                        <a href="<?= e(url('/#espacios')) ?>">Espacios</a>
                        <a href="<?= e(url('/#agenda')) ?>">Agenda</a>
                        <a href="#historias">Historias</a>
                    </div>
                </div>

                <div class="l-pie__col">
                    <h4>Accesos</h4>
                    <a href="<?= e(url('/rsvp')) ?>">Confirmar asistencia</a>
                    <a href="<?= e(url('/')) ?>">Portada del recinto</a>
                    <a href="<?= e(url('/login')) ?>">Acceso del equipo</a>
                </div>

                <div class="l-pie__col">
                    <h4>Contacto</h4>
                    <p>Av. de los Recintos 128<br>Ciudad de México</p>
                    <a href="mailto:hola@solera.mx">hola@solera.mx</a>
                </div>

                <div class="l-pie__col">
                    <h4>Horario</h4>
                    <p>Visitas al recinto<br>Lunes a sábado, 10:00 a 19:00</p>
                </div>
            </div>

            <div class="l-pie__legal">
                <span>© <?= date('Y') ?> <?= e(marca()) ?>. Proyecto académico.</span>
                <span>Operación Caos Digital · Módulos A, B, C y D</span>
            </div>
        </div>

        <div class="l-pie__firma" aria-hidden="true">Celebrar bien</div>
    </footer>

</div>
