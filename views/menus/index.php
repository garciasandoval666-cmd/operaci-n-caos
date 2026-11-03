<?php
/** @var array $menus */
/** @var int $platillos */
use App\Core\Database;
$catalogo = Database::pdo()->query('SELECT id_platillo, nombre, tipo FROM platillos WHERE activo = 1 ORDER BY tipo, nombre')->fetchAll();
$activos = count(array_filter($menus, static fn (array $m): bool => (int) $m['activo'] === 1));
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Módulo B · Banquetes</p>
        <h1>Menús y banquetes</h1>
        <p class="page-head__sub"><?= $activos ?> menús activos · <?= (int) $platillos ?> platillos en recetario. El precio por persona se congela al aceptar la cotización.</p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--claro" href="<?= e(url('/menus/platillos')) ?>"><?= icono('cubiertos') ?> Platillos</a>
        <a class="btn btn--lima" href="<?= e(url('/reportes/produccion')) ?>"><?= icono('reportes') ?> Producción</a>
    </div>
</div>

<div class="grid-detalle">
    <div class="stack">
        <section class="card">
            <div class="card__head">
                <div><h2>Catálogo de menús</h2><p>Precio por persona, mínimo de personas y platillos incluidos.</p></div>
                <span class="badge badge--neutro"><?= count($menus) ?> menús</span>
            </div>
            <?php if ($menus === []): ?>
                <div class="empty">
                    <div class="empty__icono"><?= icono('cubiertos') ?></div>
                    <h3>Sin menús registrados</h3>
                    <p>Crea el primer menú para poder contratarlo en los eventos.</p>
                </div>
            <?php else: ?>
                <div class="tabla-wrap">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>Menú</th>
                                <th>Servicio</th>
                                <th class="num">Precio persona</th>
                                <th class="num">Mínimo</th>
                                <th class="num">Platillos</th>
                                <th class="num">Eventos</th>
                                <th class="num">Personas</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($menus as $menu): ?>
                            <tr>
                                <td>
                                    <b><?= e($menu['nombre']) ?></b>
                                    <span class="texto-muted texto-xs"><?= e(recortar($menu['descripcion'] ?? '', 70)) ?></span>
                                </td>
                                <td class="texto-sm"><?= e(ucfirst(str_replace('_', ' ', $menu['tipo_servicio']))) ?></td>
                                <td class="num"><b><?= e(money($menu['precio_persona'])) ?></b></td>
                                <td class="num"><?= (int) $menu['minimo_personas'] ?></td>
                                <td class="num"><?= (int) $menu['platillos'] ?></td>
                                <td class="num"><?= (int) $menu['eventos'] ?></td>
                                <td class="num"><?= (int) $menu['personas'] ?></td>
                                <td><?= badge_estado((int) $menu['activo'] === 1 ? 'activo' : 'inactivo') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="card">
            <div class="card__head"><div><h2>Cómo se usa un menú</h2><p>Del catálogo a la hoja de producción.</p></div></div>
            <div class="card__body">
                <div class="pasos">
                    <div class="paso">
                        <span class="paso__num">1</span>
                        <h3>Se contrata en el evento</h3>
                        <p>Se elige el menú y el número de personas, validando el aforo y el mínimo del menú.</p>
                    </div>
                    <div class="paso">
                        <span class="paso__num">2</span>
                        <h3>Se cotiza</h3>
                        <p>El precio por persona se copia a la cotización como precio de catálogo y se congela al aceptar.</p>
                    </div>
                    <div class="paso">
                        <span class="paso__num">3</span>
                        <h3>Se produce</h3>
                        <p>Cocina recibe la hoja con platillos, alérgenos, restricciones e insumos apartados.</p>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <aside class="grid-detalle__lado">
        <?php if (puede(['administrador', 'banquetes'])): ?>
            <section class="card">
                <div class="card__head"><div><h2>Nuevo menú</h2><p>Se agrega al catálogo activo.</p></div></div>
                <div class="card__body">
                    <form class="formulario" method="post" action="<?= e(url('/menus')) ?>">
                        <?= csrf_field() ?>
                        <div class="campo"><label for="nombre">Nombre <span class="campo__req">*</span></label><input class="input" type="text" id="nombre" name="nombre" maxlength="100" required></div>
                        <div class="campo">
                            <label for="tipo_servicio">Tipo de servicio <span class="campo__req">*</span></label>
                            <select class="select" id="tipo_servicio" name="tipo_servicio" required>
                                <option value="plato_servido">Plato servido</option>
                                <option value="buffet">Buffet</option>
                                <option value="coctel">Cóctel</option>
                                <option value="coffee_break">Coffee break</option>
                                <option value="barra_libre">Barra libre</option>
                            </select>
                        </div>
                        <div class="campo"><label for="descripcion">Descripción</label><textarea class="textarea" id="descripcion" name="descripcion" maxlength="255" rows="2"></textarea></div>
                        <div class="campo-fila">
                            <div class="campo"><label for="precio_persona">Precio persona <span class="campo__req">*</span></label><input class="input" type="number" id="precio_persona" name="precio_persona" min="0" step="0.01" required></div>
                            <div class="campo"><label for="minimo_personas">Mínimo personas</label><input class="input" type="number" id="minimo_personas" name="minimo_personas" min="1" value="50"></div>
                        </div>
                        <?php if ($catalogo !== []): ?>
                            <div class="campo mt-2">
                                <label>Platillos incluidos</label>
                                <div class="stack" style="gap:6px;max-height:220px;overflow:auto">
                                    <?php foreach ($catalogo as $platillo): ?>
                                        <label class="campo-check">
                                            <input type="checkbox" name="platillos[]" value="<?= (int) $platillo['id_platillo'] ?>">
                                            <span><?= e($platillo['nombre']) ?> <span class="texto-muted texto-xs">· <?= e(str_replace('_', ' ', $platillo['tipo'])) ?></span></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <button class="btn btn--lima btn--bloque mt-3" type="submit"><?= icono('mas') ?> Crear menú</button>
                    </form>
                </div>
            </section>
        <?php endif; ?>

        <section class="card">
            <div class="card__head"><div><h2>Tipos de servicio</h2><p>Definen montaje y tiempos.</p></div></div>
            <div class="card__body">
                <div class="lista-datos">
                    <div class="lista-datos__fila"><span>Plato servido</span><b>Montaje formal</b></div>
                    <div class="lista-datos__fila"><span>Buffet</span><b>Estaciones</b></div>
                    <div class="lista-datos__fila"><span>Cóctel</span><b>Bocadillos</b></div>
                    <div class="lista-datos__fila"><span>Coffee break</span><b>Pausas</b></div>
                    <div class="lista-datos__fila"><span>Barra libre</span><b>Bebidas</b></div>
                </div>
            </div>
        </section>
    </aside>
</div>
