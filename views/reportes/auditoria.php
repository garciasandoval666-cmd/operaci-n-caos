<?php
/** @var array $acciones */
/** @var array $usuarios */
/** @var array $cambios */
/** @var array $precios */
/** @var int $dias */
/** @var int $total */

$maxAccion = max(1, (int) ($acciones[0]['total'] ?? 1));
$maxUsuario = max(1, (int) ($usuarios[0]['total'] ?? 1));
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Módulo D · Auditoría</p>
        <h1>Auditoría de operación</h1>
        <p class="page-head__sub"><?= e($subtitulo ?? '') ?> Ventana de <?= $dias ?> días.</p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--fantasma" href="<?= e(url('/reportes')) ?>"><?= icono('atras') ?> Reportes</a>
        <a class="btn btn--claro" href="<?= e(url('/bitacora')) ?>"><?= icono('bitacora') ?> Bitácora completa</a>
        <a class="btn btn--claro" href="<?= e(url('/reportes/exportar?tipo=eventos')) ?>"><?= icono('descargar') ?> Exportar</a>
    </div>
</div>

<form class="toolbar" method="get" action="<?= e(url('/reportes/auditoria')) ?>">
    <div class="campo">
        <label for="dias">Ventana</label>
        <select class="select" id="dias" name="dias" data-autoenviar>
            <?php foreach ([7, 30, 90, 365] as $opcion): ?>
                <option value="<?= $opcion ?>" <?= $dias === $opcion ? 'selected' : '' ?>><?= $opcion === 365 ? 'Último año' : 'Últimos ' . $opcion . ' días' ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="toolbar__acciones">
        <button class="btn btn--primario" type="submit"><?= icono('filtrar') ?> Aplicar</button>
    </div>
</form>

<div class="kpis">
    <div class="kpi kpi--oscura">
        <div class="kpi__top"><?= icono('bitacora') ?> Acciones registradas</div>
        <p class="kpi__valor"><?= number_format($total) ?></p>
        <p class="kpi__nota">en los últimos <?= $dias ?> días</p>
    </div>
    <div class="kpi kpi--lima">
        <div class="kpi__top"><?= icono('usuarios') ?> Usuarios activos</div>
        <p class="kpi__valor"><?= count($usuarios) ?></p>
        <p class="kpi__nota">con al menos una acción</p>
    </div>
    <div class="kpi">
        <div class="kpi__top"><?= icono('editar') ?> Cambios de campo</div>
        <p class="kpi__valor"><?= count($cambios) ?></p>
        <p class="kpi__nota">historial de valores anteriores</p>
    </div>
    <div class="kpi">
        <div class="kpi__top"><?= icono('pagos') ?> Cambios de precio</div>
        <p class="kpi__valor"><?= count($precios) ?></p>
        <p class="kpi__nota">trazabilidad de tarifas</p>
    </div>
</div>

<div class="grid grid-2 mb-2">
    <section class="card">
        <div class="card__head"><div><h2>Acciones más frecuentes</h2><p>Qué se hace y cuántas veces.</p></div></div>
        <div class="card__body">
            <?php if ($acciones === []): ?>
                <p class="texto-sm texto-muted">Sin actividad en la ventana seleccionada.</p>
            <?php else: ?>
                <div class="distribucion-lista">
                    <?php foreach ($acciones as $accion): ?>
                        <div class="distribucion-lista__fila">
                            <span title="<?= e($accion['accion']) ?>"><?= e(recortar($accion['accion'], 22)) ?></span>
                            <div class="progreso progreso--lima"><span style="width:<?= min(100, round(((int) $accion['total'] / $maxAccion) * 100)) ?>%"></span></div>
                            <b><?= (int) $accion['total'] ?></b>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="card">
        <div class="card__head"><div><h2>Actividad por usuario</h2><p>Quién operó el sistema.</p></div></div>
        <div class="card__body">
            <?php if ($usuarios === []): ?>
                <p class="texto-sm texto-muted">Sin actividad de usuarios.</p>
            <?php else: ?>
                <div class="distribucion-lista">
                    <?php foreach ($usuarios as $usuario): ?>
                        <div class="distribucion-lista__fila">
                            <span title="<?= e($usuario['nombre']) ?>"><?= e(recortar($usuario['nombre'], 20)) ?></span>
                            <div class="progreso"><span style="width:<?= min(100, round(((int) $usuario['total'] / $maxUsuario) * 100)) ?>%"></span></div>
                            <b><?= (int) $usuario['total'] ?></b>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<section class="card mb-2">
    <div class="card__head"><div><h2>Cambios de campo</h2><p>Valor anterior, valor nuevo, motivo y responsable.</p></div><span class="badge badge--neutro"><?= count($cambios) ?> registros</span></div>
    <?php if ($cambios === []): ?>
        <div class="tabla__vacia">No hubo cambios de campo en el periodo.</div>
    <?php else: ?>
        <div class="tabla-wrap">
            <table class="tabla tabla--compacta">
                <thead><tr><th>Fecha</th><th>Entidad</th><th>Registro</th><th>Campo</th><th>Antes</th><th>Después</th><th>Motivo</th><th>Usuario</th></tr></thead>
                <tbody>
                <?php foreach ($cambios as $cambio): ?>
                    <tr>
                        <td class="texto-sm ahorarap"><?= e(fecha_hora($cambio['fecha_cambio'])) ?></td>
                        <td class="texto-sm"><?= e($cambio['entidad']) ?></td>
                        <td class="mono">#<?= (int) $cambio['id_registro'] ?></td>
                        <td class="texto-sm"><b><?= e($cambio['campo']) ?></b></td>
                        <td class="texto-sm texto-muted"><?= e(recortar($cambio['valor_anterior'] ?? '—', 26)) ?></td>
                        <td class="texto-sm"><?= e(recortar($cambio['valor_nuevo'] ?? '—', 26)) ?></td>
                        <td class="texto-sm"><?= e(recortar($cambio['motivo'] ?? '—', 34)) ?></td>
                        <td class="texto-sm texto-muted"><?= e($cambio['usuario'] ?? 'sistema') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="card">
    <div class="card__head"><div><h2>Historial de precios</h2><p>Cambios de tarifas que afectan cotizaciones.</p></div><span class="badge badge--neutro"><?= count($precios) ?> registros</span></div>
    <?php if ($precios === []): ?>
        <div class="tabla__vacia">Sin cambios de precio en el periodo.</div>
    <?php else: ?>
        <div class="tabla-wrap">
            <table class="tabla tabla--compacta">
                <thead><tr><th>Fecha</th><th>Entidad</th><th>Registro</th><th>Motivo</th><th>Antes</th><th>Después</th><th>Usuario</th></tr></thead>
                <tbody>
                <?php foreach ($precios as $precio): ?>
                    <tr>
                        <td class="texto-sm ahorarap"><?= e(fecha_hora($precio['fecha'])) ?></td>
                        <td class="texto-sm"><?= e($precio['entidad'] ?? '—') ?></td>
                        <td class="mono">#<?= (int) ($precio['id_registro'] ?? 0) ?></td>
                        <td class="texto-sm"><?= e(recortar($precio['motivo'] ?? '—', 34)) ?></td>
                        <td class="texto-sm texto-muted"><?= e($precio['precio_anterior'] ?? '—') ?></td>
                        <td class="texto-sm"><?= e($precio['precio_nuevo'] ?? '—') ?></td>
                        <td class="texto-sm texto-muted"><?= e($precio['usuario'] ?? 'sistema') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
