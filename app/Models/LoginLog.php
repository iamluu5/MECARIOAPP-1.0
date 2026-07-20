<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Registra cada intento de acceso al sistema.
 *
 * Guarda usuario escrito, IP, fecha, estado y mensaje.
 */
final class LoginLog
{
    public function registrar(
        ?int $idUsuario,
        string $usuarioIngresado,
        string $ip,
        string $estado,
        ?string $mensaje = null
    ): bool {
        return Database::getInstancia()->ejecutar(
            'INSERT INTO login_logs
                (
                    id_usuario,
                    usuario_ingresado,
                    ip,
                    estado,
                    mensaje,
                    fecha_intento
                )
             VALUES
                (
                    :id_usuario,
                    :usuario,
                    :ip,
                    :estado,
                    :mensaje,
                    NOW()
                )',
            [
                'id_usuario' => $idUsuario,
                'usuario' => $usuarioIngresado,
                'ip' => $ip,
                'estado' => $estado,
                'mensaje' => $mensaje,
            ]
        );
    }
}
