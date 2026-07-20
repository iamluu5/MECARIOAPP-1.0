<?php

declare(strict_types=1);

namespace App\Services;

/** Cálculos comerciales centralizados para evitar repetir reglas fiscales. */
final class CommerceCalculator
{
    private float $itbmsRate;

    public function __construct()
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $this->itbmsRate = (float) ($config['commerce']['itbms_rate'] ?? 0.07);
    }

    public function calcular(float $subtotal, float $costoEntrega = 0.0): array
    {
        $subtotal = round(max(0, $subtotal), 2);
        $costoEntrega = round(max(0, $costoEntrega), 2);
        $itbms = round($subtotal * $this->itbmsRate, 2);

        return [
            'subtotal' => $subtotal,
            'itbms' => $itbms,
            'tasa_itbms' => $this->itbmsRate,
            'costo_entrega' => $costoEntrega,
            'total' => round($subtotal + $itbms + $costoEntrega, 2),
        ];
    }
}
