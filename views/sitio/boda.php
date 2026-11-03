<?php
/** @var array $menus */
/** @var array $espacios */
/** @var array|false $boda */

$foto = static fn (int $id, int $ancho = 1200): string =>
    'https://images.pexels.com/photos/' . $id . '/pexels-photo-' . $id . '.jpeg?auto=compress&cs=tinysrgb&w=' . $ancho;

$erroresForm = errores();
$anio = (int) date('Y');

$servicios = [
    'plato_servido' => 'Servicio en mesa',
    'buffet'        => 'Buffet',
    'coctel'        => 'Cóctel',
    'coffee_break'  => 'Coffee break',
    'barra_libre'   => 'Barra libre',
];

$fotosEspacios = [34496715, 3584437, 7648469, 2263436];
$galeria = [1024993, 1616113, 1729808, 265947, 3184418];
$destacada = is_array($boda) && $boda !== [] ? $boda : null;
?>
<nav class="b-nav" aria-label="Navegación de bodas">
    <div class="b-container b-nav__inner">
        <a class="b-marca" href="<?= e(url('/boda')) ?>">
            <span class="b-marca__sello">CE</span>
            <span>
                <b>ConectaEventos</b>
                <span>Bodas de autor</span>
            </span>
        </a>

        <div class="b-nav__links">
            <a href="#historia">Historia</a>
            <a href="#espacios">Espacios</a>
            <a href="#menu">Menú</a>
            <a href="#experiencia">Experiencia</a>
            <a href="#contacto">Contacto</a>
        </div>

        <div class="b-nav__cta">
            <a class="b-btn" href="<?= e(url('/rsvp')) ?>">Confirmar asistencia</a>
        </div>

        <button type="button" class="b-nav__toggle" aria-label="Abrir menú" aria-expanded="false">
            <?= icono('menu') ?>
        </button>
    </div>
</nav>

<header class="b-hero">
    <div class="b-hero__fondo">
        <img src="<?= e($foto(1024993, 1800)) ?>" alt="Pareja de novios durante la ceremonia" fetchpriority="high">
    </div>

    <div class="b-container b-hero__inner">
        <div class="b-hero__arco">
            <p class="b-hero__script">ConectaEventos</p>
            <h1>El día que<br><em>siempre</em> imaginaron.</h1>
            <div class="b-hero__meta">
                <span>Ciudad de México</span><i aria-hidden="true"></i>
                <span>Bodas íntimas y de autor</span><i aria-hidden="true"></i>
                <span><?= $anio ?>–<?= $anio + 1 ?></span>
            </div>
            <div class="b-hero__acciones">
                <a class="b-btn" href="#contacto">Reservar una fecha</a>
                <a class="b-btn b-btn--claro" href="<?= e(url('/rsvp')) ?>">Ya estoy invitado</a>
            </div>
        </div>
    </div>

    <a class="b-hero__scroll" href="#historia">Descubrir</a>
</header>

<section class="b-seccion" id="historia">
    <div class="b-container">
        <div class="b-editorial b-reveal">
            <div class="b-editorial__texto">
                <span class="b-eyebrow">Nuestra manera de celebrar</span>
                <h2 class="b-titulo">Menos prisa.<br>Más <em>presencia.</em></h2>
                <p>Una boda no se improvisa: se diseña. Empezamos escuchando cómo se conocieron, qué les hace reír y qué no puede faltar en la mesa. Después traducimos eso en un espacio, un menú y una línea de tiempo que se siente natural.</p>
                <p>Nos especializamos en celebraciones de 80 a 220 personas, con ceremonia en jardín, recepción bajo techo y una coordinación que cuida el detalle sin robar protagonismo.</p>
                <p class="b-firma">Con cariño, el equipo de ConectaEventos</p>
            </div>
            <div class="b-editorial__imagen">
                <img src="<?= e($foto(1616113, 1000)) ?>" alt="Detalle del vestido de novia" loading="lazy">
                <div class="b-editorial__sello">Desde<br>2014<br>CDMX</div>
            </div>
        </div>
    </div>
</section>

<section class="b-seccion b-seccion--arena b-seccion--compacta" style="padding:0">
    <div class="b-container">
        <div class="b-tira b-reveal">
            <div class="b-tira__item">
                <b data-contador="<?= (int) ($espacios[0]['capacidad'] ?? 90) ?>"><?= (int) ($espacios[0]['capacidad'] ?? 90) ?></b>
                <span>Invitados en el espacio íntimo</span>
            </div>
            <div class="b-tira__item">
                <b data-contador="<?= (int) max(array_map(static fn (array $e): int => (int) $e['capacidad'], $espacios ?: [['capacidad' => 220]])) ?>"><?= (int) max(array_map(static fn (array $e): int => (int) $e['capacidad'], $espacios ?: [['capacidad' => 220]])) ?></b>
                <span>Capacidad máxima del recinto</span>
            </div>
            <div class="b-tira__item">
                <b data-contador="<?= max(1, count($menus)) ?>"><?= max(1, count($menus)) ?></b>
                <span>Menús de autor</span>
            </div>
            <div class="b-tira__item">
                <b data-contador="12" data-sufijo=" meses">12 meses</b>
                <span>Planeación acompañada</span>
            </div>
        </div>
    </div>
</section>

<section class="b-seccion" id="espacios">
    <div class="b-container">
        <div class="b-cabeza b-reveal">
            <p class="b-eyebrow b-eyebrow--centro">El escenario</p>
            <h2 class="b-titulo">Espacios con <em>alma.</em></h2>
            <p class="b-intro b-intro--centro">Tres atmósferas distintas para tres momentos distintos: la ceremonia, la cena y el baile. Sin carpas improvisadas ni luces de bodega.</p>
        </div>

        <?php if ($espacios === []): ?>
            <div class="b-espacio">
                <div class="b-espacio__cuerpo">
                    <h3>Espacios en preparación</h3>
                    <p>Estamos actualizando la disponibilidad. Escríbenos para conocer las opciones vigentes.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="b-espacios b-reveal">
                <?php foreach (array_slice($espacios, 0, 3) as $indice => $espacio): ?>
                    <article class="b-espacio">
                        <div class="b-espacio__foto">
                            <img src="<?= e($foto($fotosEspacios[$indice % count($fotosEspacios)], 900)) ?>"
                                 alt="<?= e($espacio['nombre']) ?>" loading="lazy">
                        </div>
                        <div class="b-espacio__cuerpo">
                            <span><?= e(str_replace('_', ' ', (string) $espacio['tipo'])) ?></span>
                            <h3><?= e($espacio['nombre']) ?></h3>
                            <p><?= e($espacio['descripcion'] ?? 'Un espacio pensado para celebrar.') ?></p>
                            <div class="b-espacio__pie">
                                Hasta <?= (int) $espacio['capacidad'] ?> invitados · desde <?= e(money_corto($espacio['costo_referencia'])) ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="b-seccion b-seccion--oscura" id="menu">
    <div class="b-container">
        <div class="b-menu b-reveal">
            <div class="b-menu__foto">
                <img src="<?= e($foto(1729808, 1000)) ?>" alt="Montaje de mesa para banquete de boda" loading="lazy">
            </div>
            <div>
                <p class="b-eyebrow b-eyebrow--claro">La mesa</p>
                <h2 class="b-titulo" style="color:var(--b-ivory)">Un menú que se<br><em>recuerda.</em></h2>
                <p class="b-intro" style="color:#b9b2a4;margin-top:16px">Cocina propia, ingredientes de temporada y un equipo que ajusta sin drama. Estas son nuestras propuestas base; el menú final se cierra contigo y con tus restricciones.</p>

                <?php if ($menus === []): ?>
                    <p class="b-menu__nota">Consulta los menús vigentes con tu asesor.</p>
                <?php else: ?>
                    <div class="b-menu__lista">
                        <?php foreach ($menus as $menu): ?>
                            <div class="b-menu__platillo">
                                <div>
                                    <b><?= e($menu['nombre']) ?></b>
                                    <span><?= e($servicios[$menu['tipo_servicio']] ?? ucfirst((string) $menu['tipo_servicio'])) ?> · mínimo <?= (int) $menu['minimo_personas'] ?> personas</span>
                                    <p><?= e($menu['descripcion'] ?? '') ?></p>
                                </div>
                                <div style="text-align:right">
                                    <b style="color:var(--b-champagne-soft)"><?= e(money($menu['precio_persona'])) ?></b>
                                    <span style="display:block;font-size:9px;letter-spacing:.18em;color:#b9b2a4">por persona</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="b-menu__nota">Incluye montaje de mesa, mantelería y servicio. Opciones vegetarianas, veganas y sin gluten disponibles con costo adicional.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php if ($destacada !== null): ?>
<section class="b-seccion b-seccion--crema b-seccion--compacta">
    <div class="b-container">
        <div class="b-cta b-reveal" style="background:var(--b-ink)">
            <p class="b-eyebrow b-eyebrow--claro b-eyebrow--centro">La próxima celebración</p>
            <h2><?= e($destacada['nombre']) ?></h2>
            <p><?= e(fecha_larga($destacada['fecha_inicio'])) ?> · <?= (int) $destacada['aforo_maximo'] ?> invitados · expediente completo en operación.</p>
            <a class="b-btn" href="#contacto">Quiero una fecha así</a>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="b-seccion" id="experiencia">
    <div class="b-container">
        <div class="b-cabeza b-reveal">
            <p class="b-eyebrow b-eyebrow--centro">La experiencia</p>
            <h2 class="b-titulo">Así se siente<br><em>casarse aquí.</em></h2>
        </div>

        <div class="b-pasos b-reveal">
            <article class="b-paso">
                <span class="b-paso__num">01</span>
                <h3>Conversación</h3>
                <p>Un café, sin formularios interminables. Revisamos fecha, aforo y lo que imaginan.</p>
            </article>
            <article class="b-paso">
                <span class="b-paso__num">02</span>
                <h3>Visita y degustación</h3>
                <p>Conocen el espacio montado y prueban los menús antes de decidir.</p>
            </article>
            <article class="b-paso">
                <span class="b-paso__num">03</span>
                <h3>Diseño y logística</h3>
                <p>Ceremonia, cena, música y tiempos. Todo queda por escrito y reservado.</p>
            </article>
            <article class="b-paso">
                <span class="b-paso__num">04</span>
                <h3>El gran día</h3>
                <p>Coordinación en sitio, check-in de invitados y cierre con el expediente completo.</p>
            </article>
        </div>
    </div>
</section>

<section class="b-seccion b-seccion--compacta" style="padding-top:0">
    <div class="b-container">
        <div class="b-galeria b-reveal">
            <?php foreach ($galeria as $indice => $id): ?>
                <figure>
                    <img src="<?= e($foto($id, 900)) ?>" alt="Momento de una boda en ConectaEventos" loading="lazy">
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="b-seccion b-seccion--arena">
    <div class="b-container">
        <div class="b-testimonio b-reveal">
            <p class="b-testimonio__comillas">“</p>
            <blockquote>Llegamos con una idea y salimos con una boda que se sintió nuestra. Hasta la fecha, nuestros invitados siguen hablando del postre.</blockquote>
            <p class="b-testimonio__autor">Aurora &amp; Diego · Boda en el recinto</p>
        </div>
    </div>
</section>

<section class="b-seccion b-seccion--compacta">
    <div class="b-container">
        <div class="b-cta b-reveal">
            <h2>¿Ya tienes tu invitación?</h2>
            <p>Confirma tu asistencia con el código único que recibiste. Nos ayuda a preparar tu mesa, tu menú y cualquier restricción alimenticia.</p>
            <a class="b-btn" href="<?= e(url('/rsvp')) ?>">Confirmar asistencia</a>
        </div>
    </div>
</section>

<section class="b-seccion" id="contacto">
    <div class="b-container">
        <div class="b-editorial">
            <div class="b-editorial__texto b-reveal">
                <span class="b-eyebrow">Empecemos</span>
                <h2 class="b-titulo">Cuéntanos<br>su <em>historia.</em></h2>
                <p>Déjanos la fecha aproximada y el número de invitados. Te respondemos con disponibilidad real, propuesta de espacio y una estimación de menú.</p>
                <ul class="lista-check" style="margin-top:22px">
                    <li><span class="marca"><?= icono('ubicacion') ?></span><span><b>Recinto:</b> Av. de los Insurgentes Sur 1842, CDMX.</span></li>
                    <li><span class="marca"><?= icono('telefono') ?></span><span><b>Bodas:</b> +52 55 4821 9060 · cita previa.</span></li>
                    <li><span class="marca"><?= icono('correo') ?></span><span><b>Correo:</b> bodas@conectaeventos.mx</span></li>
                </ul>
                <div class="b-aviso mt-3">
                    <?= icono('corazon') ?>
                    Atendemos un máximo de dos bodas por mes para cuidar cada detalle.
                </div>
            </div>

            <form class="b-form b-reveal" method="post" action="<?= e(url('/contacto')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="origen" value="bodas">

                <div class="b-campo-fila">
                    <div class="b-campo">
                        <label for="b-nombre">Nombre completo *</label>
                        <input class="b-input" id="b-nombre" name="nombre" type="text" required minlength="3" maxlength="120" value="<?= viejo('nombre') ?>" placeholder="Sus nombres">
                    </div>
                    <div class="b-campo">
                        <label for="b-correo">Correo *</label>
                        <input class="b-input" id="b-correo" name="correo" type="email" required maxlength="120" value="<?= viejo('correo') ?>" placeholder="pareja@correo.com">
                    </div>
                </div>

                <div class="b-campo-fila">
                    <div class="b-campo">
                        <label for="b-telefono">Teléfono</label>
                        <input class="b-input" id="b-telefono" name="telefono" type="tel" maxlength="25" value="<?= viejo('telefono') ?>" placeholder="+52 55 0000 0000">
                    </div>
                    <div class="b-campo">
                        <label for="b-fecha">Fecha estimada</label>
                        <input class="b-input" id="b-fecha" name="fecha_estimada" type="date" data-futuro value="<?= viejo('fecha_estimada') ?>">
                    </div>
                </div>

                <div class="b-campo-fila">
                    <div class="b-campo">
                        <label for="b-aforo">Invitados estimados</label>
                        <input class="b-input" id="b-aforo" name="aforo" type="number" min="1" max="2000" value="<?= viejo('aforo') ?>" placeholder="120">
                    </div>
                    <div class="b-campo">
                        <label for="b-tipo">Tipo de celebración</label>
                        <select class="b-select" id="b-tipo" name="tipo_evento">
                            <option value="Boda" <?= viejo('tipo_evento') === 'Boda' ? 'selected' : '' ?>>Boda</option>
                            <option value="Boda civil" <?= viejo('tipo_evento') === 'Boda civil' ? 'selected' : '' ?>>Boda civil</option>
                            <option value="Renovación de votos" <?= viejo('tipo_evento') === 'Renovación de votos' ? 'selected' : '' ?>>Renovación de votos</option>
                        </select>
                    </div>
                </div>

                <div class="b-campo">
                    <label for="b-mensaje">Cuéntenos más</label>
                    <textarea class="b-textarea" id="b-mensaje" name="mensaje" maxlength="1000" placeholder="Estilo, lugar de la ceremonia, menú soñado, restricciones…"><?= viejo('mensaje') ?></textarea>
                </div>

                <?php if ($erroresForm !== []): ?>
                    <div class="b-aviso b-aviso--error">
                        Revisa los datos marcados e inténtalo de nuevo.
                    </div>
                <?php endif; ?>

                <button class="b-btn b-btn--bloque" type="submit">Enviar solicitud</button>
                <p class="b-rsvp__pie">Al enviar aceptas que te contactemos por correo o teléfono.</p>
            </form>
        </div>
    </div>
</section>

<footer class="b-pie">
    <div class="b-container">
        <div class="b-pie__grid">
            <div class="b-pie__marca">
                <a class="b-marca" href="<?= e(url('/boda')) ?>">
                    <span class="b-marca__sello">CE</span>
                    <span>
                        <b>ConectaEventos</b>
                        <span>Bodas de autor</span>
                    </span>
                </a>
                <p>Un recinto en el sur de la Ciudad de México para bodas íntimas y celebraciones de autor. Cocina propia, coordinación en sitio y un expediente que no se pierde.</p>
            </div>
            <div>
                <h4>Bodas</h4>
                <a href="#historia">Historia</a>
                <a href="#espacios">Espacios</a>
                <a href="#menu">Menú</a>
                <a href="#contacto">Contacto</a>
            </div>
            <div>
                <h4>Invitados</h4>
                <a href="<?= e(url('/rsvp')) ?>">Confirmar asistencia</a>
                <a href="<?= e(url('/rsvp')) ?>">Consultar mi respuesta</a>
                <a href="mailto:bodas@conectaeventos.mx">bodas@conectaeventos.mx</a>
            </div>
            <div>
                <h4>Recinto</h4>
                <a href="<?= e(url('/')) ?>">Eventos corporativos</a>
                <a href="<?= e(url('/')) ?>#disponibilidad">Disponibilidad</a>
                <a href="<?= e(url('/login')) ?>">Acceso al panel</a>
            </div>
        </div>
        <div class="b-pie__base">
            <span>© <?= $anio ?> ConectaEventos · Bodas de autor</span>
            <span>Av. de los Insurgentes Sur 1842, CDMX</span>
        </div>
    </div>
</footer>
