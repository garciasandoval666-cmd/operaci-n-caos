<?php /** @var string|null $error */ ?>
<div class="error-page">
    <div class="error-page__marca"><span class="monograma"><?= logo() ?></span> <?= e(marca()) ?></div>
    <p class="error-page__codigo">403</p>
    <h1>Esta puerta no se abre con tu llave</h1>
    <p>Tu rol no tiene permiso para esta sección. Si necesitas acceso, solicítalo al administrador del sistema.</p>
    <div class="fila" style="justify-content:center">
        <a class="btn btn--primario" href="<?= e(url('/panel')) ?>"><?= icono('panel') ?> Volver al panel</a>
        <a class="btn btn--claro" href="<?= e(url('/')) ?>">Ir al sitio</a>
    </div>
</div>
