<?php
/** @var array $solicitudes */
/** @var array $conteos */
/** @var int $total */
/** @var int $porPagina */
/** @var string $q */
/** @var string $estado */
/** @var string $origen */
/** @var array $tipos */
/** @var array $espacios */

$etiquetasEstado = [
    'nuevo'      => ['Sin atender', 'aviso'],
    'contactado' => ['Contactada', 'info'],
    'convertido' => ['Convertida', 'ok'],
    'descartado' => ['Descartada', 'neutro'],
];
$etiquetasOrigen = [
    'corporativo' => 'Portada',
    'bodas'       => 'Historias',
    'contacto'    => 'Contacto',
];
$puedeConvertir = puede(['administrador', 'comercial']);
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Módulo A · Entrada comercial</p>
        <h1>Solicitudes</h1>
        <p class="page-head__sub"><?= e($subtitulo ?? '') ?></p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--claro" href="<?= e(url('/clientes')) ?>"><?= icono('clientes') ?> Clientes</a>
        <a class="btn btn--fantasma" href="<?= e(url('/eventos')) ?>"><?= icono('calendario') ?> Eventos</a>
    </div>
</div>

<div class="kpis">
    <div class="kpi kpi--oscura">
        <div class="kpi__top"><?= icono('correo') ?> Sin atender</div>
        <p class="kpi__valor"><?= (int) ($conteos['nuevo'] ?? 0) ?></p>
        <p class="kpi__nota">esperan una respuesta</p>
    </div>
    <div class="kpi">
        <div class="kpi__top"><?= icono('telefono') ?> Contactadas</div>
        <p class="kpi__valor"><?= (int) ($conteos['contactado'] ?? 0) ?></p>
        <p class="kpi__nota">en conversación</p>
    </div>
    <div class="kpi kpi--lima">
        <div class="kpi__top"><?= icono('check') ?> Convertidas</div>
        <p class="kpi__valor"><?= (int) ($conteos['convertido'] ?? 0) ?></p>
        <p class="kpi__nota">ya son un evento</p>
    </div>
    <div class="kpi">
        <div class="kpi__top"><?= icono('grafico') ?> Conversión</div>
        <?php
        $cerradas = (int) ($conteos['convertido'] ?? 0) + (int) ($conteos['descartado'] ?? 0);
        $tasa = $cerradas > 0 ? ((int) ($conteos['convertido'] ?? 0) / $cerradas) * 100 : 0.0;
        ?>
        <p class="kpi__valor"><?= number_format($tasa, 0) ?>%</p>
        <p class="kpi__nota">de las solicitudes ya resueltas</p>
    </div>
</div>

<section class="card">
    <div class="card__head">
        <div><h2>Bandeja</h2><p>Ordenadas por lo que falta hacer: primero lo que nadie ha tocado.</p></div>
        <span class="badge badge--neutro"><?= e(plural($total, 'solicitud', 'solicitudes')) ?></span>
    </div>

    <form class="toolbar" method="get" action="<?= e(url('/solicitudes')) ?>">
        <div class="campo campo--buscar">
            <label for="q">Buscar</label>
            <input class="input" type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Nombre, correo o tipo de evento">
        </div>
        <div class="campo">
            <label for="estado">Estado</label>
            <select class="select" id="estado" name="estado" data-autoenviar>
                <option value="">Todos</option>
                <?php foreach ($etiquetasEstado as $clave => [$texto, $tono]): ?>
                    <option value="<?= e($clave) ?>" <?= $estado === $clave ? 'selected' : '' ?>><?= e($texto) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label for="origen">Llegó desde</label>
            <select class="select" id="origen" name="origen" data-autoenviar>
                <option value="">Todas las páginas</option>
                <?php foreach ($etiquetasOrigen as $clave => $texto): ?>
                    <option value="<?= e($clave) ?>" <?= $origen === $clave ? 'selected' : '' ?>><?= e($texto) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="toolbar__acciones">
            <button class="btn btn--primario" type="submit"><?= icono('buscar') ?> Filtrar</button>
            <?php if ($q !== '' || $estado !== '' || $origen !== ''): ?>
                <a class="btn btn--fantasma" href="<?= e(url('/solicitudes')) ?>">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="card__body">
        <?php if ($solicitudes === []): ?>
            <div class="empty">
                <div class="empty__icono"><?= icono('correo') ?></div>
                <h3>No hay solicitudes con esos filtros</h3>
                <p>Las que llegan por el formulario del sitio aparecen aquí en cuanto se envían.</p>
            </div>
        <?php else: ?>
            <div class="solicitudes">
                <?php foreach ($solicitudes as $s): ?>
                    <?php
                    [$textoEstado, $tonoEstado] = $etiquetasEstado[$s['estado']] ?? ['Desconocido', 'neutro'];
                    $disp = $s['disponibilidad'];
                    $abierta = $s['estado'] === 'nuevo' || $s['estado'] === 'contactado';
                    ?>
                    <article class="solicitud solicitud--<?= e($s['estado']) ?>">
                        <header class="solicitud__cabeza">
                            <div class="solicitud__quien">
                                <span class="tabla__avatar"><?= e(iniciales((string) $s['nombre'])) ?></span>
                                <div>
                                    <b><?= e($s['nombre']) ?></b>
                                    <span>
                                        <a href="mailto:<?= e($s['correo']) ?>"><?= e($s['correo']) ?></a>
                                        <?php if ($s['telefono']): ?> · <?= e($s['telefono']) ?><?php endif; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="solicitud__sellos">
                                <?= badge($textoEstado, $tonoEstado) ?>
                                <span class="texto-muted texto-xs">
                                    <?= e($etiquetasOrigen[$s['origen']] ?? $s['origen']) ?> · <?= e(fecha_hora($s['creado_en'])) ?>
                                </span>
                            </div>
                        </header>

                        <dl class="solicitud__datos">
                            <div><dt>Tipo</dt><dd><?= e($s['tipo_evento'] ?: 'Sin especificar') ?></dd></div>
                            <div><dt>Fecha pedida</dt><dd><?= $s['fecha_estimada'] ? e(fecha_larga($s['fecha_estimada'])) : 'Sin fecha' ?></dd></div>
                            <div><dt>Aforo</dt><dd><?= $s['aforo'] ? e(plural((int) $s['aforo'], 'invitado', 'invitados')) : 'Sin estimar' ?></dd></div>
                            <div>
                                <dt>Cliente</dt>
                                <dd>
                                    <?php if ($s['id_cliente']): ?>
                                        <a href="<?= e(url('/clientes/' . (int) $s['id_cliente'])) ?>">Ya es cliente</a>
                                        <span class="texto-muted texto-xs">· <?= e(plural((int) $s['cliente_eventos'], 'evento', 'eventos')) ?></span>
                                    <?php else: ?>
                                        <span class="texto-muted">Nuevo</span>
                                    <?php endif; ?>
                                </dd>
                            </div>
                        </dl>

                        <?php if ($s['mensaje']): ?>
                            <p class="solicitud__mensaje"><?= e($s['mensaje']) ?></p>
                        <?php endif; ?>

                        <?php /* La disponibilidad se resuelve aquí: quien atiende no tiene que
                                abrir el calendario en otra pestaña para saber qué contestar. */ ?>
                        <?php if ($disp['comprobable']): ?>
                            <div class="solicitud__disponibilidad <?= $disp['cabe'] ? 'es-libre' : 'es-ocupado' ?>">
                                <?= icono($disp['cabe'] ? 'check' : 'alerta') ?>
                                <?php if ($disp['cabe']): ?>
                                    <span>
                                        <b><?= e(plural(count($disp['libres']), 'espacio libre', 'espacios libres')) ?></b>
                                        esa fecha con capacidad suficiente:
                                        <?= e(implode(', ', array_map(static fn (array $e): string => (string) $e['nombre'], $disp['libres']))) ?>
                                    </span>
                                <?php else: ?>
                                    <span>
                                        Ningún espacio libre esa fecha<?= $s['aforo'] ? ' para ' . (int) $s['aforo'] . ' invitados' : '' ?>.
                                        <?php if ($disp['ocupados'] > 0): ?>
                                            <?= e(plural($disp['ocupados'], 'espacio ya reservado', 'espacios ya reservados')) ?> ese día.
                                        <?php endif; ?>
                                        Conviene proponer otra fecha.
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($puedeConvertir && $abierta): ?>
                            <footer class="solicitud__acciones">
                                <form method="post" action="<?= e(url('/solicitudes/' . (int) $s['id_lead'] . '/estado')) ?>">
                                    <?= csrf_field() ?>
                                    <?php if ($s['estado'] === 'nuevo'): ?>
                                        <input type="hidden" name="estado" value="contactado">
                                        <button class="btn btn--claro" type="submit"><?= icono('telefono') ?> Marcar contactada</button>
                                    <?php else: ?>
                                        <input type="hidden" name="estado" value="nuevo">
                                        <button class="btn btn--fantasma" type="submit"><?= icono('refrescar') ?> Volver a pendiente</button>
                                    <?php endif; ?>
                                </form>

                                <form method="post" action="<?= e(url('/solicitudes/' . (int) $s['id_lead'] . '/estado')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="estado" value="descartado">
                                    <button class="btn btn--fantasma" type="submit"><?= icono('x') ?> Descartar</button>
                                </form>

                                <details class="solicitud__convertir">
                                    <summary class="btn btn--lima"><?= icono('check') ?> Convertir en evento</summary>
                                    <form class="formulario" method="post" action="<?= e(url('/solicitudes/' . (int) $s['id_lead'] . '/convertir')) ?>">
                                        <?= csrf_field() ?>
                                        <p class="campo__ayuda mb-2">
                                            Lo que escribió el visitante es una intención, no una reserva.
                                            Confirma los datos antes de que entren al calendario.
                                        </p>

                                        <div class="campo">
                                            <label for="nombre-<?= (int) $s['id_lead'] ?>">Nombre del evento <span class="campo__req">*</span></label>
                                            <input class="input" type="text" id="nombre-<?= (int) $s['id_lead'] ?>" name="nombre" maxlength="140" required
                                                   value="<?= e(($s['tipo_evento'] ?: 'Evento') . ' de ' . $s['nombre']) ?>">
                                        </div>

                                        <div class="campo-fila">
                                            <div class="campo">
                                                <label for="tipo-<?= (int) $s['id_lead'] ?>">Tipo <span class="campo__req">*</span></label>
                                                <select class="select" id="tipo-<?= (int) $s['id_lead'] ?>" name="id_tipo_evento" required>
                                                    <?php foreach ($tipos as $t): ?>
                                                        <option value="<?= (int) $t['id_tipo_evento'] ?>"
                                                            <?= (int) $t['id_tipo_evento'] === (int) $s['tipo_sugerido'] ? 'selected' : '' ?>>
                                                            <?= e($t['nombre']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="campo">
                                                <label for="aforo-<?= (int) $s['id_lead'] ?>">Aforo</label>
                                                <input class="input" type="number" id="aforo-<?= (int) $s['id_lead'] ?>" name="aforo" min="1" max="5000"
                                                       value="<?= (int) ($s['aforo'] ?: 100) ?>">
                                            </div>
                                        </div>

                                        <div class="campo-fila">
                                            <div class="campo">
                                                <label for="inicio-<?= (int) $s['id_lead'] ?>">Inicio <span class="campo__req">*</span></label>
                                                <input class="input" type="datetime-local" id="inicio-<?= (int) $s['id_lead'] ?>" name="fecha_inicio" required
                                                       value="<?= e($s['fecha_estimada'] ? $s['fecha_estimada'] . 'T17:00' : '') ?>">
                                            </div>
                                            <div class="campo">
                                                <label for="fin-<?= (int) $s['id_lead'] ?>">Fin <span class="campo__req">*</span></label>
                                                <input class="input" type="datetime-local" id="fin-<?= (int) $s['id_lead'] ?>" name="fecha_fin" required
                                                       value="<?= e($s['fecha_estimada'] ? $s['fecha_estimada'] . 'T23:00' : '') ?>">
                                            </div>
                                        </div>

                                        <div class="campo">
                                            <label for="espacio-<?= (int) $s['id_lead'] ?>">Reservar espacio</label>
                                            <select class="select" id="espacio-<?= (int) $s['id_lead'] ?>" name="id_espacio">
                                                <option value="">Todavía no · solo crear el evento</option>
                                                <?php foreach ($espacios as $esp): ?>
                                                    <?php
                                                    // Se marca el que no cabe en vez de esconderlo: quien
                                                    // atiende tiene que ver el catálogo completo para poder
                                                    // proponer un cambio de aforo.
                                                    $cabe = !$s['aforo'] || (int) $esp['capacidad'] >= (int) $s['aforo'];
                                                    ?>
                                                    <option value="<?= (int) $esp['id_espacio'] ?>" <?= $cabe ? '' : 'disabled' ?>>
                                                        <?= e($esp['nombre']) ?> · <?= (int) $esp['capacidad'] ?> personas<?= $cabe ? '' : ' (no alcanza)' ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <span class="campo__ayuda">Si la fecha ya está ocupada, el sistema rechaza la reserva y el evento se crea igual.</span>
                                        </div>

                                        <button class="btn btn--primario btn--bloque" type="submit">
                                            <?= icono('check') ?> Crear cliente y evento
                                        </button>
                                    </form>
                                </details>
                            </footer>
                        <?php elseif ($s['estado'] === 'convertido'): ?>
                            <footer class="solicitud__acciones">
                                <span class="texto-muted texto-sm"><?= icono('check') ?> Ya es un evento del recinto.</span>
                                <?php if ($s['id_cliente']): ?>
                                    <a class="btn btn--claro" href="<?= e(url('/clientes/' . (int) $s['id_cliente'])) ?>">Ver expediente</a>
                                <?php endif; ?>
                            </footer>
                        <?php elseif ($s['estado'] === 'descartado' && $puedeConvertir): ?>
                            <footer class="solicitud__acciones">
                                <form method="post" action="<?= e(url('/solicitudes/' . (int) $s['id_lead'] . '/estado')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="estado" value="nuevo">
                                    <button class="btn btn--fantasma" type="submit"><?= icono('refrescar') ?> Reabrir</button>
                                </form>
                            </footer>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>

            <?= paginacion((int) $total, (int) $porPagina) ?>
        <?php endif; ?>
    </div>
</section>
