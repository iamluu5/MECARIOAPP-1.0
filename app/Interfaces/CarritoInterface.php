<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Contrato del carrito de compras.
 *
 * El carrito se conserva en sesión; los precios y existencias siempre se
 * vuelven a consultar en la base de datos antes de mostrar o procesar la compra.
 */
interface CarritoInterface
{
    public function agregar(int $idInventario, int $cantidad = 1): void;

    public function actualizar(array $cantidades): void;

    public function eliminar(int $idInventario): void;

    public function vaciar(): void;

    public function obtenerItems(): array;

    public function calcularTotal(): float;

    public function cantidadTotal(): int;
}
