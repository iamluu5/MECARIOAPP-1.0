<?php

declare(strict_types=1);

use App\Controllers\PublicController;
use App\Core\Router;

/**
 * Rutas generales y públicas.
 *
 * Una ruta únicamente indica:
 * MÉTODO + URL + CONTROLADOR + ACCIÓN.
 *
 * No deben colocarse consultas SQL ni HTML dentro de este archivo.
 */
return static function (Router $router): void {
    $router->get('/', [PublicController::class, 'index']);
};
