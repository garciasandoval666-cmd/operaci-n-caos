<?php /** @var string|null $error */ ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Base de datos no disponible · ConectaEventos</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 30px; background: #0a0e1a; color: #fff; font-family: "Inter", system-ui, sans-serif; }
        .caja { max-width: 620px; width: 100%; background: #111827; border: 1px solid #2a3448; border-radius: 22px; padding: 40px; box-shadow: 0 40px 80px rgba(0,0,0,.4); }
        .marca { display: flex; align-items: center; gap: 12px; font-family: "Space Grotesk", sans-serif; font-weight: 600; }
        .mono { width: 40px; height: 40px; display: grid; place-items: center; background: #c9f24d; color: #0a0e1a; border-radius: 11px; font-size: 14px; }
        .punto { display: inline-flex; align-items: center; gap: 8px; margin-top: 28px; font-size: 10px; letter-spacing: .16em; text-transform: uppercase; color: #ffb4a8; }
        .punto::before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: #ff7a6b; box-shadow: 0 0 0 4px rgba(255,122,107,.15); }
        h1 { font-family: "Space Grotesk", sans-serif; font-size: 32px; line-height: 1.1; letter-spacing: -.03em; margin: 14px 0 12px; }
        p { color: #9ba4b6; font-size: 14px; line-height: 1.65; margin: 0 0 12px; }
        ul { color: #9ba4b6; font-size: 13px; line-height: 1.8; padding-left: 18px; margin: 18px 0 0; }
        code { background: #1b2336; color: #c9f24d; padding: 2px 6px; border-radius: 4px; font-size: 12px; }
        .debug { margin-top: 22px; padding: 14px; border: 1px dashed #3a4357; border-radius: 10px; color: #ffb4a8; font-size: 12px; word-break: break-word; }
        a { display: inline-flex; margin-top: 24px; color: #0a0e1a; background: #c9f24d; padding: 11px 20px; border-radius: 999px; text-decoration: none; font-size: 13px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="caja">
        <div class="marca"><span class="mono">CE</span> ConectaEventos</div>
        <div class="punto">Servicio interrumpido</div>
        <h1>No pudimos conectar con la base de datos</h1>
        <p>La aplicación está en línea, pero el servidor de datos no respondió. Revisa que MySQL/MariaDB esté encendido y que las credenciales del archivo <code>.env</code> sean correctas.</p>
        <ul>
            <li>Inicia el servicio de MySQL o MariaDB.</li>
            <li>Verifica <code>DB_HOST</code>, <code>DB_PORT</code>, <code>DB_DATABASE</code>, <code>DB_USERNAME</code> y <code>DB_PASSWORD</code>.</li>
            <li>Importa <code>database/schema.sql</code> y <code>database/seed.sql</code> si es una instalación nueva.</li>
        </ul>
        <?php if (!empty($error)): ?>
            <div class="debug"><?= e($error) ?></div>
        <?php endif; ?>
        <a href="<?= e(url('/')) ?>">Reintentar</a>
    </div>
</body>
</html>
