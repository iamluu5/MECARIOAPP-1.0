<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use Throwable;

/** Registra acciones críticas con hash SHA-256 y firma RSA verificable. */
final class AuditTrailService
{
    private Database $db;
    private KeyManager $keys;

    public function __construct()
    {
        $this->db = Database::getInstancia();
        $this->keys = new KeyManager();
    }

    public function registrar(
        int $idUsuario,
        string $modulo,
        string $accion,
        string $entidad,
        string|int|null $entidadId,
        array $datos = []
    ): int {
        $clave = $this->keys->asegurarClave($idUsuario);
        $fecha = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'IP-desconocida';

        $payload = [
            'usuario_id' => $idUsuario,
            'modulo' => $modulo,
            'accion' => $accion,
            'entidad' => $entidad,
            'entidad_id' => $entidadId,
            'datos' => $datos,
            'ip' => $ip,
            'fecha' => $fecha,
        ];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $hash = hash('sha256', $json);
        $firma = $this->keys->firmadorParaUsuario($idUsuario)->generarEvidencia($json);

        return $this->db->insertar(
            'INSERT INTO auditoria_firmada
                (id_usuario, id_clave, modulo, accion, entidad, entidad_id, datos_firmados_json, hash_sha256, firma_base64, ip, fecha_evento)
             VALUES
                (:usuario, :clave, :modulo, :accion, :entidad, :entidad_id, :json, :hash, :firma, :ip, :fecha)',
            [
                'usuario' => $idUsuario,
                'clave' => (int) $clave['id_clave'],
                'modulo' => $modulo,
                'accion' => $accion,
                'entidad' => $entidad,
                'entidad_id' => $entidadId === null ? null : (string) $entidadId,
                'json' => $json,
                'hash' => $hash,
                'firma' => $firma,
                'ip' => $ip,
                'fecha' => $fecha,
            ]
        );
    }

    /** Evita que un fallo de auditoría rompa una operación ya completada. */
    public function registrarSeguro(
        int $idUsuario,
        string $modulo,
        string $accion,
        string $entidad,
        string|int|null $entidadId,
        array $datos = []
    ): void {
        try {
            $this->registrar($idUsuario, $modulo, $accion, $entidad, $entidadId, $datos);
        } catch (Throwable $e) {
            error_log('[MECARIO AUDITORIA] ' . $e->getMessage());
        }
    }
}
