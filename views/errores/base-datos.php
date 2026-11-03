<?php /** @var string|null $error */ ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Base de datos no disponible · ConectaEventos</title>
    <link href="https://fonts.googleapis.com/css2?family=Mona+Sans:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 30px; background: #090b0f; color: #f3f1ea; font-family: "Inter", system-ui, sans-serif; }
        .caja { max-width: 620px; width: 100%; background: #14181f; border: 1px solid rgba(243,241,234,.14); border-radius: 28px; padding: 40px; box-shadow: 0 40px 80px rgba(0,0,0,.4); }
        .marca { display: flex; align-items: center; gap: 12px; font-family: "Mona Sans", sans-serif; font-weight: 600; }
        .mono { width: 40px; height: 40px; display: grid; place-items: center; background: #bfe3c9; color: #090b0f; border-radius: 12px; font-size: 14px; }
        .punto { display: inline-flex; align-items: center; gap: 8px; margin-top: 28px; font-size: 10px; letter-spacing: .16em; text-transform: uppercase; color: #e6a8a3; }
        .punto::before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: #b4403a; box-shadow: 0 0 0 4px rgba(180,64,58,.22); }
        h1 { font-family: "Mona Sans", sans-serif; font-size: 32px; line-height: 1.1; letter-spacing: -.03em; margin: 14px 0 12px; }
        p { color: #a8a49c; font-size: 14px; line-height: 1.65; margin: 0 0 12px; }
        ul { color: #a8a49c; font-size: 13px; line-height: 1.8; padding-left: 18px; margin: 18px 0 0; }
        code { background: #1f252f; color: #bfe3c9; padding: 2px 6px; border-radius: 4px; font-size: 12px; }
        .debug { margin-top: 22px; padding: 14px; border: 1px dashed rgba(243,241,234,.2); border-radius: 10px; color: #e6a8a3; font-size: 12px; word-break: break-word; }
        a { display: inline-flex; margin-top: 24px; color: #090b0f; background: #bfe3c9; padding: 11px 20px; border-radius: 999px; text-decoration: none; font-size: 13px; font-weight: 600; }
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
