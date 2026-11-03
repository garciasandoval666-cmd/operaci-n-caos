<?php
/** @var array $calendario */
/** @var array $proximos */
/** @var array $espacios */
/** @var array $stats */
/** @var string $mes */

$foto = static fn (int $id, int $ancho = 1400): string =>
    'https://images.pexels.com/photos/' . $id . '/pexels-photo-' . $id . '.jpeg?auto=compress&cs=tinysrgb&w=' . $ancho;

$erroresForm = errores();
$anioActual = (int) date('Y');
$tipos = ['Corporativo', 'Congreso', 'Exposición', 'Graduación', 'Social', 'XV Años', 'Boda', 'Concierto'];
?>
<nav class="site-nav" aria-label="Navegación principal">
    <div class="l-container site-nav__inner">
        <a class="marca-landing" href="<?= e(url('/')) ?>">
            <span class="monograma">CE</span>
            <span>
                <b>ConectaEventos</b>
                <span>Recinto &amp; productora</span>
            </span>
        </a>

        <div class="site-nav__links">
            <a href="#recinto">Recinto</a>
            <a href="#modulos">Cómo funciona</a>
            <a href="#disponibilidad">Disponibilidad</a>
            <a href="#galeria">Eventos</a>
            <a href="#contacto">Contacto</a>
        </div>

        <div class="site-nav__cta">
            <a class="btn btn--fantasma btn--sm" href="<?= e(url('/boda')) ?>"><?= icono('corazon') ?> Bodas</a>
            <a class="btn btn--lima btn--sm" href="<?= e(url('/login')) ?>"><?= icono('llave') ?> Entrar</a>
        </div>

        <button type="button" class="site-nav__toggle" aria-label="Abrir menú" aria-expanded="false">
            <?= icono('menu') ?>
        </button>
    </div>
</nav>

<header class="hero" id="recinto">
    <div class="hero__fondo">
        <img src="<?= e($foto(2306281, 1800)) ?>" alt="Salón de eventos iluminado durante una recepción" fetchpriority="high">
    </div>
    <div class="hero__rejilla" aria-hidden="true"></div>

    <div class="l-container hero__inner">
        <div>
            <p class="eyebrow">Ciudad de México · Desde 2014</p>
            <h1>Eventos que se recuerdan.<br>Operación que <span class="resalte">no falla.</span></h1>
            <p class="hero__texto">
                Un recinto, cuatro módulos y una sola base de datos. Clientes, espacios, cocina,
                cotizaciones y cobranza trabajan coordinados para que el día del evento solo tengas
                que recibir a tus invitados.
            </p>
            <div class="hero__cta">
                <a class="btn btn--lima" href="#contacto"><?= icono('calendario') ?> Agendar una visita</a>
                <a class="btn btn--fantasma" href="#disponibilidad"><?= icono('ver') ?> Ver disponibilidad</a>
            </div>
            <div class="hero__prueba">
                <div class="avatares" aria-hidden="true">
                    <span>AL</span><span>MV</span><span>DS</span><span>+8</span>
                </div>
                <p><b><?= e(plural((int) $stats['eventos'], 'evento cerrado', 'eventos cerrados')) ?></b><br>con expediente completo y auditoría</p>
            </div>
        </div>

        <aside class="hero__panel" aria-label="Disponibilidad del mes">
            <div class="panel-dispo">
                <div class="panel-dispo__head">
                    <span class="punto" aria-hidden="true"></span>
                    <b>Disponibilidad en vivo</b>
                    <span><?= e($calendario['etiqueta']) ?></span>
                </div>
                <div class="panel-dispo__cuerpo">
                    <div class="mini-cal">
                        <?php foreach (['L', 'M', 'M', 'J', 'V', 'S', 'D'] as $dia): ?>
                            <div class="mini-cal__dia"><?= e($dia) ?></div>
                        <?php endforeach; ?>
                        <?php foreach ($calendario['celdas'] as $celda): ?>
                            <?php if ($celda['dia'] === null): ?>
                                <div class="mini-cal__celda" aria-hidden="true"></div>
                            <?php else: ?>
                                <div class="mini-cal__celda <?= $celda['hoy'] ? 'hoy' : ($celda['ocupado'] ? 'ocupado' : 'libres') ?>"
                                     title="<?= $celda['ocupado'] ? e('Ocupado: ' . $celda['evento']) : 'Disponible' ?>">
                                    <?= (int) $celda['dia'] ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <div class="panel-dispo__lista">
                        <div class="panel-fila">
                            <?= icono('espacios') ?>
                            <b>Espacios activos</b>
                            <span><?= count($espacios) ?> recintos</span>
                        </div>
                        <div class="panel-fila">
                            <?= icono('invitados') ?>
                            <b>Aforo máximo</b>
                            <span><?= number_format((int) $stats['aforo'], 0, '.', ',') ?> personas</span>
                        </div>
                        <div class="panel-fila">
                            <?= icono('calendario') ?>
                            <b>Días ocupados</b>
                            <span><?= (int) $calendario['ocupados'] ?> este mes</span>
                        </div>
                    </div>
                </div>
                <div class="panel-dispo__pie">
                    <?= icono('check') ?> Calculado con reservas confirmadas, no con promesas.
                </div>
            </div>
        </aside>
    </div>

    <div class="hero__marquesina" aria-hidden="true">
        <div class="marquesina">
            <?php for ($i = 0; $i < 2; $i++): ?>
                <span class="marquesina__item">Bodas de autor</span>
                <span class="marquesina__item">Congresos</span>
                <span class="marquesina__item">Cenas corporativas</span>
                <span class="marquesina__item">Exposiciones</span>
                <span class="marquesina__item">XV años</span>
                <span class="marquesina__item">Graduaciones</span>
                <span class="marquesina__item">Conciertos privados</span>
            <?php endfor; ?>
        </div>
    </div>
</header>

<section class="seccion seccion--compacta">
    <div class="l-container">
        <div class="tira reveal">
            <div class="tira__item">
                <b data-contador="<?= (int) $stats['eventos'] ?>" data-sufijo="+"><?= (int) $stats['eventos'] ?>+</b>
                <span>Eventos realizados</span>
            </div>
            <div class="tira__item">
                <b data-contador="<?= (int) $stats['clientes'] ?>"><?= (int) $stats['clientes'] ?></b>
                <span>Clientes en expediente</span>
            </div>
            <div class="tira__item">
                <b data-contador="<?= (int) $stats['espacios'] ?>"><?= (int) $stats['espacios'] ?></b>
                <span>Espacios y terrazas</span>
            </div>
            <div class="tira__item">
                <b data-contador="<?= (int) $stats['proveedores'] ?>"><?= (int) $stats['proveedores'] ?></b>
                <span>Proveedores homologados</span>
            </div>
        </div>
    </div>
</section>

<section class="seccion" id="modulos">
    <div class="l-container">
        <div class="seccion__cabeza reveal">
            <div>
                <p class="eyebrow eyebrow--oscuro">Un sistema, cuatro módulos</p>
                <h2>Del primer correo<br>al último pago.</h2>
            </div>
            <div class="seccion__texto">
                <p>No son cuatro sistemas pegados con copiar y pegar. Es un solo flujo donde cada área ve la información que necesita y nada más.</p>
                <p>Cuando un evento se confirma, el espacio se bloquea, la cocina recibe la producción y finanzas sabe exactamente cuánto falta cobrar.</p>
            </div>
        </div>

        <div class="modulos reveal">
            <article class="modulo">
                <span class="modulo__num">Módulo A</span>
                <div class="modulo__icono"><?= icono('clientes') ?></div>
                <h3>Clientes y eventos</h3>
                <p>Expediente único por cliente, eventos con aforo, tipo, estado y bitácora de cada cambio. Sin duplicados ni versiones huérfanas.</p>
                <div class="modulo__pie"><?= icono('flecha') ?> Contacto → evento → cierre</div>
            </article>
            <article class="modulo modulo--destacado">
                <span class="modulo__num">Módulo B</span>
                <div class="modulo__icono"><?= icono('espacios') ?></div>
                <h3>Recursos y reservas</h3>
                <p>Espacios, mobiliario y equipos con control de traslapes. La disponibilidad se calcula por día y por cantidad, no por intuición.</p>
                <div class="modulo__pie"><?= icono('check') ?> Cero dobles reservas</div>
            </article>
            <article class="modulo">
                <span class="modulo__num">Módulo C</span>
                <div class="modulo__icono"><?= icono('cotizacion') ?></div>
                <h3>Cotizaciones y pagos</h3>
                <p>Precios congelados al aceptar, anticipos, abonos y devoluciones. El saldo se calcula con pagos aplicados, nunca a mano.</p>
                <div class="modulo__pie"><?= icono('flecha') ?> Propuesta → aceptación → cobranza</div>
            </article>
            <article class="modulo">
                <span class="modulo__num">Módulo D</span>
                <div class="modulo__icono"><?= icono('reportes') ?></div>
                <h3>Auditoría y reportes</h3>
                <p>Bitácora inmutable, control de inconsistencias y reportes de ocupación, producción y cobranza para decidir con datos.</p>
                <div class="modulo__pie"><?= icono('flecha') ?> Todo queda registrado</div>
            </article>
        </div>
    </div>
</section>

<section class="seccion seccion--compacta" id="disponibilidad">
    <div class="l-container">
        <div class="split">
            <div class="split__texto reveal">
                <p class="eyebrow eyebrow--oscuro">Disponibilidad real</p>
                <h2>La fecha que ves libre,<br>de verdad lo está.</h2>
                <p>El calendario público se alimenta de las reservas confirmadas del sistema. Si un espacio está apartado para montaje o mantenimiento, no aparece disponible.</p>
                <ul class="lista-check">
                    <li><span class="marca"><?= icono('check') ?></span><span><b>Bloqueo por día completo:</b> montaje, evento y desmontaje quedan reservados.</span></li>
                    <li><span class="marca"><?= icono('check') ?></span><span><b>Control de inventario:</b> sillas, mesas, audio e iluminación se descuentan por cantidad.</span></li>
                    <li><span class="marca"><?= icono('check') ?></span><span><b>Sin sobreventa:</b> el sistema rechaza traslapes aunque dos personas capturen a la vez.</span></li>
                </ul>
                <div class="hero__cta" style="margin-top:26px">
                    <a class="btn btn--primario" href="#contacto"><?= icono('correo') ?> Consultar una fecha</a>
                    <a class="btn btn--fantasma" href="<?= e(url('/login')) ?>"><?= icono('llave') ?> Ver panel</a>
                </div>
            </div>

            <div class="mock reveal">
                <div class="mock__tarjeta">
                    <div class="mock__barra">
                        <i></i><i></i><i></i>
                        <span>Calendario operativo · <?= e($calendario['etiqueta']) ?></span>
                    </div>
                    <div class="mock__cuerpo">
                        <div class="mock__titulo">
                            <b>Ocupación del recinto</b>
                            <span><?= (int) $calendario['ocupados'] ?> días con evento</span>
                        </div>
                        <div class="mock-cal">
                            <?php foreach (['L', 'M', 'M', 'J', 'V', 'S', 'D'] as $dia): ?>
                                <div class="mock-cal__d"><?= e($dia) ?></div>
                            <?php endforeach; ?>
                            <?php foreach ($calendario['celdas'] as $celda): ?>
                                <?php if ($celda['dia'] === null): ?>
                                    <div class="mock-cal__c" aria-hidden="true"></div>
                                <?php else: ?>
                                    <div class="mock-cal__c <?= $celda['hoy'] ? 'sel' : ($celda['ocupado'] ? 'busy' : 'free') ?>">
                                        <?= (int) $celda['dia'] ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <div class="mock__leyenda">
                            <span><i class="f"></i>Disponible</span>
                            <span><i class="b"></i>Ocupado</span>
                            <span><i style="background:var(--l-ink)"></i>Hoy</span>
                        </div>
                    </div>
                </div>
                <div class="mock__flotante">
                    <span>Espacio más amplio</span>
                    <b><?= e($espacios[0]['nombre'] ?? 'Gran Salón') ?></b>
                    <small>Hasta <?= number_format((int) ($espacios[0]['capacidad'] ?? 0), 0, '.', ',') ?> personas · <?= count($espacios) ?> espacios activos</small>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="seccion seccion--oscura">
    <div class="l-container">
        <div class="seccion__cabeza reveal">
            <div>
                <p class="eyebrow">Cómo trabajamos</p>
                <h2>Cuatro pasos,<br>cero sorpresas.</h2>
            </div>
            <div class="seccion__texto">
                <p>El mismo proceso para una cena de 40 personas que para un congreso de 400. Lo que cambia es la escala, no el rigor.</p>
            </div>
        </div>

        <div class="pasos reveal">
            <article class="paso">
                <span class="paso__num">01</span>
                <h3>Diagnóstico</h3>
                <p>Escuchamos objetivo, fecha, aforo y presupuesto. Revisamos disponibilidad real antes de prometer nada.</p>
                <span class="paso__tiempo">Día 1</span>
            </article>
            <article class="paso">
                <span class="paso__num">02</span>
                <h3>Propuesta</h3>
                <p>Cotización detallada por concepto, con menú, montaje, recursos y anticipo claramente desglosados.</p>
                <span class="paso__tiempo">48 horas</span>
            </article>
            <article class="paso">
                <span class="paso__num">03</span>
                <h3>Producción</h3>
                <p>El evento entra a preparación: reservas bloqueadas, menús cerrados, invitados confirmados y proveedores citados.</p>
                <span class="paso__tiempo">Semanas previas</span>
            </article>
            <article class="paso">
                <span class="paso__num">04</span>
                <h3>Ejecución y cierre</h3>
                <p>Check-in, montaje y operación en sitio. Al final, expediente, pagos conciliados y reporte de resultados.</p>
                <span class="paso__tiempo">Día del evento</span>
            </article>
        </div>
    </div>
</section>

<section class="seccion" id="galeria">
    <div class="l-container">
        <div class="seccion__cabeza reveal">
            <div>
                <p class="eyebrow eyebrow--oscuro">En operación</p>
                <h2>Lo que viene<br>en el recinto.</h2>
            </div>
            <div class="seccion__texto">
                <p>Estos son eventos reales tomados del sistema, no fotos de catálogo. Cada uno tiene espacio, menú, invitados y cobranza en el mismo expediente.</p>
            </div>
        </div>

        <?php if ($proximos === []): ?>
            <div class="features reveal">
                <article class="feature">
                    <?= icono('calendario') ?>
                    <h3>Agenda abierta</h3>
                    <p>No hay eventos públicos próximos. Escríbenos para revisar fechas disponibles en el recinto.</p>
                </article>
            </div>
        <?php else: ?>
            <div class="galeria reveal">
                <?php $primero = $proximos[0]; ?>
                <article class="galeria__grande">
                    <img src="<?= e($foto(1181406, 1400)) ?>" alt="Montaje de un evento corporativo" loading="lazy">
                    <div class="galeria__info">
                        <span><?= e($primero['tipo']) ?> · <?= e(fecha($primero['fecha_inicio'], 'd M Y')) ?></span>
                        <b><?= e($primero['nombre']) ?></b>
                        <p><?= e($primero['espacio'] ?? 'Espacio por asignar') ?> · <?= (int) $primero['aforo_maximo'] ?> personas</p>
                    </div>
                </article>
                <div class="galeria__lado">
                    <?php $lado = 0; ?>
                    <?php foreach (array_slice($proximos, 1, 2) as $evento): ?>
                        <article class="galeria__foto">
                            <img src="<?= e($foto($lado === 0 ? 2774556 : 587741, 900)) ?>" alt="Detalle de montaje y ambientación" loading="lazy">
                            <div class="galeria__info">
                                <span><?= e($evento['tipo']) ?> · <?= e(fecha($evento['fecha_inicio'], 'd M')) ?></span>
                                <b><?= e(recortar($evento['nombre'], 44)) ?></b>
                                <p><?= e($evento['espacio'] ?? 'Espacio por asignar') ?></p>
                            </div>
                        </article>
                        <?php $lado++; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="seccion seccion--compacta">
    <div class="l-container">
        <div class="features reveal">
            <article class="feature">
                <?= icono('espacios') ?>
                <h3>Cinco espacios versátiles</h3>
                <p>Salón de gala, salón de fiestas, galería diáfana, terraza con vista y jardín con pérgola.</p>
            </article>
            <article class="feature">
                <?= icono('cubiertos') ?>
                <h3>Cocina propia</h3>
                <p>Menús de tres a cinco tiempos, opciones vegetarianas, veganas y sin gluten con costo por persona claro.</p>
            </article>
            <article class="feature">
                <?= icono('musica') ?>
                <h3>Audio e iluminación</h3>
                <p>Equipo profesional incluido en los paquetes de gala, con inventario controlado por evento.</p>
            </article>
            <article class="feature">
                <?= icono('invitados') ?>
                <h3>RSVP sin fricción</h3>
                <p>Cada invitado recibe un enlace único para confirmar, elegir menú y declarar restricciones alimenticias.</p>
            </article>
            <article class="feature">
                <?= icono('pagos') ?>
                <h3>Cobranza transparente</h3>
                <p>Anticipo, abonos y saldo visibles en todo momento. Recibos y devoluciones con rastro contable.</p>
            </article>
            <article class="feature">
                <?= icono('bitacora') ?>
                <h3>Expediente auditable</h3>
                <p>Cada cambio de fecha, aforo o precio queda registrado con usuario, fecha y motivo.</p>
            </article>
        </div>
    </div>
</section>

<section class="seccion seccion--oscura">
    <div class="l-container">
        <div class="cita reveal">
            <p class="cita__texto">“Pasamos de tres hojas de cálculo y un grupo de WhatsApp a un solo panel. El día del evento <em>nadie llamó preguntando cuántas mesas faltaban.</em>”</p>
            <div class="cita__autor">
                <span class="tabla__avatar">MV</span>
                <div style="text-align:left">
                    <b>Mariana Vázquez</b>
                    <span>Dirección de eventos · Grupo Altavista</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="seccion" id="contacto">
    <div class="l-container">
        <div class="split">
            <div class="split__texto reveal">
                <p class="eyebrow eyebrow--oscuro">Hablemos de tu evento</p>
                <h2>Cuéntanos la fecha.<br>Nosotros ponemos el resto.</h2>
                <p>Déjanos tus datos y te contactamos en menos de 24 horas hábiles con disponibilidad, propuesta de espacio y un estimado de menú.</p>

                <ul class="lista-check">
                    <li><span class="marca"><?= icono('ubicacion') ?></span><span><b>Recinto:</b> Av. de los Insurgentes Sur 1842, CDMX.</span></li>
                    <li><span class="marca"><?= icono('telefono') ?></span><span><b>Teléfono:</b> +52 55 4821 9060 · lun a vie, 9:00 a 19:00 h.</span></li>
                    <li><span class="marca"><?= icono('correo') ?></span><span><b>Correo:</b> hola@conectaeventos.mx</span></li>
                </ul>

                <div class="cta-final" style="margin-top:32px;padding:30px;grid-template-columns:1fr">
                    <div>
                        <h2 style="font-size:24px">¿Es una boda?</h2>
                        <p style="margin-top:8px">Tenemos un sitio aparte, con menús de autor, espacios íntimos y RSVP para tus invitados.</p>
                        <a class="btn btn--primario mt-3" href="<?= e(url('/boda')) ?>"><?= icono('corazon') ?> Ir a bodas</a>
                    </div>
                </div>
            </div>

            <div class="reveal">
                <form class="card formulario" method="post" action="<?= e(url('/contacto')) ?>" style="padding:26px">
                    <?= csrf_field() ?>
                    <input type="hidden" name="origen" value="corporativo">

                    <div class="fila fila--entre">
                        <div>
                            <h2 style="font-size:19px">Solicitar información</h2>
                            <p class="texto-muted texto-sm">Sin compromiso. Respuesta en un día hábil.</p>
                        </div>
                        <?= badge('Respuesta < 24 h', 'acento') ?>
                    </div>

                    <div class="campo-fila">
                        <div class="campo<?= clase_error('nombre', $erroresForm) ?>">
                            <label for="c-nombre">Nombre completo <span class="campo__req">*</span></label>
                            <input class="input" id="c-nombre" name="nombre" type="text" required minlength="3" maxlength="120" value="<?= viejo('nombre') ?>" placeholder="Tu nombre">
                            <?= error_de('nombre', $erroresForm) ?>
                        </div>
                        <div class="campo<?= clase_error('correo', $erroresForm) ?>">
                            <label for="c-correo">Correo <span class="campo__req">*</span></label>
                            <input class="input" id="c-correo" name="correo" type="email" required maxlength="120" value="<?= viejo('correo') ?>" placeholder="tu@empresa.mx">
                            <?= error_de('correo', $erroresForm) ?>
                        </div>
                    </div>

                    <div class="campo-fila">
                        <div class="campo">
                            <label for="c-telefono">Teléfono</label>
                            <input class="input" id="c-telefono" name="telefono" type="tel" maxlength="25" value="<?= viejo('telefono') ?>" placeholder="+52 55 0000 0000">
                        </div>
                        <div class="campo">
                            <label for="c-tipo">Tipo de evento</label>
                            <select class="select" id="c-tipo" name="tipo_evento">
                                <option value="">Selecciona una opción</option>
                                <?php foreach ($tipos as $tipo): ?>
                                    <option value="<?= e($tipo) ?>" <?= viejo('tipo_evento') === $tipo ? 'selected' : '' ?>><?= e($tipo) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="campo-fila">
                        <div class="campo">
                            <label for="c-fecha">Fecha estimada</label>
                            <input class="input" id="c-fecha" name="fecha_estimada" type="date" data-futuro value="<?= viejo('fecha_estimada') ?>">
                        </div>
                        <div class="campo">
                            <label for="c-aforo">Invitados estimados</label>
                            <input class="input" id="c-aforo" name="aforo" type="number" min="1" max="2000" value="<?= viejo('aforo') ?>" placeholder="120">
                        </div>
                    </div>

                    <div class="campo<?= clase_error('mensaje', $erroresForm) ?>">
                        <label for="c-mensaje">Cuéntanos más</label>
                        <textarea class="textarea" id="c-mensaje" name="mensaje" maxlength="1000" placeholder="Objetivo del evento, horario, requerimientos especiales…"><?= viejo('mensaje') ?></textarea>
                        <span class="campo__ayuda">Máximo 1000 caracteres.</span>
                        <?= error_de('mensaje', $erroresForm) ?>
                    </div>

                    <button class="btn btn--lima btn--bloque" type="submit"><?= icono('correo') ?> Enviar solicitud</button>
                    <p class="texto-muted texto-xs">Al enviar aceptas que te contactemos por correo o teléfono. No compartimos tus datos con terceros.</p>
                </form>
            </div>
        </div>
    </div>
</section>

<section class="seccion seccion--compacta">
    <div class="l-container">
        <div class="cta-final reveal">
            <div>
                <p class="eyebrow eyebrow--oscuro">Agenda 2026–<?= $anioActual + 1 ?></p>
                <h2>Las mejores fechas se apartan con tiempo.</h2>
                <p>Consulta disponibilidad real y recibe una propuesta con menú, montaje y anticipo desglosado.</p>
            </div>
            <div class="cta-final__acciones">
                <a class="btn btn--primario" href="#contacto"><?= icono('calendario') ?> Agendar visita</a>
                <a class="btn btn--claro" href="<?= e(url('/boda')) ?>"><?= icono('corazon') ?> Ver bodas</a>
            </div>
        </div>
    </div>
</section>

<footer class="pie">
    <div class="l-container">
        <div class="pie__grid">
            <div class="pie__marca">
                <a class="marca-landing" href="<?= e(url('/')) ?>">
                    <span class="monograma" style="background:var(--l-lime);color:var(--l-ink)">CE</span>
                    <span>
                        <b>ConectaEventos</b>
                        <span>Recinto &amp; productora</span>
                    </span>
                </a>
                <p>Un recinto en el sur de la Ciudad de México para eventos corporativos, sociales y bodas de autor. Operación propia, cocina propia, expediente propio.</p>
            </div>
            <div class="pie__col">
                <h4>Recinto</h4>
                <a href="#recinto">Espacios</a>
                <a href="#disponibilidad">Disponibilidad</a>
                <a href="#galeria">Eventos</a>
                <a href="#contacto">Visitas</a>
            </div>
            <div class="pie__col">
                <h4>Servicios</h4>
                <a href="#modulos">Corporativo</a>
                <a href="#modulos">Social</a>
                <a href="<?= e(url('/boda')) ?>">Bodas</a>
                <a href="<?= e(url('/rsvp')) ?>">Confirmar asistencia</a>
            </div>
            <div class="pie__col">
                <h4>Contacto</h4>
                <a href="tel:+525548219060">+52 55 4821 9060</a>
                <a href="mailto:hola@conectaeventos.mx">hola@conectaeventos.mx</a>
                <a href="<?= e(url('/login')) ?>">Acceso al panel</a>
                <a href="<?= e(url('/salud')) ?>">Estado del sistema</a>
            </div>
        </div>
        <div class="pie__base">
            <span>© <?= $anioActual ?> ConectaEventos · Todos los derechos reservados</span>
            <span>Av. de los Insurgentes Sur 1842, CDMX · Aviso de privacidad</span>
        </div>
    </div>
</footer>
