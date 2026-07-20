<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Contrato del flujo académico de pagos simulados.
 *
 * Ningún método de esta interfaz procesa dinero real ni se conecta con bancos,
 * Yappy, Visa o Mastercard. El objetivo es demostrar el flujo de checkout.
 */
interface PagoSimuladoInterface
{
    public function checkout(): void;

    public function seleccionarMetodo(): void;

    public function yappy(): void;

    public function confirmarYappy(): void;

    public function tarjeta(string $marca): void;

    public function confirmarTarjeta(): void;

    public function misCompras(): void;

    public function exito(string $id): void;
}
