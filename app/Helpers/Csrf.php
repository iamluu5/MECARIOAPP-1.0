<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\Session;

/**
 * Protección CSRF para formularios POST.
 *
 * El token demuestra que la solicitud fue generada desde una página legítima
 * del sistema y no desde un sitio externo malicioso.
 */
final class Csrf
{
    private const CLAVE_SESION = '_csrf_token';

    /**
     * Devuelve el token actual o crea uno nuevo.
     */
    public static function token(): string
    {
        Session::iniciar();

        if (!Session::existe(self::CLAVE_SESION)) {
            Session::guardar(
                self::CLAVE_SESION,
                bin2hex(random_bytes(32))
            );
        }

        return (string) Session::obtener(self::CLAVE_SESION);
    }

    /**
     * Genera el input hidden que debe colocarse dentro de un formulario.
     */
    public static function campo(): string
    {
        return '<input type="hidden" name="csrf_token" value="' .
            Sanitizer::html(self::token()) .
            '">';
    }

    /**
     * Compara el token recibido con el almacenado en la sesión.
     */
    public static function validar(?string $tokenRecibido): bool
    {
        $tokenGuardado = Session::obtener(self::CLAVE_SESION);

        return is_string($tokenGuardado)
            && is_string($tokenRecibido)
            && $tokenRecibido !== ''
            && hash_equals($tokenGuardado, $tokenRecibido);
    }

    /**
     * Crea un token nuevo, por ejemplo después de iniciar sesión.
     */
    public static function regenerar(): void
    {
        Session::guardar(
            self::CLAVE_SESION,
            bin2hex(random_bytes(32))
        );
    }
}
