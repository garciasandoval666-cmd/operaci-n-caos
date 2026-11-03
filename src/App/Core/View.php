<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $vista, array $datos = [], ?string $layout = 'app'): void
    {
        $archivo = BASE_PATH . '/views/' . $vista . '.php';
        if (!is_file($archivo)) {
            throw new \RuntimeException('Vista no encontrada: ' . $vista);
        }

        $datos['usuario'] = $datos['usuario'] ?? Auth::usuario();
        $datos['flash'] = flash_render();

        extract($datos, EXTR_SKIP);

        ob_start();
        require $archivo;
        $contenido = ob_get_clean();

        if ($layout === null) {
            echo $contenido;
            return;
        }

        $archivoLayout = BASE_PATH . '/views/layouts/' . $layout . '.php';
        if (!is_file($archivoLayout)) {
            throw new \RuntimeException('Layout no encontrado: ' . $layout);
        }
        require $archivoLayout;
    }
}
