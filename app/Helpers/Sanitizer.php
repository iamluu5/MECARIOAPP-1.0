<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Sanitización y escape centralizados.
 *
 * Sanitizar entrada:
 * elimina etiquetas o caracteres no deseados.
 *
 * Escapar salida:
 * evita que el navegador interprete contenido del usuario como HTML o scripts.
 */
final class Sanitizer
{
    /**
     * Limpia texto corto recibido desde formularios.
     */
    public static function texto(?string $valor): string
    {
        return trim(strip_tags($valor ?? ''));
    }

    /**
     * Limpia y normaliza un correo electrónico.
     */
    public static function correo(?string $valor): string
    {
        $correo = filter_var(
            trim($valor ?? ''),
            FILTER_SANITIZE_EMAIL
        );

        return is_string($correo) ? $correo : '';
    }

    public static function entero(mixed $valor): int
    {
        return (int) filter_var(
            $valor,
            FILTER_SANITIZE_NUMBER_INT
        );
    }

    public static function decimal(mixed $valor): float
    {
        $valor = str_replace(',', '.', (string) $valor);

        return (float) filter_var(
            $valor,
            FILTER_SANITIZE_NUMBER_FLOAT,
            FILTER_FLAG_ALLOW_FRACTION
        );
    }

    /**
     * Escapa contenido antes de imprimirlo en una vista.
     *
     * Ejemplo:
     * <?= Sanitizer::html($usuario['nombre']) ?>
     */
    public static function html(mixed $valor): string
    {
        return htmlspecialchars(
            (string) $valor,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }

    /**
     * Limpia un nombre de archivo.
     *
     * ImageUploader agrega posteriormente un valor aleatorio para evitar
     * nombres duplicados.
     */
    public static function nombreArchivo(string $nombre): string
    {
        $base = pathinfo($nombre, PATHINFO_FILENAME);
        $base = strtolower(trim($base));
        $base = preg_replace('/[^a-z0-9_-]+/', '_', $base) ?: 'imagen';

        return trim($base, '_');
    }
}
