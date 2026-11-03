<?php
/** @var array $proveedores */

$activos = count(array_filter($proveedores, static fn (array $p): bool => $p['estado'] === 'activo'));
$conReservas = count(array_filter($proveedores, static fn (array $p): bool => (int) $p['reservas'] > 0));
$totalRecursos = array_sum(array_map(static fn (array $p): int => (int) $p['recursos'], $proveedores));
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Módulo B · Alianzas</p>
        <h1>Proveedores</h1>
        <p class="page-head__sub"><?= e($subtitulo ?? '') ?></p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--claro" href="<?= e(url('/recursos')) ?>"><?= icono('recursos') ?> Inventario</a>
        <a class="btn btn--fantasma" href="<?= e(url('/recursos/disponibilidad')) ?>"><?= icono('calendario') ?> Disponibilidad</a>
    </div>
</div>

<div class="kpis">
    <div class="kpi kpi--oscura">
        <div class="kpi__top"><?= icono('proveedores') ?> Proveedores</div>
        <p class="kpi__valor"><?= count($proveedores) ?></p>
        <p class="kpi__nota"><?= $activos ?> activos en el directorio</p>
    </div>
    <div class="kpi">
        <div class="kpi__top"><?= icono('paquete') ?> Recursos vinculados</div>
        <p class="kpi__valor"><?= $totalRecursos ?></p>
        <p class="kpi__nota">piezas y servicios de catálogo</p>
    </div>
    <div class="kpi kpi--lima">
        <div class="kpi__top"><?= icono('reservas') ?> Con reservas vigentes</div>
        <p class="kpi__valor"><?= $conReservas ?></p>
        <p class="kpi__nota">proveedores comprometidos</p>
    </div>
    <div class="kpi">
        <div class="kpi__top"><?= icono('telefono') ?> Contacto directo</div>
        <p class="kpi__valor"><?= count(array_filter($proveedores, static fn (array $p): bool => !empty($p['telefono']))) ?></p>
        <p class="kpi__nota">con teléfono registrado</p>
    </div>
</div>

<div class="grid-detalle">
    <div class="stack">
        <section class="card">
            <div class="card__head">
                <div><h2>Directorio</h2><p>Cada proveedor agrupa recursos y reservas del inventario.</p></div>
                <span class="badge badge--neutro"><?= count($proveedores) ?> registros</span>
            </div>

            <?php if ($proveedores === []): ?>
                <div class="empty">
                    <div class="empty__icono"><?= icono('proveedores') ?></div>
                    <h3>Sin proveedores registrados</h3>
                    <p>Da de alta a los aliados de mobiliario, audio, staff y decoración.</p>
                </div>
            <?php else: ?>
                <div class="tabla-wrap">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>Proveedor</th>
                                <th>Contacto</th>
                                <th>Teléfono</th>
                                <th>Correo</th>
                                <th class="num">Recursos</th>
                                <th class="num">Reservas</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($proveedores as $proveedor): ?>
                            <tr>
                                <td>
                                    <div class="tabla__principal">
                                        <span class="tabla__avatar"><?= e(iniciales($proveedor['nombre'])) ?></span>
                                        <span><b><?= e($proveedor['nombre']) ?></b><span>Aliado del recinto</span></span>
                                    </div>
                                </td>
                                <td class="texto-sm"><?= $proveedor['contacto'] ? e($proveedor['contacto']) : '<span class="texto-muted">—</span>' ?></td>
                                <td class="texto-sm"><?= $proveedor['telefono'] ? e($proveedor['telefono']) : '<span class="texto-muted">—</span>' ?></td>
                                <td class="texto-sm"><?= $proveedor['correo'] ? '<a href="mailto:' . e($proveedor['correo']) . '">' . e($proveedor['correo']) . '</a>' : '<span class="texto-muted">—</span>' ?></td>
                                <td class="num"><?= (int) $proveedor['recursos'] ?></td>
                                <td class="num"><?= (int) $proveedor['reservas'] ?></td>
                                <td><?= badge_estado($proveedor['estado']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="card">
            <div class="card__head"><div><h2>Por qué importa el vínculo</h2><p>Un recurso sin proveedor es inventario interno; con proveedor, es una alianza con costo y disponibilidad.</p></div></div>
            <div class="card__body">
                <div class="grid grid-3">
                    <div class="mini-kpi"><?= icono('paquete') ?><div><b><?= $totalRecursos ?></b><span>recursos con dueño claro</span></div></div>
                    <div class="mini-kpi"><?= icono('reservas') ?><div><b><?= $conReservas ?></b><span>proveedores en operación</span></div></div>
                    <div class="mini-kpi"><?= icono('candado') ?><div><b>0</b><span>sobreventas permitidas</span></div></div>
                </div>
            </div>
        </section>
    </div>

    <aside class="grid-detalle__lado">
        <?php if (puede(['administrador', 'operaciones'])): ?>
            <section class="card">
                <div class="card__head"><div><h2>Nuevo proveedor</h2><p>Se agrega al directorio activo.</p></div></div>
                <div class="card__body">
                    <form class="formulario" method="post" action="<?= e(url('/proveedores')) ?>">
                        <?= csrf_field() ?>
                        <div class="campo">
                            <label for="nombre">Nombre <span class="campo__req">*</span></label>
                            <input class="input" type="text" id="nombre" name="nombre" maxlength="120" required placeholder="Sillas y Mesas del Valle">
                        </div>
                        <div class="campo">
                            <label for="contacto">Persona de contacto</label>
                            <input class="input" type="text" id="contacto" name="contacto" maxlength="120" placeholder="Nombre y apellido">
                        </div>
                        <div class="campo-fila">
                            <div class="campo">
                                <label for="telefono">Teléfono</label>
                                <input class="input" type="tel" id="telefono" name="telefono" maxlength="25" placeholder="+52 55 0000 0000">
                            </div>
                            <div class="campo">
                                <label for="correo">Correo</label>
                                <input class="input" type="email" id="correo" name="correo" maxlength="120" placeholder="contacto@proveedor.mx">
                            </div>
                        </div>
                        <button class="btn btn--lima btn--bloque" type="submit"><?= icono('mas') ?> Registrar proveedor</button>
                    </form>
                </div>
            </section>
        <?php endif; ?>

        <section class="card">
            <div class="card__head"><div><h2>Checklist de alta</h2><p>Antes de comprometer una fecha.</p></div></div>
            <div class="card__body">
                <div class="timeline">
                    <div class="timeline__item timeline__item--ok"><span class="timeline__punto"><?= icono('check') ?></span><div class="timeline__cuerpo"><b>Datos fiscales y contacto</b><span>Nombre, teléfono y correo verificados.</span></div></div>
                    <div class="timeline__item timeline__item--ok"><span class="timeline__punto"><?= icono('check') ?></span><div class="timeline__cuerpo"><b>Inventario declarado</b><span>Existencias reales por unidad.</span></div></div>
                    <div class="timeline__item"><span class="timeline__punto"><?= icono('reloj') ?></span><div class="timeline__cuerpo"><b>Contrato y penalizaciones</b><span>Se documentan fuera del sistema y se reflejan en la cotización.</span></div></div>
                </div>
            </div>
        </section>
    </aside>
</div>
