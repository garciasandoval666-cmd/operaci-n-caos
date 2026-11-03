<?php
/** @var array $cotizaciones */
/** @var array $stats */
/** @var int $total */
/** @var string $q */
/** @var string $estado */
/** @var int $porPagina */

$estados = [
    'borrador'  => 'Borrador',
    'enviada'   => 'Enviada',
    'aceptada'  => 'Aceptada',
    'rechazada' => 'Rechazada',
    'cancelada' => 'Cancelada',
];
$aceptadas = (int) ($stats['aceptadas'] ?? 0);
$totalCot = max(1, (int) ($stats['total'] ?? 0));
$conversion = porcentaje($aceptadas, $totalCot);
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Módulo C · Propuestas y cartera</p>
        <h1>Cotizaciones</h1>
        <p class="page-head__sub"><?= e($subtitulo ?? '') ?></p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--claro" href="<?= e(url('/pagos')) ?>"><?= icono('pagos') ?> Cobranza</a>
        <a class="btn btn--fantasma" href="<?= e(url('/reportes')) ?>"><?= icono('reportes') ?> Reportes</a>
    </div>
</div>

<div class="kpis">
    <div class="kpi kpi--oscura">
        <div class="kpi__top"><?= icono('cotizacion') ?> Cotizaciones</div>
        <p class="kpi__valor"><?= (int) ($stats['total'] ?? 0) ?></p>
        <p class="kpi__nota"><?= (int) ($stats['enviadas'] ?? 0) ?> enviadas · <?= $aceptadas ?> aceptadas</p>
    </div>
    <div class="kpi kpi--lima">
        <div class="kpi__top"><?= icono('grafico') ?> Cartera aceptada</div>
        <p class="kpi__valor"><?= e(money_corto($stats['cartera'] ?? 0)) ?></p>
        <p class="kpi__nota"><?= e(money($stats['cartera'] ?? 0)) ?> contratados</p>
    </div>
    <div class="kpi">
        <div class="kpi__top"><?= icono('reloj') ?> Por cobrar</div>
        <p class="kpi__valor"><?= e(money_corto($stats['por_cobrar'] ?? 0)) ?></p>
        <p class="kpi__nota">saldo de cotizaciones aceptadas</p>
    </div>
    <div class="kpi">
        <div class="kpi__top"><?= icono('alerta') ?> Vencidas</div>
        <p class="kpi__valor"><?= (int) ($stats['vencidas'] ?? 0) ?></p>
        <p class="kpi__nota">enviadas fuera de vigencia</p>
    </div>
</div>

<div class="grid grid-2 mb-2">
    <div class="card">
        <div class="card__body">
            <div class="fila fila--entre">
                <div>
                    <p class="texto-muted texto-xs" style="letter-spacing:.12em;text-transform:uppercase">Tasa de aceptación</p>
                    <p style="font-family:var(--font-display);font-size:28px;font-weight:600;letter-spacing:-.03em;margin-top:4px"><?= number_format($conversion, 1) ?>%</p>
                </div>
                <span class="badge badge--<?= $conversion >= 50 ? 'ok' : 'aviso' ?>"><?= $aceptadas ?> de <?= (int) ($stats['total'] ?? 0) ?></span>
            </div>
            <div class="progreso progreso--lima mt-2"><span style="width:<?= min(100, $conversion) ?>%"></span></div>
            <p class="texto-muted texto-xs mt-1">Las cotizaciones aceptadas congelan precios y habilitan el anticipo.</p>
        </div>
    </div>
    <div class="card">
        <div class="card__body">
            <p class="texto-muted texto-xs" style="letter-spacing:.12em;text-transform:uppercase">Embudo comercial</p>
            <div class="pipeline mt-2">
                <div class="pipeline__etapa"><b><?= (int) ($stats['total'] ?? 0) ?></b><span>Total</span></div>
                <div class="pipeline__etapa"><b><?= (int) ($stats['enviadas'] ?? 0) ?></b><span>Enviadas</span></div>
                <div class="pipeline__etapa"><b><?= $aceptadas ?></b><span>Aceptadas</span></div>
                <div class="pipeline__etapa"><b><?= (int) ($stats['canceladas'] ?? 0) ?></b><span>Canceladas</span></div>
            </div>
        </div>
    </div>
</div>

<form class="toolbar" method="get" action="<?= e(url('/cotizaciones')) ?>">
    <div class="campo campo--buscar">
        <label for="q">Buscar</label>
        <input class="input" type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Folio, evento o cliente">
    </div>
    <div class="campo">
        <label for="estado">Estado</label>
        <select class="select" id="estado" name="estado" data-autoenviar>
            <option value="">Todos</option>
            <?php foreach ($estados as $clave => $etiqueta): ?>
                <option value="<?= e($clave) ?>" <?= $estado === $clave ? 'selected' : '' ?>><?= e($etiqueta) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="toolbar__acciones">
        <button class="btn btn--primario" type="submit"><?= icono('filtrar') ?> Filtrar</button>
        <a class="btn btn--fantasma" href="<?= e(url('/cotizaciones')) ?>">Limpiar</a>
        <a class="btn btn--fantasma" href="<?= e(url('/reportes/exportar?tipo=cotizaciones')) ?>"><?= icono('descargar') ?> CSV</a>
    </div>
</form>

<section class="card">
    <div class="card__head">
        <div><h2><?= (int) $total ?> cotización<?= (int) $total === 1 ? '' : 'es' ?></h2><p>Primero las aceptadas, luego las enviadas y al final el resto.</p></div>
    </div>

    <?php if ($cotizaciones === []): ?>
        <div class="empty">
            <div class="empty__icono"><?= icono('cotizacion') ?></div>
            <h3>Sin cotizaciones en este filtro</h3>
            <p>Abre un evento y genera una propuesta desde su ficha.</p>
            <a class="btn btn--lima" href="<?= e(url('/eventos')) ?>"><?= icono('calendario') ?> Ir a eventos</a>
        </div>
    <?php else: ?>
        <div class="tabla-wrap">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Evento / cliente</th>
                        <th class="num">Total</th>
                        <th class="num">Saldo</th>
                        <th>Vigencia</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($cotizaciones as $cotizacion): ?>
                    <?php $vencida = $cotizacion['estado'] === 'enviada' && !empty($cotizacion['vigencia_hasta']) && strtotime($cotizacion['vigencia_hasta']) < strtotime(date('Y-m-d')); ?>
                    <tr>
                        <td class="ahorarap">
                            <a class="mono" href="<?= e(url('/cotizaciones/' . (int) $cotizacion['id_cotizacion'])) ?>"><b><?= e($cotizacion['folio']) ?></b></a>
                            <span class="texto-muted texto-xs">v<?= (int) $cotizacion['version'] ?></span>
                        </td>
                        <td>
                            <a class="tabla__principal" href="<?= e(url('/cotizaciones/' . (int) $cotizacion['id_cotizacion'])) ?>">
                                <span class="tabla__avatar"><?= icono('cotizacion') ?></span>
                                <span><b><?= e($cotizacion['evento']) ?></b><span><?= e($cotizacion['cliente']) ?> · <?= e(fecha($cotizacion['fecha_inicio'], 'd M Y')) ?></span></span>
                            </a>
                        </td>
                        <td class="num"><?= e(money($cotizacion['total'])) ?></td>
                        <td class="num"><b class="<?= (float) ($cotizacion['saldo'] ?? 0) > 0.01 ? 'texto-aviso' : 'texto-ok' ?>"><?= e(money($cotizacion['saldo'] ?? 0)) ?></b></td>
                        <td class="texto-sm ahorarap">
                            <?= e(fecha($cotizacion['vigencia_hasta'], 'd M Y')) ?>
                            <?php if ($vencida): ?><span class="badge badge--error">Vencida</span><?php endif; ?>
                        </td>
                        <td><?= badge_estado($cotizacion['estado']) ?></td>
                        <td>
                            <div class="acciones">
                                <a class="btn btn--fantasma btn--xs" href="<?= e(url('/cotizaciones/' . (int) $cotizacion['id_cotizacion'])) ?>" title="Abrir cotización" aria-label="Abrir cotización <?= e($cotizacion['folio']) ?>"><?= icono('ver') ?><span class="sr-only">Abrir</span></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= paginacion((int) $total, (int) $porPagina) ?>
    <?php endif; ?>
</section>
