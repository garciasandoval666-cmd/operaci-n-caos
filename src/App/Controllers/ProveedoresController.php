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

        if ($nombre === '') {
            $this->error('El nombre del proveedor es obligatorio.', '/proveedores');
        }
        if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $this->error('El correo del proveedor no es válido.', '/proveedores');
        }

        try {
            Database::pdo()->prepare(
                'INSERT INTO proveedores (nombre, contacto, telefono, correo) VALUES (?, ?, ?, ?)'
            )->execute([
                mb_substr($nombre, 0, 120),
                $contacto !== '' ? mb_substr($contacto, 0, 120) : null,
                $telefono !== '' ? mb_substr($telefono, 0, 25) : null,
                $correo !== '' ? $correo : null,
            ]);
        } catch (\PDOException) {
            $this->error('Ya existe un proveedor con ese nombre.', '/proveedores');
        }

        registrar_bitacora('CREAR_PROVEEDOR', 'proveedores', (int) Database::pdo()->lastInsertId(), $nombre);
        $this->exito('Proveedor registrado.', '/proveedores');
    }
}
