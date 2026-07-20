<?php

declare(strict_types=1);

use App\Controllers\ComentarioController;
use App\Core\Router;

/**
 * Archivo de rutas asignado a Joselyn: comentarios públicos y moderación.
 */
return static function (Router $router): void {
    // Pantalla administrativa de moderación (requiere permiso).
    $router->get('/comentarios', [ComentarioController::class, 'index']);

    // Envío de comentario desde el detalle de una pieza; el controlador exige cliente autenticado.
    $router->post('/comentarios/guardar', [ComentarioController::class, 'guardarPublico']);

    // Acciones de moderación.
    $router->post('/comentarios/aprobar/{id}', [ComentarioController::class, 'aprobar']);
    $router->post('/comentarios/ocultar/{id}', [ComentarioController::class, 'ocultar']);
    $router->post('/comentarios/eliminar/{id}', [ComentarioController::class, 'eliminar']);
};
