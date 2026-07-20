<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Services\RsaSignatureService;

final class Auditoria
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstancia();
    }

    public function listar(int $limite = 100): array
    {
        $limite = max(1, min(500, $limite));
        return $this->db->consultarTodos(
            'SELECT a.*, u.usuario, CONCAT(u.nombre, " ", u.apellido) AS usuario_nombre,
                    c.huella_sha256, c.algoritmo, c.clave_publica_pem
             FROM auditoria_firmada a
             INNER JOIN usuarios u ON u.id_usuario = a.id_usuario
             INNER JOIN claves_usuario c ON c.id_clave = a.id_clave
             ORDER BY a.fecha_evento DESC, a.id_auditoria DESC
             LIMIT ' . $limite
        );
    }

    public function verificar(array $registro): bool
    {
        if (hash('sha256', (string) $registro['datos_firmados_json']) !== (string) $registro['hash_sha256']) {
            return false;
        }

        $servicio = new RsaSignatureService('', (string) $registro['clave_publica_pem']);
        return $servicio->verificarEvidencia(
            (string) $registro['datos_firmados_json'],
            (string) $registro['firma_base64']
        );
    }
}
