<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use Throwable;

/**
 * Clase central de conexión a la base de datos.
 *
 * IMPORTANTE:
 * Este archivo NO crea la base de datos. La estructura de tablas se encuentra
 * en database/mecario.sql.
 *
 * Database.php permite que los modelos PHP se comuniquen con MySQL mediante PDO.
 * Todos los módulos reutilizan esta única clase, aplicando el principio DRY.
 *
 * Se utiliza el patrón Singleton para mantener una sola conexión durante
 * la ejecución de cada solicitud.
 */
final class Database
{
    /**
     * Guarda la única instancia creada de esta clase.
     */
    private static ?self $instancia = null;

    /**
     * Conexión PDO activa.
     */
    private PDO $conexion;

    /**
     * El constructor es privado para impedir que cada módulo cree conexiones
     * diferentes con "new Database()".
     */
    private function __construct()
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $db = $config['database'];

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $db['host'],
            $db['port'],
            $db['name'],
            $db['charset']
        );

        try {
            $this->conexion = new PDO(
                $dsn,
                $db['user'],
                $db['password'],
                [
                    // Convierte errores SQL en excepciones controlables.
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

                    // Devuelve los registros como arreglos asociativos.
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

                    // Obliga a MySQL a usar consultas preparadas reales.
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $exception) {
            /**
             * No se muestra la contraseña ni el mensaje técnico original
             * al usuario. ErrorHandler guardará la excepción interna en el log.
             */
            throw new RuntimeException(
                'No se pudo conectar con la base de datos.',
                0,
                $exception
            );
        }
    }

    /**
     * Devuelve la única instancia disponible de Database.
     */
    public static function getInstancia(): self
    {
        return self::$instancia ??= new self();
    }

    /**
     * Devuelve el objeto PDO cuando un proceso especial necesita usarlo.
     */
    public function getConexion(): PDO
    {
        return $this->conexion;
    }

    /**
     * Prepara y ejecuta una consulta SQL.
     *
     * Los valores se envían mediante $parametros para reducir el riesgo
     * de inyección SQL.
     */
    public function consultar(string $sql, array $parametros = []): PDOStatement
    {
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute($parametros);

        return $sentencia;
    }

    /**
     * Devuelve todos los registros encontrados.
     */
    public function consultarTodos(string $sql, array $parametros = []): array
    {
        return $this->consultar($sql, $parametros)->fetchAll();
    }

    /**
     * Devuelve un solo registro o null cuando no existe.
     */
    public function consultarUno(string $sql, array $parametros = []): ?array
    {
        $resultado = $this->consultar($sql, $parametros)->fetch();

        return $resultado === false ? null : $resultado;
    }

    /**
     * Ejecuta INSERT, UPDATE o DELETE lógico.
     */
    public function ejecutar(string $sql, array $parametros = []): bool
    {
        return $this->consultar($sql, $parametros)->rowCount() >= 0;
    }

    /**
     * Ejecuta un INSERT y devuelve el ID autogenerado.
     */
    public function insertar(string $sql, array $parametros = []): int
    {
        $this->consultar($sql, $parametros);

        return (int) $this->conexion->lastInsertId();
    }

    /**
     * Ejecuta varias operaciones como una sola transacción.
     *
     * Es especialmente útil al vender una parte:
     * 1. crear la venta;
     * 2. crear su detalle;
     * 3. disminuir el inventario.
     *
     * Si alguna operación falla, todas se revierten.
     */
    public function transaccion(callable $proceso): mixed
    {
        $this->conexion->beginTransaction();

        try {
            $resultado = $proceso($this->conexion);
            $this->conexion->commit();

            return $resultado;
        } catch (Throwable $exception) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * Impide clonar la conexión Singleton.
     */
    private function __clone(): void
    {
    }
}
