<?php

declare(strict_types=1);

use App\Controllers\PublicController;
use App\Core\Router;

/**
 * Archivo de rutas asignado a Joselyn: página pública.
 *
 * La ruta "/" ya está registrada en routes/base.php (archivo compartido)
 * y apunta a PublicController::index. Aquí solo se agregan las rutas
 * propias del catálogo público.
 */
return static function (Router $router): void {
    $router->get('/catalogo', [PublicController::class, 'catalogo']);
    $router->get('/categoria/{id}', [PublicController::class, 'categoria']);
    $router->get('/parte/{id}', [PublicController::class, 'detalle']);
};
