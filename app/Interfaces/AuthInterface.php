<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Contrato para el proceso de autenticación.
 */
interface AuthInterface
{
    public function mostrarLogin(): void;

    public function iniciarSesion(): void;

    public function mostrarRegistro(): void;

    public function registrarCliente(): void;

    public function cerrarSesion(): void;
}
