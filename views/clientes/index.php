<?php
/** @var array $clientes */
/** @var int $total */
/** @var string $q */
/** @var string $estado */
/** @var int $porPagina */
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Módulo A · Relación comercial</p>
        <h1>Clientes</h1>
        <p class="page-head__sub">Personas y empresas con historial de eventos, cotizaciones y saldos. Un cliente inactivo conserva todo su expediente.</p>
    </div>
    <div class="page-head__acciones">
        <?php if (puede(['administrador', 'comercial'])): ?>
            <a class="btn btn--lima" href="<?= e(url('/clientes/crear')) ?>"><?= icono('mas') ?> Nuevo cliente</a>
        <?php endif; ?>
    </div>
</div>

<form class="toolbar" method="get" action="<?= e(url('/clientes')) ?>">
    <div class="campo campo--buscar">
        <label for="q">Buscar</label>
        <input class="input" type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Nombre, correo o razón social">
    </div>
    <div class="campo">
        <label for="estado">Estado</label>
        <select class="select" id="estado" name="estado" data-autoenviar>
            <option value="">Todos</option>
            <option value="activo" <?= $estado === 'activo' ? 'selected' : '' ?>>Activos</option>
            <option value="inactivo" <?= $estado === 'inactivo' ? 'selected' : '' ?>>Inactivos</option>
        </select>
    </div>
    <div class="toolbar__acciones">
        <button class="btn btn--primario" type="submit"><?= icono('buscar') ?> Filtrar</button>
        <a class="btn btn--fantasma" href="<?= e(url('/clientes')) ?>">Limpiar</a>
    </div>
</form>

<section class="card">
    <div class="card__head">
        <div>
            <h2><?= (int) $total ?> cliente<?= (int) $total === 1 ? '' : 's' ?></h2>
            <p><?= $q !== '' ? 'Resultados para “' . e($q) . '”' : 'Directorio completo ordenado alfabéticamente.' ?></p>
        </div>
    </div>

    <?php if ($clientes === []): ?>
        <div class="empty">
            <div class="empty__icono"><?= icono('clientes') ?></div>
            <h3>Sin clientes que mostrar</h3>
            <p>Ajusta los filtros o registra el primer cliente para comenzar a crear eventos.</p>
            <?php if (puede(['administrador', 'comercial'])): ?>
                <a class="btn btn--lima" href="<?= e(url('/clientes/crear')) ?>"><?= icono('mas') ?> Registrar cliente</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="tabla-wrap">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Contacto</th>
                        <th class="num">Eventos</th>
                        <th class="num">Activos</th>
                        <th class="num">Cotizado</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($clientes as $cliente): ?>
                    <tr>
                        <td>
                            <a class="tabla__principal" href="<?= e(url('/clientes/' . (int) $cliente['id_cliente'])) ?>">
                                <span class="tabla__avatar"><?= e(iniciales($cliente['nombre'])) ?></span>
                                <span>
                                    <b><?= e($cliente['nombre']) ?></b>
                                    <span><?= $cliente['tipo_cliente'] === 'empresa' ? 'Empresa' : 'Persona' ?><?= $cliente['razon_social'] ? ' · ' . e(recortar($cliente['razon_social'], 34)) : '' ?></span>
                                </span>
                            </a>
                        </td>
                        <td>
                            <b class="texto-sm"><?= e($cliente['correo']) ?></b>
                            <span class="texto-muted texto-xs"><?= e($cliente['telefono'] ?? 'Sin teléfono') ?></span>
                        </td>
                        <td class="num"><?= (int) $cliente['eventos'] ?></td>
                        <td class="num"><?= (int) $cliente['activos'] ?></td>
                        <td class="num"><?= e(money($cliente['cotizado'])) ?></td>
                        <td><?= badge_estado($cliente['estado']) ?></td>
                        <td>
                            <div class="acciones">
                                <a class="btn btn--fantasma btn--xs" href="<?= e(url('/clientes/' . (int) $cliente['id_cliente'])) ?>"><?= icono('ver') ?> Ver</a>
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
