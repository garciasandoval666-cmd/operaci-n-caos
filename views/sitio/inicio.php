<?php
/** @var array $calendario */
/** @var string $fechaElegida */
/** @var array $proximos */
/** @var array $espacios */
/** @var array $tipos */
/** @var array $aliados */
/** @var int $aliadosConInventario */
/** @var array $stats */
/** @var string $mes */

/* El recinto se presenta por el catálogo completo de tipos de evento, no por
   uno solo: la fila de la portada se arma con lo que haya en la base. */

$etiquetasTipo = [
    'salon_gala'      => 'Salón de gala',
    'salon_fiesta'    => 'Salón de fiesta',
    'sala_exhibicion' => 'Sala de exhibición',
    'terraza'         => 'Terraza',
    'jardin'          => 'Jardín',
];

/* Respaldo por tipo: lo que se muestra es la ficha del espacio (`ideal_para`
   y `estilo`, en la base). Estos mapas solo cubren a un espacio recien dado de
   alta al que aun no le han escrito la suya. */
$idealPara = [
    'salon_gala'      => 'Bodas, galas corporativas y premiaciones',
    'salon_fiesta'    => 'XV años, cumpleaños y celebraciones privadas',
    'sala_exhibicion' => 'Expos, congresos y montajes de varios días',
    'terraza'         => 'Cóctel de bienvenida, ceremonias al aire libre',
    'jardin'          => 'Ceremonias, sesiones de foto y recepciones',
];

$estiloEspacio = [
    'salon_gala'      => 'Amplio, techo alto, iluminación escénica',
    'salon_fiesta'    => 'Cálido, íntimo, pista central',
    'sala_exhibicion' => 'Diáfano, modular, carga y descarga directa',
    'terraza'         => 'Abierto, vista panorámica, toldo retráctil',
    'jardin'          => 'Natural, arbolado, iluminación colgante',
];
?>

<!-- =====================================================================
     Navegacion
     ===================================================================== -->
<header class="l-nav">
    <div class="l-nav__caja">
        <a class="l-marca" href="<?= e(url('/')) ?>" aria-label="<?= e(marca()) ?>, inicio">
            <span class="l-marca__sello"><?= logo() ?></span>
            <span><?= e(marca()) ?></span>
        </a>

        <nav class="l-menu" aria-label="Secciones del sitio">
            <a href="#recinto">El recinto</a>
            <a href="#celebraciones">Celebraciones</a>
            <a href="#espacios">Espacios</a>
            <a href="#agenda">Agenda</a>
            <a href="<?= e(url('/boda')) ?>">Historias</a>
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
     Heroe
     ===================================================================== -->
<section class="l-hero">
    <div class="l-hero__foto" style="background-image:url('<?= e(foto('portada', 1800)) ?>')"></div>
    <div class="l-hero__velo"></div>

    <div class="l-hero__caja">
        <span class="l-eyebrow l-eyebrow--claro">Bodas · Corporativo · Congresos · Graduaciones · Conciertos</span>
        <h1 class="l-display l-parte">Un recinto para cada celebración</h1>
        <div class="l-hero__acciones">
            <a class="l-btn l-btn--crema" href="#agenda"><?= icono('calendario') ?> Ver fechas y reservar</a>
            <a class="l-btn l-btn--fantasma" href="#espacios">Conocer los espacios</a>
        </div>
    </div>

    <div class="l-hero__abajo" aria-hidden="true">
        <span class="l-hero__raton"></span>
        Desliza
    </div>
</section>

<div class="l-sobre-hero">

    <!-- =================================================================
         Declaracion de apertura
         ================================================================= -->
    <section class="l-seccion" id="recinto">
        <div class="l-ancho l-intro">
            <div class="l-reveal">
                <span class="l-eyebrow">El recinto</span>
                <h2 class="l-h3" style="margin-top:16px"><?= e(ucfirst(plural((int) $stats['espacios'], 'espacio', 'espacios'))) ?>, un mismo estándar</h2>
            </div>
            <div class="l-intro__texto l-reveal" style="--l-retraso:120ms">
                <p class="l-lead">Llevamos la operación completa de cada evento en un solo sistema: la
                    disponibilidad del espacio, el inventario, el menú, la cotización y la cobranza.
                    Nadie reserva dos veces el mismo salón ni descubre un faltante la noche del montaje.</p>
                <p>Trabajamos igual de bien una boda de doscientos invitados que un congreso de tres días
                    o un concierto con acceso general. Cambia el montaje, no el cuidado.</p>

                <div class="l-intro__cifras">
                    <div class="l-cifra">
                        <b data-contador="<?= (int) $stats['eventos'] ?>" data-sufijo="+">0</b>
                        <span>Eventos realizados</span>
                    </div>
                    <div class="l-cifra">
                        <b data-contador="<?= (int) $stats['aforo'] ?>">0</b>
                        <span>Invitados en el salón mayor</span>
                    </div>
                    <div class="l-cifra">
                        <b data-contador="<?= (int) $stats['espacios'] ?>">0</b>
                        <span>Espacios disponibles</span>
                    </div>
                    <div class="l-cifra">
                        <b data-contador="<?= (int) $stats['proveedores'] ?>">0</b>
                        <span>Proveedores aliados</span>
                    </div>
                </div>

                <p style="margin-top:28px">
                    <a class="l-link" href="#espacios">Ver los espacios <?= icono('flecha') ?></a>
                </p>
            </div>
        </div>
    </section>

    <!-- =================================================================
         Collage con parallax
         ================================================================= -->
    <section class="l-seccion l-seccion--apretada">
        <div class="l-ancho l-collage">
            <?php
            $collage = [
                ['collage-1', 'Montaje de banquete con mantel largo', '.18'],
                ['collage-2', 'Mesa decorada con flores y luces colgantes', '-.24'],
                ['collage-3', 'Celebración al aire libre', '.12'],
                ['collage-4', 'Reunión corporativa en sala de juntas', '-.16'],
                ['collage-5', 'Juegos de luces sobre el escenario', '.22'],
                ['collage-6', 'Pareja en una ceremonia al atardecer', '-.10'],
            ];
            foreach ($collage as [$clave, $alt, $fuerza]): ?>
                <figure class="l-collage__pieza" data-par="<?= e($fuerza) ?>">
                    <img src="<?= e(foto($clave, 900)) ?>" alt="<?= e($alt) ?>" loading="lazy" decoding="async">
                </figure>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- =================================================================
         Tipos de evento: la baraja
         ================================================================= -->
    <section class="l-baraja" id="celebraciones">
        <div class="l-baraja__pista">
            <div class="l-baraja__fijo l-seccion--menta">
                <div class="l-cabeza l-ancho l-reveal" style="margin-bottom:0">
                    <span class="l-eyebrow">Lo que celebramos</span>
                    <h2 class="l-h2 l-parte" style="margin-top:14px">Toda ocasión cabe aquí</h2>
                    <p>Desde una ceremonia intima hasta un congreso de tres días. Cada tipo de evento
                        tiene su propio protocolo de montaje, menú y aforo.</p>
                </div>

                <div class="l-baraja__cartas" tabindex="0" role="group" aria-label="Tipos de evento que organizamos">
                    <?php foreach ($tipos as $i => $tipo): ?>
                        <article class="l-carta">
                            <div class="l-carta__top">
                                <b><?= e($tipo['nombre']) ?></b>
                                <span class="l-carta__icono"><?= icono($tipo['icono'] ?: tipo_evento_icono($tipo['nombre'])) ?></span>
                            </div>
                            <div class="l-carta__foto">
                                <img src="<?= e(foto_tipo($tipo['nombre'], 560)) ?>"
                                     alt="<?= e($tipo['nombre']) ?>" loading="lazy" decoding="async">
                            </div>
                            <p><?= e($tipo['descripcion'] ?: 'Montaje a medida según el protocolo del evento.') ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="l-baraja__barra" aria-hidden="true"><span></span></div>
            </div>
        </div>
    </section>

    <!-- =================================================================
         Espacios apilados
         ================================================================= -->
    <section class="l-seccion l-seccion--ink" id="espacios">
        <div class="l-ancho">
            <div class="l-cabeza l-reveal">
                <span class="l-eyebrow l-eyebrow--claro">Los espacios</span>
                <h2 class="l-h2 l-parte" style="margin-top:14px">Elige donde sucede</h2>
                <p style="color:var(--sobre-ink-2)"><?= e(ucfirst(plural(count($espacios), 'espacio', 'espacios'))) ?> con capacidades
                    de <?= number_format(min(array_map(static fn (array $e): int => (int) $e['capacidad'], $espacios))) ?>
                    a <?= number_format(max(array_map(static fn (array $e): int => (int) $e['capacidad'], $espacios))) ?> invitados.
                    Se pueden combinar: cóctel en la terraza y cena en el salón, o registro en la
                    galería y sesiones en el salón de gala.</p>
            </div>

            <div class="l-espacios">
                <?php foreach ($espacios as $i => $espacio): ?>
                    <?php $tipoEsp = (string) $espacio['tipo']; ?>
                    <article class="l-espacio" style="--l-i:<?= (int) $i ?>">
                        <div class="l-espacio__foto" style="background-image:url('<?= e(foto($tipoEsp, 1400)) ?>')"></div>
                        <div class="l-espacio__velo"></div>
                        <div class="l-espacio__ficha">
                            <div>
                                <span class="l-eyebrow"><?= e($etiquetasTipo[$tipoEsp] ?? 'Espacio') ?></span>
                                <h3 style="margin-top:10px"><?= e($espacio['nombre']) ?></h3>
                                <?php if (!empty($espacio['descripcion'])): ?>
                                    <p style="margin-top:12px;font-size:.9375rem"><?= e($espacio['descripcion']) ?></p>
                                <?php endif; ?>
                            </div>
                            <dl class="l-espacio__datos">
                                <div class="l-espacio__dato">
                                    <dt>Aforo</dt>
                                    <dd>Hasta <?= number_format((int) $espacio['capacidad']) ?> invitados</dd>
                                </div>
                                <div class="l-espacio__dato">
                                    <dt>Ideal para</dt>
                                    <dd><?= e($espacio['ideal_para'] ?: ($idealPara[$tipoEsp] ?? 'Eventos sociales y corporativos')) ?></dd>
                                </div>
                                <div class="l-espacio__dato">
                                    <dt>Estilo</dt>
                                    <dd><?= e($espacio['estilo'] ?: ($estiloEspacio[$tipoEsp] ?? 'Montaje flexible')) ?></dd>
                                </div>
                                <div class="l-espacio__dato">
                                    <dt>Desde</dt>
                                    <dd><?= e(money($espacio['costo_referencia'])) ?> por jornada</dd>
                                </div>
                            </dl>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- =================================================================
         Agenda y disponibilidad
         ================================================================= -->
    <section class="l-seccion" id="agenda">
        <div class="l-ancho">
            <div class="l-cabeza l-reveal">
                <span class="l-eyebrow">Agenda abierta</span>
                <h2 class="l-h2 l-parte" style="margin-top:14px">¿Qué día tienes en mente?</h2>
                <p>Toca el día que quieres y te llevamos al formulario con la fecha puesta.
                    La disponibilidad que ves es la real: sale de las reservas confirmadas del
                    recinto, no de un calendario aparte.</p>
            </div>

            <?php /* Los tres pasos van antes del calendario porque la pregunta
                     que trae al visitante hasta aquí es «cómo reservo».
                     Responderla con una frase suelta en el pie no bastaba. */ ?>
            <ol class="l-pasos l-reveal">
                <li>
                    <b>1</b>
                    <div>
                        <h3>Elige el día</h3>
                        <p>Toca una fecha libre del calendario. Puedes cambiarla después.</p>
                    </div>
                </li>
                <li>
                    <b>2</b>
                    <div>
                        <h3>Envía la solicitud</h3>
                        <p>Nombre, correo e invitados. Te confirmamos el espacio y el estimado.</p>
                    </div>
                </li>
                <li>
                    <b>3</b>
                    <div>
                        <h3>Apartamos la fecha</h3>
                        <p>Con el anticipo, el día queda bloqueado en esta misma agenda.</p>
                    </div>
                </li>
            </ol>

            <div class="l-intro" style="--l-cols:1">
                <div class="l-reveal">
                    <div class="l-cal">
                        <div class="l-cal__top">
                            <b><?= e($calendario['etiqueta']) ?></b>
                            <div class="l-cal__nav">
                                <a href="<?= e(url('/?mes=' . $calendario['anterior'])) ?>#agenda" aria-label="Mes anterior"><?= icono('atras') ?></a>
                                <a href="<?= e(url('/?mes=' . $calendario['siguiente'])) ?>#agenda" aria-label="Mes siguiente"><?= icono('flecha') ?></a>
                            </div>
                        </div>

                        <div class="l-cal__rejilla" role="grid">
                            <?php foreach (['Lun','Mar','Mie','Jue','Vie','Sab','Dom'] as $dow): ?>
                                <div class="l-cal__dow"><?= e($dow) ?></div>
                            <?php endforeach; ?>

                            <?php foreach ($calendario['celdas'] as $celda): ?>
                                <?php if ($celda['dia'] === null): ?>
                                    <div class="l-cal__dia l-cal__dia--fuera" aria-hidden="true"></div>

                                <?php elseif (!$celda['elegible']): ?>
                                    <?php /* Fecha pasada: se muestra para no romper la rejilla,
                                             pero no se puede pedir. */ ?>
                                    <div class="l-cal__dia l-cal__dia--pasado" aria-hidden="true"><?= (int) $celda['dia'] ?></div>

                                <?php else: ?>
                                    <?php
                                    $diaTexto = fecha($celda['fecha'], 'D j') . ' de ' . fecha($celda['fecha'], 'F');
                                    $libresDia = (int) $celda['libres'];

                                    // El texto dice el numero exacto de espacios libres. Un dia
                                    // con tres salones de cuatro no es «reservado», y decirlo
                                    // asi costaba solicitudes que el recinto podia atender.
                                    if ($celda['estado'] === 'completo') {
                                        $detalle = 'sin espacios libres, escríbenos y buscamos una fecha cercana';
                                    } elseif ($celda['estado'] === 'parcial') {
                                        $detalle = plural($libresDia, 'espacio libre', 'espacios libres')
                                            . ' · ocupado: ' . $celda['espacios'];
                                    } else {
                                        $detalle = plural($libresDia, 'espacio libre', 'espacios libres');
                                    }

                                    $esElegido = $celda['fecha'] === $fechaElegida;
                                    $clases = 'l-cal__dia l-cal__dia--' . $celda['estado']
                                        . ($celda['hoy'] ? ' es-hoy' : '')
                                        . ($esElegido ? ' es-elegido' : '');
                                    ?>
                                    <a class="<?= e($clases) ?>"
                                       href="<?= e(url('/?mes=' . $calendario['mes'] . '&fecha=' . $celda['fecha']) . '#solicitud') ?>"
                                       data-dia="<?= e($celda['fecha']) ?>"
                                       title="<?= e($diaTexto . ' · ' . $detalle) ?>"
                                       aria-label="<?= e('Elegir el ' . $diaTexto . ': ' . $detalle) ?>"
                                       <?= $esElegido ? 'aria-current="date"' : '' ?>>
                                        <span><?= (int) $celda['dia'] ?></span>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <div class="l-cal__leyenda">
                            <span><i class="l-cal__punto l-cal__punto--libre"></i> Todo libre</span>
                            <span><i class="l-cal__punto l-cal__punto--parcial"></i> Quedan espacios</span>
                            <span><i class="l-cal__punto l-cal__punto--completo"></i> Sin espacios</span>
                        </div>
                        <p class="l-cal__pie">
                            <?= e(plural((int) $calendario['con_libres'], 'día con espacio libre', 'días con espacio libre')) ?>
                            en <?= e($calendario['etiqueta']) ?>, sobre
                            <?= e(plural((int) $calendario['espacios_total'], 'espacio', 'espacios')) ?> del recinto.
                        </p>
                    </div>
                </div>

                <div class="l-reveal" style="--l-retraso:120ms">
                    <h3 class="l-h4" style="margin-bottom:18px">Próximos eventos en el recinto</h3>
                    <?php if ($proximos === []): ?>
                        <p>Todavía no hay eventos confirmados en la agenda pública. Escríbenos y
                            apartamos la fecha que necesitas.</p>
                    <?php else: ?>
                        <div class="l-agenda">
                            <?php foreach ($proximos as $evento): ?>
                                <div class="l-agenda__fila">
                                    <div class="l-agenda__fecha">
                                        <b><?= e(date('d', strtotime($evento['fecha_inicio']))) ?></b>
                                        <span><?= e(fecha($evento['fecha_inicio'], 'M')) ?></span>
                                    </div>
                                    <div>
                                        <div class="l-agenda__nombre"><?= e($evento['nombre']) ?></div>
                                        <div class="l-agenda__meta">
                                            <?= e($evento['tipo']) ?> ·
                                            <?= e(plural((int) $evento['aforo_maximo'], 'invitado', 'invitados')) ?>
                                        </div>
                                    </div>
                                    <div class="l-agenda__sitio"><?= e($evento['espacio'] ?: 'Por asignar') ?></div>
                                    <span class="l-agenda__pill">Confirmado</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- =================================================================
         Aliados
         ================================================================= -->
    <?php if ($aliados !== []): ?>
        <section class="l-seccion l-seccion--apretada l-seccion--arena">
            <div class="l-ancho l-aliados__intro">
                <p class="l-eyebrow l-reveal">Aliados del recinto</p>
                <h2 class="l-parte">Quienes montan, suenan y decoran cada evento</h2>
                <p class="l-reveal">
                    <?= e(plural(count($aliados), 'proveedor', 'proveedores')) ?> en activo,
                    <?= $aliadosConInventario === count($aliados) ? 'todos' : (int) $aliadosConInventario ?>
                    con su inventario dentro del sistema: lo que se aparta para un evento deja de
                    estar disponible para otro, sin llamadas ni hojas de cálculo.
                </p>
            </div>
            <div class="l-marquesina">
                <div class="l-marquesina__pista">
                    <?php foreach ($aliados as $aliado): ?>
                        <?php
                        $etiqueta = $aliado['sitio_web'] !== null && $aliado['sitio_web'] !== '' ? 'a' : 'div';
                        ?>
                        <<?= $etiqueta ?> class="l-aliado"
                            <?php if ($etiqueta === 'a'): ?>
                                href="<?= e($aliado['sitio_web']) ?>" target="_blank" rel="noopener noreferrer nofollow"
                            <?php endif; ?>>
                            <?= sello_proveedor($aliado, 52) ?>
                            <span class="l-aliado__texto">
                                <b><?= e($aliado['nombre']) ?></b>
                                <span><?= e($aliado['descripcion'] ?: categoria_proveedor((string) $aliado['categoria'])) ?></span>
                            </span>
                        </<?= $etiqueta ?>>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- =================================================================
         Contacto
         ================================================================= -->
    <section class="l-seccion" id="contacto">
        <div class="l-ancho">
            <div class="l-cta">
                <div class="l-cta__foto" style="background-image:url('<?= e(foto('cta', 1600)) ?>')"></div>
                <div class="l-cta__velo"></div>
                <span class="l-eyebrow l-eyebrow--claro">Agenda una visita</span>
                <h2 class="l-h3" style="margin-top:14px">Ven a ver el recinto</h2>
                <p>Cuéntanos qué celebras y te respondemos con disponibilidad, aforo y un estimado
                    en menos de 24 horas hábiles.</p>
            </div>

            <div class="l-ancho--angosto" style="margin-top:-56px;position:relative;z-index:3">
                <div style="background:var(--surface);border:1px solid var(--line);border-radius:var(--r-lg);padding:clamp(24px,3vw,40px);box-shadow:var(--shadow-lg)">
                    <form class="l-form" id="solicitud" method="post" action="<?= e(url('/contacto')) ?>" data-una-vez>
                        <?= csrf_field() ?>
                        <input type="hidden" name="origen" value="corporativo">
                        <?php /* Trampa de miel: oculta para las personas, visible
                                 para los robots que rellenan todos los campos.
                                 No es un campo real; si llega con contenido, el
                                 envio se descarta en SitioController::contacto. */ ?>
                        <div class="trampa" aria-hidden="true">
                            <label for="sw-corp">No rellenar</label>
                            <input id="sw-corp" type="text" name="sitio_web" tabindex="-1" autocomplete="off" value="">
                        </div>

                        <div class="l-form__fila">
                            <div class="l-campo<?= error_campo('nombre') ? ' l-campo--error' : '' ?>">
                                <label for="nombre">Nombre completo</label>
                                <input id="nombre" name="nombre" type="text" required maxlength="120" value="<?= viejo('nombre') ?>" placeholder="Cómo te llamas">
                                <?php if ($m = error_campo('nombre')): ?><span class="l-campo__error"><?= e($m) ?></span><?php endif; ?>
                            </div>
                            <div class="l-campo<?= error_campo('correo') ? ' l-campo--error' : '' ?>">
                                <label for="correo">Correo</label>
                                <input id="correo" name="correo" type="email" required maxlength="120" value="<?= viejo('correo') ?>" placeholder="tucorreo@ejemplo.com">
                                <?php if ($m = error_campo('correo')): ?><span class="l-campo__error"><?= e($m) ?></span><?php endif; ?>
                            </div>
                        </div>

                        <div class="l-form__fila">
                            <div class="l-campo">
                                <label for="telefono">Teléfono</label>
                                <input id="telefono" name="telefono" type="tel" maxlength="25" value="<?= viejo('telefono') ?>" placeholder="55 0000 0000">
                            </div>
                            <div class="l-campo">
                                <label for="tipo_evento">Tipo de evento</label>
                                <select id="tipo_evento" name="tipo_evento">
                                    <option value="">Elige una opción</option>
                                    <?php foreach ($tipos as $tipo): ?>
                                        <option value="<?= e($tipo['nombre']) ?>"><?= e($tipo['nombre']) ?></option>
                                    <?php endforeach; ?>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                        </div>

                        <div class="l-form__fila">
                            <div class="l-campo">
                                <label for="fecha_estimada">Fecha estimada</label>
                                <?php /* El valor por defecto es el dia que se toco en el
                                         calendario; `viejo()` manda si el envio fallo. */ ?>
                                <input id="fecha_estimada" name="fecha_estimada" type="date" data-desde-hoy value="<?= viejo('fecha_estimada', $fechaElegida) ?>">
                            </div>
                            <div class="l-campo">
                                <label for="aforo">Invitados aproximados</label>
                                <input id="aforo" name="aforo" type="number" min="1" max="5000" value="<?= viejo('aforo') ?>" placeholder="150">
                            </div>
                        </div>

                        <?php /* El aviso lo rellena landing.js consultando la agenda real.
                                Nace vacío y oculto: si el JavaScript no corre, el formulario
                                sigue funcionando exactamente igual. */ ?>
                        <p class="l-dispo" data-dispo hidden aria-live="polite"></p>

                        <div class="l-campo<?= error_campo('mensaje') ? ' l-campo--error' : '' ?>">
                            <label for="mensaje">Cuéntanos un poco más</label>
                            <textarea id="mensaje" name="mensaje" maxlength="1000" placeholder="Qué celebras, qué montaje imaginas, si necesitas menú…"><?= viejo('mensaje') ?></textarea>
                            <?php if ($m = error_campo('mensaje')): ?><span class="l-campo__error"><?= e($m) ?></span><?php endif; ?>
                        </div>

                        <button class="l-btn l-btn--ink" type="submit" style="justify-self:start">
                            Enviar solicitud <?= icono('flecha') ?>
                        </button>
                    </form>
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
                        <a href="#recinto">El recinto</a>
                        <a href="#celebraciones">Celebraciones</a>
                        <a href="#espacios">Espacios</a>
                        <a href="#agenda">Agenda</a>
                        <a href="<?= e(url('/boda')) ?>">Historias</a>
                    </div>
                </div>

                <div class="l-pie__col">
                    <h4>Accesos</h4>
                    <a href="<?= e(url('/rsvp')) ?>">Confirmar asistencia</a>
                    <a href="<?= e(url('/boda')) ?>">Historias del recinto</a>
                    <a href="<?= e(url('/login')) ?>">Acceso del equipo</a>
                </div>

                <div class="l-pie__col">
                    <h4>Contacto</h4>
                    <p>Av. de los Recintos 128<br>Ciudad de México</p>
                    <p>55 1234 5678</p>
                    <a href="mailto:hola@solera.mx">hola@solera.mx</a>
                </div>

                <div class="l-pie__col">
                    <h4>Horario</h4>
                    <p>Visitas al recinto<br>Lunes a sábado, 10:00 a 19:00</p>
                    <p>Eventos<br>Todos los días</p>
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
