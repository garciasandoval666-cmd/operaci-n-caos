<?php
/** @var array $cliente */
/** @var array $eventos */
/** @var array $cotizaciones */
/** @var array $leads */
/** @var array $totales */
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Expediente de cliente · #<?= (int) $cliente['id_cliente'] ?></p>
        <h1><?= e($cliente['nombre']) ?></h1>
        <p class="page-head__sub">
            <?= $cliente['tipo_cliente'] === 'empresa' ? 'Empresa' : 'Persona física' ?>
            <?= $cliente['razon_social'] ? ' · ' . e($cliente['razon_social']) : '' ?>
            · Cliente desde <?= e(fecha($cliente['creado_en'], 'M Y')) ?>
        </p>
    </div>
    <div class="page-head__acciones">
        <?= badge_estado($cliente['estado']) ?>
        <?php if (puede(['administrador', 'comercial'])): ?>
            <a class="btn btn--claro" href="<?= e(url('/clientes/' . (int) $cliente['id_cliente'] . '/editar')) ?>"><?= icono('editar') ?> Editar</a>
            <a class="btn btn--lima" href="<?= e(url('/eventos/crear')) ?>"><?= icono('mas') ?> Nuevo evento</a>
        <?php endif; ?>
    </div>
</div>

<div class="kpis">
    <article class="kpi kpi--oscura">
        <p class="kpi__top"><?= icono('calendario') ?> Eventos</p>
        <p class="kpi__valor"><?= (int) $totales['eventos'] ?></p>
        <p class="kpi__nota">Historial completo del cliente</p>
    </article>
    <article class="kpi">
        <p class="kpi__top"><?= icono('cotizacion') ?> Cotizado</p>
        <p class="kpi__valor"><?= e(money_corto($totales['cotizado'])) ?></p>
        <p class="kpi__nota">Últimas 10 cotizaciones</p>
    </article>
    <article class="kpi">
        <p class="kpi__top"><?= icono('pagos') ?> Saldo abierto</p>
        <p class="kpi__valor"><?= e(money_corto($totales['saldo'])) ?></p>
        <p class="kpi__nota">Cotizaciones aceptadas</p>
    </article>
    <article class="kpi">
        <p class="kpi__top"><?= icono('destello') ?> Leads del sitio</p>
        <p class="kpi__valor"><?= count($leads) ?></p>
        <p class="kpi__nota">Solicitudes con el mismo correo</p>
    </article>
</div>

<div class="grid-detalle">
    <div class="stack">
        <section class="card">
            <div class="card__head">
                <div><h2>Eventos del cliente</h2><p>Cada evento conserva su estado, aforo y saldo.</p></div>
                <div class="card__head__acciones">
                    <a class="btn btn--claro btn--sm" href="<?= e(url('/eventos?q=' . urlencode($cliente['nombre']))) ?>">Ver en tablero <?= icono('flecha') ?></a>
                </div>
            </div>
            <?php if ($eventos === []): ?>
                <div class="empty">
                    <div class="empty__icono"><?= icono('calendario') ?></div>
                    <h3>Sin eventos registrados</h3>
                    <p>Este cliente aún no tiene eventos. Crea el primero para iniciar el flujo comercial.</p>
                </div>
            <?php else: ?>
                <div class="tabla-wrap">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>Evento</th>
                                <th>Fecha</th>
                                <th class="num">Aforo</th>
                                <th class="num">Saldo</th>
                                <th>Estado</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($eventos as $evento): ?>
                            <tr>
                                <td>
                                    <a class="tabla__principal" href="<?= e(url('/eventos/' . (int) $evento['id_evento'])) ?>">
                                        <span class="tabla__avatar"><?= icono(tipo_evento_icono($evento['tipo'])) ?></span>
                                        <span>
                                            <b><?= e($evento['nombre']) ?></b>
                                            <span><?= e($evento['tipo']) ?></span>
                                        </span>
                                    </a>
                                </td>
                                <td>
                                    <b class="texto-sm"><?= e(fecha($evento['fecha_inicio'], 'd M Y')) ?></b>
                                    <span class="texto-muted texto-xs"><?= e(date('H:i', strtotime($evento['fecha_inicio']))) ?> h</span>
                                </td>
                                <td class="num"><?= (int) $evento['aforo_maximo'] ?></td>
                                <td class="num"><?= e(money($evento['saldo'])) ?></td>
                                <td><?= badge_estado($evento['estado']) ?></td>
                                <td><div class="acciones"><a class="btn btn--fantasma btn--xs" href="<?= e(url('/eventos/' . (int) $evento['id_evento'])) ?>">Abrir</a></div></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="card">
            <div class="card__head">
                <div><h2>Cotizaciones recientes</h2><p>Versiones, vigencias y estado de aceptación.</p></div>
            </div>
            <?php if ($cotizaciones === []): ?>
                <div class="card__body"><div class="aviso-inline aviso-inline--info"><?= icono('cotizacion') ?> <span>Aún no hay cotizaciones para este cliente.</span></div></div>
            <?php else: ?>
                <div class="tabla-wrap">
                    <table class="tabla tabla--compacta">
                        <thead>
                            <tr><th>Folio</th><th>Evento</th><th>Vigencia</th><th class="num">Total</th><th>Estado</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($cotizaciones as $cotizacion): ?>
                            <tr>
                                <td><a class="mono" href="<?= e(url('/cotizaciones/' . (int) $cotizacion['id_cotizacion'])) ?>"><?= e($cotizacion['folio']) ?></a></td>
                                <td><?= e($cotizacion['evento']) ?></td>
                                <td class="texto-sm"><?= e(fecha($cotizacion['vigencia_hasta'])) ?></td>
                                <td class="num"><?= e(money($cotizacion['total'])) ?></td>
                                <td><?= badge_estado($cotizacion['estado']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <aside class="grid-detalle__lado">
        <section class="card">
            <div class="card__head"><div><h2>Contacto</h2><p>Datos usados en documentos.</p></div></div>
            <div class="card__body">
                <div class="lista-datos">
                    <div class="lista-datos__fila"><span>Correo</span><b><?= e($cliente['correo']) ?></b></div>
                    <div class="lista-datos__fila"><span>Teléfono</span><b><?= e($cliente['telefono'] ?? '—') ?></b></div>
                    <div class="lista-datos__fila"><span>Dirección</span><b><?= e($cliente['direccion'] ?? '—') ?></b></div>
                    <div class="lista-datos__fila"><span>Razón social</span><b><?= e($cliente['razon_social'] ?? '—') ?></b></div>
                    <div class="lista-datos__fila"><span>Alta</span><b><?= e(fecha_hora($cliente['creado_en'])) ?></b></div>
                </div>
                <?php if (!empty($cliente['notas'])): ?>
                    <div class="aviso-inline aviso-inline--info mt-2"><?= icono('destello') ?> <span><?= e($cliente['notas']) ?></span></div>
                <?php endif; ?>
            </div>
            <?php if (puede(['administrador', 'comercial'])): ?>
                <div class="card__foot">
                    <form method="post" action="<?= e(url('/clientes/' . (int) $cliente['id_cliente'] . '/estado')) ?>" data-confirmar="¿Cambiar el estado de este cliente?">
                        <?= csrf_field() ?>
                        <button class="btn <?= $cliente['estado'] === 'activo' ? 'btn--peligro' : 'btn--claro' ?> btn--sm" type="submit">
                            <?= icono($cliente['estado'] === 'activo' ? 'x' : 'check') ?>
                            <?= $cliente['estado'] === 'activo' ? 'Inactivar cliente' : 'Reactivar cliente' ?>
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </section>

        <section class="card">
            <div class="card__head"><div><h2>Leads del sitio</h2><p>Solicitudes con este correo.</p></div></div>
            <div class="card__body">
                <?php if ($leads === []): ?>
                    <p class="texto-muted texto-sm">Sin solicitudes web registradas.</p>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($leads as $lead): ?>
                            <div class="timeline__item">
                                <span class="timeline__punto"><?= icono('correo') ?></span>
                                <div class="timeline__cuerpo">
                                    <b><?= e($lead['tipo_evento'] ?: 'Solicitud general') ?></b>
                                    <span><?= e(ucfirst($lead['origen'])) ?> · <?= e(fecha_hora($lead['creado_en'])) ?></span>
                                    <p><?= e(recortar($lead['mensaje'] ?: 'Sin mensaje', 110)) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </aside>
</div>
