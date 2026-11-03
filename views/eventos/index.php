<?php
/** @var array $eventos */
/** @var array $tipos */
/** @var int $total */
/** @var string $q */
/** @var string $estado */
/** @var int $tipo */
/** @var string|null $desde */
/** @var string|null $hasta */
/** @var int $porPagina */
$estados = [
    'prospecto'      => 'Prospecto',
    'cotizado'       => 'Cotizado',
    'confirmado'     => 'Confirmado',
    'en_preparacion' => 'En preparación',
    'en_curso'       => 'En curso',
    'realizado'      => 'Realizado',
    'cerrado'        => 'Cerrado',
    'cancelado'      => 'Cancelado',
];
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Módulo A · Ciclo de vida</p>
        <h1>Eventos</h1>
        <p class="page-head__sub">Del prospecto al cierre financiero. Cada transición valida cotización, anticipo, espacio y saldo antes de avanzar.</p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--claro" href="<?= e(url('/reportes/produccion')) ?>"><?= icono('grafico') ?> Producción</a>
        <?php if (puede(['administrador', 'comercial', 'coordinador'])): ?>
            <a class="btn btn--lima" href="<?= e(url('/eventos/crear')) ?>"><?= icono('mas') ?> Nuevo evento</a>
        <?php endif; ?>
    </div>
</div>

<form class="toolbar" method="get" action="<?= e(url('/eventos')) ?>">
    <div class="campo campo--buscar">
        <label for="q">Buscar</label>
        <input class="input" type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Evento o cliente">
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
    <div class="campo">
        <label for="tipo">Tipo</label>
        <select class="select" id="tipo" name="tipo" data-autoenviar>
            <option value="0">Todos</option>
            <?php foreach ($tipos as $t): ?>
                <option value="<?= (int) $t['id_tipo_evento'] ?>" <?= (int) $tipo === (int) $t['id_tipo_evento'] ? 'selected' : '' ?>><?= e($t['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="campo campo--periodo">
        <label for="desde">Periodo</label>
        <div class="campo__rango">
            <input class="input" type="date" id="desde" name="desde" aria-label="Desde" value="<?= e($desde ?? '') ?>">
            <input class="input" type="date" id="hasta" name="hasta" aria-label="Hasta" value="<?= e($hasta ?? '') ?>">
        </div>
    </div>
    <div class="toolbar__acciones">
        <button class="btn btn--primario" type="submit"><?= icono('filtrar') ?> Filtrar</button>
        <a class="btn btn--fantasma" href="<?= e(url('/eventos')) ?>">Limpiar</a>
    </div>
</form>

<section class="card">
    <div class="card__head">
        <div>
            <h2><?= (int) $total ?> evento<?= (int) $total === 1 ? '' : 's' ?></h2>
            <p>Ordenados por fecha: primero los que están por venir.</p>
        </div>
        <div class="card__head__acciones">
            <a class="btn btn--fantasma btn--sm" href="<?= e(url('/reportes/exportar?tipo=eventos')) ?>"><?= icono('descargar') ?> CSV</a>
        </div>
    </div>

    <?php if ($eventos === []): ?>
        <div class="empty">
            <div class="empty__icono"><?= icono('calendario') ?></div>
            <h3>Sin eventos en este filtro</h3>
            <p>Prueba con otro estado o rango de fechas. Si el recinto está vacío, crea el primer evento.</p>
            <?php if (puede(['administrador', 'comercial', 'coordinador'])): ?>
                <a class="btn btn--lima" href="<?= e(url('/eventos/crear')) ?>"><?= icono('mas') ?> Crear evento</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="tabla-wrap">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Evento</th>
                        <th>Fecha</th>
                        <th>Espacio</th>
                        <th class="num" title="Invitados confirmados / aforo máximo">Aforo</th>
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
                                    <span><?= e($evento['cliente']) ?> · <?= e($evento['tipo']) ?></span>
                                </span>
                            </a>
                        </td>
                        <td class="ahorarap" title="<?= e(fecha_hora($evento['fecha_inicio'])) ?> — <?= e(fecha_hora($evento['fecha_fin'])) ?>">
                            <b class="texto-sm"><?= e(fecha($evento['fecha_inicio'], 'd M Y')) ?></b>
                            <span class="texto-muted texto-xs"><?= e(date('H:i', strtotime($evento['fecha_inicio']))) ?> h</span>
                        </td>
                        <td class="ahorarap"><?= $evento['espacio'] ? e($evento['espacio']) : '<span class="texto-aviso">Sin espacio</span>' ?></td>
                        <td class="num"><?= (int) $evento['confirmados'] ?> / <?= (int) $evento['aforo_maximo'] ?></td>
                        <td class="num"><?= e(money($evento['saldo'])) ?></td>
                        <td><?= badge_estado($evento['estado']) ?></td>
                        <td>
                            <div class="acciones">
                                <a class="btn btn--fantasma btn--xs" href="<?= e(url('/eventos/' . (int) $evento['id_evento'])) ?>" title="Abrir expediente" aria-label="Abrir expediente de <?= e($evento['nombre']) ?>"><?= icono('ver') ?><span class="sr-only">Abrir</span></a>
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
