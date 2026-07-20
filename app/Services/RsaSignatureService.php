<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\CryptographicServiceInterface;
use RuntimeException;

/** Firma y verificación RSA-SHA256 detrás del contrato criptográfico común. */
final class RsaSignatureService implements CryptographicServiceInterface
{
    public function __construct(
        private readonly string $privateKeyPem,
        private readonly string $publicKeyPem,
        private readonly string $privateKeyPassword = ''
    ) {
    }

    public function generarEvidencia(string $contenido): string
    {
        $privateKey = openssl_pkey_get_private(
            $this->privateKeyPem,
            $this->privateKeyPassword
        );

        if ($privateKey === false) {
            throw new RuntimeException('No fue posible abrir la llave privada RSA del usuario.');
        }

        $firma = '';
        $ok = openssl_sign($contenido, $firma, $privateKey, OPENSSL_ALGO_SHA256);

        if (!$ok) {
            throw new RuntimeException('No fue posible firmar digitalmente el registro.');
        }

        return base64_encode($firma);
    }

    public function verificarEvidencia(string $contenido, string $evidencia): bool
    {
        $publicKey = openssl_pkey_get_public($this->publicKeyPem);

        if ($publicKey === false) {
            return false;
        }

        $firma = base64_decode($evidencia, true);
        if ($firma === false) {
            return false;
        }

        return openssl_verify($contenido, $firma, $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }

    public function algoritmo(): string
    {
        return 'RSA-2048/SHA-256';
    }
}
