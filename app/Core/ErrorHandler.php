<?php

declare(strict_types=1);

namespace App\Core;

use ErrorException;
use Throwable;

/**
 * Manejo centralizado de errores.
 *
 * En desarrollo muestra información para que como equipo podamos corregir fallos.
 * En producción oculta rutas, consultas y detalles internos por seguridad.
 */
final class ErrorHandler
{
    /**
     * Registra los manejadores globales al iniciar la aplicación.
     */
    public static function registrar(): void
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';

        date_default_timezone_set($config['app']['timezone']);
        ini_set('display_errors', $config['errors']['show_details'] ? '1' : '0');
        error_reporting(E_ALL);

        /**
         * Convierte los errores normales de PHP en excepciones.
         * Así todos pasan por el mismo mecanismo de control.
         */
        set_error_handler(
            static function (
                int $nivel,
                string $mensaje,
                string $archivo,
                int $linea
            ): never {
                throw new ErrorException(
                    $mensaje,
                    0,
                    $nivel,
                    $archivo,
                    $linea
                );
            }
        );

        set_exception_handler([self::class, 'manejarExcepcion']);
    }

    /**
     * Guarda la excepción en un archivo log y muestra una respuesta controlada.
     */
    public static function manejarExcepcion(Throwable $exception): void
    {
        self::guardarLog($exception);
        http_response_code(500);

        $config = require dirname(__DIR__, 2) . '/config/config.php';

        if ($config['errors']['show_details']) {
            echo '<h1>Error de desarrollo</h1>';
            echo '<p><strong>Mensaje:</strong> ' .
                htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') .
                '</p>';
            echo '<p><strong>Archivo:</strong> ' .
                htmlspecialchars($exception->getFile(), ENT_QUOTES, 'UTF-8') .
                ':' . (int) $exception->getLine() .
                '</p>';
            return;
        }

        echo '<h1>Ocurrió un error inesperado</h1>';
        echo '<p>La incidencia fue registrada. Intente nuevamente más tarde.</p>';
    }

    /**
     * Escribe el detalle técnico dentro de storage/logs/errors.log.
     */
    private static function guardarLog(Throwable $exception): void
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $archivoLog = $config['errors']['log_file'];
        $directorio = dirname($archivoLog);

        if (!is_dir($directorio)) {
            mkdir($directorio, 0775, true);
        }

        $contenido = sprintf(
            "[%s] %s: %s en %s:%d%s%s%s",
            date('Y-m-d H:i:s'),
            $exception::class,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            PHP_EOL,
            $exception->getTraceAsString(),
            PHP_EOL
        );

        file_put_contents(
            $archivoLog,
            $contenido,
            FILE_APPEND | LOCK_EX
        );
    }
}
