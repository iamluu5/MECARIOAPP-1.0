<?php

declare(strict_types=1);

use App\Controllers\UsuarioController;
use App\Core\Router;

/**
 * Rutas del módulo de usuarios.
 * Responsable: Luisa.
 */
return static function (Router $router): void {
    $router->get('/usuarios', [UsuarioController::class, 'index']);
    $router->get('/usuarios/crear', [UsuarioController::class, 'crear']);
    $router->post('/usuarios/guardar', [UsuarioController::class, 'guardar']);
    $router->get('/usuarios/ver/{id}', [UsuarioController::class, 'ver']);
    $router->get('/usuarios/editar/{id}', [UsuarioController::class, 'editar']);
    $router->post('/usuarios/actualizar/{id}', [UsuarioController::class, 'actualizar']);
    $router->post('/usuarios/estado/{id}', [UsuarioController::class, 'cambiarEstado']);
    $router->post('/usuarios/desbloquear/{id}', [UsuarioController::class, 'desbloquear']);
};
