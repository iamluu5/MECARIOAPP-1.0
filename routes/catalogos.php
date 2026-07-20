<?php

declare(strict_types=1);

use App\Controllers\AutoController;
use App\Controllers\ParteController;
use App\Controllers\SeccionController;
use App\Core\Router;

/** Rutas asignadas a Franco: autos, partes y secciones. */
return static function (Router $router): void {
    $router->get('/autos', [AutoController::class, 'index']);
    $router->get('/autos/crear', [AutoController::class, 'crear']);
    $router->post('/autos/guardar', [AutoController::class, 'guardar']);
    $router->get('/autos/editar/{id}', [AutoController::class, 'editar']);
    $router->post('/autos/actualizar/{id}', [AutoController::class, 'actualizar']);
    $router->post('/autos/estado/{id}', [AutoController::class, 'cambiarEstado']);

    $router->get('/partes', [ParteController::class, 'index']);
    $router->get('/partes/crear', [ParteController::class, 'crear']);
    $router->post('/partes/guardar', [ParteController::class, 'guardar']);
    $router->get('/partes/editar/{id}', [ParteController::class, 'editar']);
    $router->post('/partes/actualizar/{id}', [ParteController::class, 'actualizar']);
    $router->post('/partes/estado/{id}', [ParteController::class, 'cambiarEstado']);

    $router->get('/secciones', [SeccionController::class, 'index']);
    $router->get('/secciones/crear', [SeccionController::class, 'crear']);
    $router->post('/secciones/guardar', [SeccionController::class, 'guardar']);
    $router->get('/secciones/editar/{id}', [SeccionController::class, 'editar']);
    $router->post('/secciones/actualizar/{id}', [SeccionController::class, 'actualizar']);
    $router->post('/secciones/estado/{id}', [SeccionController::class, 'cambiarEstado']);
};
