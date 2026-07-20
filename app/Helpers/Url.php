<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Generador central de URLs.
 *
 * Detecta automáticamente el nombre de la carpeta del proyecto.
 * Por eso no es necesario escribir manualmente:
 * http://localhost/mecario/public
 *
 * Funciona igual si el proyecto cambia de nombre o se ejecuta en WAMP.
 */
final class Url
{
    /**
     * Devuelve la ruta de la carpeta public.
     *
     * Ejemplo:
     * /mecario/public
     */
    public static function base(): string
    {
        $script = str_replace(
            '\\',
            '/',
            $_SERVER['SCRIPT_NAME'] ?? '/public/index.php'
        );

        $base = rtrim(dirname($script), '/');

        return $base === '.' ? '' : $base;
    }

    /**
     * Genera una URL interna de la aplicación.
     *
     * Url::ruta('/login')
     * Resultado: /mecario/public/login
     */
    public static function ruta(string $ruta = '/'): string
    {
        $ruta = '/' . ltrim($ruta, '/');

        return self::base() . ($ruta === '//' ? '/' : $ruta);
    }

    /**
     * Genera la URL para CSS, JavaScript o imágenes fijas.
     */
    public static function asset(string $archivo): string
    {
        return self::ruta('/assets/' . ltrim($archivo, '/'));
    }

    /**
     * Genera una URL para las imágenes dinámicas guardadas en /uploads.
     */
    public static function upload(string $archivo): string
    {
        $baseProyecto = dirname(self::base());

        return rtrim($baseProyecto, '/') . '/' . ltrim($archivo, '/');
    }

    /**
     * Redirige y termina inmediatamente la ejecución.
     */
    public static function redirigir(string $ruta): never
    {
        header('Location: ' . self::ruta($ruta));
        exit;
    }
}
