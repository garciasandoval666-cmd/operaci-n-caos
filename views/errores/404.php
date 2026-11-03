<?php /** @var string|null $error */ ?>
<div class="error-page">
    <div class="error-page__marca"><span class="monograma">CE</span> ConectaEventos</div>
    <p class="error-page__codigo">404</p>
    <h1>La página se fue antes que los invitados</h1>
    <p>No encontramos lo que buscabas. Puede que el enlace haya cambiado o que el registro ya no exista.</p>
    <div class="fila" style="justify-content:center">
        <a class="btn btn--primario" href="<?= e(url('/panel')) ?>"><?= icono('panel') ?> Volver al panel</a>
        <a class="btn btn--claro" href="<?= e(url('/')) ?>">Ir al sitio</a>
    </div>
</div>
