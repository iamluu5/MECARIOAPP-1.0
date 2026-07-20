<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Contrato para las operaciones principales del módulo de ventas.
 */
interface VendibleInterface
{
    public function obtenerInventarioDisponible(): array;

    public function procesarVenta(
        int $idUsuario,
        array $detalles,
        ?string $observacion = null,
        array $pago = []
    ): int;
}
