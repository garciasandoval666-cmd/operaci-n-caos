<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

/**
 * Conexión única (singleton) a MySQL/MariaDB.
 */
final class Database
{
    private static ?PDO $pdo = null;
    private static ?string $error = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = (string) env('DB_HOST', '127.0.0.1');
        $puerto = (string) env('DB_PORT', '3306');
        $base = (string) env('DB_DATABASE', 'conectaeventos');
        $usuario = (string) env('DB_USERNAME', 'root');
        $clave = (string) env('DB_PASSWORD', '');

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $puerto, $base);

        try {
            self::$pdo = new PDO($dsn, $usuario, $clave, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ]);
        } catch (PDOException $e) {
            self::$error = $e->getMessage();
            throw $e;
        }

        return self::$pdo;
    }

    public static function intentar(): ?PDO
    {
        try {
            return self::pdo();
        } catch (PDOException) {
            return null;
        }
    }

    public static function error(): ?string
    {
        return self::$error;
    }

    public static function transaccion(callable $fn): mixed
    {
        $pdo = self::pdo();
        $propia = !$pdo->inTransaction();
        if ($propia) {
            $pdo->beginTransaction();
        }
        try {
            $resultado = $fn($pdo);
            if ($propia) {
                $pdo->commit();
            }
            return $resultado;
        } catch (\Throwable $e) {
            if ($propia && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
