<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Registra eventos de seguridad o comportamientos anormales.
 *
 * Ejemplo: una cuenta bloqueada después de tres intentos fallidos.
 */
final class Anomalia
{
    public function registrar(
        ?int $idUsuario,
        string $modulo,
        string $descripcion,
        ?string $ip = null,
        string $nivel = 'advertencia'
    ): bool {
        return Database::getInstancia()->ejecutar(
            'INSERT INTO anomalias
                (
                    id_usuario,
                    modulo,
                    descripcion,
                    ip,
                    nivel,
                    fecha_registro
                )
             VALUES
                (
                    :id_usuario,
                    :modulo,
                    :descripcion,
                    :ip,
                    :nivel,
                    NOW()
                )',
            [
                'id_usuario' => $idUsuario,
                'modulo' => $modulo,
                'descripcion' => $descripcion,
                'ip' => $ip,
                'nivel' => $nivel,
            ]
        );
    }
}
