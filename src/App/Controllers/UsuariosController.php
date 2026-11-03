<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

/**
 * Módulo D · administración de usuarios y roles.
 */
final class UsuariosController extends Controller
{
    public function index(): void
    {
        $pdo = Database::pdo();
        $usuarios = $pdo->query(
            "SELECT u.*, r.nombre AS rol, r.descripcion AS rol_descripcion,
                    (SELECT COUNT(*) FROM bitacora b WHERE b.id_usuario = u.id_usuario) AS acciones,
                    (SELECT MAX(b.fecha) FROM bitacora b WHERE b.id_usuario = u.id_usuario) AS ultima_accion
               FROM usuarios u JOIN roles r ON r.id_rol = u.id_rol
              ORDER BY u.activo DESC, r.id_rol, u.nombre"
        )->fetchAll();

        $roles = $pdo->query(
            "SELECT r.*, (SELECT COUNT(*) FROM usuarios u WHERE u.id_rol = r.id_rol) AS usuarios
               FROM roles r ORDER BY r.id_rol"
        )->fetchAll();

        View::render('usuarios/index', [
            'titulo'    => 'Usuarios y roles',
            'subtitulo' => 'Accesos por función: cada rol ve solo lo que necesita para operar.',
            'usuarios'  => $usuarios,
            'roles'     => $roles,
            'stats'     => [
                'total'   => count($usuarios),
                'activos' => count(array_filter($usuarios, static fn (array $u): bool => (int) $u['activo'] === 1)),
                'roles'   => count($roles),
            ],
        ], 'app');
    }

    public function guardar(): void
    {
        $nombre = $this->texto('nombre');
        $correo = mb_strtolower($this->texto('correo'));
        $idRol = $this->entero('id_rol');
        $clave = (string) ($_POST['clave'] ?? '');
        $telefono = $this->texto('telefono');

        if (mb_strlen($nombre) < 3) {
            $this->error('El nombre debe tener al menos 3 caracteres.', '/usuarios');
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $this->error('Escribe un correo válido.', '/usuarios');
        }
        if (mb_strlen($clave) < 10) {
            $this->error('La contraseña temporal debe tener al menos 10 caracteres.', '/usuarios');
        }
        if (!$this->rolValido($idRol)) {
            $this->error('Selecciona un rol válido.', '/usuarios');
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE correo = ?');
        $stmt->execute([$correo]);
        if ((int) $stmt->fetchColumn() > 0) {
            $this->error('Ya existe un usuario con ese correo.', '/usuarios');
        }

        $pdo->prepare(
            'INSERT INTO usuarios (id_rol, nombre, correo, password_hash, telefono, activo) VALUES (?, ?, ?, ?, ?, 1)'
        )->execute([
            $idRol,
            mb_substr($nombre, 0, 120),
            $correo,
            password_hash($clave, PASSWORD_DEFAULT),
            $telefono !== '' ? mb_substr($telefono, 0, 25) : null,
        ]);

        $id = (int) $pdo->lastInsertId();
        registrar_bitacora('CREAR_USUARIO', 'usuarios', $id, $nombre . ' · rol ' . $idRol);
        $this->exito('Usuario creado. Comparte la contraseña temporal por un canal seguro.', '/usuarios');
    }

    public function actualizar(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id_usuario = ?');
        $stmt->execute([$id]);
        $anterior = $stmt->fetch();
        if (!$anterior) {
            $this->noEncontrado('El usuario no existe.');
        }

        $nombre = $this->texto('nombre');
        $correo = mb_strtolower($this->texto('correo'));
        $idRol = $this->entero('id_rol');
        $activo = isset($_POST['activo']) ? 1 : 0;
        $clave = (string) ($_POST['clave'] ?? '');
        $telefono = $this->texto('telefono');

        if (mb_strlen($nombre) < 3 || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $this->error('Nombre y correo válidos son obligatorios.', '/usuarios');
        }
        if (!$this->rolValido($idRol)) {
            $this->error('Selecciona un rol válido.', '/usuarios');
        }
        if ($clave !== '' && mb_strlen($clave) < 10) {
            $this->error('La nueva contraseña debe tener al menos 10 caracteres.', '/usuarios');
        }
        if ((int) $anterior['id_usuario'] === (int) (Auth::id() ?? 0) && $activo === 0) {
            $this->error('No puedes desactivar tu propio usuario.', '/usuarios');
        }

        // Si este usuario es el último administrador activo, no se le puede
        // quitar el rol ni desactivar: el sistema quedaría sin nadie capaz de
        // administrar usuarios, y /usuarios solo lo abre un administrador.
        $eraAdmin = (int) $anterior['id_rol'] === self::ROL_ADMIN && (int) $anterior['activo'] === 1;
        $sigueAdmin = $idRol === self::ROL_ADMIN && $activo === 1;
        if ($eraAdmin && !$sigueAdmin && $this->administradoresActivos() <= 1) {
            $this->error(
                'Es el único administrador activo. Nombra o reactiva a otro administrador antes de cambiar este usuario.',
                '/usuarios'
            );
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE correo = ? AND id_usuario <> ?');
        $stmt->execute([$correo, $id]);
        if ((int) $stmt->fetchColumn() > 0) {
            $this->error('Ese correo ya está asignado a otro usuario.', '/usuarios');
        }

        $sql = 'UPDATE usuarios SET id_rol = ?, nombre = ?, correo = ?, telefono = ?, activo = ?';
        $parametros = [$idRol, mb_substr($nombre, 0, 120), $correo, $telefono !== '' ? mb_substr($telefono, 0, 25) : null, $activo];
        if ($clave !== '') {
            $sql .= ', password_hash = ?';
            $parametros[] = password_hash($clave, PASSWORD_DEFAULT);
        }
        $sql .= ' WHERE id_usuario = ?';
        $parametros[] = $id;
        $pdo->prepare($sql)->execute($parametros);

        foreach (['nombre' => $nombre, 'correo' => $correo, 'id_rol' => $idRol, 'activo' => $activo] as $campo => $nuevo) {
            if ((string) $anterior[$campo] !== (string) $nuevo) {
                registrar_historial('usuarios', $id, $campo, $anterior[$campo], $nuevo, 'Administración de usuarios');
            }
        }
        registrar_bitacora('EDITAR_USUARIO', 'usuarios', $id, $nombre . ($clave !== '' ? ' · contraseña restablecida' : ''));
        $this->exito('Usuario actualizado.', '/usuarios');
    }

    /** El rol 1 es administrador; vive en `roles` y no cambia de id. */
    private const ROL_ADMIN = 1;

    /** Cuántos administradores activos quedan en el sistema. */
    private function administradoresActivos(): int
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM usuarios WHERE id_rol = ? AND activo = 1'
        );
        $stmt->execute([self::ROL_ADMIN]);
        return (int) $stmt->fetchColumn();
    }

    private function rolValido(int $idRol): bool
    {
        if ($idRol <= 0) {
            return false;
        }
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM roles WHERE id_rol = ?');
        $stmt->execute([$idRol]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
