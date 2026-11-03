<?php
/** @var array $registros */
/** @var array $usuarios */
/** @var array $entidades */
/** @var array $acciones */
/** @var array $stats */
/** @var int $total */
/** @var string $q */
/** @var int $usuarioId */
/** @var string $entidad */
/** @var string $accion */
/** @var string|null $desde */
/** @var string|null $hasta */
/** @var int $porPagina */

$filtros = array_filter([
    'q'       => $q,
    'usuario' => $usuarioId ?: null,
    'entidad' => $entidad,
    'accion'  => $accion,
    'desde'   => $desde,
    'hasta'   => $hasta,
], static fn ($valor): bool => $valor !== null && $valor !== '');
$hayFiltros = $filtros !== [];

$tonoAccion = static function (string $accion): string {
    $a = mb_strtoupper($accion);
    if (str_contains($a, 'CANCEL') || str_contains($a, 'RECHAZ') || str_contains($a, 'ELIMIN') || str_contains($a, 'REVERT')) {
        return 'error';
    }
    if (str_contains($a, 'CREAR') || str_contains($a, 'REGISTRAR') || str_contains($a, 'CONFIRM') || str_contains($a, 'PAGO')) {
        return 'ok';
    }
    if (str_contains($a, 'EDITAR') || str_contains($a, 'ACTUALIZAR') || str_contains($a, 'AJUST')) {
        return 'aviso';
    }
    return 'info';
};
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Módulo D · Trazabilidad</p>
        <h1>Bitácora de operaciones</h1>
        <p class="page-head__sub">Cada acción queda registrada con usuario, IP y fecha. El histórico no se borra: se consulta, se filtra y se audita.</p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--claro" href="<?= e(url('/reportes/auditoria')) ?>"><?= icono('reportes') ?> Auditoría</a>
        <a class="btn btn--fantasma" href="<?= e(url('/bitacora')) ?>"><?= icono('refrescar') ?> Limpiar filtros</a>
    </div>
</div>

<div class="kpis">
    <article class="kpi kpi--oscura">
        <p class="kpi__top"><?= icono('bitacora') ?> Movimientos</p>
        <p class="kpi__valor"><?= (int) $stats['total'] ?></p>
        <p class="kpi__nota">registro histórico inmutable</p>
    </article>
    <article class="kpi">
        <p class="kpi__top"><?= icono('reloj') ?> Hoy</p>
        <p class="kpi__valor"><?= (int) $stats['hoy'] ?></p>
        <p class="kpi__nota">acciones desde las 00:00</p>
    </article>
    <article class="kpi">
        <p class="kpi__top"><?= icono('calendario') ?> Últimos 7 días</p>
        <p class="kpi__valor"><?= (int) $stats['semana'] ?></p>
        <p class="kpi__nota">actividad de la semana</p>
    </article>
    <article class="kpi">
        <p class="kpi__top"><?= icono('usuarios') ?> Usuarios con actividad</p>
        <p class="kpi__valor"><?= (int) $stats['usuarios'] ?></p>
        <p class="kpi__nota">distintos en la bitácora</p>
    </article>
</div>

<section class="card mb-3">
    <form class="card__body" method="get" action="<?= e(url('/bitacora')) ?>">
        <div class="campo-fila campo-fila--3 mb-2">
            <div class="campo">
                <label for="q">Buscar</label>
                <input class="input" type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Acción, detalle, entidad o usuario">
            </div>
            <div class="campo">
                <label for="usuario">Usuario</label>
                <select class="select" id="usuario" name="usuario">
                    <option value="">Todos</option>
                    <?php foreach ($usuarios as $filaUsuario): ?>
                        <option value="<?= (int) $filaUsuario['id_usuario'] ?>" <?= (int) $filaUsuario['id_usuario'] === $usuarioId ? 'selected' : '' ?>>
                            <?= e($filaUsuario['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="entidad">Entidad</label>
                <select class="select" id="entidad" name="entidad">
                    <option value="">Todas</option>
                    <?php foreach ($entidades as $item): ?>
                        <option value="<?= e($item) ?>" <?= $item === $entidad ? 'selected' : '' ?>><?= e($item) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="campo-fila campo-fila--3">
            <div class="campo">
                <label for="accion">Acción</label>
                <select class="select" id="accion" name="accion">
                    <option value="">Todas</option>
                    <?php foreach ($acciones as $item): ?>
                        <option value="<?= e($item) ?>" <?= $item === $accion ? 'selected' : '' ?>><?= e(str_replace('_', ' ', $item)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="desde">Desde</label>
                <input class="input" type="date" id="desde" name="desde" value="<?= e($desde ?? '') ?>">
            </div>
            <div class="campo">
                <label for="hasta">Hasta</label>
                <input class="input" type="date" id="hasta" name="hasta" value="<?= e($hasta ?? '') ?>">
            </div>
        </div>
        <div class="fila fila--entre mt-3">
            <span class="texto-muted texto-sm">
                <?= (int) $total ?> resultado<?= $total === 1 ? '' : 's' ?>
                <?php if ($hayFiltros): ?> con los filtros aplicados<?php endif; ?>
            </span>
            <div class="fila">
                <button class="btn btn--primario btn--sm" type="submit"><?= icono('filtrar') ?> Filtrar</button>
                <?php if ($hayFiltros): ?>
                    <a class="btn btn--fantasma btn--sm" href="<?= e(url('/bitacora')) ?>"><?= icono('x') ?> Quitar filtros</a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</section>

<section class="card">
    <div class="card__head">
        <div>
            <h2>Registro cronológico</h2>
            <p>Ordenado del más reciente al más antiguo. Página <?= pagina_actual() ?> de <?= max(1, (int) ceil($total / max(1, $porPagina))) ?>.</p>
        </div>
        <div class="card__head__acciones">
            <?= badge('Solo lectura', 'neutro') ?>
        </div>
    </div>

    <?php if ($registros === []): ?>
        <div class="empty">
            <div class="empty__icono"><?= icono('bitacora') ?></div>
            <h3>Sin movimientos con esos filtros</h3>
            <p>Ajusta el rango de fechas o limpia los filtros para ver la actividad completa del sistema.</p>
            <a class="btn btn--claro" href="<?= e(url('/bitacora')) ?>"><?= icono('refrescar') ?> Ver toda la bitácora</a>
        </div>
    <?php else: ?>
        <div class="tabla-wrap">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Acción</th>
                        <th>Entidad</th>
                        <th>Detalle</th>
                        <th>Usuario</th>
                        <th>Origen</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($registros as $registro): ?>
                    <tr>
                        <td>
                            <b class="texto-sm"><?= e(fecha($registro['fecha'], 'd M Y')) ?></b>
                            <span class="texto-muted texto-xs mono"><?= e(date('H:i:s', strtotime($registro['fecha']))) ?></span>
                        </td>
                        <td>
                            <?= badge(str_replace('_', ' ', (string) $registro['accion']), $tonoAccion((string) $registro['accion'])) ?>
                        </td>
                        <td>
                            <?php if ($registro['entidad'] !== ''): ?>
                                <span class="mono texto-xs"><?= e($registro['entidad']) ?></span>
                                <?php if ($registro['id_registro'] !== null): ?>
                                    <span class="texto-muted texto-xs">#<?= (int) $registro['id_registro'] ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="texto-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="texto-sm"><?= e(recortar((string) $registro['detalle'], 90)) ?: '<span class="texto-muted">Sin detalle</span>' ?></td>
                        <td>
                            <b class="texto-sm"><?= e($registro['usuario'] ?? 'Sistema') ?></b>
                            <?php if (!empty($registro['rol'])): ?>
                                <span class="texto-muted texto-xs"><?= e($registro['rol']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="mono texto-xs"><?= e($registro['ip'] ?? '—') ?></span>
                            <?php if (!empty($registro['user_agent'])): ?>
                                <span class="texto-muted texto-xs" title="<?= e($registro['user_agent']) ?>"><?= e(recortar((string) $registro['user_agent'], 34)) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= paginacion($total, $porPagina) ?>
    <?php endif; ?>
</section>
