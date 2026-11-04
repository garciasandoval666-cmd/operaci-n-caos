<?php /** @var Throwable|null $error */ ?>
<div class="error-page">
    <div class="error-page__marca"><span class="monograma"><?= logo() ?></span> <?= e(marca()) ?></div>
    <p class="error-page__codigo">500</p>
    <h1>Algo se salió del guion</h1>
    <p>El equipo técnico ya registró el incidente. Intenta de nuevo en un momento; si el problema continúa, contacta al administrador.</p>
    <?php if (!empty($error)): ?>
        <div class="error-page__debug">
            <b><?= e($error->getMessage()) ?></b>
            <span><?= e($error->getFile()) ?>:<?= (int) $error->getLine() ?></span>
        </div>
    <?php endif; ?>
    <div class="fila" style="justify-content:center">
        <a class="btn btn--primario" href="<?= e(url('/panel')) ?>"><?= icono('refrescar') ?> Reintentar</a>
        <a class="btn btn--claro" href="<?= e(url('/')) ?>">Ir al sitio</a>
    </div>
</div>
