<?php
/** @var array $hallazgos */
/** @var int $criticos */
/** @var int $total */

$etiquetas = [
    'id_evento'          => 'Evento',
    'id_reserva_espacio' => 'Reserva',
    'id_cotizacion'      => 'Cotización',
    'id_insumo'          => 'Insumo',
    'nombre'             => 'Nombre',
    'evento'             => 'Evento',
    'folio'              => 'Folio',
    'tipo'               => 'Tipo',
    'estado'             => 'Estado',
    'fecha_inicio'       => 'Inicio',
    'fecha_fin'          => 'Fin',
    'evento_inicio'      => 'Inicio del evento',
    'aforo_maximo'       => 'Aforo',
    'confirmados'        => 'Confirmados',
    'numero'             => 'Mesa',
    'capacidad'          => 'Capacidad',
    'ocupados'           => 'Ocupados',
    'total'              => 'Total',
    'pagado'             => 'Pagado',
    'stock_fisico'       => 'Físico',
    'stock_comprometido' => 'Comprometido',
];
$conHallazgos = count(array_filter($hallazgos, static fn (array $h): bool => (int) $h['total'] > 0));
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Módulo D · Controles cruzados</p>
        <h1>Inconsistencias</h1>
        <p class="page-head__sub"><?= e($subtitulo ?? '') ?></p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--fantasma" href="<?= e(url('/reportes')) ?>"><?= icono('atras') ?> Reportes</a>
        <a class="btn btn--claro" href="<?= e(url('/reportes/auditoria')) ?>"><?= icono('bitacora') ?> Auditoría</a>
    </div>
</div>

<div class="kpis">
    <div class="kpi <?= $criticos > 0 ? 'kpi--oscura' : 'kpi--lima' ?>">
        <div class="kpi__top"><?= icono('alerta') ?> Controles críticos</div>
        <p class="kpi__valor"><?= $criticos ?></p>
        <p class="kpi__nota">con hallazgos de tono error</p>
    </div>
    <div class="kpi">
        <div class="kpi__top"><?= icono('filtrar') ?> Controles ejecutados</div>
        <p class="kpi__valor"><?= count($hallazgos) ?></p>
        <p class="kpi__nota">consultas de integridad</p>
    </div>
    <div class="kpi">
        <div class="kpi__top"><?= icono('x') ?> Hallazgos totales</div>
        <p class="kpi__valor"><?= $total ?></p>
        <p class="kpi__nota">filas a revisar</p>
    </div>
    <div class="kpi <?= $conHallazgos === 0 ? 'kpi--lima' : '' ?>">
        <div class="kpi__top"><?= icono('check') ?> Controles limpios</div>
        <p class="kpi__valor"><?= count($hallazgos) - $conHallazgos ?></p>
        <p class="kpi__nota">sin diferencias detectadas</p>
    </div>
</div>

<?php if ($total === 0): ?>
    <div class="card">
        <div class="empty">
            <div class="empty__icono" style="background:var(--green-soft);color:var(--green)"><?= icono('check') ?></div>
            <h3>Todo cuadra</h3>
            <p>Los <?= count($hallazgos) ?> controles cruzados no encontraron diferencias. Vuelve a ejecutar este reporte después de operaciones masivas o importaciones.</p>
        </div>
    </div>
<?php else: ?>
    <div class="stack">
        <?php foreach ($hallazgos as $hallazgo): ?>
            <?php if ((int) $hallazgo['total'] === 0) { continue; } ?>
            <section class="card">
                <div class="card__head">
                    <div>
                        <h2>
                            <?= icono($hallazgo['tono'] === 'error' ? 'alerta' : 'aviso') ?>
                            <?= e($hallazgo['titulo']) ?>
                        </h2>
                        <p><?= e($hallazgo['detalle']) ?></p>
                    </div>
                    <div class="card__head__acciones">
                        <?= badge($hallazgo['tono'] === 'error' ? 'Crítico' : 'Aviso', $hallazgo['tono'] === 'error' ? 'error' : 'aviso') ?>
                        <span class="badge badge--neutro"><?= (int) $hallazgo['total'] ?> fila<?= (int) $hallazgo['total'] === 1 ? '' : 's' ?></span>
                    </div>
                </div>
                <div class="tabla-wrap">
                    <table class="tabla tabla--compacta">
                        <thead>
                            <tr>
                                <?php foreach (array_keys($hallazgo['filas'][0]) as $columna): ?>
                                    <th><?= e($etiquetas[$columna] ?? ucfirst(str_replace('_', ' ', $columna))) ?></th>
                                <?php endforeach; ?>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($hallazgo['filas'] as $fila): ?>
                            <tr>
                                <?php foreach ($fila as $columna => $valor): ?>
                                    <td class="<?= in_array($columna, ['total', 'pagado', 'confirmados', 'capacidad', 'ocupados', 'stock_fisico', 'stock_comprometido'], true) ? 'num' : 'texto-sm' ?>">
                                        <?php if ($columna === 'estado'): ?>
                                            <?= badge_estado((string) $valor) ?>
                                        <?php elseif (str_starts_with($columna, 'fecha') || str_ends_with($columna, '_inicio') || str_ends_with($columna, '_fin')): ?>
                                            <?= e(fecha_hora((string) $valor)) ?>
                                        <?php elseif (in_array($columna, ['total', 'pagado'], true)): ?>
                                            <?= e(money($valor)) ?>
                                        <?php else: ?>
                                            <?= e(recortar((string) $valor, 40)) ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <td>
                                    <?php
                                    $enlace = null;
                                    foreach ($fila as $columna => $valor) {
                                        if (str_starts_with((string) $columna, 'id_') && (int) $valor > 0) {
                                            $enlace = $hallazgo['url'] . (int) $valor;
                                            break;
                                        }
                                    }
                                    ?>
                                    <?php if ($enlace !== null): ?>
                                        <div class="acciones"><a class="btn btn--fantasma btn--xs" href="<?= e(url($enlace)) ?>"><?= icono('externo') ?> Corregir</a></div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card__foot">
                    <details style="width:100%">
                        <summary class="texto-sm texto-muted" style="cursor:pointer">Ver consulta de verificación</summary>
                        <pre class="mono mt-2" style="white-space:pre-wrap;background:var(--surface-2);padding:12px;border-radius:8px;font-size:10.5px;line-height:1.5"><?= e(trim($hallazgo['sql'])) ?></pre>
                    </details>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
