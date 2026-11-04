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
                                <th>Suministra</th>
                                <th>Contacto</th>
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
                                        <?= sello_proveedor($proveedor, 38) ?>
                                        <span>
                                            <b>
                                                <?= e($proveedor['nombre']) ?>
                                                <?php if (!empty($proveedor['sitio_web'])): ?>
                                                    <a class="enlace-sitio" href="<?= e($proveedor['sitio_web']) ?>" target="_blank" rel="noopener noreferrer nofollow" title="Abrir su sitio web"><?= icono('externo') ?><span class="sr-only">Sitio de <?= e($proveedor['nombre']) ?></span></a>
                                                <?php endif; ?>
                                            </b>
                                            <span><?= e($proveedor['descripcion'] ?: 'Sin descripción registrada') ?></span>
                                        </span>
                                    </div>
                                </td>
                                <td class="texto-sm">
                                    <?= badge(categoria_proveedor((string) $proveedor['categoria']), 'neutro') ?>
                                    <?php if ($proveedor['desde'] !== null): ?>
                                        <span class="texto-muted texto-xs" style="display:block;margin-top:4px">Aliado desde <?= (int) $proveedor['desde'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="texto-sm">
                                    <?php if ($proveedor['contacto'] || $proveedor['telefono'] || $proveedor['correo']): ?>
                                        <div class="celda-contacto">
                                            <?php if ($proveedor['contacto']): ?><b><?= e($proveedor['contacto']) ?></b><?php endif; ?>
                                            <?php if ($proveedor['telefono']): ?><span><?= e($proveedor['telefono']) ?></span><?php endif; ?>
                                            <?php if ($proveedor['correo']): ?><a href="mailto:<?= e($proveedor['correo']) ?>"><?= e($proveedor['correo']) ?></a><?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="texto-muted">—</span>
                                    <?php endif; ?>
                                </td>
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
                    <div class="mini-kpi"><?= icono('proveedores') ?><div><b><?= count(array_unique(array_column($proveedores, 'categoria'))) ?></b><span>categorías cubiertas</span></div></div>
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
                        <div class="campo-fila">
                            <div class="campo">
                                <label for="categoria">Qué suministra</label>
                                <select class="select" id="categoria" name="categoria">
                                    <?php foreach (['mobiliario', 'personal', 'audio_video', 'decoracion', 'transporte', 'otro'] as $cat): ?>
                                        <option value="<?= e($cat) ?>" <?= $cat === 'otro' ? 'selected' : '' ?>><?= e(categoria_proveedor($cat)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="campo">
                                <label for="desde">Aliado desde</label>
                                <input class="input" type="number" id="desde" name="desde" min="1950" max="<?= (int) date('Y') ?>" placeholder="<?= (int) date('Y') ?>">
                            </div>
                        </div>
                        <div class="campo">
                            <label for="descripcion">Descripción</label>
                            <input class="input" type="text" id="descripcion" name="descripcion" maxlength="160" placeholder="Mobiliario de banquete, mantelería y montaje">
                            <span class="campo__ayuda">Es la línea que acompaña a su nombre en el sitio público.</span>
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
                        <div class="campo">
                            <label for="logo">Logotipo</label>
                            <select class="select" id="logo" name="logo">
                                <option value="">Sin logotipo · se usa el monograma</option>
                                <?php foreach (logos_disponibles() as $archivo): ?>
                                    <option value="<?= e($archivo) ?>"><?= e(ucfirst(str_replace(['-', '.svg'], [' ', ''], $archivo))) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="campo__ayuda">Los archivos viven en <code>assets/img/proveedores/</code>. Sin logotipo, el sello se dibuja con las iniciales.</span>
                        </div>
                        <div class="campo-fila">
                            <div class="campo">
                                <label for="sitio_web">Sitio web</label>
                                <input class="input" type="url" id="sitio_web" name="sitio_web" maxlength="160" placeholder="https://proveedor.mx">
                            </div>
                            <div class="campo">
                                <label for="color_marca">Color de marca</label>
                                <input class="input input--color" type="color" id="color_marca" name="color_marca" value="#1f6b4f">
                                <span class="campo__ayuda">Pinta su sello en el directorio y en el sitio.</span>
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
