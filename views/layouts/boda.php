<?php
/** @var string $contenido */
$titulo = $titulo ?? 'Bodas';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f7f3ea">
    <title><?= e($titulo) ?> · ConectaEventos Bodas</title>
    <meta name="description" content="Bodas íntimas y celebraciones de autor en ConectaEventos. Un espacio editorial, marfil y champán para el día más importante.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,400;1,500&family=Jost:wght@300;400;500;600&family=Great+Vibes&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/boda.css')) ?>">
</head>
<body class="boda">
<?php if ($flash !== ''): ?><div class="b-flash"><?= $flash ?></div><?php endif; ?>
<?= $contenido ?>
<script src="<?= e(asset('assets/js/landing.js')) ?>" defer></script>
</body>
</html>
