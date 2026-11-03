<?php
/** @var array $evento */
/** @var array $espacios */
/** @var array $recursos */
/** @var array $menus */
/** @var array $invitadosPorEstado */
/** @var int $totalInvitados */
/** @var int $checkins */
/** @var array|null $cotizacion */
/** @var array $pagos */
/** @var array $programa */
/** @var array $servicios */
/** @var array $insumos */
/** @var int $mesas */
/** @var int $restricciones */
/** @var array $transiciones */
/** @var array $menusDisponibles */
/** @var array $catalogoServicios */
/** @var array $insumosDisponibles */
$id = (int) $evento['id_evento'];
$etiquetasEstado = [
    'prospecto' => 'Prospecto', 'cotizado' => 'Cotizado', 'confirmado' => 'Confirmado',
    'en_preparacion' => 'En preparación', 'en_curso' => 'En curso', 'realizado' => 'Realizado',
    'cerrado' => 'Cerrado', 'cancelado' => 'Cancelado',
];
$confirmados = (int) ($invitadosPorEstado['confirmado'] ?? 0);
$avance = (int) $evento['aforo_maximo'] > 0 ? min(100, round(($confirmados / (int) $evento['aforo_maximo']) * 100)) : 0;
$saldo = $cotizacion ? (float) ($cotizacion['saldo'] ?? ((float) $cotizacion['total'] - (float) ($cotizacion['pagado'] ?? 0))) : 0.0;
$pagado = $cotizacion ? (float) ($cotizacion['pagado'] ?? 0) : 0.0;
$idsServicios = array_map(static fn (array $s): int => (int) $s['id_servicio'], $servicios);
?>
<div class="detalle-hero">
    <div class="detalle-hero__top">
        <?= badge_estado($evento['estado']) ?>
        <span class="badge badge--oscuro"><?= e($evento['tipo_evento']) ?></span>
        <span class="badge badge--oscuro">#<?= $id ?></span>
        <?php if ((int) $evento['requiere_menu'] === 1): ?><span class="badge badge--oscuro">Requiere menú</span><?php endif; ?>
        <?php if ((int) $evento['requiere_rsvp'] === 1): ?><span class="badge badge--oscuro">RSVP activo</span><?php endif; ?>
    </div>
    <h1><?= e($evento['nombre']) ?></h1>
    <div class="detalle-hero__meta">
        <div><?= icono('calendario') ?> <?= e(fecha_larga($evento['fecha_inicio'])) ?> · <?= e(date('H:i', strtotime($evento['fecha_inicio']))) ?> h</div>
        <div><?= icono('reloj') ?> Cierre <?= e(fecha($evento['fecha_fin'], 'd M · H:i')) ?> h</div>
        <div><?= icono('clientes') ?> <a href="<?= e(url('/clientes/' . (int) $evento['id_cliente'])) ?>"><?= e($evento['cliente']) ?></a></div>
        <div><?= icono('invitados') ?> <?= $confirmados ?> de <?= (int) $evento['aforo_maximo'] ?> confirmados</div>
        <div><?= icono('ubicacion') ?> <?= e($evento['espacio'] ?? 'Sin espacio') ?></div>
    </div>
    <div class="detalle-hero__acciones">
        <?php if (puede(['administrador', 'comercial', 'coordinador'])): ?>
            <a class="btn btn--claro btn--sm" href="<?= e(url('/eventos/' . $id . '/editar')) ?>"><?= icono('editar') ?> Editar</a>
        <?php endif; ?>
        <?php if (puede(['administrador', 'comercial'])): ?>
            <a class="btn btn--lima btn--sm" href="<?= e(url('/eventos/' . $id . '/cotizacion/crear')) ?>"><?= icono('cotizacion') ?> Cotizar</a>
        <?php endif; ?>
        <a class="btn btn--claro btn--sm" href="<?= e(url('/eventos/' . $id . '/historial')) ?>"><?= icono('bitacora') ?> Historial</a>
    </div>
</div>

<div class="grid grid-4 mb-3">
    <div class="mini-kpi"><?= icono('cotizacion') ?><div><b><?= e($cotizacion ? money_corto($cotizacion['total']) : '—') ?></b><span>Total cotizado</span></div></div>
    <div class="mini-kpi"><?= icono('pagos') ?><div><b><?= e(money_corto($pagado)) ?></b><span>Pagado</span></div></div>
    <div class="mini-kpi"><?= icono('alerta') ?><div><b class="<?= $saldo > 0.01 ? 'texto-error' : 'texto-ok' ?>"><?= e(money_corto($saldo)) ?></b><span>Saldo pendiente</span></div></div>
    <div class="mini-kpi"><?= icono('check') ?><div><b><?= $checkins ?></b><span>Check-in registrados</span></div></div>
</div>

<div class="grid-detalle">
    <div class="stack">

        <section class="card">
            <div class="card__head">
                <div><h2>Resumen operativo</h2><p>Una lectura rápida antes de entrar al detalle.</p></div>
                <div class="card__head__acciones"><span class="badge badge--acento"><?= $avance ?>% del aforo</span></div>
            </div>
            <div class="card__body">
                <div class="ficha">
                    <div class="ficha__item"><span>Aforo estimado</span><b><?= (int) $evento['aforo_estimado'] ?> personas</b></div>
                    <div class="ficha__item"><span>Aforo máximo</span><b><?= (int) $evento['aforo_maximo'] ?> personas</b></div>
                    <div class="ficha__item"><span>Invitados registrados</span><b><?= (int) $totalInvitados ?></b></div>
                    <div class="ficha__item"><span>Mesas</span><b><?= (int) $mesas ?></b></div>
                    <div class="ficha__item"><span>Restricciones alimenticias</span><b><?= (int) $restricciones ?></b></div>
                    <div class="ficha__item"><span>Recursos apartados</span><b><?= count($recursos) ?></b></div>
                </div>

                <div class="progreso progreso--lima mt-3"><span data-barra="<?= $avance ?>" style="width:0"></span></div>

                <div class="distribucion-lista mt-3">
                    <?php
                    $tonos = ['confirmado' => 'ok', 'pendiente' => 'neutro', 'declinado' => 'error', 'lista_espera' => 'aviso'];
                    foreach ($invitadosPorEstado as $estadoRsvp => $cantidad):
                    ?>
                        <div class="distribucion-lista__fila">
                            <span><?= e(ucfirst(str_replace('_', ' ', $estadoRsvp))) ?></span>
                            <div class="progreso"><span data-barra="<?= (int) $totalInvitados > 0 ? round(($cantidad / $totalInvitados) * 100) : 0 ?>" style="width:0"></span></div>
                            <b><?= (int) $cantidad ?></b>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="fila mt-3">
                    <a class="btn btn--claro btn--sm" href="<?= e(url('/eventos/' . $id . '/invitados')) ?>"><?= icono('invitados') ?> Lista de invitados</a>
                    <?php if (puede(['administrador', 'coordinador', 'operaciones'])): ?>
                        <a class="btn btn--claro btn--sm" href="<?= e(url('/eventos/' . $id . '/mesas')) ?>"><?= icono('espacios') ?> Mesas</a>
                    <?php endif; ?>
                    <a class="btn btn--claro btn--sm" href="<?= e(url('/checkin?evento=' . $id)) ?>"><?= icono('check') ?> Check-in</a>
                    <?php if (puede(['administrador', 'coordinador', 'banquetes'])): ?>
                        <a class="btn btn--claro btn--sm" href="<?= e(url('/eventos/' . $id . '/produccion')) ?>"><?= icono('cubiertos') ?> Hoja de producción</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="card">
            <div class="card__head">
                <div><h2>Cotización y cobranza</h2><p>La versión más reciente es la que manda.</p></div>
                <?php if ($cotizacion): ?>
                    <div class="card__head__acciones">
                        <a class="btn btn--claro btn--sm" href="<?= e(url('/cotizaciones/' . (int) $cotizacion['id_cotizacion'])) ?>"><?= icono('ver') ?> Ver documento</a>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (!$cotizacion): ?>
                <div class="card__body">
                    <div class="aviso-inline"><?= icono('alerta') ?> <span>Este evento aún no tiene cotización. Sin cotización aceptada no se puede confirmar.</span></div>
                    <?php if (puede(['administrador', 'comercial'])): ?>
                        <a class="btn btn--lima btn--sm mt-2" href="<?= e(url('/eventos/' . $id . '/cotizacion/crear')) ?>"><?= icono('cotizacion') ?> Crear cotización</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="card__body">
                    <div class="ficha">
                        <div class="ficha__item"><span>Folio</span><b class="mono"><?= e($cotizacion['folio']) ?></b></div>
                        <div class="ficha__item"><span>Versión</span><b>v<?= (int) $cotizacion['version'] ?></b></div>
                        <div class="ficha__item"><span>Total</span><b><?= e(money($cotizacion['total'])) ?></b></div>
                        <div class="ficha__item"><span>Anticipo requerido</span><b><?= e(money($cotizacion['anticipo_requerido'])) ?></b></div>
                        <div class="ficha__item"><span>Vigencia</span><b><?= e(fecha($cotizacion['vigencia_hasta'])) ?></b></div>
                        <div class="ficha__item"><span>Estado</span><b><?= badge_estado($cotizacion['estado']) ?></b></div>
                    </div>

                    <div class="progreso progreso--ok mt-3"><span data-barra="<?= (float) $cotizacion['total'] > 0 ? min(100, round(($pagado / (float) $cotizacion['total']) * 100)) : 0 ?>" style="width:0"></span></div>
                    <div class="fila fila--entre mt-1">
                        <span class="texto-muted texto-xs">Pagado <?= e(money($pagado)) ?></span>
                        <span class="texto-muted texto-xs">Saldo <?= e(money($saldo)) ?></span>
                    </div>

                    <?php if ($pagos !== []): ?>
                        <div class="tabla-wrap mt-3">
                            <table class="tabla tabla--compacta">
                                <thead><tr><th>Fecha</th><th>Tipo</th><th>Método</th><th>Referencia</th><th class="num">Monto</th><th>Estado</th></tr></thead>
                                <tbody>
                                <?php foreach ($pagos as $pago): ?>
                                    <tr>
                                        <td class="texto-sm"><?= e(fecha_hora($pago['fecha_pago'])) ?></td>
                                        <td><?= badge_estado($pago['tipo']) ?></td>
                                        <td class="texto-sm"><?= e(metodo_pago_etiqueta($pago['metodo'])) ?></td>
                                        <td class="mono"><?= e($pago['referencia'] ?? '—') ?></td>
                                        <td class="num"><b><?= e(money($pago['monto'])) ?></b></td>
                                        <td><?= badge_estado($pago['estado']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <?php if (puede(['administrador', 'finanzas']) && $saldo > 0.01 && $cotizacion['estado'] === 'aceptada'): ?>
                        <form class="mt-3" method="post" action="<?= e(url('/pagos')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id_cotizacion" value="<?= (int) $cotizacion['id_cotizacion'] ?>">
                            <div class="campo-fila campo-fila--3">
                                <div class="campo">
                                    <label for="monto">Monto</label>
                                    <input class="input" type="number" id="monto" name="monto" step="0.01" min="0.01" max="<?= e((string) $saldo) ?>" value="<?= e(number_format($saldo, 2, '.', '')) ?>" required>
                                </div>
                                <div class="campo">
                                    <label for="tipo_pago">Tipo</label>
                                    <select class="select" id="tipo_pago" name="tipo">
                                        <option value="abono">Abono</option>
                                        <option value="saldo">Liquidar saldo</option>
                                        <option value="anticipo">Anticipo</option>
                                        <option value="devolucion">Devolución</option>
                                    </select>
                                </div>
                                <div class="campo">
                                    <label for="metodo">Método</label>
                                    <select class="select" id="metodo" name="metodo">
                                        <option value="transferencia">Transferencia</option>
                                        <option value="efectivo">Efectivo</option>
                                        <option value="tarjeta">Tarjeta</option>
                                        <option value="cheque">Cheque</option>
                                    </select>
                                </div>
                            </div>
                            <div class="campo-fila mt-2">
                                <div class="campo">
                                    <label for="referencia">Referencia</label>
                                    <input class="input" type="text" id="referencia" name="referencia" maxlength="80" placeholder="Folio SPEI, autorización…">
                                </div>
                                <div class="campo">
                                    <label for="notas_pago">Notas</label>
                                    <input class="input" type="text" id="notas_pago" name="notas" maxlength="300">
                                </div>
                            </div>
                            <div class="fila fila--fin mt-2">
                                <button class="btn btn--lima btn--sm" type="submit"><?= icono('pagos') ?> Registrar pago</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>

        <div class="grid grid-2">
            <section class="card">
                <div class="card__head">
                    <div><h2>Espacios reservados</h2><p>Bloqueo por día, sin empalmes.</p></div>
                </div>
                <?php if ($espacios === []): ?>
                    <div class="card__body"><div class="aviso-inline aviso-inline--error"><?= icono('alerta') ?> <span>Sin espacio confirmado. El calendario y el aforo no están garantizados.</span></div>
                        <?php if (puede(['administrador', 'coordinador', 'operaciones'])): ?>
                            <a class="btn btn--lima btn--sm mt-2" href="<?= e(url('/reservas/crear?evento=' . $id)) ?>"><?= icono('mas') ?> Reservar espacio</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="card__body">
                        <?php foreach ($espacios as $espacio): ?>
                            <div class="platillo-linea">
                                <b><?= e($espacio['espacio']) ?></b>
                                <span><?= e(str_replace('_', ' ', $espacio['tipo_espacio'])) ?> · <?= (int) $espacio['capacidad'] ?> personas</span>
                                <span class="puntos"></span>
                                <span class="precio"><?= e(money($espacio['costo_referencia'])) ?></span>
                            </div>
                            <p class="texto-muted texto-xs mt-1"><?= e(fecha_hora($espacio['fecha_inicio'])) ?> → <?= e(fecha_hora($espacio['fecha_fin'])) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="card">
                <div class="card__head">
                    <div><h2>Recursos apartados</h2><p>Mobiliario, audio y personal.</p></div>
                </div>
                <?php if ($recursos === []): ?>
                    <div class="card__body"><p class="texto-muted texto-sm">Sin recursos confirmados para este evento.</p></div>
                <?php else: ?>
                    <div class="card__body">
                        <?php foreach ($recursos as $recurso): ?>
                            <div class="platillo-linea">
                                <b><?= e($recurso['nombre']) ?></b>
                                <span><?= (int) $recurso['cantidad'] ?> <?= e($recurso['unidad']) ?><?= $recurso['proveedor'] ? ' · ' . e($recurso['proveedor']) : '' ?></span>
                                <span class="puntos"></span>
                                <span class="precio"><?= e(money((float) $recurso['costo_referencia'] * (int) $recurso['cantidad'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <section class="card">
            <div class="card__head">
                <div><h2>Menús contratados</h2><p>Cocina trabaja con esta información.</p></div>
                <div class="card__head__acciones">
                    <?php if (puede(['administrador', 'coordinador', 'banquetes'])): ?>
                        <a class="btn btn--claro btn--sm" href="<?= e(url('/eventos/' . $id . '/produccion')) ?>"><?= icono('imprimir') ?> Hoja de producción</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card__body">
                <?php if ($menus === []): ?>
                    <div class="aviso-inline"><?= icono('alerta') ?> <span>Sin menú contratado<?= (int) $evento['requiere_menu'] === 1 ? '. Este tipo de evento lo requiere.' : '.' ?></span></div>
                <?php else: ?>
                    <div class="tabla-wrap">
                        <table class="tabla tabla--compacta">
                            <thead><tr><th>Menú</th><th>Servicio</th><th class="num">Personas</th><th class="num">Precio persona</th><th class="num">Importe</th><th class="num">Platillos</th></tr></thead>
                            <tbody>
                            <?php foreach ($menus as $menu): ?>
                                <tr>
                                    <td><b><?= e($menu['nombre']) ?></b></td>
                                    <td class="texto-sm"><?= e(ucfirst(str_replace('_', ' ', $menu['tipo_servicio']))) ?></td>
                                    <td class="num"><?= (int) $menu['personas'] ?></td>
                                    <td class="num"><?= e(money($menu['precio_persona'])) ?></td>
                                    <td class="num"><b><?= e(money((float) $menu['precio_persona'] * (int) $menu['personas'])) ?></b></td>
                                    <td class="num"><?= (int) $menu['platillos'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <?php if (puede(['administrador', 'coordinador', 'banquetes']) && $menusDisponibles !== []): ?>
                    <form class="mt-3" method="post" action="<?= e(url('/eventos/' . $id . '/menus')) ?>">
                        <?= csrf_field() ?>
                        <div class="campo-fila campo-fila--3">
                            <div class="campo">
                                <label for="id_menu">Contratar menú</label>
                                <select class="select" id="id_menu" name="id_menu" required>
                                    <option value="">Selecciona…</option>
                                    <?php foreach ($menusDisponibles as $menu): ?>
                                        <option value="<?= (int) $menu['id_menu'] ?>"><?= e($menu['nombre']) ?> · <?= e(money($menu['precio_persona'])) ?>/persona</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="campo">
                                <label for="personas">Personas</label>
                                <input class="input" type="number" id="personas" name="personas" min="1" max="<?= (int) $evento['aforo_maximo'] ?>" value="<?= max(1, (int) $evento['aforo_estimado']) ?>" required>
                            </div>
                            <div class="campo">
                                <label for="notas_menu">Notas</label>
                                <input class="input" type="text" id="notas_menu" name="notas" maxlength="200" placeholder="Ajustes de cocina">
                            </div>
                        </div>
                        <div class="fila fila--fin mt-2">
                            <button class="btn btn--claro btn--sm" type="submit"><?= icono('mas') ?> Agregar menú</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </section>

        <div class="grid grid-2">
            <section class="card">
                <div class="card__head"><div><h2>Programa del evento</h2><p>Momentos y responsables.</p></div></div>
                <?php if ($programa === []): ?>
                    <div class="card__body"><p class="texto-muted texto-sm">Aún no hay actividades programadas.</p></div>
                <?php else: ?>
                    <div class="card__body">
                        <div class="timeline">
                            <?php foreach ($programa as $actividad): ?>
                                <div class="timeline__item">
                                    <span class="timeline__punto"><?= icono('reloj') ?></span>
                                    <div class="timeline__cuerpo">
                                        <b><?= e(date('H:i', strtotime($actividad['inicio']))) ?> · <?= e($actividad['actividad']) ?></b>
                                        <span><?= $actividad['fin'] ? 'Hasta ' . e(date('H:i', strtotime($actividad['fin']))) : 'Sin hora de cierre' ?><?= $actividad['responsable'] ? ' · ' . e($actividad['responsable']) : '' ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (puede(['administrador', 'coordinador', 'operaciones'])): ?>
                    <div class="card__foot">
                        <form class="formulario" method="post" action="<?= e(url('/eventos/' . $id . '/programa')) ?>" style="width:100%">
                            <?= csrf_field() ?>
                            <div class="campo-fila campo-fila--3">
                                <div class="campo"><label for="inicio_prog">Inicio</label><input class="input" type="datetime-local" id="inicio_prog" name="inicio" required></div>
                                <div class="campo"><label for="fin_prog">Fin</label><input class="input" type="datetime-local" id="fin_prog" name="fin"></div>
                                <div class="campo"><label for="responsable">Responsable</label><input class="input" type="text" id="responsable" name="responsable" maxlength="100"></div>
                            </div>
                            <div class="campo mt-2"><label for="actividad">Actividad</label><input class="input" type="text" id="actividad" name="actividad" maxlength="150" placeholder="Ceremonia, brindis, cena…" required></div>
                            <div class="fila fila--fin mt-2"><button class="btn btn--claro btn--sm" type="submit"><?= icono('mas') ?> Agregar actividad</button></div>
                        </form>
                    </div>
                <?php endif; ?>
            </section>

            <section class="card">
                <div class="card__head"><div><h2>Servicios del evento</h2><p>Selecciona lo que se cotizará.</p></div></div>
                <div class="card__body">
                    <?php if (puede(['administrador', 'comercial'])): ?>
                        <form method="post" action="<?= e(url('/eventos/' . $id . '/servicios')) ?>">
                            <?= csrf_field() ?>
                            <div class="stack" style="gap:8px">
                                <?php foreach ($catalogoServicios as $servicio): ?>
                                    <label class="campo-check">
                                        <input type="checkbox" name="servicios[]" value="<?= (int) $servicio['id_servicio'] ?>" <?= in_array((int) $servicio['id_servicio'], $idsServicios, true) ? 'checked' : '' ?>>
                                        <span><b><?= e($servicio['nombre']) ?></b><br><span class="texto-muted texto-xs"><?= e(ucfirst(str_replace('_', ' ', $servicio['categoria']))) ?> · <?= e(money($servicio['precio_base'])) ?></span></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="fila fila--fin mt-3"><button class="btn btn--claro btn--sm" type="submit"><?= icono('check') ?> Guardar servicios</button></div>
                        </form>
                    <?php else: ?>
                        <?php if ($servicios === []): ?>
                            <p class="texto-muted texto-sm">Sin servicios vinculados.</p>
                        <?php else: ?>
                            <div class="lista-datos">
                                <?php foreach ($servicios as $servicio): ?>
                                    <div class="lista-datos__fila"><span><?= e($servicio['nombre']) ?></span><b><?= e(money($servicio['precio_unitario'])) ?></b></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <section class="card">
            <div class="card__head"><div><h2>Insumos comprometidos</h2><p>El stock se descuenta del disponible, no del físico.</p></div></div>
            <div class="card__body">
                <?php if ($insumos === []): ?>
                    <p class="texto-muted texto-sm">Sin insumos apartados para este evento.</p>
                <?php else: ?>
                    <div class="tabla-wrap">
                        <table class="tabla tabla--compacta">
                            <thead><tr><th>Insumo</th><th class="num">Cantidad</th><th class="num">Físico</th><th class="num">Comprometido</th><th>Estado</th></tr></thead>
                            <tbody>
                            <?php foreach ($insumos as $insumo): ?>
                                <tr>
                                    <td><b><?= e($insumo['nombre']) ?></b></td>
                                    <td class="num"><?= e(number_format((float) $insumo['cantidad'], 2)) ?> <?= e($insumo['unidad']) ?></td>
                                    <td class="num"><?= e(number_format((float) $insumo['stock_fisico'], 2)) ?></td>
                                    <td class="num"><?= e(number_format((float) $insumo['stock_comprometido'], 2)) ?></td>
                                    <td><?= badge_estado($insumo['estado']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <?php if (puede(['administrador', 'banquetes'])): ?>
                    <form class="mt-3" method="post" action="<?= e(url('/eventos/' . $id . '/insumos')) ?>">
                        <?= csrf_field() ?>
                        <div class="campo-fila campo-fila--3">
                            <div class="campo">
                                <label for="id_insumo">Insumo</label>
                                <select class="select" id="id_insumo" name="id_insumo" required>
                                    <option value="">Selecciona…</option>
                                    <?php foreach ($insumosDisponibles as $insumo): ?>
                                        <option value="<?= (int) $insumo['id_insumo'] ?>" <?= (float) $insumo['disponible'] <= 0 ? 'disabled' : '' ?>>
                                            <?= e($insumo['nombre']) ?> · <?= e(number_format((float) $insumo['disponible'], 2)) ?> <?= e($insumo['unidad']) ?> libres
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="campo"><label for="cantidad_insumo">Cantidad</label><input class="input" type="number" id="cantidad_insumo" name="cantidad" step="0.01" min="0.01" required></div>
                            <div class="campo"><label for="motivo_insumo">Motivo</label><input class="input" type="text" id="motivo_insumo" name="motivo" maxlength="220" placeholder="Consumo estimado" required></div>
                        </div>
                        <div class="fila fila--fin mt-2"><button class="btn btn--claro btn--sm" type="submit"><?= icono('paquete') ?> Apartar insumo</button></div>
                    </form>
                <?php endif; ?>
            </div>
        </section>

    </div>

    <aside class="grid-detalle__lado">

        <section class="card">
            <div class="card__head"><div><h2>Avanzar estado</h2><p>Solo transiciones permitidas.</p></div></div>
            <div class="card__body">
                <div class="lista-datos mb-2">
                    <div class="lista-datos__fila"><span>Estado actual</span><b><?= badge_estado($evento['estado']) ?></b></div>
                    <div class="lista-datos__fila"><span>Siguiente posible</span><b><?= $transiciones === [] ? '—' : e(implode(', ', array_map(static fn (string $t): string => $etiquetasEstado[$t] ?? $t, $transiciones))) ?></b></div>
                </div>
                <?php $opciones = array_values(array_filter($transiciones, static fn (string $t): bool => $t !== 'cancelado')); ?>
                <?php if (puede(['administrador', 'coordinador', 'operaciones']) && $opciones !== []): ?>
                    <form class="formulario" method="post" action="<?= e(url('/eventos/' . $id . '/estado')) ?>">
                        <?= csrf_field() ?>
                        <div class="campo">
                            <label for="nuevo_estado">Nuevo estado</label>
                            <select class="select" id="nuevo_estado" name="estado" required>
                                <?php foreach ($opciones as $opcion): ?>
                                    <option value="<?= e($opcion) ?>"><?= e($etiquetasEstado[$opcion] ?? $opcion) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="campo">
                            <label for="motivo_estado">Motivo</label>
                            <input class="input" type="text" id="motivo_estado" name="motivo" maxlength="220" placeholder="Opcional, queda en historial">
                        </div>
                        <button class="btn btn--lima btn--sm btn--bloque" type="submit"><?= icono('flecha') ?> Cambiar estado</button>
                    </form>
                <?php else: ?>
                    <div class="aviso-inline aviso-inline--info"><?= icono('candado') ?> <span><?= $transiciones === [] ? 'Este evento está en un estado final.' : 'Tu rol no puede cambiar el estado.' ?></span></div>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card__head"><div><h2>Reprogramar</h2><p>Mueve el evento y sus reservas en una transacción.</p></div></div>
            <div class="card__body">
                <?php if (puede(['administrador', 'comercial'])): ?>
                    <form class="formulario" method="post" action="<?= e(url('/eventos/' . $id . '/fecha')) ?>" data-confirmar="¿Reprogramar el evento y mover todas sus reservas?">
                        <?= csrf_field() ?>
                        <div class="campo"><label for="nueva_inicio">Nuevo inicio</label><input class="input" type="datetime-local" id="nueva_inicio" name="fecha_inicio" value="<?= e(fecha_input($evento['fecha_inicio'])) ?>" required></div>
                        <div class="campo"><label for="nueva_fin">Nuevo fin</label><input class="input" type="datetime-local" id="nueva_fin" name="fecha_fin" value="<?= e(fecha_input($evento['fecha_fin'])) ?>" required></div>
                        <div class="campo"><label for="motivo_fecha">Motivo <span class="campo__req">*</span></label><input class="input" type="text" id="motivo_fecha" name="motivo" maxlength="220" placeholder="Solicitud del cliente, ajuste de montaje…" required></div>
                        <button class="btn btn--claro btn--sm btn--bloque" type="submit"><?= icono('calendario') ?> Reprogramar</button>
                    </form>
                <?php else: ?>
                    <p class="texto-muted texto-sm">Solo administración y comercial pueden reprogramar.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card__head"><div><h2>Datos del cliente</h2><p>Contacto para el expediente.</p></div></div>
            <div class="card__body">
                <div class="lista-datos">
                    <div class="lista-datos__fila"><span>Cliente</span><b><?= e($evento['cliente']) ?></b></div>
                    <div class="lista-datos__fila"><span>Correo</span><b><?= e($evento['cliente_correo']) ?></b></div>
                    <div class="lista-datos__fila"><span>Teléfono</span><b><?= e($evento['cliente_telefono'] ?? '—') ?></b></div>
                    <div class="lista-datos__fila"><span>Tipo de cliente</span><b><?= e(ucfirst($evento['tipo_cliente'])) ?></b></div>
                </div>
            </div>
        </section>

        <?php if (!empty($evento['notas'])): ?>
            <section class="card">
                <div class="card__head"><div><h2>Notas internas</h2><p>Solo equipo.</p></div></div>
                <div class="card__body"><p class="texto-sm"><?= nl2br(e($evento['notas'])) ?></p></div>
            </section>
        <?php endif; ?>

        <?php if (puede(['administrador', 'coordinador']) && $evento['estado'] !== 'cancelado' && $evento['estado'] !== 'cerrado'): ?>
            <section class="card">
                <div class="card__head"><div><h2>Cancelar evento</h2><p>Libera espacios, recursos e insumos.</p></div></div>
                <div class="card__body">
                    <div class="aviso-inline aviso-inline--error"><?= icono('alerta') ?> <span>La cancelación es en cascada y calcula penalización según la política vigente. No se borra nada: todo queda en historial.</span></div>
                    <form class="formulario mt-2" method="post" action="<?= e(url('/eventos/' . $id . '/cancelar')) ?>" data-confirmar="¿Cancelar este evento? Se liberarán todas sus reservas.">
                        <?= csrf_field() ?>
                        <div class="campo"><label for="motivo_cancelar">Motivo <span class="campo__req">*</span></label><textarea class="textarea" id="motivo_cancelar" name="motivo" maxlength="220" required></textarea></div>
                        <button class="btn btn--peligro btn--sm btn--bloque" type="submit"><?= icono('x') ?> Cancelar evento</button>
                    </form>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($evento['estado'] === 'cancelado' && !empty($evento['motivo_cancelacion'])): ?>
            <section class="card">
                <div class="card__head"><div><h2>Motivo de cancelación</h2><p>Registro inmutable.</p></div></div>
                <div class="card__body"><p class="texto-sm"><?= e($evento['motivo_cancelacion']) ?></p></div>
            </section>
        <?php endif; ?>
    </aside>
</div>
