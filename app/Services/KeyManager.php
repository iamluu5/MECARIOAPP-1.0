<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use RuntimeException;

/**
 * Gestión de llaves RSA por usuario interno.
 *
 * - La llave pública y su huella se guardan en MySQL.
 * - La llave privada se guarda fuera de /public y cifrada con contraseña.
 * - La rotación desactiva la llave anterior sin destruirla, preservando la
 *   verificación de firmas históricas.
 */
final class KeyManager
{
    private Database $db;
    private array $config;

    public function __construct()
    {
        $this->db = Database::getInstancia();
        $this->config = require dirname(__DIR__, 2) . '/config/config.php';
    }

    public function asegurarClave(int $idUsuario): array
    {
        $actual = $this->obtenerClaveActiva($idUsuario);
        return $actual ?? $this->generarNuevaClave($idUsuario);
    }

    public function obtenerClaveActiva(int $idUsuario): ?array
    {
        return $this->db->consultarUno(
            'SELECT * FROM claves_usuario
             WHERE id_usuario = :id AND activa = 1
             ORDER BY id_clave DESC LIMIT 1',
            ['id' => $idUsuario]
        );
    }

    public function listarClaves(): array
    {
        return $this->db->consultarTodos(
            'SELECT c.*, CONCAT(u.nombre, " ", u.apellido) AS usuario_nombre, u.usuario
             FROM claves_usuario c
             INNER JOIN usuarios u ON u.id_usuario = c.id_usuario
             ORDER BY c.fecha_creacion DESC'
        );
    }

    public function rotarClave(int $idUsuario): array
    {
        $this->db->ejecutar(
            'UPDATE claves_usuario
             SET activa = 0,
                 fecha_revocacion = NOW(),
                 motivo_revocacion = "Rotación controlada de llave RSA"
             WHERE id_usuario = :id AND activa = 1',
            ['id' => $idUsuario]
        );

        return $this->generarNuevaClave($idUsuario);
    }

    public function firmadorParaUsuario(int $idUsuario): RsaSignatureService
    {
        $clave = $this->asegurarClave($idUsuario);
        $ruta = (string) $clave['ruta_clave_privada'];

        if (!is_file($ruta)) {
            throw new RuntimeException('No se encontró la llave privada RSA asociada al usuario.');
        }

        $privatePem = file_get_contents($ruta);
        if ($privatePem === false) {
            throw new RuntimeException('No fue posible leer la llave privada RSA.');
        }

        return new RsaSignatureService(
            $privatePem,
            (string) $clave['clave_publica_pem'],
            $this->passphraseUsuario($idUsuario)
        );
    }

    private function generarNuevaClave(int $idUsuario): array
    {
        if (!extension_loaded('openssl')) {
            throw new RuntimeException('La extensión OpenSSL de PHP es necesaria para generar llaves RSA.');
        }

        $bits = (int) ($this->config['security']['rsa_bits'] ?? 2048);
        $opensslConfig = (string) ($this->config['security']['openssl_config'] ?? '');

        if ($opensslConfig === '' || !is_file($opensslConfig) || !is_readable($opensslConfig)) {
            throw new RuntimeException('No se encontró el archivo de configuración OpenSSL: ' . $opensslConfig);
        }

        $opensslOptions = [
            'config' => $opensslConfig,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => $bits,
        ];

        while (openssl_error_string() !== false) {
            // Limpiar errores anteriores de OpenSSL.
        }

        $recurso = openssl_pkey_new($opensslOptions);

        if ($recurso === false) {
            throw new RuntimeException('OpenSSL no pudo generar el par de llaves RSA. ' . $this->erroresOpenSsl());
        }

        $privatePem = '';
        $passphrase = $this->passphraseUsuario($idUsuario);
        if (!openssl_pkey_export($recurso, $privatePem, $passphrase, $opensslOptions)) {
            throw new RuntimeException('OpenSSL no pudo exportar la llave privada cifrada. ' . $this->erroresOpenSsl());
        }

        $detalles = openssl_pkey_get_details($recurso);
        if ($detalles === false || empty($detalles['key'])) {
            throw new RuntimeException('OpenSSL no pudo obtener la llave pública RSA.');
        }

        $publicPem = (string) $detalles['key'];
        $huella = hash('sha256', $publicPem);
        $directorio = (string) $this->config['security']['private_keys_directory'];

        if (!is_dir($directorio) && !mkdir($directorio, 0700, true) && !is_dir($directorio)) {
            throw new RuntimeException('No fue posible crear el directorio seguro de llaves.');
        }

        $ruta = rtrim($directorio, '/\\') . DIRECTORY_SEPARATOR
            . 'usuario_' . $idUsuario . '_' . date('Ymd_His') . '.pem';

        if (file_put_contents($ruta, $privatePem, LOCK_EX) === false) {
            throw new RuntimeException('No fue posible guardar la llave privada cifrada.');
        }
        @chmod($ruta, 0600);

        $idClave = $this->db->insertar(
            'INSERT INTO claves_usuario
                (id_usuario, clave_publica_pem, ruta_clave_privada, huella_sha256, algoritmo, activa)
             VALUES
                (:usuario, :publica, :ruta, :huella, :algoritmo, 1)',
            [
                'usuario' => $idUsuario,
                'publica' => $publicPem,
                'ruta' => $ruta,
                'huella' => $huella,
                'algoritmo' => 'RSA-' . $bits . '/SHA-256',
            ]
        );

        return $this->db->consultarUno(
            'SELECT * FROM claves_usuario WHERE id_clave = :id',
            ['id' => $idClave]
        ) ?? throw new RuntimeException('No fue posible recuperar la llave RSA generada.');
    }

    private function passphraseUsuario(int $idUsuario): string
    {
        $secreto = (string) ($this->config['security']['key_encryption_secret'] ?? 'mecario-local');
        return hash_hmac('sha256', 'usuario:' . $idUsuario, $secreto);
    }

    private function erroresOpenSsl(): string
    {
        $errores = [];
        while (($error = openssl_error_string()) !== false) {
            $errores[] = $error;
        }

        return $errores === [] ? 'OpenSSL no proporcionó detalles.' : implode(' | ', $errores);
    }
}
