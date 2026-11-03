<?php
/** @var string $q */
/** @var array $resultados */
$totalResultados = array_sum(array_map('count', $resultados));
$grupos = [
    'eventos'      => ['titulo' => 'Eventos', 'icono' => 'calendario', 'url' => '/eventos/'],
    'clientes'     => ['titulo' => 'Clientes', 'icono' => 'clientes', 'url' => '/clientes/'],
    'cotizaciones' => ['titulo' => 'Cotizaciones', 'icono' => 'cotizacion', 'url' => '/cotizaciones/'],
    'invitados'    => ['titulo' => 'Invitados', 'icono' => 'invitados', 'url' => '/invitados'],
];
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Búsqueda global</p>
        <h1>Encuentra cualquier registro</h1>
        <p class="page-head__sub">Un solo buscador para eventos, clientes, cotizaciones e invitados. Escribe al menos dos caracteres.</p>
    </div>
</div>

<form class="toolbar" method="get" action="<?= e(url('/buscar')) ?>">
    <div class="campo campo--buscar">
        <label for="q">Término de búsqueda</label>
        <input class="input" type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Nombre, correo, folio, evento…" autofocus>
    </div>
    <div class="toolbar__acciones">
        <button class="btn btn--primario" type="submit"><?= icono('buscar') ?> Buscar</button>
        <?php if ($q !== ''): ?>
            <a class="btn btn--fantasma" href="<?= e(url('/buscar')) ?>">Limpiar</a>
        <?php endif; ?>
    </div>
</form>

<?php if ($q === ''): ?>
    <section class="card">
        <div class="empty">
            <div class="empty__icono"><?= icono('buscar') ?></div>
            <h3>¿Qué estás buscando?</h3>
            <p>Prueba con el nombre de un cliente, el folio de una cotización o el nombre de un evento.</p>
        </div>
    </section>
<?php elseif (mb_strlen($q) < 2): ?>
    <div class="aviso-inline aviso-inline--info"><?= icono('alerta') ?> <span>Escribe al menos dos caracteres para buscar.</span></div>
<?php elseif ($totalResultados === 0): ?>
    <section class="card">
        <div class="empty">
            <div class="empty__icono"><?= icono('buscar') ?></div>
            <h3>Sin resultados para “<?= e($q) ?>”</h3>
            <p>Revisa la ortografía o intenta con otro término. También puedes buscar por folio o número de evento.</p>
        </div>
    </section>
<?php else: ?>
    <p class="texto-muted texto-sm mb-2"><?= $totalResultados ?> resultado<?= $totalResultados === 1 ? '' : 's' ?> en total.</p>

    <div class="grid grid-2">
        <?php foreach ($grupos as $clave => $grupo): ?>
            <?php $filas = $resultados[$clave] ?? []; ?>
            <section class="card">
                <div class="card__head">
                    <div>
                        <h2><?= icono($grupo['icono']) ?> <?= e($grupo['titulo']) ?></h2>
                        <p><?= count($filas) ?> coincidencia<?= count($filas) === 1 ? '' : 's' ?>.</p>
                    </div>
                </div>
                <div class="card__body card__body--sin">
                    <?php if ($filas === []): ?>
                        <div class="card__body"><p class="texto-muted texto-sm">Sin coincidencias en este grupo.</p></div>
                    <?php else: ?>
                        <div class="buscador-resultados">
                            <?php foreach ($filas as $fila): ?>
                                <?php if ($clave === 'eventos'): ?>
                                    <a class="resultado" href="<?= e(url('/eventos/' . (int) $fila['id_evento'])) ?>">
                                        <span class="resultado__icono"><?= icono('calendario') ?></span>
                                        <span>
                                            <b><?= e($fila['nombre']) ?></b>
                                            <span><?= e($fila['cliente']) ?> · <?= e(fecha($fila['fecha_inicio'], 'd M Y')) ?></span>
                                        </span>
                                        <span class="resultado__tipo"><?= badge_estado($fila['estado']) ?></span>
                                    </a>
                                <?php elseif ($clave === 'clientes'): ?>
                                    <a class="resultado" href="<?= e(url('/clientes/' . (int) $fila['id_cliente'])) ?>">
                                        <span class="resultado__icono"><?= e(iniciales($fila['nombre'])) ?></span>
                                        <span>
                                            <b><?= e($fila['nombre']) ?></b>
                                            <span><?= e($fila['correo']) ?></span>
                                        </span>
                                        <span class="resultado__tipo"><?= e(ucfirst($fila['tipo_cliente'])) ?></span>
                                    </a>
                                <?php elseif ($clave === 'cotizaciones'): ?>
                                    <a class="resultado" href="<?= e(url('/cotizaciones/' . (int) $fila['id_cotizacion'])) ?>">
                                        <span class="resultado__icono"><?= icono('cotizacion') ?></span>
                                        <span>
                                            <b><?= e($fila['folio']) ?> · <?= e(money($fila['total'])) ?></b>
                                            <span><?= e($fila['evento']) ?></span>
                                        </span>
                                        <span class="resultado__tipo"><?= badge_estado($fila['estado']) ?></span>
                                    </a>
                                <?php else: ?>
                                    <a class="resultado" href="<?= e(url('/eventos/' . (int) $fila['id_evento'] . '/invitados')) ?>">
                                        <span class="resultado__icono"><?= icono('invitados') ?></span>
                                        <span>
                                            <b><?= e($fila['nombre']) ?></b>
                                            <span><?= e($fila['evento']) ?><?= $fila['correo'] ? ' · ' . e($fila['correo']) : '' ?></span>
                                        </span>
                                        <span class="resultado__tipo"><?= badge_estado($fila['estado_rsvp']) ?></span>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
