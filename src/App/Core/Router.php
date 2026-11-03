<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{metodo:string, patron:string, regex:string, handler:array, publico:bool, roles:array}> */
    private array $rutas = [];

    public function get(string $patron, array $handler, bool $publico = false, array $roles = []): void
    {
        $this->agregar('GET', $patron, $handler, $publico, $roles);
    }

    public function post(string $patron, array $handler, bool $publico = false, array $roles = []): void
    {
        $this->agregar('POST', $patron, $handler, $publico, $roles);
    }

    private function agregar(string $metodo, string $patron, array $handler, bool $publico, array $roles): void
    {
        $regex = preg_replace_callback('/\{([a-zA-Z_]+)\}/', static fn (array $m): string => '(?P<' . $m[1] . '>[^/]+)', $patron);
        $this->rutas[] = [
            'metodo'  => $metodo,
            'patron'  => $patron,
            'regex'   => '#^' . $regex . '$#',
            'handler' => $handler,
            'publico' => $publico,
            'roles'   => $roles,
        ];
    }

    public function despachar(string $metodo, string $ruta): void
    {
        $ruta = '/' . trim($ruta, '/');
        if ($ruta === '/') {
            $ruta = '/';
        }

        foreach ($this->rutas as $def) {
            if ($def['metodo'] !== $metodo) {
                continue;
            }
            if (!preg_match($def['regex'], $ruta, $coincidencias)) {
                continue;
            }
            $params = array_filter($coincidencias, 'is_string', ARRAY_FILTER_USE_KEY);
            unset($params['r']);

            if (!$def['publico']) {
                Auth::requiere($def['roles']);
            }

            [$clase, $accion] = $def['handler'];
            $controlador = new $clase();
            $controlador->{$accion}($params);
            return;
        }

        http_response_code(404);
        View::render('errores/404', ['titulo' => 'Página no encontrada'], Auth::usuario() ? 'app' : 'sitio');
    }

    public function rutaActual(): string
    {
        return '/' . trim((string) ($_GET['r'] ?? ''), '/');
    }
}
