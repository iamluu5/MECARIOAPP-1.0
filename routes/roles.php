<?php

declare(strict_types=1);

use App\Controllers\RolController;
use App\Core\Router;

/**
 * Rutas del módulo de roles y permisos.
 * Responsable: Luisa.
 */
return static function (Router $router): void {
    $router->get('/roles', [RolController::class, 'index']);
    $router->get('/roles/crear', [RolController::class, 'crear']);
    $router->post('/roles/guardar', [RolController::class, 'guardar']);
    $router->get('/roles/ver/{id}', [RolController::class, 'ver']);
    $router->get('/roles/editar/{id}', [RolController::class, 'editar']);
    $router->post('/roles/actualizar/{id}', [RolController::class, 'actualizar']);
    $router->post('/roles/estado/{id}', [RolController::class, 'cambiarEstado']);
};
