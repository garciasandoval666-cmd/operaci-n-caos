<?php
/** @var array $usuarios */
/** @var array $roles */
/** @var array $stats */
?>
<div class="page-head">
    <div>
        <p class="page-head__eyebrow">Módulo D · Accesos</p>
        <h1>Usuarios y roles</h1>
        <p class="page-head__sub">Cada persona entra con su propia cuenta. Los permisos se asignan por función y toda alta o cambio queda en la bitácora.</p>
    </div>
    <div class="page-head__acciones">
        <a class="btn btn--claro" href="<?= e(url('/bitacora')) ?>"><?= icono('bitacora') ?> Bitácora</a>
        <a class="btn btn--fantasma" href="<?= e(url('/reportes/auditoria')) ?>"><?= icono('reportes') ?> Auditoría</a>
    </div>
</div>

<div class="kpis">
    <article class="kpi kpi--oscura">
        <p class="kpi__top"><?= icono('usuarios') ?> Cuentas</p>
        <p class="kpi__valor"><?= (int) $stats['total'] ?></p>
        <p class="kpi__nota">registradas en el sistema</p>
    </article>
    <article class="kpi kpi--lima">
        <p class="kpi__top"><?= icono('check') ?> Activas</p>
        <p class="kpi__valor"><?= (int) $stats['activos'] ?></p>
        <p class="kpi__nota">pueden iniciar sesión hoy</p>
    </article>
    <article class="kpi">
        <p class="kpi__top"><?= icono('llave') ?> Roles</p>
        <p class="kpi__valor"><?= (int) $stats['roles'] ?></p>
        <p class="kpi__nota">perfiles de permiso definidos</p>
    </article>
    <article class="kpi">
        <p class="kpi__top"><?= icono('candado') ?> Inactivas</p>
        <p class="kpi__valor"><?= (int) $stats['total'] - (int) $stats['activos'] ?></p>
        <p class="kpi__nota">conservan historial, sin acceso</p>
    </article>
</div>

<div class="grid-detalle">
    <div class="stack">
        <section class="card">
            <div class="card__head">
                <div>
                    <h2>Equipo con acceso</h2>
                    <p>Desactiva una cuenta para retirar el acceso sin borrar su historial.</p>
                </div>
                <div class="card__head__acciones">
                    <?= badge(count($usuarios) . ' cuentas', 'neutro') ?>
                </div>
            </div>

            <?php if ($usuarios === []): ?>
                <div class="empty">
                    <div class="empty__icono"><?= icono('usuarios') ?></div>
                    <h3>Sin usuarios</h3>
                    <p>Crea la primera cuenta con el formulario lateral para empezar a operar el sistema.</p>
                </div>
            <?php else: ?>
                <div class="tabla-wrap">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Contacto</th>
                                <th class="num">Acciones</th>
                                <th>Última actividad</th>
                                <th>Estado</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($usuarios as $filaUsuario): ?>
                            <tr>
                                <td>
                                    <div class="tabla__principal">
                                        <span class="tabla__avatar"><?= e(iniciales($filaUsuario['nombre'])) ?></span>
                                        <span>
                                            <b><?= e($filaUsuario['nombre']) ?></b>
                                            <span><?= e($filaUsuario['correo']) ?></span>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <b class="texto-sm"><?= e($filaUsuario['rol']) ?></b>
                                    <span class="texto-muted texto-xs"><?= e(recortar((string) $filaUsuario['rol_descripcion'], 46)) ?></span>
                                </td>
                                <td class="texto-sm"><?= e($filaUsuario['telefono'] ?? '—') ?></td>
                                <td class="num"><?= (int) $filaUsuario['acciones'] ?></td>
                                <td class="texto-sm">
                                    <?= $filaUsuario['ultima_accion'] ? e(fecha_hora($filaUsuario['ultima_accion'])) : '<span class="texto-muted">Sin actividad</span>' ?>
                                </td>
                                <td><?= badge_estado((int) $filaUsuario['activo'] === 1 ? 'activo' : 'inactivo') ?></td>
                                <td>
                                    <div class="acciones">
                                        <a class="btn btn--fantasma btn--xs" href="#usuario-<?= (int) $filaUsuario['id_usuario'] ?>" data-editar-usuario="<?= (int) $filaUsuario['id_usuario'] ?>">
                                            <?= icono('editar') ?> Editar
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="card">
            <div class="card__head">
                <div>
                    <h2>Editar cuentas</h2>
                    <p>Cambia rol, datos de contacto o restablece la contraseña. Deja la contraseña vacía para conservarla.</p>
                </div>
            </div>
            <div class="card__body">
                <div class="stack">
                    <?php foreach ($usuarios as $filaUsuario): ?>
                        <details id="usuario-<?= (int) $filaUsuario['id_usuario'] ?>" class="usuario-editor">
                            <summary>
                                <span class="tabla__avatar"><?= e(iniciales($filaUsuario['nombre'])) ?></span>
                                <span>
                                    <b><?= e($filaUsuario['nombre']) ?></b>
                                    <span><?= e($filaUsuario['correo']) ?> · <?= e($filaUsuario['rol']) ?></span>
                                </span>
                                <?= badge_estado((int) $filaUsuario['activo'] === 1 ? 'activo' : 'inactivo') ?>
                                <?= icono('abajo') ?>
                            </summary>
                            <form class="formulario" method="post" action="<?= e(url('/usuarios/' . (int) $filaUsuario['id_usuario'])) ?>">
                                <?= csrf_field() ?>
                                <div class="campo-fila">
                                    <div class="campo">
                                        <label for="nombre-<?= (int) $filaUsuario['id_usuario'] ?>">Nombre completo <span class="campo__req">*</span></label>
                                        <input class="input" id="nombre-<?= (int) $filaUsuario['id_usuario'] ?>" name="nombre" type="text" required minlength="3" maxlength="120" value="<?= e($filaUsuario['nombre']) ?>">
                                    </div>
                                    <div class="campo">
                                        <label for="correo-<?= (int) $filaUsuario['id_usuario'] ?>">Correo <span class="campo__req">*</span></label>
                                        <input class="input" id="correo-<?= (int) $filaUsuario['id_usuario'] ?>" name="correo" type="email" required maxlength="120" value="<?= e($filaUsuario['correo']) ?>">
                                    </div>
                                </div>
                                <div class="campo-fila">
                                    <div class="campo">
                                        <label for="rol-<?= (int) $filaUsuario['id_usuario'] ?>">Rol <span class="campo__req">*</span></label>
                                        <select class="select" id="rol-<?= (int) $filaUsuario['id_usuario'] ?>" name="id_rol" required>
                                            <?php foreach ($roles as $rol): ?>
                                                <option value="<?= (int) $rol['id_rol'] ?>" <?= (int) $rol['id_rol'] === (int) $filaUsuario['id_rol'] ? 'selected' : '' ?>>
                                                    <?= e($rol['nombre']) ?> · <?= e(recortar((string) $rol['descripcion'], 52)) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="campo">
                                        <label for="telefono-<?= (int) $filaUsuario['id_usuario'] ?>">Teléfono</label>
                                        <input class="input" id="telefono-<?= (int) $filaUsuario['id_usuario'] ?>" name="telefono" type="tel" maxlength="25" value="<?= e($filaUsuario['telefono'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="campo">
                                    <label for="clave-<?= (int) $filaUsuario['id_usuario'] ?>">Nueva contraseña</label>
                                    <input class="input" id="clave-<?= (int) $filaUsuario['id_usuario'] ?>" name="clave" type="password" minlength="10" autocomplete="new-password" placeholder="Dejar vacío para conservar la actual">
                                    <span class="campo__ayuda">Mínimo 10 caracteres. Compártela por un canal seguro y pide cambiarla al primer ingreso.</span>
                                </div>
                                <label class="campo-check">
                                    <input type="checkbox" name="activo" value="1" <?= (int) $filaUsuario['activo'] === 1 ? 'checked' : '' ?>>
                                    <span>Cuenta activa · puede iniciar sesión</span>
                                </label>
                                <div class="fila fila--entre">
                                    <span class="texto-muted texto-xs"><?= (int) $filaUsuario['acciones'] ?> acciones registradas</span>
                                    <button class="btn btn--primario btn--sm" type="submit"><?= icono('check') ?> Guardar cambios</button>
                                </div>
                            </form>
                        </details>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </div>

    <aside class="grid-detalle__lado">
        <section class="card">
            <div class="card__head">
                <div>
                    <h2>Nueva cuenta</h2>
                    <p>Alta con contraseña temporal.</p>
                </div>
            </div>
            <form class="card__body formulario" method="post" action="<?= e(url('/usuarios')) ?>">
                <?= csrf_field() ?>
                <div class="campo">
                    <label for="nuevo-nombre">Nombre completo <span class="campo__req">*</span></label>
                    <input class="input" id="nuevo-nombre" name="nombre" type="text" required minlength="3" maxlength="120" value="<?= viejo('nombre') ?>" placeholder="Nombre y apellidos">
                </div>
                <div class="campo">
                    <label for="nuevo-correo">Correo <span class="campo__req">*</span></label>
                    <input class="input" id="nuevo-correo" name="correo" type="email" required maxlength="120" value="<?= viejo('correo') ?>" placeholder="persona@conectaeventos.mx">
                </div>
                <div class="campo">
                    <label for="nuevo-rol">Rol <span class="campo__req">*</span></label>
                    <select class="select" id="nuevo-rol" name="id_rol" required>
                        <option value="">Selecciona un rol</option>
                        <?php foreach ($roles as $rol): ?>
                            <option value="<?= (int) $rol['id_rol'] ?>" <?= (string) $rol['id_rol'] === viejo('id_rol') ? 'selected' : '' ?>>
                                <?= e($rol['nombre']) ?> (<?= (int) $rol['usuarios'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo">
                    <label for="nuevo-telefono">Teléfono</label>
                    <input class="input" id="nuevo-telefono" name="telefono" type="tel" maxlength="25" value="<?= viejo('telefono') ?>" placeholder="+52 55 0000 0000">
                </div>
                <div class="campo">
                    <label for="nuevo-clave">Contraseña temporal <span class="campo__req">*</span></label>
                    <input class="input" id="nuevo-clave" name="clave" type="password" required minlength="10" autocomplete="new-password">
                    <span class="campo__ayuda">Mínimo 10 caracteres. No se guarda en texto plano.</span>
                </div>
                <button class="btn btn--lima btn--bloque" type="submit"><?= icono('mas') ?> Crear usuario</button>
            </form>
        </section>

        <section class="card">
            <div class="card__head">
                <div>
                    <h2>Roles del sistema</h2>
                    <p>Alcance de cada perfil.</p>
                </div>
            </div>
            <div class="card__body card__body--sin">
                <table class="tabla tabla--compacta">
                    <tbody>
                    <?php foreach ($roles as $rol): ?>
                        <tr>
                            <td>
                                <b class="texto-sm"><?= e($rol['nombre']) ?></b>
                                <span class="texto-muted texto-xs" style="display:block"><?= e(recortar((string) $rol['descripcion'], 60)) ?></span>
                            </td>
                            <td class="num"><span class="badge badge--neutro"><?= (int) $rol['usuarios'] ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card__foot">
                <span class="texto-muted texto-xs">Los permisos se validan en el servidor en cada ruta, no solo en el menú.</span>
            </div>
        </section>

        <section class="card">
            <div class="card__body">
                <div class="aviso-inline"><?= icono('candado') ?> <span>No compartas contraseñas por correo ni chat. Usa el gestor de contraseñas del equipo y rota las claves temporales.</span></div>
            </div>
        </section>
    </aside>
</div>
