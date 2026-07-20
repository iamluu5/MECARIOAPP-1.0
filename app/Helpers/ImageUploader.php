<?php

declare(strict_types=1);

namespace App\Helpers;

use RuntimeException;

/**
 * Carga segura de imágenes del inventario.
 *
 * Diferencia:
 * - public/assets/img: logo, íconos e imágenes fijas del diseño.
 * - uploads/: fotografías cargadas desde el CRUD de inventario.
 */
final class ImageUploader
{
    public static function guardarThumbnail(array $archivo): ?string
    {
        return self::guardar($archivo, 'thumbnail');
    }

    public static function guardarGrande(array $archivo): ?string
    {
        return self::guardar($archivo, 'grande');
    }

    /**
     * Valida y mueve una imagen hacia su carpeta correspondiente.
     */
    private static function guardar(
        array $archivo,
        string $tipo
    ): ?string {
        $error = $archivo['error'] ?? UPLOAD_ERR_NO_FILE;

        /**
         * La imagen puede ser opcional durante una edición.
         */
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(
                'La imagen no pudo cargarse correctamente.'
            );
        }

        if (
            !isset($archivo['tmp_name'], $archivo['name'], $archivo['size'])
            || !is_uploaded_file($archivo['tmp_name'])
        ) {
            throw new RuntimeException(
                'La carga recibida no corresponde a un archivo válido.'
            );
        }

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $reglas = $config['uploads'];

        if ((int) $archivo['size'] > (int) $reglas['max_size']) {
            throw new RuntimeException(
                'La imagen supera el tamaño máximo permitido.'
            );
        }

        $extension = strtolower(
            pathinfo($archivo['name'], PATHINFO_EXTENSION)
        );

        if (!in_array($extension, $reglas['extensions'], true)) {
            throw new RuntimeException(
                'La extensión de la imagen no está permitida.'
            );
        }

        /**
         * No se confía únicamente en la extensión escrita por el usuario.
         * finfo revisa el tipo real del archivo.
         */
        $mime = (new \finfo(FILEINFO_MIME_TYPE))
            ->file($archivo['tmp_name']);

        if (
            !in_array($mime, $reglas['mime_types'], true)
            || @getimagesize($archivo['tmp_name']) === false
        ) {
            throw new RuntimeException(
                'El archivo cargado no es una imagen válida.'
            );
        }

        $directorio = $tipo === 'thumbnail'
            ? $reglas['thumbnail_directory']
            : $reglas['large_directory'];

        if (
            !is_dir($directorio)
            && !mkdir($directorio, 0775, true)
            && !is_dir($directorio)
        ) {
            throw new RuntimeException(
                'No se pudo crear la carpeta de imágenes.'
            );
        }

        /**
         * Se genera un nombre aleatorio para evitar colisiones
         * y para no confiar en el nombre original.
         */
        $nombre = Sanitizer::nombreArchivo($archivo['name'])
            . '_'
            . bin2hex(random_bytes(8))
            . '.'
            . $extension;

        $destino = $directorio . $nombre;

        if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
            throw new RuntimeException(
                'No se pudo guardar la imagen en el servidor.'
            );
        }

        return $tipo === 'thumbnail'
            ? 'uploads/thumbnails/' . $nombre
            : 'uploads/grandes/' . $nombre;
    }

    /**
     * Elimina una imagen antigua con protección contra rutas externas.
     */
    public static function eliminar(?string $rutaRelativa): bool
    {
        if ($rutaRelativa === null || $rutaRelativa === '') {
            return false;
        }

        $raizProyecto = realpath(dirname(__DIR__, 2));
        $archivo = realpath(
            dirname(__DIR__, 2) . '/' . ltrim($rutaRelativa, '/')
        );

        if (
            $raizProyecto === false
            || $archivo === false
            || !str_starts_with(
                $archivo,
                $raizProyecto . DIRECTORY_SEPARATOR
            )
        ) {
            return false;
        }

        return is_file($archivo) && unlink($archivo);
    }
}
