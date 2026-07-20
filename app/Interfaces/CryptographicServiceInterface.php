<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Contrato común para servicios criptográficos.
 *
 * Tanto el hashing de contraseñas como la firma digital producen una
 * "evidencia" a partir de un contenido y permiten verificarla después.
 * La lógica de negocio depende de este contrato y no del algoritmo concreto.
 */
interface CryptographicServiceInterface
{
    public function generarEvidencia(string $contenido): string;

    public function verificarEvidencia(string $contenido, string $evidencia): bool;

    public function algoritmo(): string;
}
