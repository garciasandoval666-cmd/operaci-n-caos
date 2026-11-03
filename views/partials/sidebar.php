<?php
/** @var array|null $usuario */
$u = usuario_actual();
?>
<aside class="sidebar" id="sidebar">
    <a class="sidebar__marca" href="<?= e(url('/panel')) ?>">
        <span class="monograma">CE</span>
        <span>
            <b>ConectaEventos</b>
            <span>Operación integrada</span>
        </span>
    </a>

    <nav class="sidebar__nav" aria-label="Menú principal">
        <div class="nav-grupo">
            <p class="nav-grupo__titulo">Operación</p>
            <a class="nav-item<?= nav_activo('/panel', '/buscar') ?>" href="<?= e(url('/panel')) ?>"><?= icono('panel') ?> Panel</a>
            <a class="nav-item<?= nav_activo('/eventos') ?>" href="<?= e(url('/eventos')) ?>"><?= icono('calendario') ?> Eventos</a>
            <?php if (puede(roles_grupo('clientes'))): ?>
                <a class="nav-item<?= nav_activo('/clientes') ?>" href="<?= e(url('/clientes')) ?>"><?= icono('clientes') ?> Clientes</a>
            <?php endif; ?>
            <a class="nav-item<?= nav_activo('/invitados') ?>" href="<?= e(url('/invitados')) ?>"><?= icono('invitados') ?> Invitados</a>
            <?php if (puede(['administrador', 'coordinador', 'recepcion'])): ?>
                <a class="nav-item<?= nav_activo('/checkin') ?>" href="<?= e(url('/checkin')) ?>"><?= icono('check') ?> Check-in</a>
            <?php endif; ?>
        </div>

        <div class="nav-grupo">
            <p class="nav-grupo__titulo">Recursos y producción</p>
            <a class="nav-item<?= nav_activo('/espacios') ?>" href="<?= e(url('/espacios')) ?>"><?= icono('espacios') ?> Espacios</a>
            <a class="nav-item<?= nav_activo('/recursos') ?>" href="<?= e(url('/recursos')) ?>"><?= icono('recursos') ?> Recursos</a>
            <a class="nav-item<?= nav_activo('/proveedores') ?>" href="<?= e(url('/proveedores')) ?>"><?= icono('proveedores') ?> Proveedores</a>
            <a class="nav-item<?= nav_activo('/reservas') ?>" href="<?= e(url('/reservas')) ?>"><?= icono('reservas') ?> Reservas</a>
            <?php if (puede(roles_grupo('insumos'))): ?>
                <a class="nav-item<?= nav_activo('/insumos') ?>" href="<?= e(url('/insumos')) ?>"><?= icono('insumos') ?> Insumos</a>
            <?php endif; ?>
            <a class="nav-item<?= nav_activo('/menus') ?>" href="<?= e(url('/menus')) ?>"><?= icono('cubiertos') ?> Menús</a>
        </div>

        <?php if (puede(roles_grupo('dinero'))): ?>
        <div class="nav-grupo">
            <p class="nav-grupo__titulo">Comercial y finanzas</p>
            <a class="nav-item<?= nav_activo('/cotizaciones') ?>" href="<?= e(url('/cotizaciones')) ?>"><?= icono('cotizacion') ?> Cotizaciones</a>
            <a class="nav-item<?= nav_activo('/pagos') ?>" href="<?= e(url('/pagos')) ?>"><?= icono('pagos') ?> Pagos</a>
        </div>
        <?php endif; ?>

        <div class="nav-grupo">
            <p class="nav-grupo__titulo">Control</p>
            <?php if (puede(['administrador', 'finanzas', 'auditor', 'coordinador'])): ?>
                <a class="nav-item<?= nav_activo('/reportes') ?>" href="<?= e(url('/reportes')) ?>"><?= icono('reportes') ?> Reportes</a>
            <?php endif; ?>
            <?php if (puede(['administrador', 'auditor'])): ?>
                <a class="nav-item<?= nav_activo('/bitacora') ?>" href="<?= e(url('/bitacora')) ?>"><?= icono('bitacora') ?> Bitácora</a>
            <?php endif; ?>
            <?php if (puede('administrador')): ?>
                <a class="nav-item<?= nav_activo('/usuarios') ?>" href="<?= e(url('/usuarios')) ?>"><?= icono('usuarios') ?> Usuarios</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="sidebar__pie">
        <?php if ($u): ?>
            <div class="usuario-mini-fila">
                <a class="usuario-mini" href="<?= e(url('/panel')) ?>">
                    <span class="usuario-mini__avatar"><?= e(iniciales($u['nombre'])) ?></span>
                    <span class="usuario-mini__datos">
                        <b><?= e($u['nombre']) ?></b>
                        <span><?= e(App\Core\Auth::ROLES[$u['rol']] ?? $u['rol']) ?></span>
                    </span>
                </a>
                <form class="usuario-mini__forma" method="post" action="<?= e(url('/logout')) ?>">
                    <?= csrf_field() ?>
                    <button class="usuario-mini__salir" type="submit" title="Cerrar sesión" aria-label="Cerrar sesión"><?= icono('salir') ?></button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</aside>
