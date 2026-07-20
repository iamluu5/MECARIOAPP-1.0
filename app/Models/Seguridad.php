<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Consultas de auditoría de seguridad.
 */
final class Seguridad
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstancia();
    }

    public function listarIntentos(int $limite = 100): array
    {
        $limite = max(1, min(500, $limite));

        return $this->db->consultarTodos(
            'SELECT
                l.id_login_log,
                l.usuario_ingresado,
                l.ip,
                l.estado,
                l.mensaje,
                l.fecha_intento,
                CONCAT(u.nombre, " ", u.apellido) AS nombre_usuario
            FROM login_logs l
            LEFT JOIN usuarios u ON u.id_usuario = l.id_usuario
            ORDER BY l.fecha_intento DESC
            LIMIT ' . $limite
        );
    }

    public function listarAnomalias(int $limite = 100): array
    {
        $limite = max(1, min(500, $limite));

        return $this->db->consultarTodos(
            'SELECT
                a.id_anomalia,
                a.modulo,
                a.descripcion,
                a.ip,
                a.nivel,
                a.fecha_registro,
                COALESCE(u.usuario, "Sistema/Desconocido") AS usuario
            FROM anomalias a
            LEFT JOIN usuarios u ON u.id_usuario = a.id_usuario
            ORDER BY a.fecha_registro DESC
            LIMIT ' . $limite
        );
    }

    public function resumen(): array
    {
        return [
            'intentos_hoy' => (int) ($this->db->consultarUno(
                'SELECT COUNT(*) AS total FROM login_logs WHERE DATE(fecha_intento) = CURDATE()'
            )['total'] ?? 0),
            'fallidos_hoy' => (int) ($this->db->consultarUno(
                "SELECT COUNT(*) AS total FROM login_logs WHERE DATE(fecha_intento) = CURDATE() AND estado IN ('fallido','bloqueado')"
            )['total'] ?? 0),
            'anomalias' => (int) ($this->db->consultarUno(
                'SELECT COUNT(*) AS total FROM anomalias'
            )['total'] ?? 0),
            'bloqueados' => (int) ($this->db->consultarUno(
                'SELECT COUNT(*) AS total FROM usuarios WHERE bloqueado = 1'
            )['total'] ?? 0),
        ];
    }
}
