<?php /** @var string|null $error */ ?>
<div class="error-page">
    <div class="error-page__marca"><span class="monograma">CE</span> ConectaEventos</div>
    <p class="error-page__codigo">419</p>
    <h1>La sesión expiró</h1>
    <p>Por seguridad, el formulario dejó de ser válido. Vuelve a cargar la página e inténtalo de nuevo.</p>
    <div class="fila" style="justify-content:center">
        <a class="btn btn--primario" href="<?= e(url('/login')) ?>"><?= icono('llave') ?> Iniciar sesión</a>
        <?php /* No se usa href="javascript:…": la CSP del .htaccess declara
                 script-src 'self' y el navegador bloquea esos URL, con lo que el
                 boton no hacia nada. Se resuelve con un manejador en app.js. */ ?>
        <button class="btn btn--claro" type="button" data-atras>Regresar</button>
    </div>
</div>
