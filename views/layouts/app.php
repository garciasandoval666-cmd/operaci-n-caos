<?php
/** @var string $contenido */
/** @var array|null $usuario */
$titulo = $titulo ?? 'Panel';
$migas = $migas ?? [];
$flash = $flash ?? '';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#090b0f">
    <title><?= e($titulo) ?> · ConectaEventos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mona+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
</head>
<body data-base="<?= e(base_url()) ?>">
<div class="admin">
    <?php require BASE_PATH . '/views/partials/sidebar.php'; ?>

    <div class="main">
        <header class="topbar">
            <button type="button" class="btn-icono topbar__menu" data-sidebar-toggle aria-label="Abrir menú" aria-expanded="false">
                <?= icono('menu') ?>
            </button>

            <nav class="migas" aria-label="Ruta">
                <a href="<?= e(url('/panel')) ?>"><?= icono('casa') ?> Panel</a>
                <?php foreach ($migas as $miga): ?>
                    <?= icono('flecha') ?>
                    <?php if (!empty($miga['url'])): ?>
                        <a href="<?= e(url($miga['url'])) ?>"><?= e($miga['titulo']) ?></a>
                    <?php else: ?>
                        <b><?= e($miga['titulo']) ?></b>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>

            <div class="topbar__acciones">
                <form class="buscador-top" action="<?= e(url('/buscar')) ?>" method="get" role="search">
                    <?= icono('buscar') ?>
                    <input type="search" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Buscar evento, cliente, folio…" aria-label="Buscar">
                </form>
                <a class="topbar__enlace" href="<?= e(url('/')) ?>" target="_blank" rel="noopener">
                    <?= icono('externo') ?><span>Ver sitio</span>
                </a>
                <?php if (puede(['administrador', 'comercial', 'coordinador'])): ?>
                    <a class="btn btn--lima btn--sm" href="<?= e(url('/eventos/crear')) ?>"><?= icono('mas') ?> Evento</a>
                <?php endif; ?>
            </div>
        </header>

        <main class="contenido">
            <?= $flash ?>
            <?= $contenido ?>
        </main>
    </div>
</div>
<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>
</html>
