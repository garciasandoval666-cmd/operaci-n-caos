<?php
/** @var array $recursos */
/** @var string $inicio */
/** @var string $fin */

$categorias = [
    'mobiliario' => 'Mobiliario',
    'personal'   => 'Personal',
    'audio_video'=> 'Audio y video',
    'decoracion' => 'Decoración',
    'transporte' => 'Transporte',
    'otro'       => 'Otro',
];
$libres = count(array_filter($recursos, static fn (array $r): bool => (int) $r['disponible'] > 0));
$bloqueados = count($recursos) - $libres;
$dias = count(dias_entre($inicio, $fin));
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Módulo B · Consulta de disponibilidad</p>
        <h1>Disponibilidad de recursos</h1>
        <p class="page-head__sub"><?= e($subtitulo ?? '') ?> Rango consultado: <b><?= e(fecha_larga($inicio)) ?></b> al <b><?= e(fecha_larga($fin)) ?></b> (<?= $dias ?> día<?= $dias === 1 ? '' : 's' ?>).</p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--fantasma" href="<?= e(url('/recursos')) ?>"><?= icono('atras') ?> Inventario</a>
    </div>
</div>

<form class="toolbar" method="get" action="<?= e(url('/recursos/disponibilidad')) ?>">
    <div class="campo">
        <label for="inicio">Desde</label>
        <input class="input" type="date" id="inicio" name="inicio" value="<?= e($inicio) ?>" required>
    </div>
    <div class="campo">
        <label for="fin">Hasta</label>
        <input class="input" type="date" id="fin" name="fin" value="<?= e($fin) ?>" required>
    </div>
    <div class="toolbar__acciones">
        <button class="btn btn--primario" type="submit"><?= icono('buscar') ?> Consultar</button>
        <a class="btn btn--fantasma" href="<?= e(url('/recursos/disponibilidad')) ?>">Hoy</a>
    </div>
</form>

<div class="kpis">
    <div class="kpi kpi--oscura">
        <div class="kpi__top"><?= icono('recursos') ?> Recursos consultados</div>
        <p class="kpi__valor"><?= count($recursos) ?></p>
        <p class="kpi__nota">inventario activo del recinto</p>
    </div>
    <div class="kpi kpi--lima">
        <div class="kpi__top"><?= icono('check') ?> Con disponibilidad</div>
        <p class="kpi__valor"><?= $libres ?></p>
        <p class="kpi__nota">al menos una unidad libre</p>
    </div>
    <div class="kpi">
        <div class="kpi__top"><?= icono('alerta') ?> Comprometidos</div>
        <p class="kpi__valor"><?= $bloqueados ?></p>
        <p class="kpi__nota">sin unidades en todo el rango</p>
    </div>
    <div class="kpi">
        <div class="kpi__top"><?= icono('calendario') ?> Días del rango</div>
        <p class="kpi__valor"><?= $dias ?></p>
        <p class="kpi__nota">se evalúa el mínimo diario</p>
    </div>
</div>

<section class="card">
    <div class="card__head">
        <div><h2>Resultado por recurso</h2><p>El disponible corresponde al día más ocupado del rango: si falla un día, no se puede comprometer.</p></div>
        <span class="badge badge--neutro"><?= e(fecha($inicio, 'd M')) ?> – <?= e(fecha($fin, 'd M Y')) ?></span>
    </div>

    <?php if ($recursos === []): ?>
        <div class="empty">
            <div class="empty__icono"><?= icono('recursos') ?></div>
            <h3>Sin recursos activos</h3>
            <p>Agrega recursos al inventario para consultar su disponibilidad por fecha.</p>
        </div>
    <?php else: ?>
        <div class="tabla-wrap">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Recurso</th>
                        <th>Categoría</th>
                        <th>Proveedor</th>
                        <th class="num">Existencias</th>
                        <th class="num">Máx. apartado</th>
                        <th class="num">Disponible</th>
                        <th>Semáforo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recursos as $recurso): ?>
                    <?php $disponible = (int) $recurso['disponible']; $total = max(1, (int) $recurso['existencias_total']); $uso = min(100, round(((int) $recurso['reservado'] / $total) * 100)); ?>
                    <tr>
                        <td><b><?= e($recurso['nombre']) ?></b><span class="texto-muted texto-xs">por <?= e($recurso['unidad']) ?></span></td>
                        <td class="texto-sm"><?= e($categorias[$recurso['categoria']] ?? ucfirst($recurso['categoria'])) ?></td>
                        <td class="texto-sm"><?= $recurso['proveedor'] ? e($recurso['proveedor']) : '<span class="texto-muted">Interno</span>' ?></td>
                        <td class="num"><?= number_format((int) $recurso['existencias_total']) ?></td>
                        <td class="num"><?= number_format((int) $recurso['reservado']) ?></td>
                        <td class="num"><b class="<?= $disponible === 0 ? 'texto-error' : 'texto-ok' ?>"><?= number_format($disponible) ?></b></td>
                        <td style="min-width:120px">
                            <div class="progreso <?= $disponible === 0 ? 'progreso--aviso' : ($uso > 70 ? 'progreso--lima' : 'progreso--ok') ?>">
                                <span style="width:<?= $uso ?>%"></span>
                            </div>
                            <span class="texto-muted texto-xs"><?= $uso ?>% comprometido</span>
                        </td>
                        <td>
                            <div class="acciones">
                                <a class="btn btn--fantasma btn--xs" href="<?= e(url('/reservas/crear')) ?>"><?= icono('mas') ?> Apartar</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
