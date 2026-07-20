<?php

declare(strict_types=1);

use App\Controllers\CarritoController;
use App\Controllers\PagoController;
use App\Core\Router;

/**
 * Carrito, checkout y pagos académicos simulados.
 */
return static function (Router $router): void {
    $router->get('/carrito', [CarritoController::class, 'index']);
    $router->post('/carrito/agregar/{id}', [CarritoController::class, 'agregar']);
    $router->post('/carrito/actualizar', [CarritoController::class, 'actualizar']);
    $router->post('/carrito/eliminar/{id}', [CarritoController::class, 'eliminar']);
    $router->post('/carrito/vaciar', [CarritoController::class, 'vaciar']);

    $router->get('/checkout', [PagoController::class, 'checkout']);
    $router->post('/checkout/metodo', [PagoController::class, 'seleccionarMetodo']);
    $router->get('/pago/yappy', [PagoController::class, 'yappy']);
    $router->post('/pago/yappy/confirmar', [PagoController::class, 'confirmarYappy']);
    $router->get('/pago/tarjeta/{marca}', [PagoController::class, 'tarjeta']);
    $router->post('/pago/tarjeta/confirmar', [PagoController::class, 'confirmarTarjeta']);
    $router->get('/mis-compras', [PagoController::class, 'misCompras']);
    $router->get('/compra/exito/{id}', [PagoController::class, 'exito']);
};
