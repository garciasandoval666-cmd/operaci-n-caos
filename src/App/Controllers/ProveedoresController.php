<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;

final class ProveedoresController extends Controller
{
    public function index(): void
    {
        $pdo = Database::pdo();
        $proveedores = $pdo->query(
            "SELECT p.*,
                    (SELECT COUNT(*) FROM recursos r WHERE r.id_proveedor = p.id_proveedor) AS recursos,
                    (SELECT COUNT(*) FROM reservas_recurso rr
                       JOIN recursos r2 ON r2.id_recurso = rr.id_recurso
                      WHERE r2.id_proveedor = p.id_proveedor AND rr.estado = 'confirmada' AND rr.fecha_fin >= NOW()) AS reservas
               FROM proveedores p ORDER BY p.estado, p.nombre"
        )->fetchAll();

        View::render('proveedores/index', [
            'titulo'      => 'Proveedores',
            'subtitulo'   => 'Aliados de mobiliario, audio, staff y decoración con su inventario vinculado.',
            'proveedores' => $proveedores,
        ], 'app');
    }

    public function guardar(): void
    {
        $nombre = $this->texto('nombre');
        $contacto = $this->texto('contacto');
        $telefono = $this->texto('telefono');
        $correo = mb_strtolower($this->texto('correo'));
        $descripcion = $this->texto('descripcion');
        $sitio = $this->texto('sitio_web');
        $color = mb_strtolower($this->texto('color_marca'));
        $desde = (int) $this->texto('desde');

        // El logotipo se elige de los archivos que existen, no se sube: una
        // subida de SVG es codigo que el navegador ejecuta, y aqui no hace
        // falta correr ese riesgo. Lo que no este en la lista se guarda vacio.
        $logo = $this->texto('logo');
        if ($logo !== '' && !in_array($logo, logos_disponibles(), true)) {
            $logo = '';
        }

        // La categoria llega de un <select>, pero el formulario es del cliente:
        // se valida contra la lista del ENUM y lo que no este en ella cae en
        // 'otro' en lugar de hacer fallar la insercion.
        $categorias = ['mobiliario', 'personal', 'audio_video', 'decoracion', 'transporte', 'otro'];
        $categoria = $this->texto('categoria');
        if (!in_array($categoria, $categorias, true)) {
            $categoria = 'otro';
        }

        if ($nombre === '') {
            $this->error('El nombre del proveedor es obligatorio.', '/proveedores');
        }
        if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $this->error('El correo del proveedor no es válido.', '/proveedores');
        }
        // El sitio se publica en la portada: solo http(s), para que un
        // `javascript:` nunca llegue al atributo href de una pagina publica.
        if ($sitio !== '') {
            $esquema = mb_strtolower((string) parse_url($sitio, PHP_URL_SCHEME));
            if (!filter_var($sitio, FILTER_VALIDATE_URL) || !in_array($esquema, ['http', 'https'], true)) {
                $this->error('El sitio web debe ser una dirección http o https.', '/proveedores');
            }
        }
        if ($color === '' || preg_match('/^#[0-9a-f]{6}$/', $color) !== 1) {
            $color = '#1f6b4f';
        }
        if ($desde !== 0 && ($desde < 1950 || $desde > (int) date('Y'))) {
            $this->error('El año de inicio de la alianza no es válido.', '/proveedores');
        }

        try {
            Database::pdo()->prepare(
                'INSERT INTO proveedores (nombre, categoria, descripcion, logo, contacto, telefono, correo, sitio_web, color_marca, desde)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                mb_substr($nombre, 0, 120),
                $categoria,
                $descripcion !== '' ? mb_substr($descripcion, 0, 160) : null,
                $logo !== '' ? $logo : null,
                $contacto !== '' ? mb_substr($contacto, 0, 120) : null,
                $telefono !== '' ? mb_substr($telefono, 0, 25) : null,
                $correo !== '' ? $correo : null,
                $sitio !== '' ? mb_substr($sitio, 0, 160) : null,
                $color,
                $desde !== 0 ? $desde : null,
            ]);
        } catch (\PDOException) {
            $this->error('Ya existe un proveedor con ese nombre.', '/proveedores');
        }

        registrar_bitacora('CREAR_PROVEEDOR', 'proveedores', (int) Database::pdo()->lastInsertId(), $nombre);
        $this->exito('Proveedor registrado.', '/proveedores');
    }
}
