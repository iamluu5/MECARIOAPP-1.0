<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\CryptographicServiceInterface;
use RuntimeException;

/** Hashing seguro de contraseñas detrás del contrato criptográfico común. */
final class PasswordHashService implements CryptographicServiceInterface
{
    public function generarEvidencia(string $contenido): string
    {
        $hash = password_hash($contenido, PASSWORD_DEFAULT);

        if ($hash === false) {
            throw new RuntimeException('No fue posible proteger la contraseña.');
        }

        return $hash;
    }

    public function verificarEvidencia(string $contenido, string $evidencia): bool
    {
        return password_verify($contenido, $evidencia);
    }

    public function algoritmo(): string
    {
        return 'PASSWORD_DEFAULT';
    }
}
