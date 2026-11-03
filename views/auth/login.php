<?php
/** @var string $destino */
/** @var string $flash */
$esLocal = (string) env('APP_ENV', 'production') === 'local';
?>
<div class="auth">
    <section class="auth__visual">
        <a class="auth__marca" href="<?= e(url('/')) ?>">
            <span class="monograma">CE</span>
            <span>
                <b>ConectaEventos</b>
                <span>Recinto &amp; productora</span>
            </span>
        </a>

        <div>
            <p class="eyebrow">Operación Caos Digital</p>
            <h2 class="auth__claim">Cuatro módulos.<br>Una sola <em>verdad operativa.</em></h2>
            <p class="auth__texto">Clientes, espacios, cocina, cotizaciones y cobranza comparten la misma base de datos. Lo que se aparta aquí se descuenta allá, sin capturas duplicadas.</p>
            <ul class="auth__lista">
                <li><?= icono('check') ?> Disponibilidad de espacios y recursos sin traslapes</li>
                <li><?= icono('check') ?> Cotizaciones con precios congelados al aceptar</li>
                <li><?= icono('check') ?> Pagos, ajustes y devoluciones con saldo real</li>
                <li><?= icono('check') ?> Bitácora inmutable y reportes de integridad</li>
            </ul>
        </div>

        <div class="fila fila--entre" style="color:#8f98aa;font-size:11px">
            <span>Ciudad de México · 2026</span>
            <a href="<?= e(url('/boda')) ?>" style="color:#c9f24d">Bodas <?= icono('flecha') ?></a>
        </div>
    </section>

    <section class="auth__form">
        <div class="auth__caja">
            <a href="<?= e(url('/')) ?>" class="texto-muted texto-xs" style="display:inline-flex;align-items:center;gap:6px;margin-bottom:18px">
                <?= icono('atras') ?> Volver al sitio
            </a>

            <h1>Iniciar sesión</h1>
            <p>Accede con tu cuenta del equipo para operar eventos, recursos y cobranza.</p>

            <?= $flash ?>

            <form class="formulario" method="post" action="<?= e(url('/login')) ?>" autocomplete="on">
                <?= csrf_field() ?>
                <input type="hidden" name="destino" value="<?= e($destino) ?>">

                <div class="campo">
                    <label for="correo">Correo corporativo</label>
                    <input class="input" id="correo" name="correo" type="email" required autofocus
                           autocomplete="username" maxlength="120"
                           value="<?= viejo('correo') ?>" placeholder="nombre@conectaeventos.mx">
                </div>

                <div class="campo">
                    <label for="clave">Contraseña</label>
                    <input class="input" id="clave" name="clave" type="password" required
                           autocomplete="current-password" minlength="8" placeholder="••••••••">
                </div>

                <button class="btn btn--lima btn--bloque" type="submit"><?= icono('llave') ?> Entrar al panel</button>
            </form>

            <?php if ($esLocal): ?>
                <div class="auth__demo">
                    <b>Acceso de demostración</b>
                    <p>Administración: <code>admin@conectaeventos.mx</code></p>
                    <p>Contraseña para todas las cuentas demo: <code>Conecta2026*</code></p>
                    <p class="texto-muted" style="font-size:11px;margin-top:8px">También hay cuentas demo para comercial, coordinación, banquetes, operaciones, recepción, finanzas y auditoría.</p>
                </div>
            <?php endif; ?>

            <p class="texto-muted texto-xs" style="margin-top:20px">
                ¿Problemas para entrar? Escribe a <a href="mailto:sistemas@conectaeventos.mx" style="color:var(--ink);text-decoration:underline">sistemas@conectaeventos.mx</a>.
                La cuenta se bloquea temporalmente tras varios intentos fallidos.
            </p>
        </div>
    </section>
</div>
