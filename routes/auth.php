<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Core\Router;

/**
 * Rutas de autenticación y registro público.
 */
return static function (Router $router): void {
    $router->get('/login', [AuthController::class, 'mostrarLogin']);
    $router->post('/login', [AuthController::class, 'iniciarSesion']);
    $router->get('/registro', [AuthController::class, 'mostrarRegistro']);
    $router->post('/registro', [AuthController::class, 'registrarCliente']);
    $router->post('/logout', [AuthController::class, 'cerrarSesion']);
};
