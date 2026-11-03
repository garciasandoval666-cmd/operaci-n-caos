<?php
/** @var string $contenido */
$titulo = $titulo ?? 'Sitio';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#080c17">
    <title><?= e($titulo) ?> · ConectaEventos</title>
    <meta name="description" content="ConectaEventos · recinto y productora de eventos corporativos, sociales y de bodas en Ciudad de México.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/landing.css')) ?>">
</head>
<body class="landing">
<?php if ($flash !== ''): ?><div class="l-flash"><?= $flash ?></div><?php endif; ?>
<?= $contenido ?>
<script src="<?= e(asset('assets/js/landing.js')) ?>" defer></script>
</body>
</html>
