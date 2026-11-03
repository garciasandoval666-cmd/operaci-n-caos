<?php
/**
 * Router para el servidor embebido de PHP.
 * Uso: php -S localhost:8080 router.php
 *
 * El servidor embebido no lee .htaccess, así que este archivo aplica el mismo
 * perímetro que Apache: nunca se sirven dotfiles, carpetas internas ni
 * artefactos descargables (.sql, .log, .md, .bat, llaves).
 */
declare(strict_types=1);

$ruta = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

$prohibido =
    preg_match('#(^|/)\.#', $ruta) === 1
    || preg_match('#^/(storage|database|docs|tests|src|views)(/|$)#i', $ruta) === 1
    || preg_match('#\.(sql|md|log|bak|ini|sh|bat|lock|env|pem|key|p12)$#i', $ruta) === 1;

if ($prohibido) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "No encontrado\n";
    return true;
}

$archivo = __DIR__ . $ruta;

if ($archivo !== __DIR__ . '/' && is_file($archivo)) {
    return false; // deja que el servidor embebido sirva el archivo real
}

require __DIR__ . '/index.php';
