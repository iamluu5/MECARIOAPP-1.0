<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Clase central para administrar sesiones.
 *
 * Evita repetir session_start(), acceso directo a $_SESSION y mensajes
 * temporales en cada controlador.
 */
final class Session
{
    /**
     * Inicia la sesión una sola vez y configura la cookie de forma segura.
     */
    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $session = $config['session'];

        session_name($session['name']);

        session_set_cookie_params([
            'lifetime' => (int) $session['lifetime'],
            'path' => '/',
            'domain' => '',
            'secure' => (bool) $session['secure'],
            'httponly' => (bool) $session['httponly'],
            'samesite' => $session['samesite'],
        ]);

        session_start();
    }

    /**
     * Regenera el ID de sesión.
     *
     * Se usa después del login para reducir el riesgo de fijación de sesión.
     */
    public static function regenerar(): void
    {
        self::iniciar();
        session_regenerate_id(true);
    }

    public static function guardar(string $clave, mixed $valor): void
    {
        self::iniciar();
        $_SESSION[$clave] = $valor;
    }

    public static function obtener(string $clave, mixed $predeterminado = null): mixed
    {
        self::iniciar();

        return $_SESSION[$clave] ?? $predeterminado;
    }

    public static function existe(string $clave): bool
    {
        self::iniciar();

        return array_key_exists($clave, $_SESSION);
    }

    public static function eliminar(string $clave): void
    {
        self::iniciar();
        unset($_SESSION[$clave]);
    }

    /**
     * Devuelve la información del usuario autenticado.
     */
    public static function usuario(): ?array
    {
        $usuario = self::obtener('usuario');

        return is_array($usuario) ? $usuario : null;
    }

    /**
     * Indica si existe un usuario autenticado.
     */
    public static function estaAutenticado(): bool
    {
        return self::usuario() !== null;
    }

    /**
     * Verifica si el usuario tiene un permiso específico.
     *
     * Ejemplo:
     * Session::tienePermiso('inventario.gestionar')
     */
    public static function tienePermiso(string $permiso): bool
    {
        $usuario = self::usuario();

        if ($usuario === null) {
            return false;
        }

        return in_array($permiso, $usuario['permisos'] ?? [], true);
    }



    /**
     * Verifica si el usuario autenticado tiene un rol concreto.
     */
    public static function tieneRol(string $rol): bool
    {
        $usuario = self::usuario();

        if ($usuario === null) {
            return false;
        }

        return in_array($rol, $usuario['roles'] ?? [], true);
    }

    /**
     * Identifica una cuenta de cliente sin privilegios administrativos.
     */
    public static function esCliente(): bool
    {
        return self::tieneRol('Cliente')
            && !self::tienePermiso('usuarios.ver')
            && !self::tienePermiso('inventario.ver')
            && !self::tienePermiso('ventas.ver');
    }

    /**
     * Guarda un mensaje que se mostrará una sola vez después de redirigir.
     *
     * Tipos recomendados: success, error, warning e info.
     */
    public static function mensaje(string $tipo, string $texto): void
    {
        self::iniciar();

        $_SESSION['mensajes'][] = [
            'tipo' => $tipo,
            'texto' => $texto,
        ];
    }

    /**
     * Obtiene y elimina los mensajes temporales.
     */
    public static function consumirMensajes(): array
    {
        self::iniciar();

        $mensajes = $_SESSION['mensajes'] ?? [];
        unset($_SESSION['mensajes']);

        return $mensajes;
    }

    /**
     * Destruye completamente la sesión durante el logout.
     */
    public static function destruir(): void
    {
        self::iniciar();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $parametros = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $parametros['path'],
                $parametros['domain'],
                $parametros['secure'],
                $parametros['httponly']
            );
        }

        session_destroy();
    }
}
