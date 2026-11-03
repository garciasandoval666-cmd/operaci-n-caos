<?php
/** @var array $invitados */
/** @var array $eventos */
/** @var array $stats */
/** @var int $total */
/** @var string $q */
/** @var int $eventoId */
/** @var string $estado */
/** @var int $porPagina */
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Módulo A · RSVP y aforo</p>
        <h1>Invitados</h1>
        <p class="page-head__sub">Cada invitado tiene un enlace único de confirmación. El aforo se valida en el servidor antes de aceptar una confirmación.</p>
    </div>
    <div class="page-head__acciones">
        <?php if (puede(['administrador', 'coordinador', 'operaciones'])): ?>
            <a class="btn btn--claro" href="<?= e(url('/invitados/importar')) ?>"><?= icono('descargar') ?> Importar lista</a>
        <?php endif; ?>
        <a class="btn btn--lima" href="<?= e(url('/checkin')) ?>"><?= icono('check') ?> Check-in</a>
    </div>
</div>

<div class="grid grid-4 mb-3">
    <div class="kpi kpi--oscura">
        <p class="kpi__top"><?= icono('invitados') ?> Invitados totales</p>
        <p class="kpi__valor"><?= (int) $stats['total'] ?></p>
        <p class="kpi__nota"><b><?= (int) $stats['checkins'] ?></b> con check-in registrado</p>
    </div>
    <div class="kpi">
        <p class="kpi__top"><?= icono('check') ?> Confirmados</p>
        <p class="kpi__valor"><?= (int) $stats['confirmados'] ?></p>
        <div class="progreso progreso--ok mt-2"><span data-barra="<?= porcentaje((int) $stats['confirmados'], (int) $stats['total']) ?>" style="width:0"></span></div>
    </div>
    <div class="kpi">
        <p class="kpi__top"><?= icono('reloj') ?> Pendientes</p>
        <p class="kpi__valor"><?= (int) $stats['pendientes'] ?></p>
        <p class="kpi__nota">Esperando respuesta</p>
    </div>
    <div class="kpi">
        <p class="kpi__top"><?= icono('x') ?> Declinados</p>
        <p class="kpi__valor"><?= (int) $stats['declinados'] ?></p>
        <p class="kpi__nota"><?= (int) $stats['espera'] ?> en lista de espera</p>
    </div>
</div>

<form class="toolbar" method="get" action="<?= e(url('/invitados')) ?>">
    <div class="campo campo--buscar">
        <label for="q">Buscar invitado</label>
        <input class="input" type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Nombre o correo">
    </div>
    <div class="campo">
        <label for="evento">Evento</label>
        <select class="select" id="evento" name="evento" data-autoenviar>
            <option value="">Todos los eventos</option>
            <?php foreach ($eventos as $evento): ?>
                <option value="<?= (int) $evento['id_evento'] ?>" <?= (int) $eventoId === (int) $evento['id_evento'] ? 'selected' : '' ?>>
                    <?= e($evento['nombre']) ?> · <?= e(fecha($evento['fecha_inicio'], 'd M Y')) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="campo">
        <label for="estado">RSVP</label>
        <select class="select" id="estado" name="estado" data-autoenviar>
            <option value="">Todos</option>
            <?php foreach (['pendiente' => 'Pendiente', 'confirmado' => 'Confirmado', 'declinado' => 'Declinado', 'lista_espera' => 'Lista de espera'] as $clave => $etiqueta): ?>
                <option value="<?= e($clave) ?>" <?= $estado === $clave ? 'selected' : '' ?>><?= e($etiqueta) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="toolbar__acciones">
        <button class="btn btn--primario" type="submit"><?= icono('buscar') ?> Filtrar</button>
        <a class="btn btn--fantasma" href="<?= e(url('/invitados')) ?>">Limpiar</a>
    </div>
</form>

<section class="card">
    <div class="card__head">
        <div>
            <h2><?= (int) $total ?> invitado<?= (int) $total === 1 ? '' : 's' ?></h2>
            <p><?= $q !== '' ? 'Resultados para “' . e($q) . '”' : 'Listado global con su evento, mesa y estado de confirmación.' ?></p>
        </div>
    </div>

    <?php if ($invitados === []): ?>
        <div class="empty">
            <div class="empty__icono"><?= icono('invitados') ?></div>
            <h3>Sin invitados que mostrar</h3>
            <p>Ajusta los filtros o agrega invitados desde el expediente de un evento.</p>
            <a class="btn btn--lima" href="<?= e(url('/eventos')) ?>"><?= icono('calendario') ?> Ir a eventos</a>
        </div>
    <?php else: ?>
        <div class="tabla-wrap">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Invitado</th>
                        <th>Evento</th>
                        <th>Categoría</th>
                        <th>Mesa</th>
                        <th>Restricción</th>
                        <th>RSVP</th>
                        <th>Check-in</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($invitados as $invitado): ?>
                    <tr>
                        <td>
                            <a class="tabla__principal" href="<?= e(url('/eventos/' . (int) $invitado['id_evento'] . '/invitados')) ?>">
                                <span class="tabla__avatar"><?= e(iniciales($invitado['nombre'])) ?></span>
                                <span>
                                    <b><?= e($invitado['nombre']) ?></b>
                                    <span><?= e($invitado['correo'] ?? 'Sin correo') ?></span>
                                </span>
                            </a>
                        </td>
                        <td>
                            <b class="texto-sm"><?= e($invitado['evento']) ?></b>
                            <span class="texto-muted texto-xs"><?= e(fecha($invitado['fecha_inicio'], 'd M Y')) ?></span>
                        </td>
                        <td><?= badge(ucfirst($invitado['categoria']), $invitado['categoria'] === 'vip' ? 'acento' : 'neutro') ?></td>
                        <td><?= $invitado['mesa'] ? e($invitado['mesa']) : '<span class="texto-muted">Sin mesa</span>' ?></td>
                        <td class="texto-sm"><?= $invitado['restriccion_alimenticia'] === 'ninguna' ? '<span class="texto-muted">Ninguna</span>' : badge(ucfirst(str_replace('_', ' ', $invitado['restriccion_alimenticia'])), 'aviso') ?></td>
                        <td><?= badge_estado($invitado['estado_rsvp']) ?></td>
                        <td class="texto-sm"><?= $invitado['checkin_en'] ? e(date('H:i', strtotime($invitado['checkin_en']))) . ' h' : '<span class="texto-muted">—</span>' ?></td>
                        <td>
                            <div class="acciones">
                                <a class="btn btn--fantasma btn--xs" href="<?= e(url('/eventos/' . (int) $invitado['id_evento'] . '/invitados')) ?>"><?= icono('ver') ?> Ver</a>
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
